#!/usr/bin/env python3
"""SSG блога ОдноЭтажники.РФ: Markdown + YAML Frontmatter -> статические PHP-страницы.

Что делает сборка:
  1. читает content/articles/*.md (Frontmatter + Markdown);
  2. генерирует blog/{slug}.php, инлайня шаблон templates/template-article.php
     (сам шаблон на хостинг не выгружается — страницы должны быть автономны);
  3. пересобирает сетку карточек в blog.php между маркерами BLOG_CARDS;
  4. обновляет sitemap.xml: записи /blog/{slug}.php и lastmod у /blog.php;
  5. удаляет blog/*.php, осиротевшие после удаления исходной статьи.

Зависимости: только стандартная библиотека Python 3.10+ (в проекте нет pip-пакетов).

Запуск:
    python scripts/build_blog.py            # собрать/пересобрать блог
    python scripts/build_blog.py --check    # только проверка актуальности (CI): 0 — ок, 1 — расхождение

Frontmatter (обязательные поля: title, slug, date):
    ---
    title: "Заголовок статьи"
    slug: "slug-stati"
    date: "2026-10-23"
    read_time: "7 мин"                  # опционально, иначе оценка по объёму
    description: "Краткое описание"     # опционально, иначе первый абзац
    card_target: "01"                   # опционально, связка с карточкой на главной
    category: "Кровля"                  # опционально, бейдж в каталоге
    ---

Поддерживаемый Markdown: заголовки, абзацы, списки (с вложенностью), таблицы
(в обёртке .article-table-scroll со скроллом), цитаты СП/ГОСТ, блоки кода,
строчная разметка, ссылки, изображения, горизонтальные линии и raw HTML.
Цитата, начинающаяся со слов «Важно для самостройщика», превращается во
врезку <aside class="article-note">.
"""

from __future__ import annotations

import argparse
import html
import io
import re
import sys
import xml.etree.ElementTree as ET
from dataclasses import dataclass
from datetime import date as Date
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parents[1]
ARTICLES_DIR = REPO_ROOT / "content" / "articles"
TEMPLATE_FILE = REPO_ROOT / "templates" / "template-article.php"
OUTPUT_DIR = REPO_ROOT / "blog"
CATALOG_FILE = REPO_ROOT / "blog.php"
SITEMAP_FILE = REPO_ROOT / "sitemap.xml"

SITE_URL = "https://xn--80ahhialrcec8b1i.xn--p1ai"
SITEMAP_NS = "http://www.sitemaps.org/schemas/sitemap/0.9"
# sitemap.xml исторически пишется без префиксов (<urlset>, <url>, <loc>), поэтому
# просим ElementTree сериализовать namespace по умолчанию вместо auto-префиксов ns0.
ET.register_namespace("", SITEMAP_NS)
CARD_MARKER_START = "<!-- BLOG_CARDS:START -->"
CARD_MARKER_END = "<!-- BLOG_CARDS:END -->"
GENERATED_MARKER = "Файл сгенерирован scripts/build_blog.py"
NOTE_TITLE = "Важно для самостройщика"

MONTHS_RU = (
    "января", "февраля", "марта", "апреля", "мая", "июня",
    "июля", "августа", "сентября", "октября", "ноября", "декабря",
)

CARD_GRADIENTS = (
    "linear-gradient(135deg, #dfe8e2 0%, #b9cdbf 100%)",
    "linear-gradient(135deg, #efe7dc 0%, #cdb9a3 100%)",
    "linear-gradient(135deg, #e3e6ea 0%, #b9c4cf 100%)",
    "linear-gradient(135deg, #eae6dd 0%, #c7bfae 100%)",
    "linear-gradient(135deg, #e0e7e9 0%, #b3c6ca 100%)",
)

SLUG_RE = re.compile(r"^[a-z0-9]+(?:-[a-z0-9]+)*$")
FRONTMATTER_RE = re.compile(r"\A---[ \t]*\r?\n(.*?)\r?\n---[ \t]*(?:\r?\n|\Z)", re.S)


class BuildError(Exception):
    """Ошибка сборки: печатается без трейсбека, код возврата 1."""


@dataclass
class Article:
    """Статья блога, разобранная из Markdown-файла."""

    source: Path
    slug: str
    title: str
    description: str
    published: Date
    read_time: str
    card_target: str
    category: str
    body: str


def _parse_scalar(raw: str, where: str) -> str:
    """Значение Frontmatter: кавычки, инлайн-комментарии, пустые строки."""
    value = raw.strip()
    if not value:
        return ""
    if value[0] in "\"'":
        quote = value[0]
        chars: list[str] = []
        index = 1
        while index < len(value):
            char = value[index]
            if char == "\\" and quote == '"' and index + 1 < len(value):
                chars.append(value[index + 1])
                index += 2
                continue
            if char == quote:
                return "".join(chars)
            chars.append(char)
            index += 1
        raise BuildError(f"{where}: незакрытая кавычка в значении Frontmatter")
    return re.split(r"\s+#", value, maxsplit=1)[0].strip()


def parse_frontmatter(text: str, source: Path) -> tuple[dict[str, str], str]:
    """Делит файл на словарь Frontmatter и Markdown-тело."""
    match = FRONTMATTER_RE.match(text)
    if not match:
        raise BuildError(f"{source.name}: нет YAML Frontmatter (--- ... ---) в начале файла")
    meta: dict[str, str] = {}
    for offset, raw_line in enumerate(match.group(1).splitlines(), start=2):
        line = raw_line.strip()
        if not line or line.startswith("#"):
            continue
        if ":" not in line:
            raise BuildError(f"{source.name}:{offset}: ожидается строка вида 'ключ: значение'")
        key, _, raw_value = line.partition(":")
        key = key.strip()
        if not key:
            raise BuildError(f"{source.name}:{offset}: пустой ключ Frontmatter")
        meta[key] = _parse_scalar(raw_value, f"{source.name}:{offset}")
    return meta, text[match.end():]


def _plain_text(markdown_text: str) -> str:
    """Markdown -> плоский текст (для автоописания и оценки времени чтения)."""
    text = re.sub(r"```.*?```", " ", markdown_text, flags=re.S)
    text = re.sub(r"[#>*_`|\[\]()!-]+", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def _auto_description(body: str) -> str:
    text = _plain_text(body)
    if len(text) <= 160:
        return text
    cut = text[:157].rsplit(" ", 1)[0]
    return cut + "…"


def _estimate_read_time(body: str) -> str:
    words = len(re.findall(r"\w+", _plain_text(body)))
    minutes = max(3, round(words / 180))
    return f"{minutes} мин"


def load_article(path: Path) -> Article:
    """Читает .md и валидирует Frontmatter."""
    try:
        text = path.read_text(encoding="utf-8")
    except OSError as exc:
        raise BuildError(f"{path.name}: не удалось прочитать файл ({exc})") from exc
    if text.startswith("\ufeff"):
        text = text[1:]

    meta, body = parse_frontmatter(text, path)

    title = meta.get("title", "").strip()
    if not title:
        raise BuildError(f"{path.name}: обязательное поле 'title' не заполнено")

    slug = meta.get("slug", "").strip()
    if not slug:
        slug = re.sub(r"[^a-z0-9]+", "-", path.stem.lower()).strip("-")
    if not SLUG_RE.match(slug):
        raise BuildError(
            f"{path.name}: slug '{slug}' должен состоять из строчной латиницы, цифр и дефисов"
        )

    date_raw = meta.get("date", "").strip()
    if not date_raw:
        raise BuildError(f"{path.name}: обязательное поле 'date' не заполнено")
    try:
        published = Date.fromisoformat(date_raw)
    except ValueError as exc:
        raise BuildError(f"{path.name}: дата '{date_raw}' не в формате YYYY-MM-DD") from exc

    if not body.strip():
        raise BuildError(f"{path.name}: нет Markdown-текста после Frontmatter")

    return Article(
        source=path,
        slug=slug,
        title=title,
        description=meta.get("description", "").strip() or _auto_description(body),
        published=published,
        read_time=meta.get("read_time", "").strip() or _estimate_read_time(body),
        card_target=meta.get("card_target", "").strip(),
        category=meta.get("category", "").strip(),
        body=body,
    )


# ===== MARKDOWN -> HTML =====
LIST_RE = re.compile(r"^(\s*)([-*+]|\d{1,3}[.)])\s+(.*)$")
HEADING_RE = re.compile(r"^(#{1,6})[ \t]+(.+?)[ \t]*$")
HR_RE = re.compile(r"^\s*(?:-{3,}|\*{3,}|_{3,})\s*$")
TABLE_SEPARATOR_RE = re.compile(r"^\s*\|?\s*:?-{3,}:?\s*(?:\|\s*:?-{3,}:?\s*)+\|?\s*$")
RAW_HTML_RE = re.compile(
    r"^\s*</?(?:div|aside|section|figure|figcaption|table|thead|tbody|tr|td|th|details|summary|"
    r"iframe|video|picture|img|p|ul|ol|blockquote|hr)\b",
    re.I,
)

_CODE_RE = re.compile(r"`([^`\n]+)`")
_IMAGE_RE = re.compile(r"!\[([^\]]*)\]\((\S+?)(?:\s+\"([^\"]*)\")?\)")
_LINK_RE = re.compile(r"\[([^\]]+)\]\((\S+?)(?:\s+\"([^\"]*)\")?\)")
_BOLD_RE = re.compile(r"\*\*(?!\s)(.+?)(?<!\s)\*\*")
_UNDERSCORE_BOLD_RE = re.compile(r"(?<![\w_])__(?!\s)(.+?)(?<!\s)__(?![\w_])")
_ITALIC_RE = re.compile(r"(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])")


def inline(text: str) -> str:
    """Строчная разметка Markdown с экранированием HTML."""
    stash: list[str] = []

    def keep(fragment: str) -> str:
        stash.append(fragment)
        return f"\x00{len(stash) - 1}\x00"

    text = _CODE_RE.sub(lambda m: keep("<code>" + html.escape(m.group(1), quote=False) + "</code>"), text)

    def image(match: re.Match[str]) -> str:
        alt = html.escape(match.group(1), quote=True)
        src = html.escape(match.group(2), quote=True)
        title = f' title="{html.escape(match.group(3), quote=True)}"' if match.group(3) else ""
        return keep(f'<img src="{src}" alt="{alt}"{title} loading="lazy">')

    text = _IMAGE_RE.sub(image, text)

    def link(match: re.Match[str]) -> str:
        label = html.escape(match.group(1), quote=False)
        href = html.escape(match.group(2), quote=True)
        title = f' title="{html.escape(match.group(3), quote=True)}"' if match.group(3) else ""
        return keep(f'<a href="{href}"{title}>{label}</a>')

    text = _LINK_RE.sub(link, text)
    text = html.escape(text, quote=False)
    text = _BOLD_RE.sub(r"<strong>\1</strong>", text)
    text = _UNDERSCORE_BOLD_RE.sub(r"<strong>\1</strong>", text)
    text = _ITALIC_RE.sub(r"<em>\1</em>", text)
    for index, fragment in enumerate(stash):
        text = text.replace(f"\x00{index}\x00", fragment)
    return text


_CELL_SPLIT_RE = re.compile(r"(?<!\\)\|")


def _split_row(line: str) -> list[str]:
    row = line.strip()
    if row.startswith("|"):
        row = row[1:]
    if row.endswith("|"):
        row = row[:-1]
    return [cell.strip().replace("\\|", "|") for cell in _CELL_SPLIT_RE.split(row)]


def _is_table_start(lines: list[str], index: int) -> bool:
    if index + 1 >= len(lines):
        return False
    return "|" in lines[index] and TABLE_SEPARATOR_RE.match(lines[index + 1]) is not None


def _render_table(lines: list[str], start: int) -> tuple[str, int]:
    header = _split_row(lines[start])
    index = start + 2
    rows: list[list[str]] = []
    while index < len(lines) and lines[index].strip() and "|" in lines[index]:
        rows.append(_split_row(lines[index]))
        index += 1

    parts = [
        '<div class="article-table-scroll">',
        "<table>",
        "<thead>",
        "<tr>" + "".join(f"<th>{inline(cell)}</th>" for cell in header) + "</tr>",
        "</thead>",
        "<tbody>",
    ]
    for row in rows:
        cells = (row + [""] * len(header))[: len(header)]
        parts.append("<tr>" + "".join(f"<td>{inline(cell)}</td>" for cell in cells) + "</tr>")
    parts += ["</tbody>", "</table>", "</div>"]
    return "\n".join(parts), index


def _indent_width(line: str) -> int:
    return len(line) - len(line.lstrip())


def _render_list(lines: list[str], start: int) -> tuple[str, int]:
    first = LIST_RE.match(lines[start])
    assert first is not None  # вызывается только после проверки LIST_RE
    ordered = first.group(2)[0].isdigit()
    base_indent = len(first.group(1).expandtabs(4))
    items: list[str] = []
    index = start
    total = len(lines)

    while index < total:
        line = lines[index]
        if not line.strip():
            break
        match = LIST_RE.match(line)
        if (
            match is None
            or len(match.group(1).expandtabs(4)) != base_indent
            or match.group(2)[0].isdigit() != ordered
        ):
            break

        item_parts = [match.group(3)]
        line_indent = _indent_width(line)
        index += 1
        while index < total:
            nxt = lines[index]
            if not nxt.strip():
                break
            nxt_match = LIST_RE.match(nxt)
            nxt_indent = _indent_width(nxt)
            if nxt_match is not None and nxt_indent > line_indent:
                nested, index = _render_list(lines, index)
                item_parts.append(nested)
                continue
            if nxt_match is not None or nxt_indent <= base_indent:
                break
            item_parts.append(nxt.strip())
            index += 1

        text_parts = [part for part in item_parts if not part.startswith(("<ul", "<ol"))]
        nested_parts = [part for part in item_parts if part.startswith(("<ul", "<ol"))]
        items.append(f"<li>{inline(' '.join(text_parts))}{''.join(nested_parts)}</li>")

    tag = "ol" if ordered else "ul"
    return f"<{tag}>\n" + "\n".join(items) + f"\n</{tag}>", index


def _render_blockquote(lines: list[str]) -> str:
    cleaned = [line.strip() for line in lines]
    probe = (cleaned[0] if cleaned else "").replace("**", "").replace("__", "").strip()
    if probe.lower().startswith(NOTE_TITLE.lower()):
        rest_text = probe[len(NOTE_TITLE):].lstrip(" .:—-")
        rest = ([rest_text] if rest_text else []) + cleaned[1:]
        body = render_markdown("\n".join(rest).strip()) if any(item.strip() for item in rest) else ""
        parts = ['<aside class="article-note">', f'<p class="article-note__title">{NOTE_TITLE}</p>']
        if body:
            parts.append(body)
        parts.append("</aside>")
        return "\n".join(parts)

    body = render_markdown("\n".join(cleaned).strip())
    return f"<blockquote>\n{body}\n</blockquote>"


def render_markdown(text: str) -> str:
    """Минималистичный Markdown -> HTML без внешних зависимостей."""
    lines = text.replace("\r\n", "\n").replace("\r", "\n").split("\n")
    blocks: list[str] = []
    index = 0
    total = len(lines)

    while index < total:
        line = lines[index]
        if not line.strip():
            index += 1
            continue

        stripped = line.strip()

        if stripped.startswith("```"):
            fence = stripped[:3]
            lang = stripped[3:].strip()
            index += 1
            code_lines: list[str] = []
            while index < total and not lines[index].strip().startswith(fence):
                code_lines.append(lines[index])
                index += 1
            if index < total:
                index += 1
            css_class = f' class="language-{html.escape(lang, quote=True)}"' if lang else ""
            code = html.escape("\n".join(code_lines), quote=False)
            blocks.append(f"<pre><code{css_class}>{code}</code></pre>")
            continue

        heading = HEADING_RE.match(line)
        if heading:
            level = len(heading.group(1))
            blocks.append(f"<h{level}>{inline(heading.group(2))}</h{level}>")
            index += 1
            continue

        if HR_RE.match(line):
            blocks.append("<hr>")
            index += 1
            continue

        if _is_table_start(lines, index):
            table_html, index = _render_table(lines, index)
            blocks.append(table_html)
            continue

        if stripped.startswith(">"):
            quote_lines: list[str] = []
            while index < total and lines[index].lstrip().startswith(">"):
                quote_lines.append(re.sub(r"^\s*>\s?", "", lines[index]))
                index += 1
            blocks.append(_render_blockquote(quote_lines))
            continue

        if LIST_RE.match(line):
            list_html, index = _render_list(lines, index)
            blocks.append(list_html)
            continue

        if RAW_HTML_RE.match(line):
            raw_lines: list[str] = []
            while index < total and lines[index].strip():
                raw_lines.append(lines[index])
                index += 1
            blocks.append("\n".join(raw_lines))
            continue

        paragraph: list[str] = []
        while index < total and lines[index].strip():
            current = lines[index]
            if (
                HEADING_RE.match(current)
                or HR_RE.match(current)
                or LIST_RE.match(current)
                or current.lstrip().startswith((">", "```"))
                or _is_table_start(lines, index)
                or RAW_HTML_RE.match(current)
            ):
                break
            paragraph.append(current.strip())
            index += 1
        blocks.append("<p>" + inline(" ".join(paragraph)) + "</p>")

    return "\n".join(blocks)


# ===== РЕНДЕР PHP-СТРАНИЦЫ =====
def php_quote(value: str) -> str:
    """Строковый литерал PHP в одинарных кавычках."""
    return "'" + value.replace("\\", "\\\\").replace("'", "\\'") + "'"


def _strip_php_open_tag(template: str) -> str:
    stripped = template.lstrip()
    if not stripped.startswith("<?php"):
        raise BuildError("templates/template-article.php должен начинаться с тега <?php")
    return stripped[len("<?php"):]


def render_article_page(article: Article, template: str) -> str:
    """Собирает автономный PHP-файл: шапка-комментарий, данные статьи, шаблон."""
    content_html = render_markdown(article.body).strip()

    # Nowdoc — чтобы HTML статьи не пришлось экранировать; маркер не должен
    # встречаться в тексте, поэтому при совпадении добавляем суффикс.
    nowdoc = "BLOG_ARTICLE_HTML"
    while nowdoc in content_html:
        nowdoc += "_X"

    header_comment = "\n".join(
        [
            "<?php",
            "// " + "=" * 76,
            f"// {GENERATED_MARKER} — НЕ РЕДАКТИРОВАТЬ ВРУЧНУЮ.",
            f"// Источник: content/articles/{article.source.name}",
            "// Пересборка: python scripts/build_blog.py",
            "// " + "=" * 76,
            "",
        ]
    )
    assignments = "\n".join(
        [
            f"$title = {php_quote(article.title)};",
            f"$description = {php_quote(article.description)};",
            f"$date = {php_quote(article.published.isoformat())};",
            f"$readTime = {php_quote(article.read_time)};",
            f"$slug = {php_quote(article.slug)};",
            f"$targetCard = {php_quote(article.card_target)};",
            f"$contentHtml = <<<'{nowdoc}'",
            content_html,
            nowdoc + ";",
        ]
    )
    return header_comment + assignments + "\n\n" + _strip_php_open_tag(template).rstrip() + "\n"


def format_date_ru(value: Date) -> str:
    return f"{value.day} {MONTHS_RU[value.month - 1]} {value.year}"


def render_catalog(articles: list[Article]) -> str:
    """HTML каталога для blog.php: фильтры (если категорий > 1) и сетка карточек."""
    indent = " " * 16
    lines: list[str] = []

    if not articles:
        lines.append(f'{indent}<p class="articles-empty">Инженерные разборы узлов готовятся к публикации — загляните чуть позже.</p>')
        return "\n".join(lines)

    categories = sorted({article.category for article in articles if article.category})
    category_keys = {name: f"cat-{position}" for position, name in enumerate(categories, start=1)}

    if len(categories) > 1:
        lines.append(f'{indent}<div class="blog-filters">')
        lines.append(f'{indent}    <button class="filter-btn active" onclick="filterBlog(\'all\')">Все статьи</button>')
        for name in categories:
            lines.append(
                f'{indent}    <button class="filter-btn" onclick="filterBlog(\'{category_keys[name]}\')">'
                f"{html.escape(name)}</button>"
            )
        lines.append(f"{indent}</div>")

    lines.append(f'{indent}<div class="articles-grid" id="articlesGrid">')
    for index, article in enumerate(articles):
        gradient = CARD_GRADIENTS[index % len(CARD_GRADIENTS)]
        badge = article.category or "Инженерный разбор узла"
        if article.card_target:
            badge = f"{badge} · №{article.card_target}"
        data_attr = f' data-category="{category_keys[article.category]}"' if article.category else ""
        href = f"blog/{article.slug}.php"
        lines += [
            f'{indent}    <article class="article-card"{data_attr}>',
            f'{indent}        <div class="article-image" style="background: {gradient};" aria-hidden="true"></div>',
            f'{indent}        <span class="article-category">{html.escape(badge)}</span>',
            f'{indent}        <h2 class="article-card__title"><a href="{href}">{html.escape(article.title)}</a></h2>',
            f'{indent}        <p class="article-meta"><time datetime="{article.published.isoformat()}">'
            f"{format_date_ru(article.published)}</time>"
            f' <span aria-hidden="true">·</span> {html.escape(article.read_time)} чтения</p>',
            f'{indent}        <p class="article-excerpt">{html.escape(article.description)}</p>',
            f'{indent}        <a class="read-more-btn" href="{href}">Читать разбор →</a>',
            f"{indent}    </article>",
        ]
    lines.append(f"{indent}</div>")
    return "\n".join(lines)


def update_catalog(articles: list[Article], check_only: bool) -> bool:
    """Пересобирает сетку карточек в blog.php. True — файл изменился."""
    try:
        text = CATALOG_FILE.read_text(encoding="utf-8")
    except OSError as exc:
        raise BuildError(f"blog.php: не удалось прочитать файл ({exc})") from exc

    start = text.find(CARD_MARKER_START)
    if start == -1:
        raise BuildError(f"blog.php: не найден маркер {CARD_MARKER_START}")
    end = text.find(CARD_MARKER_END, start + len(CARD_MARKER_START))
    if end == -1:
        raise BuildError(f"blog.php: не найден маркер {CARD_MARKER_END}")

    indent = text[text.rfind("\n", 0, start) + 1: start]
    updated = (
        text[:start]
        + CARD_MARKER_START
        + "\n"
        + render_catalog(articles)
        + "\n"
        + indent
        + text[end:]
    )
    if updated == text:
        return False
    if not check_only:
        CATALOG_FILE.write_text(updated, encoding="utf-8")
    return True


def _tag(name: str) -> str:
    return f"{{{SITEMAP_NS}}}{name}"


def update_sitemap(articles: list[Article], check_only: bool) -> bool:
    """Синхронизирует sitemap.xml: статьи блога + lastmod каталога."""
    try:
        tree = ET.parse(SITEMAP_FILE)
    except (OSError, ET.ParseError) as exc:
        raise BuildError(f"sitemap.xml: не удалось разобрать файл ({exc})") from exc

    root = tree.getroot()
    if root.tag != _tag("urlset"):
        raise BuildError("sitemap.xml: корневой элемент должен быть <urlset> в namespace sitemaps.org")

    for url in list(root.findall(_tag("url"))):
        loc = (url.findtext(_tag("loc")) or "").strip()
        if re.search(r"/blog/[^/]+\.php$", loc):
            root.remove(url)

    latest = articles[0].published.isoformat() if articles else None
    for url in root.findall(_tag("url")):
        loc = (url.findtext(_tag("loc")) or "").strip()
        if loc != f"{SITE_URL}/blog.php" or latest is None:
            continue
        lastmod = url.find(_tag("lastmod"))
        if lastmod is None:
            lastmod = ET.SubElement(url, _tag("lastmod"))
        lastmod.text = latest

    for article in articles:
        url = ET.SubElement(root, _tag("url"))
        ET.SubElement(url, _tag("loc")).text = f"{SITE_URL}/blog/{article.slug}.php"
        ET.SubElement(url, _tag("lastmod")).text = article.published.isoformat()
        ET.SubElement(url, _tag("changefreq")).text = "monthly"
        ET.SubElement(url, _tag("priority")).text = "0.7"

    ET.indent(tree, space="    ")
    buffer = io.BytesIO()
    tree.write(buffer, encoding="UTF-8", xml_declaration=True)
    xml_text = buffer.getvalue().decode("utf-8").replace(
        "<?xml version='1.0' encoding='UTF-8'?>",
        '<?xml version="1.0" encoding="UTF-8"?>',
    )
    if not xml_text.endswith("\n"):
        xml_text += "\n"

    try:
        current = SITEMAP_FILE.read_text(encoding="utf-8")
    except OSError:
        current = ""
    if current == xml_text:
        return False
    if not check_only:
        SITEMAP_FILE.write_text(xml_text, encoding="utf-8")
    return True


def prune_orphans(actual_slugs: set[str], check_only: bool) -> list[str]:
    """Удаляет сгенерированные страницы, чьи исходники больше не существуют."""
    removed: list[str] = []
    if not OUTPUT_DIR.is_dir():
        return removed
    for page in sorted(OUTPUT_DIR.glob("*.php")):
        if page.stem in actual_slugs:
            continue
        try:
            head = page.read_text(encoding="utf-8", errors="replace")[:800]
        except OSError:
            continue
        if GENERATED_MARKER not in head:
            continue  # чужой файл не трогаем
        if not check_only:
            page.unlink()
        removed.append(page.name)
    return removed


def build(check_only: bool = False) -> int:
    """Компилирует блог. Возвращает код выхода процесса."""
    if not ARTICLES_DIR.is_dir():
        raise BuildError(f"нет каталога {ARTICLES_DIR.relative_to(REPO_ROOT)} — нечего собирать")
    if not TEMPLATE_FILE.is_file():
        raise BuildError("нет шаблона templates/template-article.php")
    try:
        template = TEMPLATE_FILE.read_text(encoding="utf-8")
    except OSError as exc:
        raise BuildError(f"templates/template-article.php: не удалось прочитать файл ({exc})") from exc

    articles = [load_article(path) for path in sorted(ARTICLES_DIR.glob("*.md"))]

    seen: dict[str, Path] = {}
    for article in articles:
        if article.slug in seen:
            raise BuildError(
                f"дубликат slug '{article.slug}': {seen[article.slug].name} и {article.source.name}"
            )
        seen[article.slug] = article.source
    articles.sort(key=lambda item: (item.published, item.slug), reverse=True)

    if not check_only:
        OUTPUT_DIR.mkdir(parents=True, exist_ok=True)

    written: list[str] = []
    unchanged: list[str] = []
    for article in articles:
        target = OUTPUT_DIR / f"{article.slug}.php"
        page = render_article_page(article, template)
        try:
            is_current = target.is_file() and target.read_text(encoding="utf-8") == page
        except OSError:
            is_current = False
        if is_current:
            unchanged.append(target.name)
            continue
        if not check_only:
            target.write_text(page, encoding="utf-8")
        written.append(target.name)

    orphans = prune_orphans({article.slug for article in articles}, check_only)
    catalog_changed = update_catalog(articles, check_only)
    sitemap_changed = update_sitemap(articles, check_only)

    mode = "проверка" if check_only else "сборка"
    print(f"SSG блога · режим: {mode}")
    print(f"  статей в content/articles: {len(articles)}")
    if written:
        print("  пересобрано: " + ", ".join(f"blog/{name}" for name in written))
    if unchanged:
        print(f"  без изменений: {len(unchanged)} стр.")
    if orphans:
        print("  удалено осротевших: " + ", ".join(f"blog/{name}" for name in orphans))
    print(f"  blog.php: {'обновлён' if catalog_changed else 'актуален'}")
    print(f"  sitemap.xml: {'обновлён' if sitemap_changed else 'актуален'}")

    drift = bool(written or orphans or catalog_changed or sitemap_changed)
    if check_only and drift:
        print("\nОбнаружены расхождения: выполните python scripts/build_blog.py")
        return 1
    print("\nГотово." if not check_only else "\nСгенерированные файлы актуальны.")
    return 0


def main(argv: list[str] | None = None) -> int:
    for stream in (sys.stdout, sys.stderr):
        if hasattr(stream, "reconfigure"):
            stream.reconfigure(encoding="utf-8", errors="replace")

    parser = argparse.ArgumentParser(description="Компилятор статического блога ОдноЭтажники.РФ")
    parser.add_argument(
        "--check",
        action="store_true",
        help="не записывать файлы, только проверить актуальность (код 1 при расхождениях)",
    )
    args = parser.parse_args(argv)

    try:
        return build(check_only=args.check)
    except BuildError as exc:
        print(f"Ошибка сборки: {exc}", file=sys.stderr)
        return 1


if __name__ == "__main__":
    sys.exit(main())
