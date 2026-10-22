<?php
// index.php (ОБНОВЛЕНО С ПОЛНЫМ SEO)
include 'header.php';

$page_title = 'ОдноЭтажники.РФ - Архитектура, проектирование и строительство одноэтажных домов';
$page_description = 'Полный цикл услуг: проектирование, строительство, контроль качества. 200+ готовых проектов. Готовые дома и типовые серии. Смета за 24 часа.';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- CANONICAL URL для главной -->
    <link rel="canonical" href="https://одноэтажники.рф/">
    
    <!-- OPEN GRAPH для соцсетей -->
    <meta property="og:url" content="https://одноэтажники.рф/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="https://одноэтажники.рф/images/hero-house.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="ru_RU">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title; ?>">
    <meta name="twitter:description" content="<?php echo $page_description; ?>">
    <meta name="twitter:image" content="https://одноэтажники.рф/images/hero-house.jpg">
    
    <!-- ДОПОЛНИТЕЛЬНЫЕ МЕТА-ТЕГИ -->
    <meta name="theme-color" content="#208089">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="icon" href="https://одноэтажники.рф/favicon.ico">
    <link rel="apple-touch-icon" href="https://одноэтажники.рф/apple-touch-icon.png">
    
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="new-styles.css">
</head>
<body>
    <!-- Навигация -->
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="/">ОдноЭтажники.РФ</a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="#services">Услуги</a></li>
                        <li><a href="our-houses/">Наши дома</a></li>
                        <li><a href="portfolio/">Портфолио</a></li>
                        <li><a href="blog/">Блог</a></li>
                        <li><a href="about/">О компании</a></li>
                        <li><a href="contacts/">Контакты</a></li>
                    </ul>
                </nav>
                <div class="header-contacts">
                    <a href="tel:+79850961086" class="phone"><?php echo $company['phone']; ?></a>
                </div>
            </div>
        </div>
    </header>

    <!-- ГЛАВНЫЙ HERO БЛОК -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Архитектура и строительство одноэтажных домов</h1>
                <p class="hero-subtitle">Полный цикл услуг: проектирование, строительство, контроль качества</p>
                <p class="hero-stats">15+ лет опыта • 200+ проектов • 50+ специалистов</p>
                <div class="hero-cta">
                    <a href="contacts/#form" class="btn btn-primary btn-lg">Загрузить техзадание</a>
                    <a href="our-houses/" class="btn btn-secondary btn-lg">Посмотреть готовые дома</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="images/hero-house.jpg" alt="Современный одноэтажный дом проектирование и строительство">
            </div>
        </div>
    </section>

    <!-- НОВЫЙ РАЗДЕЛ: НАШИ ДОМА -->
    <section class="our-houses-teaser">
        <div class="container">
            <h2>Наши дома</h2>
            <p class="section-subtitle">Готовые проекты и типовые серии</p>
            
            <div class="houses-grid">
                <!-- Карточка 1: Шоурум -->
                <div class="house-card">
                    <div class="house-card-image">
                        <img src="images/shoorum-house.jpg" alt="Дом из шоурума 180 кв.м готовый проект">
                        <span class="house-badge">Готовый проект</span>
                    </div>
                    <div class="house-card-content">
                        <h3>Шоурум</h3>
                        <p>Готовый дом площадью 180 кв.м со всеми коммуникациями. Реализованный пример всех наших решений.</p>
                        <ul class="house-features">
                            <li>✓ 180 кв.м</li>
                            <li>✓ Все решения</li>
                            <li>✓ С фото</li>
                            <li>✓ Актуальная цена</li>
                        </ul>
                        <div class="house-cta">
                            <a href="our-houses/shoorum/" class="btn btn-outline">Подробнее о шоуруме</a>
                        </div>
                    </div>
                </div>

                <!-- Карточка 2: Типовая серия -->
                <div class="house-card">
                    <div class="house-card-image">
                        <img src="images/typical-series.jpg" alt="Типовая серия проектов 6 вариантов от 120 до 250 кв.м">
                        <span class="house-badge">6 вариантов</span>
                    </div>
                    <div class="house-card-content">
                        <h3>Типовая серия</h3>
                        <p>6 готовых проектов от 120 до 250 кв.м. Адаптируются под ваш участок. Расчет стоимости за 30 минут.</p>
                        <ul class="house-features">
                            <li>✓ 6 вариантов</li>
                            <li>✓ 120–250 кв.м</li>
                            <li>✓ Адаптируем</li>
                            <li>✓ Калькулятор</li>
                        </ul>
                        <div class="house-cta">
                            <a href="our-houses/typical-series/" class="btn btn-primary">Выбрать проект</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="our-houses/" class="btn btn-secondary btn-lg">Все готовые дома</a>
            </div>
        </div>
    </section>

    <!-- УСЛУГИ -->
    <section class="services" id="services">
        <div class="container">
            <h2>Услуги</h2>
            <p class="section-subtitle">Полный спектр решений для реализации ваших идей</p>
            
            <div class="services-grid">
                <!-- Услуга 1: Проектирование -->
                <div class="service-card">
                    <div class="service-icon">🏗️</div>
                    <h3>Проектирование</h3>
                    <p>Архитектурное проектирование, 3D-визуализация, дизайн интерьеров и ландшафтное проектирование.</p>
                    <ul class="service-features">
                        <li>• BIM-проектирование</li>
                        <li>• 3D-визуализация</li>
                        <li>• Рабочие чертежи</li>
                    </ul>
                    <a href="uslugi/proektirovanie/" class="service-link">Подробнее →</a>
                    <a href="contacts/#form" class="btn btn-outline btn-sm">Загрузить эскиз</a>
                </div>

                <!-- Услуга 2: Строительство -->
                <div class="service-card">
                    <div class="service-icon">🔨</div>
                    <h3>Строительство</h3>
                    <p>Монтажные, электромонтажные и санитарно-технические работы от опытных мастеров с лицензиями.</p>
                    <ul class="service-features">
                        <li>• 50+ специалистов</li>
                        <li>• Все лицензии</li>
                        <li>• Гарантия качества</li>
                    </ul>
                    <a href="uslugi/stroitelstvo/" class="service-link">Подробнее →</a>
                    <a href="contacts/#form" class="btn btn-outline btn-sm">Загрузить план участка</a>
                </div>

                <!-- Услуга 3: Контроль качества -->
                <div class="service-card">
                    <div class="service-icon">✓</div>
                    <h3>Контроль качества</h3>
                    <p>Контроль всех этапов от согласования до сдачи объекта. Еженедельные отчеты и фотографии.</p>
                    <ul class="service-features">
                        <li>• Ежедневный контроль</li>
                        <li>• Еженедельные отчеты</li>
                        <li>• Прозрачная смета</li>
                    </ul>
                    <a href="uslugi/kontrol-kachestva/" class="service-link">Подробнее →</a>
                    <a href="contacts/#form" class="btn btn-outline btn-sm">Закончить форму</a>
                </div>

                <!-- Услуга 4: Готовые дома и типовые проекты -->
                <div class="service-card service-card-new">
                    <div class="service-icon">⭐</div>
                    <h3>Готовые дома и типовые проекты</h3>
                    <p>Не хотите ждать проектирования? Выберите готовый проект или типовую серию. Адаптируем под ваш участок.</p>
                    <ul class="service-features">
                        <li>• Шоурум 180 кв.м</li>
                        <li>• 6 типовых проектов</li>
                        <li>• Быстро и надежно</li>
                    </ul>
                    <a href="our-houses/" class="service-link">Подробнее →</a>
                    <a href="our-houses/shoorum/" class="btn btn-outline btn-sm">В шоурум</a>
                </div>
            </div>
        </div>
    </section>

    <!-- ПРЕИМУЩЕСТВА КОМПАНИИ -->
    <section class="advantages">
        <div class="container">
            <h2>Почему выбирают нас</h2>
            <div class="advantages-grid">
                <div class="advantage-item">
                    <div class="advantage-number">200+</div>
                    <h4>Реализованных проектов</h4>
                    <p>Более 200 успешно завершенных объектов в Москве и Подмосковье</p>
                </div>
                <div class="advantage-item">
                    <div class="advantage-number">50+</div>
                    <h4>Специалистов</h4>
                    <p>Архитекторы, инженеры, прорабы, электрики, сантехники с опытом 15+ лет</p>
                </div>
                <div class="advantage-item">
                    <div class="advantage-number">100%</div>
                    <h4>Гарантия качества</h4>
                    <p>Полная гарантия на все работы и материалы. Без скрытых платежей и переплат.</p>
                </div>
                <div class="advantage-item">
                    <div class="advantage-number">24ч</div>
                    <h4>Смета</h4>
                    <p>Детальная смета на первой консультации. Ежедневный контроль и еженедельные отчеты.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ПОРТФОЛИО (с фильтрами) -->
    <section class="portfolio">
        <div class="container">
            <h2>Портфолио</h2>
            <p class="section-subtitle">Примеры успешно реализованных объектов</p>
            
            <div class="portfolio-filters">
                <a href="portfolio/" class="filter-btn active">Все проекты</a>
                <a href="portfolio/?category=zhilye" class="filter-btn">Жилые дома</a>
                <a href="portfolio/?category=kommercheskie" class="filter-btn">Коммерческие</a>
                <a href="portfolio/?material=kirpich" class="filter-btn">Кирпич</a>
                <a href="portfolio/?material=beton" class="filter-btn">Бетон</a>
            </div>

            <div class="portfolio-grid">
                <!-- Проект 1 -->
                <div class="portfolio-item">
                    <img src="images/project-1.jpg" alt="Жилой дом 450 кв.м проект архитектура">
                    <div class="portfolio-overlay">
                        <h4>Жилой дом 450 кв.м</h4>
                        <p>Подмосковье</p>
                        <a href="portfolio/project-1/" class="portfolio-link">Подробнее</a>
                        <div class="portfolio-related-blog">
                            <small><a href="blog/konstruktivnye-resheniya/plitnyj-fundament/">← Статья о плитном фундаменте</a></small>
                        </div>
                    </div>
                </div>

                <!-- Проект 2 -->
                <div class="portfolio-item">
                    <img src="images/project-2.jpg" alt="Коммерческое здание 2000 кв.м строительство">
                    <div class="portfolio-overlay">
                        <h4>Коммерческое здание 2000 кв.м</h4>
                        <p>Москва</p>
                        <a href="portfolio/project-2/" class="portfolio-link">Подробнее</a>
                        <div class="portfolio-related-blog">
                            <small><a href="blog/inzhenernye-sistemy/ventilyaciya/">← Статья о вентиляции</a></small>
                        </div>
                    </div>
                </div>

                <!-- Проект 3 -->
                <div class="portfolio-item">
                    <img src="images/project-3.jpg" alt="Жилой дом 320 кв.м проект">
                    <div class="portfolio-overlay">
                        <h4>Жилой дом 320 кв.м</h4>
                        <p>М.О.</p>
                        <a href="portfolio/project-3/" class="portfolio-link">Подробнее</a>
                        <div class="portfolio-related-blog">
                            <small><a href="blog/planirovochnie-resheniya/dva-sanuzla/">← Статья о двух санузлах</a></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="portfolio/" class="btn btn-secondary btn-lg">Все проекты</a>
            </div>
        </div>
    </section>

    <!-- БЛОГ (категоризирован) -->
    <section class="blog">
        <div class="container">
            <h2>Полезная информация</h2>
            <p class="section-subtitle">Советы, тренды и примеры проектов</p>

            <div class="blog-categories">
                <!-- Категория 1: Конструктивные решения -->
                <div class="blog-category">
                    <h3>Конструктивные решения</h3>
                    <div class="blog-posts">
                        <article class="blog-post">
                            <h4><a href="blog/konstruktivnye-resheniya/plitnyj-fundament/">Плитный фундамент</a></h4>
                            <p>Подробное руководство по устройству мелкозаглубленной железобетонной плиты на пенопласте...</p>
                            <a href="blog/konstruktivnye-resheniya/plitnyj-fundament/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="our-houses/shoorum/">Примеры в шоуруме →</a></small>
                            </div>
                        </article>

                        <article class="blog-post">
                            <h4><a href="blog/konstruktivnye-resheniya/vodyanoj-teplyj-pol/">Водяной теплый пол</a></h4>
                            <p>Как правильно спроектировать и установить систему теплого пола. Расчеты и практические советы...</p>
                            <a href="blog/konstruktivnye-resheniya/vodyanoj-teplyj-pol/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="contacts/#form">Загрузить техзадание →</a></small>
                            </div>
                        </article>

                        <article class="blog-post">
                            <h4><a href="blog/konstruktivnye-resheniya/tepłaya-otmostka/">Теплая отмостка</a></h4>
                            <p>Защита фундамента от промерзания. Технология укладки и расчет толщины утеплителя...</p>
                            <a href="blog/konstruktivnye-resheniya/tepłaya-otmostka/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="portfolio/">Смотреть примеры →</a></small>
                            </div>
                        </article>
                    </div>
                    <a href="blog/konstruktivnye-resheniya/" class="category-all-link">Все статьи →</a>
                </div>

                <!-- Категория 2: Инженерные системы -->
                <div class="blog-category">
                    <h3>Инженерные системы</h3>
                    <div class="blog-posts">
                        <article class="blog-post">
                            <h4><a href="blog/inzhenernye-sistemy/ventilyaciya/">Система вентиляции</a></h4>
                            <p>Как выбрать между рекуператором и естественной вентиляцией. Расчеты для одноэтажного дома...</p>
                            <a href="blog/inzhenernye-sistemy/ventilyaciya/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="uslugi/proektirovanie/">Заказать проект →</a></small>
                            </div>
                        </article>

                        <article class="blog-post">
                            <h4><a href="blog/inzhenernye-sistemy/fanovanaya-truba/">Фановая труба</a></h4>
                            <p>Зачем нужна фановая труба. Расчет диаметра и высоты. Частые ошибки при монтаже...</p>
                            <a href="blog/inzhenernye-sistemy/fanovanaya-truba/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="uslugi/stroitelstvo/">Доверить нам →</a></small>
                            </div>
                        </article>
                    </div>
                    <a href="blog/inzhenernye-sistemy/" class="category-all-link">Все статьи →</a>
                </div>

                <!-- Категория 3: Планировочные решения -->
                <div class="blog-category">
                    <h3>Планировочные решения</h3>
                    <div class="blog-posts">
                        <article class="blog-post">
                            <h4><a href="blog/planirovochnie-resheniya/dva-sanuzla/">Два санузла в одноэтажном доме</a></h4>
                            <p>Как правильно разместить два санузла. Раскладка труб, экономия пространства...</p>
                            <a href="blog/planirovochnie-resheniya/dva-sanuzla/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="our-houses/typical-series/">Готовые проекты →</a></small>
                            </div>
                        </article>

                        <article class="blog-post">
                            <h4><a href="blog/planirovochnie-resheniya/nizkie-podokonnki/">Низкие подоконники</a></h4>
                            <p>Подоконники от пола: преимущества и недостатки. Технология установки и утепления...</p>
                            <a href="blog/planirovochnie-resheniya/nizkie-podokonnki/" class="read-more">Читать далее →</a>
                            <div class="blog-related-offer">
                                <small><a href="our-houses/shoorum/">Смотреть в шоуруме →</a></small>
                            </div>
                        </article>
                    </div>
                    <a href="blog/planirovochnie-resheniya/" class="category-all-link">Все статьи →</a>
                </div>
            </div>

            <div class="section-cta-center">
                <a href="blog/" class="btn btn-secondary btn-lg">Весь блог</a>
            </div>
        </div>
    </section>

    <!-- ГЛАВНЫЙ CTA (форма) -->
    <section class="cta-section">
        <div class="container">
            <h2>Готовы обсудить ваш проект?</h2>
            <p>Оставьте заявку или позвоните нам прямо сейчас</p>
            
            <div class="cta-flex">
                <div class="cta-form">
                    <h3>Загрузить техзадание</h3>
                    <form action="send-email.php" method="POST" enctype="multipart/form-data" id="main-form">
                        <div class="form-group">
                            <label for="name">Ваше имя *</label>
                            <input type="text" id="name" name="name" required>
                        </div>

                        <div class="form-group">
                            <label for="phone">Телефон *</label>
                            <input type="tel" id="phone" name="phone" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email">
                        </div>

                        <div class="form-group">
                            <label for="service">Интересует услуга *</label>
                            <select id="service" name="service" required>
                                <option value="">Выберите услугу</option>
                                <option value="Проектирование">Проектирование</option>
                                <option value="Строительство">Строительство</option>
                                <option value="Контроль качества">Контроль качества</option>
                                <option value="Готовые дома">Готовые дома и типовые проекты</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="description">Описание проекта</label>
                            <textarea id="description" name="description" rows="4"></textarea>
                        </div>

                        <div class="form-group">
                            <label for="file">Загрузить файл (план, эскиз)</label>
                            <input type="file" id="file" name="file">
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-full">Отправить заявку</button>
                    </form>
                </div>

                <div class="cta-contacts">
                    <h3>Или свяжитесь с нами</h3>
                    <div class="contact-method">
                        <strong>Телефон:</strong>
                        <a href="tel:<?php echo str_replace(' ', '', $company['phone']); ?>"><?php echo $company['phone']; ?></a>
                    </div>
                    <div class="contact-method">
                        <strong>Email:</strong>
                        <a href="mailto:<?php echo $company['email']; ?>"><?php echo $company['email']; ?></a>
                    </div>
                    <div class="contact-method">
                        <strong>Адрес:</strong>
                        <p><?php echo $company['address']; ?></p>
                    </div>
                    <div class="contact-method">
                        <strong>Telegram:</strong>
                        <a href="<?php echo $company['telegram']; ?>" target="_blank">Написать</a>
                    </div>

                    <div class="service-areas">
                        <h4>Наши районы:</h4>
                        <ul>
                            <li>Москва</li>
                            <li>Московская область</li>
                            <li>Тверская область</li>
                            <li>Смоленская область</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- STRUCTURED DATA (Schema.org) для главной -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "Organization",
      "name": "ОдноЭтажники.РФ",
      "url": "https://одноэтажники.рф/",
      "logo": "https://одноэтажники.рф/images/logo.png",
      "description": "Архитектура, проектирование и строительство одноэтажных домов. Полный цикл услуг.",
      "telephone": "+7 985 096 10 86",
      "email": "info@odnoetagniki.ru",
      "address": {
        "@type": "PostalAddress",
        "streetAddress": "Боровское шоссе, 2",
        "addressLocality": "Москва",
        "addressCountry": "RU"
      },
      "sameAs": [
        "https://t.me/CodeofEnvironment"
      ]
    }
    </script>

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "LocalBusiness",
      "name": "одноэтажники.РФ",
      "image": "https://одноэтажники.рф/images/hero-house.jpg",
      "description": "Полный цикл услуг: проектирование, строительство, контроль качества одноэтажных домов",
      "priceRange": "от 50000 RUB",
      "areaServed": {
        "@type": "AdministrativeArea",
        "name": "Moscow, Тверская область, Смоленская область"
      }
    }
    </script>

    <!-- FOOTER -->
    <footer class="footer">
        <?php include 'footer.php'; ?>
    </footer>

    <script src="js/main.js"></script>
</body>
</html>