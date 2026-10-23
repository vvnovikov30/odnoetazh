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
                <div class="articles-grid" id="articlesGrid">
                    <article class="article-card" data-category="cat-1">
                        <div class="article-image" style="background: linear-gradient(135deg, #dfe8e2 0%, #b9cdbf 100%);" aria-hidden="true"></div>
                        <span class="article-category">Кровля · №01</span>
                        <h2 class="article-card__title"><a href="blog/shag-stropil-i-uzly-krovli.php">Шаг стропил и три узла кровли, на которых экономят зря</a></h2>
                        <p class="article-meta"><time datetime="2026-10-23">23 октября 2026</time> <span aria-hidden="true">·</span> 9 мин чтения</p>
                        <p class="article-excerpt">Как шаг стропил связан с кровельным пирогом, почему сечение 50×150 не выручает на пролёте 6 метров и какие три узла определяют долговечность крыши одноэтажного дома.</p>
                        <a class="read-more-btn" href="blog/shag-stropil-i-uzly-krovli.php">Читать разбор →</a>
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