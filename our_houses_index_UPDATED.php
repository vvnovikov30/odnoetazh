<?php
// our-houses/index.php — главная страница раздела "Наши дома" (ОБНОВЛЕНО С SEO)
include '../header.php';

$page_title = 'Наши дома - Готовые проекты и типовые серии | ОдноЭтажники.РФ';
$page_description = 'Готовые дома, шоурум и типовые проекты. 180 кв.м реализованного примера. 6 типовых проектов от 120 до 250 кв.м.';
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <meta name="description" content="<?php echo $page_description; ?>">
    
    <!-- CANONICAL URL для избежания дублей -->
    <link rel="canonical" href="https://одноэтажники.рф/our-houses/">
    
    <!-- OPEN GRAPH для соцсетей -->
    <meta property="og:url" content="https://одноэтажники.рф/our-houses/">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?php echo $page_title; ?>">
    <meta property="og:description" content="<?php echo $page_description; ?>">
    <meta property="og:image" content="https://одноэтажники.рф/images/shoorum-house.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:locale" content="ru_RU">
    
    <!-- TWITTER CARD -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo $page_title; ?>">
    <meta name="twitter:description" content="<?php echo $page_description; ?>">
    <meta name="twitter:image" content="https://одноэтажники.рф/images/shoorum-house.jpg">
    
    <!-- ДОПОЛНИТЕЛЬНЫЕ МЕТА-ТЕГИ -->
    <meta name="theme-color" content="#208089">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <link rel="icon" href="https://одноэтажники.рф/favicon.ico">
    
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../new-styles.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header-inner">
                <div class="logo">
                    <a href="/">ОдноЭтажники.РФ</a>
                </div>
                <nav class="main-nav">
                    <ul>
                        <li><a href="/#services">Услуги</a></li>
                        <li><a href="/our-houses/">Наши дома</a></li>
                        <li><a href="/portfolio/">Портфолио</a></li>
                        <li><a href="/blog/">Блог</a></li>
                        <li><a href="/about/">О компании</a></li>
                        <li><a href="/contacts/">Контакты</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <section class="hero" style="background: linear-gradient(135deg, #208089 0%, #186270 100%); color: white; padding: 100px 0;">
        <div class="container">
            <h1 style="color: white; margin-bottom: 20px;">Наши дома</h1>
            <p style="font-size: 20px; color: rgba(255,255,255,0.9); margin-bottom: 40px;">Готовые проекты и типовые серии, адаптированные под ваш участок</p>
        </div>
    </section>

    <section style="padding: 60px 0;">
        <div class="container">
            <div class="houses-grid">
                <!-- Шоурум -->
                <div class="house-card">
                    <div class="house-card-image">
                        <img src="/images/shoorum-house.jpg" alt="Дом из шоурума 180 кв.м">
                        <span class="house-badge">Готовый проект</span>
                    </div>
                    <div class="house-card-content">
                        <h2>Шоурум</h2>
                        <p>Готовый дом площадью 180 кв.м. Реализованный пример всех наших архитектурных и инженерных решений.</p>
                        <ul class="house-features">
                            <li>✓ 180 кв.м жилой площади</li>
                            <li>✓ Все решения в одном доме</li>
                            <li>✓ Полная фотогалерея</li>
                            <li>✓ Актуальная цена на 2025 год</li>
                            <li>✓ Возможен осмотр</li>
                        </ul>
                        <div class="house-cta">
                            <a href="shoorum/" class="btn btn-primary btn-lg">Подробнее о шоуруме</a>
                        </div>
                    </div>
                </div>

                <!-- Типовая серия -->
                <div class="house-card">
                    <div class="house-card-image">
                        <img src="/images/typical-series.jpg" alt="Типовая серия проектов от 120 до 250 кв.м">
                        <span class="house-badge">6 вариантов</span>
                    </div>
                    <div class="house-card-content">
                        <h2>Типовая серия</h2>
                        <p>6 готовых проектов от 120 до 250 кв.м. Адаптируются под ваш участок. Расчет стоимости за 30 минут.</p>
                        <ul class="house-features">
                            <li>✓ 6 готовых вариантов</li>
                            <li>✓ От 120 до 250 кв.м</li>
                            <li>✓ Адаптируем под участок</li>
                            <li>✓ Интерактивный калькулятор</li>
                            <li>✓ Быстрое согласование</li>
                        </ul>
                        <div class="house-cta">
                            <a href="typical-series/" class="btn btn-primary btn-lg">Выбрать проект</a>
                        </div>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 60px;">
                <h2 style="margin-bottom: 30px;">Как выбрать готовый проект?</h2>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px;">
                    <div style="padding: 30px; background: #f5f5f0; border-radius: 12px;">
                        <div style="font-size: 32px; margin-bottom: 16px;">📐</div>
                        <h3>1. Выберите размер</h3>
                        <p style="color: #626c7c;">От 120 до 250 кв.м жилой площади</p>
                    </div>
                    <div style="padding: 30px; background: #f5f5f0; border-radius: 12px;">
                        <div style="font-size: 32px; margin-bottom: 16px;">🏡</div>
                        <h3>2. Адаптируем план</h3>
                        <p style="color: #626c7c;">Под форму и ориентацию вашего участка</p>
                    </div>
                    <div style="padding: 30px; background: #f5f5f0; border-radius: 12px;">
                        <div style="font-size: 32px; margin-bottom: 16px;">💰</div>
                        <h3>3. Получаете смету</h3>
                        <p style="color: #626c7c;">Точная цена в течение 24 часов</p>
                    </div>
                </div>
            </div>

            <div style="text-align: center; margin-top: 60px;">
                <a href="/contacts/#form" class="btn btn-primary btn-lg">Загрузить план участка</a>
            </div>
        </div>
    </section>

    <!-- STRUCTURED DATA (Schema.org) для страницы -->
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {
          "@type": "ListItem",
          "position": 1,
          "name": "ОдноЭтажники",
          "item": "https://одноэтажники.рф/"
        },
        {
          "@type": "ListItem",
          "position": 2,
          "name": "Наши дома",
          "item": "https://одноэтажники.рф/our-houses/"
        }
      ]
    }
    </script>

    <footer class="footer">
        <?php include '../footer.php'; ?>
    </footer>
</body>
</html>