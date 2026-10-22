<?php
// index.php - Главная страница

$page_title = 'ОдноЭтажники.РФ - Архитектура и Строительство | Полный цикл услуг';
$page_description = 'ОдноЭтажники.РФ - архитектура, проектирование и строительство. 15+ лет опыта, 200+ проектов, гарантия 5 лет. Консультация бесплатно!';

include 'header.php';
?>

        <!-- Hero Section -->
        <section class="hero">
            <div class="container">
                <div class="hero-content">
                    <h1>Ваш проект воплотится в жизнь вместе с <?php echo $company['name']; ?></h1>
                    <p>Полный цикл услуг: от архитектурного проектирования до завершения строительства. 15+ лет опыта, 200+ реализованных проектов.</p>
                    <button class="cta-btn-large">Получить бесплатную консультацию</button>
                </div>
                <div class="hero-image">
                    <div style="background: linear-gradient(135deg, #FF9500 0%, #FFB84D 100%); height: 400px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: white; font-size: 48px;">🏗️</div>
                </div>
            </div>
        </section>

        <!-- Services Overview -->
        <section class="services-overview">
            <div class="container">
                <h2>Наши услуги</h2>
                <p class="section-subtitle">Полный спектр услуг для реализации ваших архитектурных и строительных идей</p>
                
                <div class="services-grid">
                    <div class="service-card">
                        <div class="service-icon">🎨</div>
                        <h3>Архитектура и дизайн</h3>
                        <p>Архитектурное проектирование, 3D-визуализация, дизайн интерьеров и ландшафтное проектирование</p>
                        <a href="architecture.php" class="service-link">Подробнее →</a>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">🔨</div>
                        <h3>Строительные работы</h3>
                        <p>Монтажные, электромонтажные и санитарно-технические работы от опытных мастеров</p>
                        <a href="construction.php" class="service-link">Подробнее →</a>
                    </div>
                    
                    <div class="service-card">
                        <div class="service-icon">🏢</div>
                        <h3>Управление проектом</h3>
                        <p>Контроль всех этапов от согласования до сдачи объекта. Прозрачная смета и регулярные отчеты</p>
                        <a href="contacts.php" class="service-link">Узнать больше →</a>
                    </div>
                </div>
            </div>
        </section>

        <!-- Advantages Section -->
        <section class="advantages">
            <div class="container">
                <h2>Почему выбирают <?php echo $company['name']; ?></h2>
                
                <div class="advantages-grid">
                    <div class="advantage-item">
                        <h3>Опыт 15+ лет</h3>
                        <p>Более 200 успешно реализованных проектов по всему Московскому региону</p>
                    </div>
                    
                    <div class="advantage-item">
                        <h3>Команда экспертов</h3>
                        <p>50+ специалистов: архитекторы, инженеры, прорабы, электрики, сантехники</p>
                    </div>
                    
                    <div class="advantage-item">
                        <h3>Гарантия 5 лет</h3>
                        <p>Полная гарантия на все строительные работы и соответствие смете</p>
                    </div>
                    
                    <div class="advantage-item">
                        <h3>Современные технологии</h3>
                        <p>BIM-проектирование, 3D-визуализация, системы автоматизации</p>
                    </div>
                    
                    <div class="advantage-item">
                        <h3>Честное ценообразование</h3>
                        <p>Смета на первой консультации, без скрытых платежей и переплат</p>
                    </div>
                    
                    <div class="advantage-item">
                        <h3>Сертифицированные специалисты</h3>
                        <p>Все работники имеют необходимые лицензии и допуски</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Portfolio Preview -->
        <section class="portfolio-preview">
            <div class="container">
                <h2>Избранные проекты</h2>
                <p class="section-subtitle">Примеры успешно реализованных объектов</p>
                
                <div class="portfolio-grid">
                    <div class="portfolio-item">
                        <div class="portfolio-image" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                        <h3>Коттедж премиум-класса</h3>
                        <p>450 кв.м | Подмосковье</p>
                        <span class="project-type">Жилой проект</span>
                    </div>
                    
                    <div class="portfolio-item">
                        <div class="portfolio-image" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);"></div>
                        <h3>Офисный центр</h3>
                        <p>2000 кв.м | Москва</p>
                        <span class="project-type">Коммерческий</span>
                    </div>
                    
                    <div class="portfolio-item">
                        <div class="portfolio-image" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);"></div>
                        <h3>Загородный дом</h3>
                        <p>320 кв.м | М.О.</p>
                        <span class="project-type">Жилой проект</span>
                    </div>
                </div>
                
                <div class="view-all">
                    <a href="contacts.php" class="btn-link">Посмотреть все проекты →</a>
                </div>
            </div>
        </section>

        <!-- Blog Preview -->
        <section class="blog-preview">
            <div class="container">
                <h2>Актуальные статьи</h2>
                <p class="section-subtitle">Полезная информация о строительстве и архитектуре</p>
                
                <div class="blog-grid">
                    <article class="blog-card">
                        <h3>Как выбрать подрядчика для строительства дома</h3>
                        <p class="blog-meta">15 ноября 2025 | Сергей Иванов</p>
                        <p>Выбор подрядчика - критическое решение, от которого зависит качество и сроки проекта. Узнайте, на какие факторы обратить внимание...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                    
                    <article class="blog-card">
                        <h3>Архитектурные тренды 2025</h3>
                        <p class="blog-meta">12 ноября 2025 | Александра Петрова</p>
                        <p>Главные тренды в архитектуре и дизайне, которые определяют облик современного жилья. Минимализм, устойчивость, функциональность...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                    
                    <article class="blog-card">
                        <h3>Электромонтажные работы: полный гайд</h3>
                        <p class="blog-meta">10 ноября 2025 | Игорь Сорокин</p>
                        <p>Разбираемся, какие работы входят в электромонтаж, какие требуют лицензирования и как не допустить ошибок при выборе подрядчика...</p>
                        <a href="blog.php" class="read-more">Читать далее →</a>
                    </article>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="cta-section">
            <div class="container">
                <div class="cta-content">
                    <h2>Готовы начать ваш проект?</h2>
                    <p>Свяжитесь с нами для бесплатной консультации. Наши специалисты помогут определить объем работ и сроки реализации.</p>
                    
                    <form class="contact-form-inline" id="ctaForm" action="send-email.php" method="POST">
                        <div style="position:absolute;left:-9999px;" aria-hidden="true">
                            <input type="text" name="website" tabindex="-1" autocomplete="off">
                        </div>
                        <input type="hidden" name="form_ts" value="<?php echo time(); ?>">
                        <div class="form-group">
                            <input type="text" name="name" placeholder="Ваше имя" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="phone" placeholder="Номер телефона" required>
                        </div>
                        <button type="submit" class="btn-submit">Получить консультацию</button>
                    </form>
                </div>
            </div>
        </section>

<?php include 'footer.php'; ?>