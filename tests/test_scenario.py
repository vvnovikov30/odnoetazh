#!/usr/bin/env python3
"""Сценарное тестирование сайта ОдноЭтажники.РФ.

Лёгкий автономный набор проверок на стандартной библиотеке Python
(без requests/pytest и прочих зависимостей).

Запуск:
    run_tests.bat              — локальный прогон (сервер на 8000 поднимается сам)
    run_tests.bat prod         — проверка боевого сайта
    python tests/test_scenario.py --target local --serve
    python tests/test_scenario.py --target prod

Цели:
    local — http://localhost:8000 (встроенный сервер PHP, `preview_both.bat` / `--serve`)
    prod  — https://одноэтажники.рф (Punycode: xn--80ahhialrcec8b1i.xn--p1ai)

Сценарии:
    1. Статический анализ синтаксиса PHP (`php -l` по *.php корня, blog/, templates/)
       и актуальность сгенерированных файлов блога (scripts/build_blog.py --check) — только local.
    2. Доступность страниц, сгенерированных статей блога, robots.txt, sitemap.xml
       и отсутствие PHP-ошибок в HTML.
    3. Закрытие служебных файлов и файлов с ПДн (.htaccess: 403/404).
    4. API заявок send-email.php: только POST, honeypot, form_ts, валидная заявка.
    5. Медиа-ассеты и фотографии галереи проекта «Лесные Поляны».

Локально валидная заявка действительно сохраняется в <repo>/../data/requests/requests.ndjson
(вне вебрута, как на хостинге) — так проверяется запись ПДн. Это тестовые данные.
Счётчики rate-limit перед локальным прогоном сбрасываются, а тестовая запись после
проверки откатывается: журнал заявок возвращается в исходное состояние.

Код возврата: 0 — все проверки пройдены, 1 — есть ошибки (CI-friendly).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import socket
import ssl
import subprocess
import sys
import time
import urllib.error
import urllib.parse
import urllib.request
import xml.etree.ElementTree as ET
from dataclasses import dataclass, field
from pathlib import Path
from typing import Any

# Каталоги: скрипт лежит в <repo>/tests/, вебрут — корень репозитория.
REPO_ROOT = Path(__file__).resolve().parents[1]

# send-email.php пишет ПДн в dirname(__DIR__) . '/data/requests' — на хостинге это
# каталог ВНЕ вебрута, локально — соседняя с репозиторием папка.
DATA_DIR = REPO_ROOT.parent / "data" / "requests"

LOCAL_BASE_URL = "http://localhost:8000"
PROD_HOST = "xn--80ahhialrcec8b1i.xn--p1ai"  # одноэтажники.рф
PROD_BASE_URL = "https://" + PROD_HOST

PHP_RELATIVE = Path(".tools") / "php" / "php.exe"

USER_AGENT = "Odnoetazh-QA-Scenario/1.0 (+tests/test_scenario.py)"

PAGES = ("/", "/blog.php", "/architecture.php", "/construction.php", "/contacts.php")
PHP_ERROR_MARKERS = ("Fatal error", "Parse error", "Warning:", "Notice:")

# Пути, которые Apache обязан закрывать (.htaccess) либо которых не должно быть вовсе.
PROTECTED_PATHS = (
    "/requests.json",
    "/requests.txt",
    "/data/requests/requests.ndjson",
    "/.clinerules",
    "/AUDIT_DIFF_REPORT.md",
    "/test-mail.php",
    "/send-email2.php",
)
DENIED_STATUSES = (403, 404)

ASSETS = (
    "/favicon.ico",
    "/logo2.jpg",
    "/style.css",
    "/scripts/main.js",
    "/images/projects/lesnye-polyany/exterior/"
    "lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01.jpg",
    "/images/projects/lesnye-polyany/plan/"
    "lesnye-polyany-plan-energy-efficient-floor-plan-ushp-01.jpg",
)

# WebP-производные (thumb для сетки + _full для лайтбокса) — основной формат витрины.
WEBP_ASSETS = (
    "/images/projects/lesnye-polyany/exterior/"
    "lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01_thumb.webp",
    "/images/projects/lesnye-polyany/exterior/"
    "lesnye-polyany-exterior-modern-japandi-chalet-noki-overhangs-01_full.webp",
    "/images/projects/lesnye-polyany/plan/"
    "lesnye-polyany-plan-energy-efficient-floor-plan-ushp-01_thumb.webp",
)

PASS, FAIL, SKIP, INFO = "PASS", "FAIL", "SKIP", "INFO"


class Color:
    """ANSI-коды; применяются только если включён цветной вывод."""

    RESET = "\033[0m"
    BOLD = "\033[1m"
    DIM = "\033[2m"
    RED = "\033[31m"
    GREEN = "\033[32m"
    YELLOW = "\033[33m"
    CYAN = "\033[36m"


def enable_ansi() -> None:
    """Включает обработку ANSI-кодов в консоли Windows (VT100)."""
    if os.name != "nt":
        return
    try:
        import ctypes

        kernel32 = ctypes.windll.kernel32
        handle = kernel32.GetStdHandle(-11)  # STD_OUTPUT_HANDLE
        mode = ctypes.c_uint32()
        if kernel32.GetConsoleMode(handle, ctypes.byref(mode)):
            kernel32.SetConsoleMode(handle, mode.value | 0x0004)  # VIRTUAL_TERMINAL_PROCESSING
    except Exception:  # noqa: BLE001 - косметика не должна ломать тесты
        pass


@dataclass
class Result:
    """Одна проверка в отчёте."""

    scenario: str
    name: str
    status: str
    detail: str = ""
    duration: float = 0.0


class Reporter:
    """Вывод результатов в консоль и накопление итогов."""

    NAME_WIDTH = 46

    def __init__(self, use_color: bool) -> None:
        self.use_color = use_color
        self.results: list[Result] = []
        self.alerts: list[str] = []
        self.scenario = ""
        self.started = time.perf_counter()

    def paint(self, text: str, code: str) -> str:
        if not self.use_color or not code:
            return text
        return f"{code}{text}{Color.RESET}"

    def header(self, title: str) -> None:
        print()
        print(self.paint(f"── {title}", Color.BOLD))

    def row(self, name: str, status: str, duration: float = 0.0, detail: str = "") -> None:
        self.results.append(Result(self.scenario, name, status, detail, duration))
        tag_colors = {PASS: Color.GREEN, FAIL: Color.RED, SKIP: Color.YELLOW, INFO: Color.DIM}
        detail_colors = {PASS: Color.DIM, FAIL: Color.RED, SKIP: Color.YELLOW, INFO: Color.DIM}
        tag = f"[{status}]"
        padding = " " * max(2, self.NAME_WIDTH - len(tag) - 1 - len(name))
        timing = f"{duration:6.2f}s" if duration else " " * 7
        print(f"  {self.paint(tag, tag_colors.get(status, ''))} {name}{padding}{self.paint(timing, Color.DIM)}")
        if detail:
            for line in detail.splitlines():
                print(self.paint(f"      ⤷ {line}", detail_colors.get(status, "")))

    def counts(self) -> dict[str, int]:
        totals = {PASS: 0, FAIL: 0, SKIP: 0, INFO: 0}
        for result in self.results:
            totals[result.status] = totals.get(result.status, 0) + 1
        return totals

    def summary(self) -> int:
        totals = self.counts()
        elapsed = time.perf_counter() - self.started
        print()
        print("=" * 74)
        print(
            f"  {self.paint('[PASS]', Color.GREEN)} {totals[PASS]:>3}   "
            f"{self.paint('[FAIL]', Color.RED)} {totals[FAIL]:>3}   "
            f"{self.paint('[SKIP]', Color.YELLOW)} {totals[SKIP]:>3}   "
            f"время {elapsed:.1f} c"
        )
        if self.alerts:
            print()
            print(self.paint("!!! КРИТИЧНО (152-ФЗ / безопасность):", Color.RED + Color.BOLD))
            for alert in self.alerts:
                print(self.paint(f"    • {alert}", Color.RED))
        if totals[FAIL]:
            print()
            for result in self.results:
                if result.status == FAIL:
                    print(self.paint(f"  [FAIL] {result.scenario} :: {result.name}", Color.RED))
            print(self.paint("  [FAIL] Тесты не пройдены", Color.RED + Color.BOLD))
            return 1
        print(self.paint("  [PASS] Все проверки пройдены", Color.GREEN + Color.BOLD))
        return 0


@dataclass
class HttpResult:
    """Результат одного HTTP-запроса (сетевые ошибки не бросают исключение)."""

    url: str
    status: int | None
    headers: dict[str, str] = field(default_factory=dict)
    body: bytes = b""
    elapsed: float = 0.0
    error: str | None = None
    redirect_target: str = ""

    @property
    def ok(self) -> bool:
        return self.status == 200

    @property
    def text(self) -> str:
        ctype = self.headers.get("content-type", "")
        match = re.search(r"charset=([\w\-]+)", ctype, re.I)
        charset = match.group(1) if match else "utf-8"
        try:
            return self.body.decode(charset, errors="replace")
        except LookupError:
            return self.body.decode("utf-8", errors="replace")

    @property
    def content_type(self) -> str:
        return self.headers.get("content-type", "").lower()

    def describe(self) -> str:
        if self.error:
            return f"нет соединения: {self.error}"
        return f"HTTP {self.status}"


class _NoRedirectHandler(urllib.request.HTTPRedirectHandler):
    """Не следовать редиректам: для проверок безопасности 3xx — это провал."""

    def redirect_request(self, req, fp, code, msg, headers, newurl):  # noqa: ANN001, ANN201
        return None


def _lower_headers(headers: Any) -> dict[str, str]:
    result: dict[str, str] = {}
    for key, value in dict(headers).items():
        result[str(key).lower()] = str(value)
    return result


class HttpClient:
    """Минимальный HTTP-клиент на urllib: таймауты, повторы, отключение редиректов."""

    def __init__(self, base_url: str, timeout: float = 15.0, retries: int = 1, verify_tls: bool = True) -> None:
        self.base_url = base_url.rstrip("/")
        self.timeout = timeout
        self.retries = max(0, retries)
        self._context = ssl.create_default_context()
        if not verify_tls:
            self._context.check_hostname = False
            self._context.verify_mode = ssl.CERT_NONE

    def url(self, path: str) -> str:
        return f"{self.base_url}/{path.lstrip('/')}"

    def get(self, path: str, follow_redirects: bool = True) -> HttpResult:
        return self._request("GET", path, None, follow_redirects)

    def post(self, path: str, data: dict[str, str], follow_redirects: bool = False) -> HttpResult:
        payload = urllib.parse.urlencode(data).encode("utf-8")
        return self._request("POST", path, payload, follow_redirects)

    def _request(self, method: str, path: str, data: bytes | None, follow_redirects: bool) -> HttpResult:
        url = self.url(path)
        last_error = ""
        for attempt in range(self.retries + 1):
            if attempt:
                time.sleep(0.7)
            request = urllib.request.Request(url, data=data, method=method)
            request.add_header("User-Agent", USER_AGENT)
            request.add_header("Accept", "*/*")
            if data is not None:
                request.add_header("Content-Type", "application/x-www-form-urlencoded; charset=UTF-8")

            handlers: list[Any] = [urllib.request.HTTPSHandler(context=self._context)]
            if not follow_redirects:
                handlers.insert(0, _NoRedirectHandler())
            opener = urllib.request.build_opener(*handlers)
            started = time.perf_counter()

            try:
                with opener.open(request, timeout=self.timeout) as response:
                    body = response.read()
                    elapsed = time.perf_counter() - started
                    target = "" if response.url == url else response.url
                    return HttpResult(url, int(response.status), _lower_headers(response.headers), body,
                                      elapsed, redirect_target=target)
            except urllib.error.HTTPError as exc:
                try:
                    body = exc.read()
                except Exception:  # noqa: BLE001 - тело ошибки не критично
                    body = b""
                return HttpResult(url, int(exc.code), _lower_headers(exc.headers or {}), body,
                                  time.perf_counter() - started)
            except urllib.error.URLError as exc:
                last_error = str(exc.reason)
            except (TimeoutError, socket.timeout):
                last_error = f"таймаут {self.timeout:.0f} c"
            except OSError as exc:
                last_error = str(exc)

        return HttpResult(url, None, error=last_error)


@dataclass
class Context:
    """Общий контекст прогона."""

    target: str
    base_url: str
    client: HttpClient
    repo_root: Path
    data_dir: Path

    @property
    def is_local(self) -> bool:
        return self.target == "local"


def parse_json(result: HttpResult) -> dict[str, Any] | None:
    """JSON-объект из ответа или None, если тело не является JSON-объектом."""
    try:
        payload = json.loads(result.text)
    except (json.JSONDecodeError, ValueError):
        return None
    return payload if isinstance(payload, dict) else None


# ===== ЛОКАЛЬНЫЕ ХЕЛПЕРЫ =====
def find_php(repo_root: Path) -> Path | None:
    """Portable-интерпретатор из .tools/php, иначе php из PATH."""
    portable = repo_root / PHP_RELATIVE
    if portable.is_file():
        return portable
    system_php = shutil.which("php")
    return Path(system_php) if system_php else None


def lint_php_file(php: Path, file: Path, repo_root: Path) -> tuple[bool, str]:
    """`php -l <file>`; возвращает (успех, человекочитаемый вердикт)."""
    try:
        completed = subprocess.run(
            [str(php), "-l", str(file)],
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            cwd=str(repo_root),
            timeout=60,
        )
    except (OSError, subprocess.SubprocessError) as exc:
        return False, f"не удалось запустить php -l: {exc}"

    output = f"{completed.stdout}\n{completed.stderr}".strip()
    if completed.returncode == 0 and "No syntax errors detected" in output:
        return True, "No syntax errors detected"
    verdict = " ".join(line.strip() for line in output.splitlines() if line.strip())
    return False, (verdict or f"php -l завершился с кодом {completed.returncode}")[:400]


FILESMATCH_RE = re.compile(r"<FilesMatch\s+\"([^\"]+)\"\s*>(.*?)</FilesMatch>", re.I | re.S)
DENY_MARKERS = ("require all denied", "deny from all")


@dataclass
class DenyRule:
    """Правило запрета из .htaccess (эмуляция поведения Apache на dev-сервере)."""

    pattern: str

    def matches(self, filename: str) -> bool:
        try:
            return re.search(self.pattern, filename) is not None
        except re.error:
            return False


def load_deny_rules(htaccess: Path) -> list[DenyRule]:
    """Правила `<FilesMatch> ... Require all denied ...` из .htaccess."""
    if not htaccess.is_file():
        return []
    text = htaccess.read_text(encoding="utf-8", errors="replace")
    rules: list[DenyRule] = []
    for match in FILESMATCH_RE.finditer(text):
        block = match.group(2).lower()
        if any(marker in block for marker in DENY_MARKERS):
            rules.append(DenyRule(match.group(1)))
    return rules


@dataclass
class LedgerSnapshot:
    """Состояние NDJSON-журнала заявок до/после запроса."""

    exists: bool
    size: int
    lines: int
    last_line: str


def snapshot_ledger(path: Path) -> LedgerSnapshot:
    try:
        raw = path.read_bytes()
    except OSError:
        return LedgerSnapshot(False, 0, 0, "")
    rows = [line for line in raw.decode("utf-8", errors="replace").splitlines() if line.strip()]
    return LedgerSnapshot(True, len(raw), len(rows), rows[-1] if rows else "")


def reset_local_rate_limit(data_dir: Path) -> int:
    """Сбрасывает локальные счётчики rate-limit, чтобы прогоны были повторяемыми.

    send-email.php разрешает 3 заявки с одного IP за 10 минут. Сценарный тест
    отправляет валидную заявку на каждом прогоне, поэтому без сброса счётчика
    4-й прогон подряд получил бы HTTP 429. Журнал заявок не трогаем.
    """
    if not data_dir.is_dir():
        return 0
    removed = 0
    for file in data_dir.glob("rate_*.json"):
        try:
            file.unlink()
            removed += 1
        except OSError:
            continue
    return removed


def read_ledger_bytes(path: Path) -> bytes | None:
    """Сырое содержимое журнала заявок (None, если файла ещё нет)."""
    try:
        return path.read_bytes()
    except OSError:
        return None


def restore_ledger(path: Path, original: bytes | None) -> str:
    """Откатывает тестовую заявку: журнал возвращается к состоянию до прогона.

    Тест не должен оставлять в журнале запись «Тест Тестович»: иначе каждый
    прогон добавляет туда ПДн-мусор. Заодно следующий прогон снова проверяет
    «запись добавлена» на чистом состоянии.
    """
    try:
        if original is None:
            path.unlink(missing_ok=True)
        else:
            path.write_bytes(original)
    except OSError as exc:
        return f"журнал не удалось вернуть в исходное состояние: {exc}"
    return "тестовая запись удалена, журнал в исходном состоянии"


def tcp_reachable(host: str, port: int, timeout: float = 1.0) -> bool:
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except OSError:
        return False


def resolve_local_base(base_url: str) -> tuple[bool, str, str]:
    """Проверяет, что локальный сервер поднят (localhost → 127.0.0.1 при необходимости)."""
    parsed = urllib.parse.urlsplit(base_url)
    host = parsed.hostname or "localhost"
    port = parsed.port or 80
    if tcp_reachable(host, port):
        return True, base_url, ""
    if host != "127.0.0.1" and tcp_reachable("127.0.0.1", port):
        alternative = urllib.parse.urlunsplit((parsed.scheme, f"127.0.0.1:{port}", "", "", ""))
        return True, alternative, f"localhost недоступен, используется {alternative}"
    return False, base_url, ""


DEV_SERVER_HOST = "127.0.0.1"
DEV_SERVER_PORT = 8000


def start_dev_server(port: int) -> tuple[subprocess.Popen | None, str]:
    """Поднимает встроенный PHP-сервер (`php -S`) на время прогона тестов.

    Слушаем именно 127.0.0.1: привязка к имени `localhost` на Windows может не
    создать слушающий сокет. Возвращает (процесс, текст ошибки).
    """
    php = find_php(REPO_ROOT)
    if php is None:
        return None, ("PHP не найден: нужен portable .tools\\php\\php.exe или php в PATH "
                      "(установка: setup_portable_php.ps1)")

    creationflags = 0
    if os.name == "nt":
        creationflags = getattr(subprocess, "CREATE_NO_WINDOW", 0)
    try:
        process = subprocess.Popen(
            [str(php), "-S", f"{DEV_SERVER_HOST}:{port}", "-t", str(REPO_ROOT)],
            cwd=str(REPO_ROOT),
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.DEVNULL,
            creationflags=creationflags,
        )
    except OSError as exc:
        return None, f"не удалось запустить php -S: {exc}"
    return process, ""


def wait_for_port(host: str, port: int, timeout: float = 20.0) -> bool:
    """Ждёт, пока порт начнёт принимать соединения (старт dev-сервера)."""
    deadline = time.monotonic() + timeout
    while time.monotonic() < deadline:
        if tcp_reachable(host, port):
            return True
        time.sleep(0.25)
    return False


def stop_dev_server(process: subprocess.Popen | None) -> None:
    """Гасит dev-сервер, поднятый тестами (чужие серверы не трогаем)."""
    if process is None or process.poll() is not None:
        return
    process.terminate()
    try:
        process.wait(timeout=5)
    except subprocess.TimeoutExpired:
        process.kill()
        process.wait(timeout=5)


# ===== СЦЕНАРИЙ 1: СИНТАКСИС PHP =====
def scenario_php_syntax(reporter: Reporter, ctx: Context) -> None:
    """`php -l` по всем PHP-файлам корня репозитория (только для local)."""
    reporter.scenario = "Сценарий 1. Синтаксис PHP (php -l)"
    reporter.header(reporter.scenario)

    if not ctx.is_local:
        reporter.row("php -l по всем файлам корня", SKIP, 0.0, "выполняется только при --target local")
        return

    php = find_php(ctx.repo_root)
    if php is None:
        reporter.row(
            "php -l по всем файлам корня",
            FAIL,
            0.0,
            "интерпретатор не найден: нет .tools\\php\\php.exe и php в PATH "
            "(установка: setup_portable_php.ps1)",
        )
        return

    files = sorted(
        path
        for pattern in ("*.php", "blog/*.php", "templates/*.php")
        for path in ctx.repo_root.glob(pattern)
        if path.is_file()
    )
    if not files:
        reporter.row("поиск PHP-файлов в корне", FAIL, 0.0, f"в {ctx.repo_root} нет ни одного *.php")
        return

    broken = 0
    for file in files:
        started = time.perf_counter()
        ok, verdict = lint_php_file(php, file, ctx.repo_root)
        reporter.row(file.relative_to(ctx.repo_root).as_posix(), PASS if ok else FAIL,
                     time.perf_counter() - started, "" if ok else verdict)
        broken += 0 if ok else 1

    if broken:
        reporter.alerts.append(f"синтаксические ошибки PHP в {broken} файл(ах) — деплой запрещён")

    check_ssg_freshness(reporter, ctx)


# ===== СЦЕНАРИЙ 2: СТРАНИЦЫ =====
def check_ssg_freshness(reporter: Reporter, ctx: Context) -> None:
    """Страницы blog/*.php и sitemap.xml должны совпадать с content/articles/*.md."""
    script = ctx.repo_root / "scripts" / "build_blog.py"
    if not script.is_file():
        reporter.row("SSG блога (build_blog.py --check)", SKIP, 0.0, "скрипт сборки не найден")
        return

    started = time.perf_counter()
    try:
        completed = subprocess.run(
            [sys.executable, str(script), "--check"],
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace",
            cwd=str(ctx.repo_root),
            timeout=120,
        )
    except (OSError, subprocess.SubprocessError) as exc:
        reporter.row("SSG блога (build_blog.py --check)", FAIL, time.perf_counter() - started,
                     f"не удалось запустить сборку: {exc}")
        return

    if completed.returncode == 0:
        reporter.row("SSG блога (build_blog.py --check)", PASS, time.perf_counter() - started)
        return

    output = f"{completed.stdout}\n{completed.stderr}".strip()
    verdict = " ".join(line.strip() for line in output.splitlines() if line.strip())
    reporter.row("SSG блога (build_blog.py --check)", FAIL, time.perf_counter() - started,
                 (verdict or f"код возврата {completed.returncode}")[:400])


def article_paths(repo_root: Path) -> list[str]:
    """URL-пути статей блога из sitemap.xml (источник истины — сгенерированный файл)."""
    sitemap = repo_root / "sitemap.xml"
    if not sitemap.is_file():
        return []
    try:
        root = ET.fromstring(sitemap.read_text(encoding="utf-8"))
    except (OSError, ET.ParseError):
        return []

    paths = set()
    for element in root.iter():
        if not element.tag.endswith("loc") or not element.text:
            continue
        path = urllib.parse.urlparse(element.text.strip()).path
        if re.fullmatch(r"/blog/[^/]+\.php", path):
            paths.add(path)
    return sorted(paths)


def check_page(reporter: Reporter, ctx: Context, path: str) -> None:
    """HTTP 200 + отсутствие PHP-ошибок + признаки живой страницы."""
    result = ctx.client.get(path)
    if not result.ok:
        detail = f"ожидался HTTP 200, получено {result.describe()}"
        if result.redirect_target:
            detail += f" → {result.redirect_target}"
        reporter.row(path, FAIL, result.elapsed, detail)
        return

    body = result.text
    # display_errors с HTML-форматированием даёт "<b>Warning</b>:", поэтому теги убираем.
    plain = re.sub(r"<[^>]+>", " ", body)
    found = [marker for marker in PHP_ERROR_MARKERS if marker in plain]
    if found:
        reporter.row(path, FAIL, result.elapsed, f"в HTML обнаружено: {', '.join(found)}")
        return
    if "ОдноЭтажники" not in body:
        reporter.row(path, FAIL, result.elapsed, "в HTML нет названия компании — страница отдана некорректно")
        return
    reporter.row(path, PASS, result.elapsed)


def check_blog_cards(reporter: Reporter, ctx: Context, articles: list[str]) -> None:
    """Каталог blog.php должен ссылаться на каждую статью из sitemap.xml."""
    result = ctx.client.get("/blog.php")
    if not result.ok:
        reporter.row("blog.php → карточки статей", FAIL, result.elapsed,
                     f"ожидался HTTP 200, получено {result.describe()}")
        return

    body = result.text
    missing = [path for path in articles if f'href="{path.lstrip("/")}"' not in body]
    reporter.row(
        "blog.php → карточки статей",
        FAIL if missing else PASS,
        result.elapsed,
        f"нет ссылок на: {', '.join(missing)}" if missing else f"карточек со ссылкой на статьи: {len(articles)}",
    )


def check_robots(reporter: Reporter, ctx: Context) -> None:
    """robots.txt: сайт открыт для индексации, sitemap указан."""
    result = ctx.client.get("/robots.txt")
    if not result.ok:
        reporter.row("/robots.txt", FAIL, result.elapsed, f"ожидался HTTP 200, получено {result.describe()}")
        return

    text = result.text
    problems: list[str] = []
    if not re.search(r"^\s*User-agent:\s*\*\s*$", text, re.I | re.M):
        problems.append("нет секции «User-agent: *»")
    if not re.search(r"^\s*Allow:\s*/\s*$", text, re.I | re.M):
        problems.append("нет директивы «Allow: /»")
    if re.search(r"^\s*Disallow:\s*/\s*$", text, re.I | re.M):
        problems.append("есть «Disallow: /» — сайт закрыт от индексации")
    if not re.search(r"^\s*Sitemap:\s*https?://\S+", text, re.I | re.M):
        problems.append("не указан Sitemap")
    reporter.row("/robots.txt", FAIL if problems else PASS, result.elapsed, "; ".join(problems))


def check_sitemap(reporter: Reporter, ctx: Context) -> None:
    """sitemap.xml: валидный XML с корректным namespace и списком URL."""
    result = ctx.client.get("/sitemap.xml")
    if not result.ok:
        reporter.row("/sitemap.xml", FAIL, result.elapsed, f"ожидался HTTP 200, получено {result.describe()}")
        return
    try:
        root = ET.fromstring(result.body)
    except ET.ParseError as exc:
        reporter.row("/sitemap.xml", FAIL, result.elapsed, f"невалидный XML: {exc}")
        return

    tag = root.tag
    namespace = ""
    if tag.startswith("{"):
        namespace = tag[1:].split("}")[0]
        tag = tag.split("}", 1)[1]

    prefix = f"{{{namespace}}}" if namespace else ""
    urls = root.findall(f"{prefix}url")
    locations = [(url.findtext(f"{prefix}loc") or "").strip() for url in urls]

    problems: list[str] = []
    if tag != "urlset":
        problems.append(f"корневой тег «{tag}», ожидался «urlset»")
    if namespace and namespace != "http://www.sitemaps.org/schemas/sitemap/0.9":
        problems.append(f"неожиданный namespace: {namespace}")
    if not urls:
        problems.append("нет ни одного элемента <url>")
    insecure = [loc for loc in locations if not loc.startswith("https://")]
    if insecure:
        problems.append(f"URL без https: {insecure[0]}")
    reporter.row("/sitemap.xml", FAIL if problems else PASS, result.elapsed,
                 "; ".join(problems) or f"валидный XML, URL: {len(urls)}")


def scenario_pages(reporter: Reporter, ctx: Context) -> None:
    """Сценарий 2: доступность страниц, статей блога, robots.txt и sitemap.xml."""
    reporter.scenario = "Сценарий 2. Страницы, статьи, robots.txt, sitemap.xml"
    reporter.header(reporter.scenario)

    for path in PAGES:
        check_page(reporter, ctx, path)

    articles = article_paths(ctx.repo_root)
    if articles:
        for path in articles:
            check_page(reporter, ctx, path)
        check_blog_cards(reporter, ctx, articles)
    else:
        reporter.row("страницы статей блога", INFO, 0.0, "в sitemap.xml нет статей — проверять нечего")

    check_robots(reporter, ctx)
    check_sitemap(reporter, ctx)


# ===== СЦЕНАРИЙ 3: ЗАКРЫТИЕ СЛУЖЕБНЫХ ФАЙЛОВ =====
def check_protected_path(reporter: Reporter, ctx: Context, path: str, rules: list[DenyRule]) -> None:
    """403/404 обязателен на проде; локально допустим 200, если путь закрыт в .htaccess."""
    result = ctx.client.get(path, follow_redirects=False)
    filename = path.rsplit("/", 1)[-1]

    if result.status in DENIED_STATUSES:
        reporter.row(path, PASS, result.elapsed, f"HTTP {result.status}")
        return

    if result.status == 200:
        if ctx.is_local:
            covered = [rule.pattern for rule in rules if rule.matches(filename)]
            if covered:
                reporter.row(path, PASS, result.elapsed,
                             f"dev-сервер не применяет .htaccess, правило закрытия есть: {covered[0]}")
            else:
                reporter.row(path, FAIL, result.elapsed,
                             "HTTP 200, файл не закрыт ни одним правилом .htaccess — утечка служебных данных")
            return
        reporter.row(path, FAIL, result.elapsed,
                     "HTTP 200 — файл доступен публично! Утечка служебных данных, нарушение 152-ФЗ")
        reporter.alerts.append(f"{ctx.base_url}{path} отдаётся с HTTP 200 на боевом сайте")
        return

    if result.status is None:
        reporter.row(path, FAIL, result.elapsed, f"нет соединения: {result.error}")
        return

    reporter.row(path, FAIL, result.elapsed, f"ожидался 403/404, получено {result.describe()}")


def scenario_security(reporter: Reporter, ctx: Context) -> None:
    """Сценарий 3: служебные файлы, журналы заявок и мёртвые обработчики закрыты."""
    reporter.scenario = "Сценарий 3. Защита служебных файлов и ПДн"
    reporter.header(reporter.scenario)

    rules: list[DenyRule] = []
    if ctx.is_local:
        rules = load_deny_rules(ctx.repo_root / ".htaccess")
        if rules:
            reporter.row("разбор правил .htaccess", PASS, 0.0, f"найдено правил запрета: {len(rules)}")
        else:
            reporter.row("разбор правил .htaccess", FAIL, 0.0,
                         "в .htaccess нет ни одного правила с «Require all denied»")

    for path in PROTECTED_PATHS:
        check_protected_path(reporter, ctx, path, rules)

    if ctx.is_local:
        blocked = [rule.pattern for rule in rules if rule.matches("robots.txt")]
        reporter.row("robots.txt не попадает под запрет", FAIL if blocked else PASS, 0.0,
                     f"правило {blocked[0]} заблокирует robots.txt на Apache" if blocked
                     else "исключение в .htaccess работает")


# ===== СЦЕНАРИЙ 4: API ЗАЯВОК =====
BOT_PAYLOAD = {
    "name": "QA Bot",
    "phone": "+79990000001",
    "email": "",
    "projectType": "",
    "message": "",
}


def verify_bot_response(reporter: Reporter, ctx: Context, name: str, result: HttpResult,
                        before: LedgerSnapshot, ledger: Path) -> None:
    """Бот должен получить фейковый success и не создать запись в NDJSON-журнале."""
    problems: list[str] = []
    if result.status != 200:
        problems.append(f"ожидался HTTP 200, получено {result.describe()}")
    payload = parse_json(result)
    if payload is None:
        problems.append(f"ответ не JSON: {result.text[:120]!r}")
    elif payload.get("success") is not True:
        problems.append(f"success != true: {payload}")

    if ctx.is_local:
        after = snapshot_ledger(ledger)
        if (after.size, after.lines) != (before.size, before.lines):
            problems.append(f"в requests.ndjson появилась запись: строк {before.lines} → {after.lines}")

    detail = "; ".join(problems)
    if not problems:
        detail = "фейковый success, запись в requests.ndjson не создана" if ctx.is_local \
            else "фейковый success (журнал на проде недоступен для проверки)"
    reporter.row(name, FAIL if problems else PASS, result.elapsed, detail)


def scenario_form_api(reporter: Reporter, ctx: Context) -> None:
    """Сценарий 4: только POST, honeypot, form_ts и валидная заявка."""
    reporter.scenario = "Сценарий 4. API заявок send-email.php"
    reporter.header(reporter.scenario)

    ledger = ctx.data_dir / "requests.ndjson"
    if ctx.is_local:
        removed = reset_local_rate_limit(ctx.data_dir)
        reporter.row("сброс локального rate-limit", INFO, 0.0,
                     f"удалено счётчиков: {removed}" if removed else "активных счётчиков нет")

    # 4.1 — GET запрещён
    result = ctx.client.get("/send-email.php", follow_redirects=False)
    if result.status == 405:
        reporter.row("4.1 GET → 405 Method Not Allowed", PASS, result.elapsed,
                     f"Allow: {result.headers.get('allow', '—')}")
    else:
        reporter.row("4.1 GET → 405 Method Not Allowed", FAIL, result.elapsed,
                     f"ожидался HTTP 405, получено {result.describe()}")

    # 4.2 — honeypot заполнен (валидные поля тоже отправляем: иначе тест неинформативен)
    payload = {**BOT_PAYLOAD, "website": "http://spam-link.com", "form_ts": str(int(time.time()) - 30)}
    before = snapshot_ledger(ledger)
    result = ctx.client.post("/send-email.php", payload)
    verify_bot_response(reporter, ctx, "4.2 honeypot (website заполнен)", result, before, ledger)

    # 4.3a — форма «заполнена» мгновенно
    payload = {**BOT_PAYLOAD, "website": "", "form_ts": str(int(time.time()))}
    before = snapshot_ledger(ledger)
    result = ctx.client.post("/send-email.php", payload)
    verify_bot_response(reporter, ctx, "4.3a form_ts = сейчас (< 2 c)", result, before, ledger)

    # 4.3b — form_ts отсутствует
    payload = {**BOT_PAYLOAD, "website": ""}
    before = snapshot_ledger(ledger)
    result = ctx.client.post("/send-email.php", payload)
    verify_bot_response(reporter, ctx, "4.3b form_ts отсутствует", result, before, ledger)

    # 4.4 — валидная заявка: только локально, на прод не отправляем
    if not ctx.is_local:
        reporter.row("4.4 валидная заявка", SKIP, 0.0,
                     "на прод не отправляется, чтобы не создавать лишние заявки и письма")
        return

    payload = {
        "name": "Тест Тестович",
        "phone": "+79991234567",
        "email": "",
        "projectType": "",
        "message": "",
        "website": "",
        "form_ts": str(int(time.time()) - 5),
    }
    before = snapshot_ledger(ledger)
    original = read_ledger_bytes(ledger)  # для отката тестовой записи после проверки
    result = ctx.client.post("/send-email.php", payload)
    problems: list[str] = []
    if result.status != 200:
        problems.append(f"ожидался HTTP 200, получено {result.describe()}")
    body = parse_json(result)
    if body is None:
        problems.append(f"ответ не JSON: {result.text[:120]!r}")
    elif body.get("success") is not True:
        problems.append(f"success != true: {body}")

    after = snapshot_ledger(ledger)
    if after.lines != before.lines + 1:
        problems.append(f"в requests.ndjson нет новой записи: строк {before.lines} → {after.lines}")
    else:
        try:
            row = json.loads(after.last_line)
        except json.JSONDecodeError:
            problems.append("последняя строка requests.ndjson не является JSON")
        else:
            if row.get("phone") != "+79991234567":
                problems.append(f"в записи другой телефон: {row.get('phone')!r}")
            if row.get("name") != "Тест Тестович":
                problems.append(f"в записи другое имя: {row.get('name')!r}")

    reporter.row("4.4 валидная заявка сохранена", FAIL if problems else PASS, result.elapsed,
                 "; ".join(problems) or f"запись добавлена, строк в журнале: {after.lines}")

    note = restore_ledger(ledger, original)
    reporter.row("4.5 откат тестовой заявки", INFO, 0.0, note)


# ===== СЦЕНАРИЙ 5: МЕДИА =====
def scenario_assets(reporter: Reporter, ctx: Context) -> None:
    """Сценарий 5: ключевые ассеты и фотографии галереи отдаются без 404."""
    reporter.scenario = "Сценарий 5. Медиа-файлы и галерея"
    reporter.header(reporter.scenario)

    for path in ASSETS:
        result = ctx.client.get(path)
        problems: list[str] = []
        if not result.ok:
            problems.append(f"ожидался HTTP 200, получено {result.describe()}")
        elif not result.body:
            problems.append("пустой ответ")
        elif "text/html" in result.content_type:
            problems.append(f"вместо файла отдана HTML-страница ({result.content_type})")
        reporter.row(path.rsplit("/", 1)[-1], FAIL if problems else PASS, result.elapsed,
                     "; ".join(problems) or f"{len(result.body) / 1024:.0f} КБ, {result.content_type}")

    # 5b — WebP витрины: thumb для сетки и _full для лайтбокса
    for path in WEBP_ASSETS:
        result = ctx.client.get(path)
        problems = []
        if not result.ok:
            problems.append(f"ожидался HTTP 200, получено {result.describe()}")
        elif not result.body:
            problems.append("пустой ответ")
        elif "text/html" in result.content_type:
            problems.append(f"вместо WebP отдана HTML-страница ({result.content_type})")
        elif "image/webp" not in result.content_type:
            problems.append(f"неожиданный Content-Type: {result.content_type}")
        elif result.body[:4] != b"RIFF" or result.body[8:12] != b"WEBP":
            problems.append("ответ не является WebP-контейнером (нет сигнатуры RIFF/WEBP)")
        reporter.row(path.rsplit("/", 1)[-1], FAIL if problems else PASS, result.elapsed,
                     "; ".join(problems) or f"{len(result.body) / 1024:.0f} КБ, {result.content_type}")


# ===== ЗАПУСК =====
def parse_args(argv: list[str]) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Сценарные тесты сайта ОдноЭтажники.РФ (local: http://localhost:8000, "
                    "prod: https://одноэтажники.рф)",
        formatter_class=argparse.RawDescriptionHelpFormatter,
    )
    parser.add_argument("--target", choices=("local", "prod"), default="local",
                        help="local — http://localhost:8000, prod — https://одноэтажники.рф")
    parser.add_argument("--serve", action="store_true",
                        help="поднять встроенный PHP-сервер на время прогона, если порт свободен (local)")
    parser.add_argument("--serve-port", type=int, default=DEV_SERVER_PORT,
                        help=f"порт для --serve (по умолчанию {DEV_SERVER_PORT})")
    parser.add_argument("--timeout", type=float, default=15.0, help="таймаут HTTP-запроса, с (по умолчанию 15)")
    parser.add_argument("--retries", type=int, default=1, help="повторы при сетевой ошибке (по умолчанию 1)")
    parser.add_argument("--no-color", action="store_true", help="отключить ANSI-цвета")
    parser.add_argument("--insecure", action="store_true", help="не проверять TLS-сертификат (отладка)")
    return parser.parse_args(argv)


def main(argv: list[str] | None = None) -> int:
    args = parse_args(sys.argv[1:] if argv is None else argv)
    use_color = (not args.no_color) and "NO_COLOR" not in os.environ and (
        sys.stdout.isatty() or "FORCE_COLOR" in os.environ)
    if use_color:
        enable_ansi()

    reporter = Reporter(use_color)
    note = ""
    base_url = PROD_BASE_URL if args.target == "prod" else LOCAL_BASE_URL
    server: subprocess.Popen | None = None

    if args.serve and args.target == "prod":
        print(reporter.paint("  [!] --serve игнорируется: для prod локальный сервер не нужен",
                             Color.YELLOW))

    if args.target == "local":
        ready, base_url, note = resolve_local_base(LOCAL_BASE_URL)
        if not ready and args.serve:
            port = args.serve_port
            error = ""
            server, error = start_dev_server(port)
            if server is not None and not wait_for_port(DEV_SERVER_HOST, port):
                stop_dev_server(server)
                server = None
                error = f"php -S не начал слушать {DEV_SERVER_HOST}:{port} за 20 с"
            if server is None:
                print(reporter.paint(f"ОШИБКА: {error}", Color.RED + Color.BOLD))
                print("  Проверьте portable PHP: powershell -NoProfile -ExecutionPolicy Bypass "
                      "-File setup_portable_php.ps1")
                return 1
            base_url = f"http://{DEV_SERVER_HOST}:{port}"
            note = "встроенный PHP-сервер поднят тестами и будет остановлен после прогона"
        elif not ready:
            print(reporter.paint("ОШИБКА: локальный сервер не отвечает на http://localhost:8000",
                                 Color.RED + Color.BOLD))
            print("  Запустите тесты через run_tests.bat local — сервер поднимется автоматически,")
            print("  либо добавьте ключ --serve, либо поднимите предпросмотр: preview_both.bat")
            return 1

    client = HttpClient(base_url, timeout=args.timeout, retries=args.retries,
                        verify_tls=not args.insecure)
    ctx = Context(args.target, base_url, client, REPO_ROOT, DATA_DIR)

    print("=" * 74)
    print(reporter.paint(" Сценарное тестирование сайта ОдноЭтажники.РФ", Color.BOLD + Color.CYAN))
    print(f" Цель       : {args.target} — {base_url}")
    if note:
        print(f" Примечание : {note}")
    print(f" Репозиторий: {REPO_ROOT}")
    print(f" Python     : {sys.version.split()[0]} | {time.strftime('%d.%m.%Y %H:%M:%S')}")
    print("=" * 74)

    try:
        scenario_php_syntax(reporter, ctx)
        scenario_pages(reporter, ctx)
        scenario_security(reporter, ctx)
        scenario_form_api(reporter, ctx)
        scenario_assets(reporter, ctx)
        return reporter.summary()
    finally:
        stop_dev_server(server)


if __name__ == "__main__":
    sys.exit(main())
