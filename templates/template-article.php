<?php
/**
 * templates/template-article.php — единый шаблон статьи блога (стиль japandi).
 *
 * ИСХОДНИК SSG: на хостинг не выгружается (.gitattributes: templates/ export-ignore).
 * scripts/build_blog.py инлайнит этот файл в blog/{slug}.php, предварительно
 * присвоив переменные:
 *   $title, $description, $date (Y-m-d), $readTime, $slug, $contentHtml, $targetCard
 *
 * В скомпилированной странице __DIR__ указывает на blog/, поэтому
 * '../header.php' и '../footer.php' — сквозные компоненты в корне сайта.
 */

$page_title = $title . ' | Блог ОдноЭтажники.РФ';
$page_description = $description;
// Статья лежит в подкаталоге /blog/, поэтому относительные URL (style.css,
// logo2.jpg, index.php, scripts/main.js) в header.php/footer.php считаются
// от корня сайта — см. поддержку $base_href в header.php.
$base_href = '../';

$monthsRu = [
    1 => 'января', 2 => 'февраля', 3 => 'марта', 4 => 'апреля',
    5 => 'мая', 6 => 'июня', 7 => 'июля', 8 => 'августа',
    9 => 'сентября', 10 => 'октября', 11 => 'ноября', 12 => 'декабря',
];
$articleTs = strtotime($date) ?: time();
$dateRu = date('j', $articleTs) . ' ' . $monthsRu[(int) date('n', $articleTs)] . ' ' . date('Y', $articleTs);

$siteUrl = 'https://xn--80ahhialrcec8b1i.xn--p1ai';
$articleUrl = $siteUrl . '/blog/' . rawurlencode($slug) . '.php';

// TechArticle: инженерный разбор узла с явным автором-организацией.
$articleSchema = [
    '@context'         => 'https://schema.org',
    '@type'            => 'TechArticle',
    'headline'         => $title,
    'description'      => $description,
    'datePublished'    => $date,
    'dateModified'     => $date,
    'inLanguage'       => 'ru-RU',
    'url'              => $articleUrl,
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id'   => $articleUrl,
    ],
    'author'           => [
        '@type' => 'Organization',
        'name'  => 'ООО «ОдноЭтажники.РФ»',
        'url'   => $siteUrl . '/',
    ],
    'publisher'        => [
        '@type' => 'Organization',
        'name'  => 'ООО «ОдноЭтажники.РФ»',
        'url'   => $siteUrl . '/',
    ],
    'isPartOf'         => [
        '@type' => 'Blog',
        'name'  => 'Блог ОдноЭтажники.РФ',
        'url'   => $siteUrl . '/blog.php',
    ],
];

require_once __DIR__ . '/../header.php';
?>

        <article class="article-page">
            <div class="container">
                <nav class="article-breadcrumbs" aria-label="Хлебные крошки">
                    <ol>
                        <li><a href="/">Главная</a></li>
                        <li><a href="/blog.php">Блог</a></li>
                        <li><span aria-current="page"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></span></li>
                    </ol>
                </nav>

                <header class="article-header">
                    <p class="article-badge">Инженерный разбор узла<?php echo $targetCard !== '' ? ' · №' . htmlspecialchars($targetCard, ENT_QUOTES, 'UTF-8') : ''; ?></p>
                    <h1><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p class="article-meta">
                        <time datetime="<?php echo htmlspecialchars($date, ENT_QUOTES, 'UTF-8'); ?>"><?php echo $dateRu; ?></time>
                        <span aria-hidden="true">·</span>
                        <span><?php echo htmlspecialchars($readTime, ENT_QUOTES, 'UTF-8'); ?> чтения</span>
                    </p>
                    <p class="article-lead"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
                </header>

                <div class="article-content">
<?php echo $contentHtml; ?>
                </div>

                <aside class="article-cta" aria-labelledby="article-cta-title">
                    <p class="article-cta__eyebrow">Дом-шоурум 90.8 м²</p>
                    <h2 id="article-cta-title">Хотите увидеть реализацию этого узла вживую?</h2>
                    <p>Приглашаем на тест-драйв в дом-шоурум 90.8 м² в Новой Москве: посмотрите кровельный пирог, шаг стропил и узлы примыкания так, как они собраны в готовом доме.</p>
                    <a class="article-cta__button" href="/#cta">Записаться на тест-драйв</a>
                </aside>

            </div>
        </article>

        <script type="application/ld+json">
<?php echo json_encode($articleSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT); ?>
        </script>

        <!-- Lightbox статьи: иллюстрации .article-figure открываются в карусели
             (scripts/main.js ограничивает её группой ссылок внутри .article-content) -->
        <div id="lightboxModal" class="lightbox-modal" aria-hidden="true" role="dialog" aria-label="Просмотр фотографии">
            <button type="button" class="lightbox-close" aria-label="Закрыть (Esc)">&times;</button>
            <button type="button" class="lightbox-prev" aria-label="Предыдущее фото (Стрелка влево)">&#10094;</button>
            <button type="button" class="lightbox-next" aria-label="Следующее фото (Стрелка вправо)">&#10095;</button>
            <figure class="lightbox-figure">
                <img alt="" class="lightbox-image">
                <figcaption class="lightbox-caption"></figcaption>
            </figure>
        </div>

<?php // FAQ (AIO): локальный реестр data/faq_registry.json + JSON-LD FAQPage. ?>
<?php require_once __DIR__ . '/../includes/faq_block.php'; ?>
<?php require_once __DIR__ . '/../footer.php'; ?>
