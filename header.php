<?php
// header.php - Единый header для всех страниц

// Компания конфиг
$company = [
    'name' => 'ОдноЭтажники.РФ',
    'phone' => '+7 985 096 10 86',
    'email' => 'info@odnoetagniki.ru',
    'address' => 'Москва, Боровское ш., 2',
    'telegram' => 'https://t.me/CodeofEnvironment',
    'tagline' => 'Архитектура, проектирование, строительство'
];

// Страница по умолчанию если не указана
if (!isset($page_title)) {
    $page_title = $company['name'] . ' - ' . $company['tagline'];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- Фавиконы -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="192x192" href="android-chrome-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="android-chrome-512x512.png">
    <link rel="apple-touch-icon" href="apple-touch-icon.png">
    <link rel="manifest" href="site.webmanifest">
    
    <link rel="stylesheet" href="style.css">
    <title><?php echo $page_title; ?></title>
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="header-content">
                <div class="logo">
                    <a href="index.php" class="logo-link">
                        <img src="logodom.jpg" alt="<?php echo $company['name']; ?>" class="logo-image">
                    </a>
                </div>
                <nav class="nav">
                    <a href="index.php">Главная</a>
                    <a href="architecture.php">Архитектура</a>
                    <a href="construction.php">Строительство</a>
                    <a href="blog.php">Блог</a>
                    <a href="contacts.php">Контакты</a>
                </nav>
                <button class="cta-btn">Заказать консультацию</button>
            </div>
        </div>
    </header>

    <main>