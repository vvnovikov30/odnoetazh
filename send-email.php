<?php
/**
 * Обработчик форм для сайта ОдноЭтажники.РФ
 * Версия 2.1 - Добавлена антиспам защита (Honeypot + Time-check)
 * 
 * Обрабатывает:
 * 1. Заявки на консультацию (требует: name, phone)
 * 2. Комментарии (требует: name, message, без phone)
 */

// Запускаем сессию для проверки времени отправки
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

// ===== ПРОВЕРКА МЕТОДА ЗАПРОСА =====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Только POST запросы']);
    exit;
}

// ===== АНТИСПАМ БАРЬЕР 1: HONEYPOT (ЛОВУШКА) =====
// Боты автоматически заполняют невидимое поле 'user_phone_check'
if (!empty($_POST['user_phone_check'])) {
    // Имитируем успешную отправку, но ничего не сохраняем и не шлём
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.'
    ]);
    exit;
}

// ===== АНТИСПАМ БАРЬЕР 2: TIME-CHECK (ПРОВЕРКА СКОРОСТИ) =====
// Человек тратит на чтение и ввод минимум 3 секунды. Боты отправляют за миллисекунды.
$min_submission_time = 3; 
if (isset($_SESSION['form_loaded_time'])) {
    $elapsed_time = time() - (int)$_SESSION['form_loaded_time'];
    if ($elapsed_time < $min_submission_time) {
        // Слишком быстро — бот
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.'
        ]);
        exit;
    }
}
// Обновляем метку времени, чтобы нельзя было слать повторные запросы пачками
$_SESSION['form_loaded_time'] = time();

// ===== КОНФИГУРАЦИЯ =====
$recipient_email = 'vvnovikov30@yandex.ru';
$site_name = 'ОдноЭтажники.РФ';
$site_phone = '+7 985 096 10 86';
$site_domain = 'xn--80ahhialrcec8b1i.xn--p1ai';
$noreply_email = 'noreply@' . $site_domain; // Правильный From для Reg.ru

$requests_file = __DIR__ . '/requests.txt';
$json_file = __DIR__ . '/requests.json';
$comments_file = __DIR__ . '/comments.txt';

// ===== ПОЛУЧЕНИЕ И САНИТИЗАЦИЯ ДАННЫХ =====
$name = trim(htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8'));
$phone = trim(htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8'));
$email = trim(htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'));
$project_type = trim(htmlspecialchars($_POST['projectType'] ?? '', ENT_QUOTES, 'UTF-8'));
$message = trim(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));

// ===== ОПРЕДЕЛЕНИЕ ТИПА ЗАПРОСА =====
$is_request = !empty($phone) && !empty($name);  // Заявка на консультацию (с телефоном)
$is_comment = empty($phone) && !empty($message) && !empty($name); // Комментарий (без телефона, с сообщением)

// ===== ВАЛИДАЦИЯ =====

// Проверка на заполненность обязательного поля: Имя
if (empty($name)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Пожалуйста, укажите ваше имя']);
    exit;
}

// Если это заявка - проверяем телефон
if ($is_request) {
    $phone_digits = preg_replace('/\D/', '', $phone);
    
    if (strlen($phone_digits) < 10) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Некорректный номер телефона (минимум 10 цифр)']);
        exit;
    }
}

// Если это не заявка и не комментарий - ошибка
if (!$is_request && !$is_comment) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Укажите телефон для заявки или сообщение']);
    exit;
}

// Проверка email если он указан
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный email адрес']);
    exit;
}

// ===== СОХРАНЕНИЕ КОММЕНТАРИЯ =====
if ($is_comment) {
    $comment_entry = "=== КОММЕНТАРИЙ " . date('Y-m-d H:i:s') . " ===\n";
    $comment_entry .= "Имя: $name\n";
    $comment_entry .= "Комментарий: $message\n";
    $comment_entry .= "Email: " . (!empty($email) ? $email : 'не указан') . "\n";
    $comment_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $comment_entry .= "User-Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
    $comment_entry .= "---\n\n";
    
    @file_put_contents($comments_file, $comment_entry, FILE_APPEND);
    
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо за комментарий! Он будет опубликован после проверки модератора.'
    ]);
    exit;
}

// ===== СОХРАНЕНИЕ ЗАЯВКИ В ФАЙЛЫ И ОТПРАВКА ПОЧТЫ =====
if ($is_request) {
    
    // Подготовка данных для сохранения
    $request_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'name' => $name,
        'phone' => $phone,
        'email' => !empty($email) ? $email : '',
        'projectType' => $project_type,
        'message' => $message
    ];
    
    // Сохранение в текстовый файл (лог)
    $file_entry = "=== ЗАЯВКА " . date('Y-m-d H:i:s') . " ===\n";
    $file_entry .= "Имя: $name\n";
    $file_entry .= "Телефон: $phone\n";
    $file_entry .= "Email: " . (!empty($email) ? $email : 'не указан') . "\n";
    $file_entry .= "Тип проекта: " . (!empty($project_type) ? $project_type : 'не указан') . "\n";
    $file_entry .= "Сообщение: " . (!empty($message) ? $message : 'не указано') . "\n";
    $file_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
    $file_entry .= "User-Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? '', ENT_QUOTES, 'UTF-8') . "\n";
    $file_entry .= "---\n\n";
    
    @file_put_contents($requests_file, $file_entry, FILE_APPEND);
    
    // Сохранение в JSON
    $json_requests = [];
    if (file_exists($json_file)) {
        $content = file_get_contents($json_file);
        $json_requests = json_decode($content, true) ?? [];
    }
    $json_requests[] = $request_data;
    @file_put_contents($json_file, json_encode($json_requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    // ===== ФОРМИРОВАНИЕ ПИСЬМА =====
    $subject = "Новая заявка с сайта $site_name";
    
    // Тело письма для администратора (HTML)
    $admin_body = "<!DOCTYPE html>\n";
    $admin_body .= "<html>\n";
    $admin_body .= "<head><meta charset='UTF-8'></head>\n";
    $admin_body .= "<body style='font-family: Arial, sans-serif; color: #333;'>\n";
    $admin_body .= "<h2>Новая заявка на консультацию</h2>\n";
    $admin_body .= "<p><strong>Имя:</strong> " . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . "</p>\n";
    $admin_body .= "<p><strong>Телефон:</strong> " . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</p>\n";
    
    if (!empty($email)) {
        $admin_body .= "<p><strong>Email:</strong> " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</p>\n";
    }
    
    if (!empty($project_type)) {
        $admin_body .= "<p><strong>Тип проекта:</strong> " . htmlspecialchars($project_type, ENT_QUOTES, 'UTF-8') . "</p>\n";
    }
    
    if (!empty($message)) {
        $admin_body .= "<p><strong>Сообщение:</strong><br>" . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . "</p>\n";
    }
    
    $admin_body .= "<hr>\n";
    $admin_body .= "<p style='color: #999; font-size: 12px;'>\n";
    $admin_body .= "IP адрес: " . htmlspecialchars($_SERVER['REMOTE_ADDR'], ENT_QUOTES, 'UTF-8') . "<br>\n";
    $admin_body .= "Время: " . date('Y-m-d H:i:s') . "\n";
    $admin_body .= "</p>\n";
    $admin_body .= "</body>\n";
    $admin_body .= "</html>\n";
    
    // ===== ЗАГОЛОВКИ ПИСЬМА (критично для Reg.ru!) =====
    $headers = "From: $noreply_email\r\n";
    
    // Если есть email клиента - добавляем Reply-To
    if (!empty($email)) {
        $headers .= "Reply-To: " . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "\r\n";
    }
    
    $headers .= "Return-Path: $noreply_email\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";
    
    // Кодируем тему для кириллицы
    $subject_encoded = "=?UTF-8?B?" . base64_encode($subject) . "?=";
    
    // ===== ОТПРАВКА ПИСЬМА =====
    $mail_sent = @mail($recipient_email, $subject_encoded, $admin_body, $headers);
    
    if ($mail_sent) {
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.'
        ]);
    } else {
        // Логируем ошибку отправки почты
        error_log("Mail send failed for request from " . $_SERVER['REMOTE_ADDR'] . " at " . date('Y-m-d H:i:s'));
        
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время. (Письмо не отправлено, но заявка записана)'
        ]);
    }
    
    exit;
}

// Ошибка по умолчанию
http_response_code(500);
echo json_encode(['success' => false, 'message' => 'Ошибка обработки запроса']);
exit;
