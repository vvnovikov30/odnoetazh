<?php
// blog.php - Блог с статьями

$page_title = 'Блог | ОдноЭтажники.РФ';
$page_description = 'Блог ОдноЭтажники.РФ: статьи о строительстве, архитектуре, дизайне и выборе материалов. Полезная информация для владельцев недвижимости.';

include 'header.php';
?>

        <!-- Blog Header -->
        <section class="blog-header">
            <div class="container">
                <h1>Блог <?php echo $company['name']; ?></h1>
                <p>Полезная информация о строительстве, архитектуре и дизайне. Советы экспертов, кейсы проектов, обзоры материалов.</p>
            </div>
        </section>

        <!-- Blog Section -->
        <section class="blog-section">
            <div class="container">
                <!-- Filters -->
                <div class="blog-filters">
                    <button class="filter-btn active" onclick="filterBlog('all')">Все статьи</button>
                    <button class="filter-btn" onclick="filterBlog('architecture')">Архитектура</button>
                    <button class="filter-btn" onclick="filterBlog('construction')">Строительство</button>
                    <button class="filter-btn" onclick="filterBlog('materials')">Материалы</button>
                </div>

                <!-- Articles Grid -->
                <div class="articles-grid" id="articlesGrid">
                    <!-- Article 1: Construction -->
                    <article class="article-card" data-category="construction">
                        <div class="article-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                        <div class="article-content">
                            <span class="article-category">Строительство</span>
                            <h2>Как выбрать подрядчика для строительства дома: 7 ключевых критериев</h2>
                            <p class="article-meta">15 ноября 2025 | Сергей Иванов</p>
                            <p class="article-excerpt">Выбор подрядчика — критическое решение, от которого зависит качество, сроки и бюджет проекта. Разбираемся в том, на какие факторы нужно обратить внимание при выборе строительной компании...</p>
                            
                            <div class="article-full" style="display: none;">
                                <p>Выбор подрядчика — это один из самых важных решений при возведении собственного дома. От этого выбора зависит не только качество работ, но и соблюдение сроков, финансовых рамок и вашего душевного спокойствия.</p>
                            </div>
                            
                            <button class="read-more-btn" onclick="toggleArticle(this)">Читать полностью →</button>
                        </div>
                    </article>

                    <!-- Article 2: Architecture -->
                    <article class="article-card" data-category="architecture">
                        <div class="article-image" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                        <div class="article-content">
                            <span class="article-category">Архитектура</span>
                            <h2>Архитектурные тренды 2025: модные решения для современного дома</h2>
                            <p class="article-meta">12 ноября 2025 | Александра Петрова</p>
                            <p class="article-excerpt">Посмотрим на главные тренды в архитектуре и дизайне, которые определяют облик современного жилья. От минимализма к максимализму, от натуральных материалов к инновациям...</p>
                            
                            <div class="article-full" style="display: none;">
                                <p>Архитектура постоянно развивается, отражая изменения в образе жизни и ценностях общества. Давайте рассмотрим самые актуальные тренды 2025 года.</p>
                            </div>
                            
                            <button class="read-more-btn" onclick="toggleArticle(this)">Читать полностью →</button>
                        </div>
                    </article>

                    <!-- Article 3: Construction -->
                    <article class="article-card" data-category="construction">
                        <div class="article-image" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                        <div class="article-content">
                            <span class="article-category">Строительство</span>
                            <h2>Электромонтажные работы: полный гайд для собственника</h2>
                            <p class="article-meta">10 ноября 2025 | Игорь Сорокин</p>
                            <p class="article-excerpt">Разбираемся, какие работы входят в электромонтаж, какие требуют лицензирования и как не допустить ошибок при выборе электрика...</p>
                            
                            <div class="article-full" style="display: none;">
                                <p>Электромонтажные работы — один из критически важных этапов строительства. Неправильное выполнение может привести к серьезным проблемам.</p>
                            </div>
                            
                            <button class="read-more-btn" onclick="toggleArticle(this)">Читать полностью →</button>
                        </div>
                    </article>

                    <!-- Article 4: Materials -->
                    <article class="article-card" data-category="materials">
                        <div class="article-image" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);"></div>
                        <div class="article-content">
                            <span class="article-category">Материалы</span>
                            <h2>Сравнение материалов: газобетон, кирпич, монолит</h2>
                            <p class="article-meta">8 ноября 2025 | Михаил Козлов</p>
                            <p class="article-excerpt">Анализируем основные строительные материалы, их плюсы, минусы и применение в разных климатических условиях...</p>
                            
                            <div class="article-full" style="display: none;">
                                <p>Выбор материала для стен — один из ключевых моментов при проектировании дома. Каждый материал имеет свои характеристики.</p>
                            </div>
                            
                            <button class="read-more-btn" onclick="toggleArticle(this)">Читать полностью →</button>
                        </div>
                    </article>
                </div>
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