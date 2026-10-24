#!/usr/bin/env python3
"""tests/test_spam_defense.py — автотесты антиспам-защиты send-email.php (TC-01…TC-09).

Проверки идут реальными HTTP-запросами (requests) к встроенному PHP-серверу,
который тест поднимает сам на свободном порту:

    TC-01  Honeypot user_middle_name заполнен              → Silent Drop
    TC-02  Honeypot отсутствует в POST / нет в форме       → Silent Drop
    TC-03  Мгновенная отправка (< 3 секунд)                → Silent Drop
    TC-04  Метка form_loaded_at отсутствует или испорчена  → Silent Drop
    TC-05  Ссылка, домен, Telegram или @ник в тексте       → Silent Drop
    TC-06  Телефон не из 11 цифр или не с префиксом 7/8    → Silent Drop
    TC-07  Кириллица: латиница блокируется, бренды проходят → адаптивный порог
    TC-08  Валидная заявка                                 → 200 + запись + письмо
    TC-09  Теневой лог блокировок                          → NDJSON вне вебрута

Silent Drop — это HTTP 200 {"status":"success"}: бот думает, что заявка принята,
но письмо не отправляется и запись в requests.ndjson не создаётся. Причина
фиксируется в теневом логе dirname(__DIR__) . '/data/logs/spam_blocked.log'.

Письма в тестах реально не уходят: PHP-сервер запускается с SMTP=127.0.0.1:1,
поэтому попытка отправки видна только как строка 'mail() failed' в stderr PHP.
Это и используется как доказательство «письмо не отправлялось» (для блокировок)
и «письмо отправлялось» (для валидной заявки).

Запуск:
    python tests/test_spam_defense.py
    python tests/test_spam_defense.py --port 8010 --no-color
    run_tests.bat

Требуется пакет requests. Локальные журналы (requests.ndjson и spam_blocked.log)
после прогона возвращаются в исходное состояние, тестовые записи не остаются.
Счётчики rate-limit сбрасываются: 3 валидные заявки за прогон укладываются в лимит.

Код возврата: 0 — все 9 кейсов пройдены, 1 — есть провалы (CI-friendly).
"""

from __future__ import annotations

import argparse
import json
import os
import re
import shutil
import socket
import subprocess
import sys
import threading
import time
from dataclasses import dataclass
from pathlib import Path
from typing import Any, Callable

try:
    import requests
except ImportError:  # pragma: no cover - подсказка вместо трейсбека
    print("ОШИБКА: не найден пакет requests. Установите: python -m pip install requests")
    sys.exit(1)

# Каталоги: скрипт лежит в <repo>/tests/, вебрут — корень репозитория.
REPO_ROOT = Path(__file__).resolve().parents[1]

# send-email.php пишет данные в dirname(__DIR__) . '/data/...' — вне вебрута.
DATA_DIR = REPO_ROOT.parent / "data" / "requests"
LEDGER = DATA_DIR / "requests.ndjson"
SPAM_LOG = REPO_ROOT.parent / "data" / "logs" / "spam_blocked.log"

PHP_RELATIVE = Path(".tools") / "php" / "php.exe"
HOST = "127.0.0.1"
ENDPOINT = "/send-email.php"
USER_AGENT = "Odnoetazh-QA-SpamDefense/1.0 (+tests/test_spam_defense.py)"

MIN_FILL_TIME = 3  # MIN_FILL_TIME в send-email.php
SUCCESS_MESSAGE = "Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время."

# SMTP заведомо недоступен: письма не уходят, но попытка отправки логируется.
TEST_SMTP_HOST = "127.0.0.1"
TEST_SMTP_PORT = "1"

FORMS = {
    "index.php": 'id="ctaForm"',
    "contacts.php": 'id="contactForm"',
    "architecture.php": 'id="architectureForm"',
    "construction.php": 'id="constructionForm"',
}

PASS, FAIL, INFO = "PASS", "FAIL", "INFO"


class CheckFailure(AssertionError):
    """Проверка не прошла; текст исключения попадает в отчёт как причина [FAIL]."""


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
            kernel32.SetConsoleMode(handle, mode.value | 0x0004)
    except Exception:  # noqa: BLE001 - косметика не должна ломать тесты
        pass


@dataclass
class Result:
    """Одна проверка в отчёте."""

    name: str
    status: str
    detail: str = ""
    duration: float = 0.0


class Reporter:
    """Вывод результатов в консоль и накопление итогов."""

    NAME_WIDTH = 52

    def __init__(self, use_color: bool) -> None:
        self.use_color = use_color
        self.results: list[Result] = []
        self.started = time.perf_counter()

    def paint(self, text: str, code: str) -> str:
        if not self.use_color or not code:
            return text
        return f"{code}{text}{Color.RESET}"

    def header(self, title: str) -> None:
        print()
        print(self.paint(f"── {title}", Color.BOLD))

    def row(self, name: str, status: str, duration: float = 0.0, detail: str = "") -> None:
        self.results.append(Result(name, status, detail, duration))
        tag_colors = {PASS: Color.GREEN, FAIL: Color.RED, INFO: Color.DIM}
        detail_colors = {PASS: Color.DIM, FAIL: Color.RED, INFO: Color.DIM}
        tag = f"[{status}]"
        padding = " " * max(2, self.NAME_WIDTH - len(tag) - 1 - len(name))
        timing = f"{duration:6.2f}s" if duration else " " * 7
        print(f"  {self.paint(tag, tag_colors.get(status, ''))} {name}{padding}"
              f"{self.paint(timing, Color.DIM)}")
        if detail:
            for line in detail.splitlines():
                print(self.paint(f"      ⤷ {line}", detail_colors.get(status, "")))

    def summary(self) -> int:
        passed = sum(1 for result in self.results if result.status == PASS)
        failed = sum(1 for result in self.results if result.status == FAIL)
        elapsed = time.perf_counter() - self.started
        print()
        print("=" * 78)
        print(
            f"  {self.paint('[PASS]', Color.GREEN)} {passed:>3}   "
            f"{self.paint('[FAIL]', Color.RED)} {failed:>3}   "
            f"время {elapsed:.1f} c"
        )
        if failed:
            print()
            for result in self.results:
                if result.status == FAIL:
                    print(self.paint(f"  [FAIL] {result.name}: {result.detail}", Color.RED))
            print(self.paint("  [FAIL] Не все тест-кейсы пройдены", Color.RED + Color.BOLD))
            return 1
        print(self.paint("  [PASS] Все 9 тест-кейсов пройдены", Color.GREEN + Color.BOLD))
        return 0
class StderrCollector(threading.Thread):
    """Постоянно читает stderr PHP-сервера, чтобы канал не блокировался.

    Встроенный сервер пишет туда access-log и сообщения error_log(), в том числе
    'send-email: mail() failed ...' — по ним видно, пытался ли обработчик
    отправить письмо.
    """

    def __init__(self, stream: Any) -> None:
        super().__init__(daemon=True)
        self._stream = stream
        self._lock = threading.Lock()
        self._chunks: list[str] = []

    def run(self) -> None:  # pragma: no cover - фоновый поток
        try:
            for line in self._stream:
                with self._lock:
                    self._chunks.append(line)
        except (ValueError, OSError):
            return

    def text(self) -> str:
        with self._lock:
            return "".join(self._chunks)


@dataclass
class Server:
    """Поднятый тестами PHP-сервер."""

    process: subprocess.Popen
    stderr: StderrCollector
    port: int

    @property
    def base_url(self) -> str:
        return f"http://{HOST}:{self.port}"


def find_php() -> Path | None:
    """Portable PHP из .tools или php из PATH."""
    candidate = REPO_ROOT / PHP_RELATIVE
    if candidate.is_file():
        return candidate
    found = shutil.which("php")
    return Path(found) if found else None


def free_port() -> int:
    """Свободный TCP-порт на loopback (окно гонки минимально)."""
    with socket.socket(socket.AF_INET, socket.SOCK_STREAM) as sock:
        sock.bind((HOST, 0))
        return int(sock.getsockname()[1])


def tcp_reachable(host: str, port: int, timeout: float = 1.0) -> bool:
    try:
        with socket.create_connection((host, port), timeout=timeout):
            return True
    except OSError:
        return False


def wait_for_port(host: str, port: int, timeout: float = 20.0) -> bool:
    """Ждёт, пока порт начнёт принимать соединения (старт dev-сервера)."""
    deadline = time.monotonic() + timeout
    while time.monotonic() < deadline:
        if tcp_reachable(host, port):
            return True
        time.sleep(0.2)
    return False


def start_server(port: int) -> tuple[Server | None, str]:
    """Поднимает php -S с отключённым SMTP.

    Слушаем 127.0.0.1: привязка к имени localhost на Windows может не создать
    слушающий сокет. SMTP направлен в закрытый порт — письма не покидают машину,
    а попытка отправки фиксируется в stderr и используется как маркер.
    """
    php = find_php()
    if php is None:
        return None, ("PHP не найден: нужен portable .tools\\php\\php.exe или php в PATH "
                      "(установка: setup_portable_php.ps1)")

    creationflags = getattr(subprocess, "CREATE_NO_WINDOW", 0) if os.name == "nt" else 0
    command = [
        str(php),
        "-d", f"SMTP={TEST_SMTP_HOST}",
        "-d", f"smtp_port={TEST_SMTP_PORT}",
        "-d", "log_errors=1",
        "-S", f"{HOST}:{port}",
        "-t", str(REPO_ROOT),
    ]
    try:
        process = subprocess.Popen(
            command,
            cwd=str(REPO_ROOT),
            stdin=subprocess.DEVNULL,
            stdout=subprocess.DEVNULL,
            stderr=subprocess.PIPE,
            text=True,
            encoding="utf-8",
            errors="replace",
            bufsize=1,
            creationflags=creationflags,
        )
    except OSError as exc:
        return None, f"не удалось запустить php -S: {exc}"

    assert process.stderr is not None
    collector = StderrCollector(process.stderr)
    collector.start()
    return Server(process, collector, port), ""


def stop_server(server: Server | None) -> None:
    """Гасит dev-сервер, поднятый тестами (чужие серверы не трогаем)."""
    if server is None or server.process.poll() is not None:
        return
    server.process.terminate()
    try:
        server.process.wait(timeout=5)
    except subprocess.TimeoutExpired:
        server.process.kill()
        server.process.wait(timeout=5)

# ===== ЖУРНАЛЫ ОБРАБОТЧИКА =====
def read_ndjson(path: Path) -> list[dict[str, Any]]:
    """Читает NDJSON-журнал. Битая строка — провал: журнал обязан оставаться валидным."""
    try:
        raw = path.read_text(encoding="utf-8")
    except FileNotFoundError:
        return []

    entries: list[dict[str, Any]] = []
    for number, line in enumerate(raw.splitlines(), start=1):
        if not line.strip():
            continue
        try:
            entry = json.loads(line)
        except json.JSONDecodeError as exc:
            raise CheckFailure(f"{path.name}: строка {number} — невалидный JSON ({exc.msg})") from exc
        if not isinstance(entry, dict):
            raise CheckFailure(f"{path.name}: строка {number} — ожидался JSON-объект")
        entries.append(entry)
    return entries


def growth(path: Path, before: int) -> list[dict[str, Any]]:
    """Записи, появившиеся после снимка `before` (только прирост от нашего запроса)."""
    return read_ndjson(path)[before:]


def read_optional(path: Path) -> bytes | None:
    try:
        return path.read_bytes()
    except FileNotFoundError:
        return None


def restore_optional(path: Path, payload: bytes | None) -> None:
    """Возвращает журнал в исходное состояние; тестовые записи не остаются."""
    if payload is None:
        path.unlink(missing_ok=True)
        return
    path.parent.mkdir(parents=True, exist_ok=True)
    path.write_bytes(payload)


def reset_rate_limits() -> int:
    """Удаляет счётчики rate-limit, чтобы прогон не упирался в лимит по 127.0.0.1."""
    removed = 0
    if not DATA_DIR.is_dir():
        return removed
    for counter in DATA_DIR.glob("rate_*.json"):
        counter.unlink(missing_ok=True)
        removed += 1
    return removed


# ===== HTTP-КОНТЕКСТ =====
@dataclass
class Probe:
    """Снимок журналов и stderr до запроса — чтобы видеть только прирост."""

    ledger: int
    spam_log: int
    stderr: int


class Context:
    """HTTP-клиент тестов плюс доступ к локальным журналам обработчика."""

    def __init__(self, server: Server, timeout: float) -> None:
        self.server = server
        self.timeout = timeout
        self.session = requests.Session()
        self.session.headers.update({"User-Agent": USER_AGENT})

    @property
    def endpoint(self) -> str:
        return f"{self.server.base_url}{ENDPOINT}"

    def probe(self) -> Probe:
        return Probe(
            ledger=len(read_ndjson(LEDGER)),
            spam_log=len(read_ndjson(SPAM_LOG)),
            stderr=len(self.server.stderr.text()),
        )

    def stderr_growth(self, probe: Probe, needle: str | None, timeout: float = 5.0) -> str:
        """Прирост stderr после снимка.

        needle=None — ждём строку 'Closing' access-лога, то есть запрос полностью
        обработан; иначе ждём конкретную подстроку (например 'mail() failed').
        """
        deadline = time.monotonic() + timeout
        text = ""
        while time.monotonic() < deadline:
            text = self.server.stderr.text()[probe.stderr:]
            if needle is None:
                if "Closing" in text:
                    return text
            elif needle in text:
                return text
            time.sleep(0.05)
        return text

    def post(self, payload: dict[str, str]) -> requests.Response:
        try:
            return self.session.post(self.endpoint, data=payload, timeout=self.timeout)
        except requests.RequestException as exc:
            raise CheckFailure(f"запрос не удался: {exc}") from exc

    def get(self, path: str) -> requests.Response:
        try:
            return self.session.get(f"{self.server.base_url}{path}", timeout=self.timeout)
        except requests.RequestException as exc:
            raise CheckFailure(f"запрос не удался: {exc}") from exc

    def close(self) -> None:
        self.session.close()

# ===== ПРОВЕРКИ =====
def reasons_text(entries: list[dict[str, Any]]) -> str:
    reasons = [str(entry.get("reason")) for entry in entries]
    return ", ".join(reasons) if reasons else "нет"


def expect_silent_drop(response: requests.Response) -> None:
    """Silent Drop: 200 + {"status":"success"} и тот же текст, что у честного клиента."""
    if response.status_code != 200:
        raise CheckFailure(
            f"ожидался HTTP 200 (Silent Drop), получен {response.status_code}: {response.text[:160]}"
        )
    try:
        body = response.json()
    except ValueError as exc:
        raise CheckFailure(f"ответ не JSON: {response.text[:160]}") from exc
    if not isinstance(body, dict) or body.get("status") != "success":
        raise CheckFailure(f'в ответе нет {{"status": "success"}}: {response.text[:160]}')
    if body.get("message") != SUCCESS_MESSAGE:
        raise CheckFailure(f"ответ выдаёт блокировку: {body.get('message')!r}")


def expect_success(response: requests.Response) -> dict[str, Any]:
    """Честный клиент: 200 + {"success": true} и благодарность."""
    if response.status_code != 200:
        raise CheckFailure(f"ожидался HTTP 200, получен {response.status_code}: {response.text[:160]}")
    try:
        body = response.json()
    except ValueError as exc:
        raise CheckFailure(f"ответ не JSON: {response.text[:160]}") from exc
    if not isinstance(body, dict) or body.get("success") is not True:
        raise CheckFailure(f"в ответе нет success=true: {response.text[:160]}")
    if body.get("message") != SUCCESS_MESSAGE:
        raise CheckFailure(f"неожиданный текст ответа: {body.get('message')!r}")
    return body


def check_blocked(ctx: Context, payload: dict[str, str], reason: str) -> str:
    """Отправляет заявку и проверяет Silent Drop + причину + отсутствие побочных эффектов."""
    probe = ctx.probe()
    response = ctx.post(payload)
    expect_silent_drop(response)

    if growth(LEDGER, probe.ledger):
        raise CheckFailure("заблокированная заявка попала в requests.ndjson")
    spam_growth = growth(SPAM_LOG, probe.spam_log)
    reasons = [entry.get("reason") for entry in spam_growth]
    if reason not in reasons:
        raise CheckFailure(f"в теневом логе ожидалась причина {reason!r}, получено: {reasons_text(spam_growth)}")

    stderr = ctx.stderr_growth(probe, needle=None)
    if "mail()" in stderr:
        raise CheckFailure("обработчик вызывал mail(): письмо могло уйти")
    return f"reason={reason}"


def check_lead(ctx: Context, payload: dict[str, str]) -> dict[str, Any]:
    """Валидная заявка: 200, ровно одна запись в журнале, письмо владельцу, нет блокировки."""
    probe = ctx.probe()
    response = ctx.post(payload)
    expect_success(response)

    ledger_growth = growth(LEDGER, probe.ledger)
    if len(ledger_growth) != 1:
        raise CheckFailure(f"ожидалась 1 запись в requests.ndjson, получено {len(ledger_growth)}")

    spam_growth = growth(SPAM_LOG, probe.spam_log)
    if spam_growth:
        raise CheckFailure(f"валидная заявка заблокирована: {reasons_text(spam_growth)}")

    stderr = ctx.stderr_growth(probe, needle="mail() failed")
    if "mail() failed" not in stderr:
        raise CheckFailure("письмо владельцу не отправлялось: в stderr PHP нет попытки mail()")
    return ledger_growth[0]


# ===== ДАННЫЕ ЗАЯВОК =====
# Длинное русское сообщение с брендами и техническими обозначениями:
# порог кириллицы (30 %) должно проходить, ссылки/домены — не находиться.
MESSAGE_VALID = (
    "Интересует одноэтажный дом около 120 м2: нужны окна Rehau, "
    "утеплитель Rockwool и проект по СП 22.13330."
)


def payload(**overrides: Any) -> dict[str, str]:
    """Базовая валидная заявка с «человеческим» поведением: метка 30 секунд назад."""
    data: dict[str, Any] = {
        "name": "Иван Петров",
        "phone": "+7 (999) 123-45-67",
        "email": "",
        "projectType": "Строительство дома",
        "message": MESSAGE_VALID,
        "user_middle_name": "",
        "form_loaded_at": str(int(time.time()) - 30),
    }
    data.update(overrides)
    return {key: str(value) for key, value in data.items() if value is not None}


def without(data: dict[str, str], *keys: str) -> dict[str, str]:
    """Копия payload без указанных полей — имитация «поле не отправлено»."""
    return {key: value for key, value in data.items() if key not in keys}


def form_markup(path: Path) -> str:
    """HTML формы из исходника страницы (проверка скрытых полей без запуска браузера)."""
    try:
        return path.read_text(encoding="utf-8")
    except FileNotFoundError as exc:
        raise CheckFailure(f"не найден файл формы {path.name}") from exc


def expect_form_fields() -> str:
    """В каждой форме есть honeypot user_middle_name и метка form_loaded_at."""
    missing: list[str] = []
    for name in FORMS:
        markup = form_markup(REPO_ROOT / name)
        for field in ('name="user_middle_name"', 'name="form_loaded_at"'):
            if field not in markup:
                missing.append(f"{name}: нет {field}")
        if "form_ts" in markup:
            missing.append(f"{name}: остался legacy form_ts")
    if missing:
        raise CheckFailure("; ".join(missing))
    return f"поля honeypot и form_loaded_at найдены в {len(FORMS)} формах"

# ===== ТЕСТ-КЕЙСЫ =====
def tc01_honeypot_filled(ctx: Context) -> list[str]:
    """TC-01: бот заполняет скрытое «Отчество» — Silent Drop с причиной honeypot."""
    notes = ["заполнено: " + check_blocked(ctx, payload(user_middle_name="Иван Иванович"), "honeypot")]
    notes.append("заполнено с пробелами: "
                 + check_blocked(ctx, payload(user_middle_name="   Бот   "), "honeypot"))
    return notes


def tc02_honeypot_missing(ctx: Context) -> list[str]:
    """TC-02: поля нет в POST (или его нет в форме) — тоже блокировка, а не приём."""
    notes = [expect_form_fields()]
    notes.append("поле не отправлено: "
                 + check_blocked(ctx, without(payload(), "user_middle_name"), "honeypot"))
    return notes


def tc03_fast_submit(ctx: Context) -> list[str]:
    """TC-03: форма заполнена быстрее MIN_FILL_TIME — бот."""
    now = int(time.time())
    notes = [f"elapsed=0 c: " + check_blocked(ctx, payload(form_loaded_at=str(now)), "timing")]
    notes.append(f"elapsed={MIN_FILL_TIME - 1} c: "
                 + check_blocked(ctx, payload(form_loaded_at=str(now - MIN_FILL_TIME + 1)), "timing"))
    return notes


def tc04_broken_timestamp(ctx: Context) -> list[str]:
    """TC-04: метки нет или она испорчена — честная форма всегда её присылает."""
    notes = ["без form_loaded_at: " + check_blocked(ctx, without(payload(), "form_loaded_at"), "timing")]
    notes.append("мусор в метке: "
                 + check_blocked(ctx, payload(form_loaded_at="not-a-timestamp"), "timing"))
    notes.append("пустая метка: " + check_blocked(ctx, payload(form_loaded_at=""), "timing"))
    return notes


def tc05_links(ctx: Context) -> list[str]:
    """TC-05: URL, домен, Telegram и @ник в любом текстовом поле — спам."""
    cases = [
        ("https-ссылка", payload(message="Лучшие окна со скидкой: https://spam-shop.com/windows")),
        ("домен", payload(message="Заходите на spam-shop.com, цены ниже всех")),
        ("telegram", payload(message="Пишите в телеграм t.me/spam_shop_manager")),
        ("@ник", payload(message="Наш менеджер @spam_shop_manager подберёт окна")),
        ("ссылка в имени", payload(name="Окна от производителя www.okna-shop.ru")),
    ]
    return [f"{label}: " + check_blocked(ctx, data, "links") for label, data in cases]


def tc06_phone(ctx: Context) -> list[str]:
    """TC-06: телефон заявки — ровно 11 цифр с префиксом 7 или 8."""
    cases = [
        ("5 цифр", "12345"),
        ("лишняя цифра", "+7 (999) 123-45-67 8"),
        ("неверный префикс", "+3 999 123-45-67"),
        ("городской 10 цифр", "4951234567"),
    ]
    return [f"{label}: " + check_blocked(ctx, payload(phone=value), "phone_invalid")
            for label, value in cases]


def tc07_language(ctx: Context) -> list[str]:
    """TC-07: адаптивная кириллица — латиница блокируется, бренды в русском тексте проходят."""
    notes = ["латиница: " + check_blocked(
        ctx, payload(message="Buy cheap windows from our store, best price today"), "language")]

    brands = check_lead(ctx, payload(message="Хочу дом с окнами Rehau и утеплителем Rockwool"))
    notes.append(f"бренды в русском тексте: принято (phone_digits={brands.get('phone_digits')})")

    check_lead(ctx, payload(message="Rehau"))
    notes.append("короткое сообщение 'Rehau' (5 символов): проверка языка не применяется, принято")
    return notes


def tc08_valid_lead(ctx: Context) -> list[str]:
    """TC-08: честный клиент проходит все барьеры: запись в журнале и письмо владельцу."""
    data = payload(
        name="Иван Петров",
        phone="8 (999) 123-45-67",
        email="ivan.petrov@yandex.ru",
        projectType="Строительство дома",
        message=MESSAGE_VALID + "\nПрошу перезвонить после 18:00.",
    )
    record = check_lead(ctx, data)

    expected = {
        "type": "request",
        "ip": "127.0.0.1",
        "name": data["name"],
        "phone": data["phone"],
        "phone_digits": "89991234567",
        "email": data["email"],
        "projectType": data["projectType"],
        "message": data["message"],
    }
    for field, value in expected.items():
        if record.get(field) != value:
            raise CheckFailure(f"в записи {field}={record.get(field)!r}, ожидалось {value!r}")
    if re.fullmatch(r"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}", str(record.get("timestamp", ""))) is None:
        raise CheckFailure(f"некорректный timestamp записи: {record.get('timestamp')!r}")

    return [
        "HTTP 200 success=true, письмо владельцу отправлено (mail() вызван)",
        f"requests.ndjson += 1: phone_digits={record['phone_digits']}, ip={record['ip']}",
        "в spam_blocked.log новых записей нет",
    ]

def tc09_shadow_log(ctx: Context) -> list[str]:
    """TC-09: теневой лог — валидный NDJSON со всеми причинами и недоступен по HTTP."""
    entries = read_ndjson(SPAM_LOG)
    if not entries:
        raise CheckFailure(f"теневой лог пуст: {SPAM_LOG}")

    required = {"timestamp", "reason", "ip", "user_agent", "referer"}
    seen: set[str] = set()
    for number, entry in enumerate(entries, start=1):
        missing = required - entry.keys()
        if missing:
            raise CheckFailure(f"строка {number}: нет полей {sorted(missing)}")
        if re.fullmatch(r"\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}", str(entry["timestamp"])) is None:
            raise CheckFailure(f"строка {number}: timestamp={entry['timestamp']!r}")
        if not str(entry["reason"]):
            raise CheckFailure(f"строка {number}: пустая причина блокировки")
        seen.add(str(entry["reason"]))

    expected_reasons = {"honeypot", "timing", "links", "phone_invalid", "language"}
    if not expected_reasons <= seen:
        raise CheckFailure(f"в логе нет причин {sorted(expected_reasons - seen)}")

    resolved = SPAM_LOG.resolve()
    if resolved.is_relative_to(REPO_ROOT.resolve()):
        raise CheckFailure(f"лог лежит внутри вебрута: {resolved}")
    if not (SPAM_LOG.parent / ".htaccess").is_file():
        raise CheckFailure("в каталоге логов нет защитного .htaccess")

    for path in ("/data/logs/spam_blocked.log", "/../data/logs/spam_blocked.log"):
        response = ctx.get(path)
        if response.status_code == 200:
            raise CheckFailure(f"лог отдаётся по HTTP: GET {path} → 200")

    return [
        f"{len(entries)} NDJSON-записей, причины: {', '.join(sorted(seen))}",
        f"лог вне вебрута: {resolved}",
        "GET /data/logs/spam_blocked.log и /../data/logs/... → не 200",
    ]


CASES: list[tuple[str, str, Callable[[Context], list[str]]]] = [
    ("TC-01", "Honeypot: заполненное скрытое поле", tc01_honeypot_filled),
    ("TC-02", "Honeypot: поле не пришло в POST", tc02_honeypot_missing),
    ("TC-03", "Тайминг: мгновенная отправка", tc03_fast_submit),
    ("TC-04", "Тайминг: метки нет или она испорчена", tc04_broken_timestamp),
    ("TC-05", "Фильтр ссылок, доменов и Telegram", tc05_links),
    ("TC-06", "Телефон: 11 цифр с префиксом 7 или 8", tc06_phone),
    ("TC-07", "Адаптивная проверка кириллицы", tc07_language),
    ("TC-08", "Валидная заявка проходит целиком", tc08_valid_lead),
    ("TC-09", "Теневой лог блокировок", tc09_shadow_log),
]



# ===== ЗАПУСК =====
def configure_output() -> None:
    """Защита от UnicodeEncodeError на рамках и стрелках в консоли Windows.

    Python в пайпе/файле берёт кодировку из локали (cp1251) и падает на '─'.
    Перенаправленный вывод переводим в UTF-8, живой терминал оставляем как есть
    и лишь смягчаем неперекодируемые символы.
    """
    for stream in (sys.stdout, sys.stderr):
        if stream is None:
            continue
        try:
            if not stream.isatty() or (stream.encoding or "").replace("-", "").lower() == "utf8":
                stream.reconfigure(encoding="utf-8", errors="replace")
            else:
                stream.reconfigure(errors="replace")
        except (AttributeError, ValueError, OSError):
            pass


def parse_args(argv: list[str] | None = None) -> argparse.Namespace:
    parser = argparse.ArgumentParser(
        description="Автотесты антиспам-защиты send-email.php: TC-01…TC-09 через requests.",
    )
    parser.add_argument("--port", type=int, default=0,
                        help="порт для php -S (по умолчанию — свободный)")
    parser.add_argument("--timeout", type=float, default=30.0,
                        help="таймаут одного HTTP-запроса, с (по умолчанию 30)")
    parser.add_argument("--no-color", action="store_true", help="отключить цветной вывод")
    parser.add_argument("--keep-artifacts", action="store_true",
                        help="не восстанавливать журналы после прогона (для разбора)")
    return parser.parse_args(argv)


def print_intro(reporter: Reporter, php: Path, port: int, timeout: float) -> None:
    print("=" * 78)
    print(reporter.paint(" Антиспам-защита форм send-email.php — 9 тест-кейсов", Color.BOLD + Color.CYAN))
    print(f" Вебрут      : {REPO_ROOT}")
    print(f" Цель        : http://{HOST}:{port}{ENDPOINT}")
    print(f" PHP         : {php}")
    print(f" SMTP        : отключён ({TEST_SMTP_HOST}:{TEST_SMTP_PORT}) — письма не уходят")
    print(f" Журналы     : {LEDGER} | {SPAM_LOG}")
    print(f" Python      : {sys.version.split()[0]} | таймаут {timeout:g} c")
    print("=" * 78)


def run_case(ctx: Context, tc_id: str, title: str, func: Callable[[Context], list[str]]) -> Result:
    """Один кейс: любая ошибка внутри превращается в [FAIL], прогон продолжается."""
    started = time.perf_counter()
    name = f"{tc_id} {title}"
    try:
        notes = func(ctx)
        status, detail = PASS, "\n".join(notes)
    except CheckFailure as exc:
        status, detail = FAIL, str(exc)
    except Exception as exc:  # noqa: BLE001 - падение кейса не должно скрывать остальные
        status, detail = FAIL, f"{type(exc).__name__}: {exc}"
    return Result(name, status, detail, time.perf_counter() - started)


def main(argv: list[str] | None = None) -> int:
    configure_output()
    args = parse_args(argv)
    use_color = not args.no_color and (sys.stdout.isatty() or os.environ.get("FORCE_COLOR") == "1")
    if use_color:
        enable_ansi()
    reporter = Reporter(use_color)

    php = find_php()
    if php is None:
        print("ОШИБКА: PHP не найден. Нужен portable .tools\\php\\php.exe "
              "(setup_portable_php.ps1) или php в PATH.")
        return 1

    port = args.port or free_port()
    print_intro(reporter, php, port, args.timeout)

    ledger_backup = read_optional(LEDGER)
    spam_log_backup = read_optional(SPAM_LOG)
    removed_counters = reset_rate_limits()

    server, error = start_server(port)
    ctx: Context | None = None
    exit_code = 1
    try:
        if server is None:
            print(f"\n  [FAIL] {error}")
            return 1
        if not wait_for_port(HOST, port):
            print(f"\n  [FAIL] php -S не поднялся на {HOST}:{port}\n"
                  f"      ⤷ stderr: {server.stderr.text()[-800:].strip() or 'пусто'}")
            return 1

        ctx = Context(server, args.timeout)

        reporter.header("Подготовка")
        reporter.row("PHP встроенный сервер запущен", INFO,
                     detail=f"php -S {HOST}:{port} -t {REPO_ROOT.name}")
        reporter.row("счётчики rate-limit сброшены", INFO,
                     detail=f"удалено файлов: {removed_counters}; 3 валидные заявки за прогон")
        try:
            index = ctx.get("/")
            site_ok = index.status_code == 200
        except CheckFailure as exc:
            site_ok = False
            error = str(exc)
        if site_ok:
            reporter.row("сайт отвечает на GET /", INFO, detail="HTTP 200")
        else:
            reporter.row("сайт отвечает на GET /", FAIL, detail=error or "HTTP != 200")
            return 1

        reporter.header("Тест-кейсы")
        for tc_id, title, func in CASES:
            result = run_case(ctx, tc_id, title, func)
            reporter.row(result.name, result.status, result.duration, result.detail)
            if result.status == FAIL:
                # Дальше проверять бессмысленно: без приёма заявок TC-08/TC-09 упадут каскадом.
                if tc_id in ("TC-01", "TC-02"):
                    print(reporter.paint("  [FAIL] Базовая защита не работает — прогон остановлен",
                                         Color.RED + Color.BOLD))
                    break

        reporter.header("Состояние журналов")
        if args.keep_artifacts:
            reporter.row("журналы оставлены как есть", INFO,
                         detail="--keep-artifacts: тестовые записи не удалялись")
        else:
            restore_optional(LEDGER, ledger_backup)
            restore_optional(SPAM_LOG, spam_log_backup)
            reset_rate_limits()
            reporter.row("журналы восстановлены", INFO,
                         detail="requests.ndjson и spam_blocked.log — как до прогона")

        exit_code = reporter.summary()
        return exit_code
    finally:
        if ctx is not None:
            ctx.close()
        stop_server(server)


if __name__ == "__main__":
    sys.exit(main())

