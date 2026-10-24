<?php
// blog.php - Блог: каталог инженерных разборов.
// Сетка карточек между маркерами BLOG_CARDS генерируется scripts/build_blog.py
// из content/articles/*.md — вручную этот блок не редактируется.

$page_title = 'Блог | ОдноЭтажники.РФ';
$page_description = 'Инженерные разборы узлов одноэтажного дома: кровельные пироги, каркас, нормы СП и ГОСТ, ошибки монтажа и решения из практики.';

include 'header.php';
?>

        <!-- Blog Header -->
        <section class="blog-header">
            <div class="container">
                <h1>Блог <?php echo $company['name']; ?></h1>
                <p>Инженерные разборы узлов одноэтажного дома: нормы, пироги, шаг стропил и ошибки, которые видно только на объекте.</p>
            </div>
        </section>

        <!-- Blog Section -->
        <section class="blog-section">
            <div class="container">
                <!-- Сетка карточек генерируется scripts/build_blog.py из content/articles/*.md.
                     Правки внутри маркеров BLOG_CARDS будут перезаписаны при сборке. -->
                <!-- BLOG_CARDS:START -->
                <div class="blog-filters">
                    <button class="filter-btn active" onclick="filterBlog('all')">Все статьи</button>
                    <button class="filter-btn" onclick="filterBlog('cat-1')">Микроклимат</button>
                    <button class="filter-btn" onclick="filterBlog('cat-2')">Фундамент</button>
                </div>
                <div class="articles-grid" id="articlesGrid">
                    <article class="article-card" data-category="cat-1">
                        <div class="article-image" style="background: linear-gradient(135deg, #dfe8e2 0%, #b9cdbf 100%);" aria-hidden="true"></div>
                        <span class="article-category">Микроклимат · №04</span>
                        <h2 class="article-card__title"><a href="blog/ventilyaciya.php">Физика свежести: рекуперация, аэроионы и потолки 3.3 м в одноэтажном доме</a></h2>
                        <p class="article-meta"><time datetime="2026-10-23">23 октября 2026</time> <span aria-hidden="true">·</span> 9 мин чтения</p>
                        <p class="article-excerpt">Почему при высоте потолков 3.3 м микроклимат принципиально отличается: физиология аэроионизации, воздухообмен и рекуперация тепла.</p>
                        <a class="read-more-btn" href="blog/ventilyaciya.php">Читать разбор →</a>
                    </article>
                    <article class="article-card" data-category="cat-2">
                        <div class="article-image" style="background: linear-gradient(135deg, #efe7dc 0%, #cdb9a3 100%);" aria-hidden="true"></div>
                        <span class="article-category">Фундамент · №01</span>
                        <h2 class="article-card__title"><a href="blog/plita-na-penoplaste.php">Плита на пенопласте: расчет нагрузок, теплотехника и нормативная база СП 22.13330</a></h2>
                        <p class="article-meta"><time datetime="2026-10-23">23 октября 2026</time> <span aria-hidden="true">·</span> 10 мин чтения</p>
                        <p class="article-excerpt">Инженерный анализ плитного фундамента: распределение давлений, ликвидация морозного пучения и физика водяного теплого пола без радиаторов.</p>
                        <a class="read-more-btn" href="blog/plita-na-penoplaste.php">Читать разбор →</a>
                    </article>
                </div>
                <!-- BLOG_CARDS:END -->
            </div>
        </section>

        <!-- Newsletter Section -->
        <section class="newsletter-section">
            <div class="container">
                <h2>Подпишитесь на обновления</h2>
                <p>Получайте новые статьи блога, советы экспертов и предложения прямо в ваш Telegram</p>
                
                <div class="newsletter-form">
                    <a href="<?php echo $company['telegram']; ?>" class="btn-telegram" target="_blank">Подписаться на Telegram канал</a>
                </div>
            </div>
        </section>

<?php include 'footer.php'; ?>