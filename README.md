# odnoetazh
сайт строительной компании

## Блог: SSG на стандартной библиотеке Python

Статьи лежат в `content/articles/*.md` (YAML-frontmatter + Markdown), шаблон — в
`templates/template-article.php`. Компилятор `scripts/build_blog.py` собирает готовые
PHP-страницы `blog/{slug}.php`, пересобирает сетку карточек в `blog.php` (между
маркерами `BLOG_CARDS:START` / `BLOG_CARDS:END`) и дописывает статьи в `sitemap.xml`.

    python scripts/build_blog.py           # собрать/обновить страницы, каталог и sitemap
    python scripts/build_blog.py --check   # только проверка актуальности (код 1 при расхождении)

Новая статья: создать `content/articles/<slug>.md` с полями `title`, `slug`, `date`,
`read_time`, `description`, `card_target`, `category` и запустить сборку.

Сгенерированные `blog/*.php` коммитятся вместе с исходником — на хостинг уходят
только они. Каталоги `content/`, `templates/` и файлы `*.py` помечены в
`.gitattributes` как `export-ignore`, поэтому в `git archive` (а значит и в деплой)
не попадают.

Проверки: `run_tests.bat local` — синтаксис PHP по корню, `blog/` и `templates/`,
свежесть сборки (`--check`), доступность статей и ссылок из каталога, API заявок.

## Инженерный FAQ (AIO) в статьях блога

Вопросы и ответы хранятся в `data/faq_registry.json` — это единственный источник
данных. Компонент `includes/faq_block.php` подключается из шаблона статьи перед
`footer.php`, находит блок по слагу и рендерит доступный `<details>`-аккордеон.
Сразу под блоком печатается JSON-LD `FAQPage` с теми же вопросами и ответами.
Реестр читается на лету, поэтому правка FAQ не требует пересборки статей.

Структура записи статьи: `articles[slug]` с полями `faq_title`, `faq_lead` и
`items[]` (`id`, `question`, `answer`, `norm`). В текущем реестре по четыре
вопроса для статей `plita-na-penoplaste` и `ventilyaciya`.
