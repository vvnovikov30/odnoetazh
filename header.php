<?php
// header.php - Единый header для всех страниц

// Компания конфиг
$company = [
    'name' => 'ОдноЭтажники.РФ',
    'phone' => '+7 985 096 10 86',
    'email' => 'vvnovikov30@yandex.ru',
    'address' => 'Москва, Боровское ш., 2',
    'telegram' => 'https://t.me/CodeofEnvironment',
    'tagline' => 'Дома для жизни'
];

// Страница по умолчанию если не указана
if (!isset($page_title)) {
    $page_title = $company['name'] . ' - ' . $company['tagline'];
}

// Префикс относительных URL: '' для страниц в корне сайта, '../' для страниц
// в подкаталогах (например /blog/). Задаётся до include header.php.
if (!isset($base_href)) {
    $base_href = '';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Фавиконы -->
    <link rel="icon" type="image/x-icon" href="<?php echo $base_href; ?>favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_href; ?>favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base_href; ?>favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo $base_href; ?>android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?php echo $base_href; ?>android-chrome-512x512.png">
    <link rel="apple-touch-icon" href="<?php echo $base_href; ?>apple-touch-icon.png">
    <link rel="manifest" href="<?php echo $base_href; ?>site.webmanifest">
    
    <!-- link rel="stylesheet" href="style.css" -->
    <link rel="stylesheet" href="<?php echo $base_href; ?>style.css?v=<?php echo filemtime(__DIR__ . '/style.css'); ?>">

    <?php if (!empty($page_description)): ?>
    <meta name="description" content="<?php echo htmlspecialchars($page_description, ENT_QUOTES, 'UTF-8'); ?>">
    <?php endif; ?>

    <title><?php echo $page_title; ?></title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
             <div class="logo">
                    <a href="<?php echo $base_href; ?>index.php" class="logo-link">
                        <img src="<?php echo $base_href; ?>logo2.jpg" alt="<?php echo $company['name']; ?>" class="logo-image"
                             width="62" height="44" loading="eager" decoding="async">
                        <div class="logo-text">
                            <h2 class="logo-title"><?php echo $company['name']; ?></h2>
                            <p class="logo-tagline"><?php echo $company['tagline']; ?></p>
                        </div>
                    </a>
                </div>
                <nav class="nav">
                    <a href="<?php echo $base_href; ?>index.php">Главная</a>
                    <a href="<?php echo $base_href; ?>architecture.php">Архитектура</a>
                    <a href="<?php echo $base_href; ?>construction.php">Строительство</a>
                    <a href="<?php echo $base_href; ?>blog.php">Блог</a>
                    <a href="<?php echo $base_href; ?>contacts.php">Контакты</a>
                </nav>
                <button class="cta-btn">Заказать консультацию</button>
            </div>
        </div>
    </header>

    <main>