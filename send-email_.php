<?php
/**
 * send-email.php - Отправка писем с контактных форм
 * Сохраните этот файл в корне сайта рядом с index.php
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Только POST запросы']);
    exit;
}

// ===== КОНФИГУРАЦИЯ =====
$recipient_email = 'info@odnoetagniki.ru';  // Куда отправлять письма
$site_name = 'ОдноЭтажники.РФ';
$site_phone = '+7 985 096 10 86';

// ===== ПОЛУЧЕНИЕ ДАННЫХ =====
$name = htmlspecialchars($_POST['name'] ?? '', ENT_QUOTES, 'UTF-8');
$phone = htmlspecialchars($_POST['phone'] ?? '', ENT_QUOTES, 'UTF-8');
$email = htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8');
$project_type = htmlspecialchars($_POST['projectType'] ?? '', ENT_QUOTES, 'UTF-8');
$message = htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8');

// ===== ВАЛИДАЦИЯ =====
if (empty($name) || empty($phone)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Заполните обязательные поля']);
    exit;
}

// Проверка телефона (минимум 10 цифр)
$phone_digits = preg_replace('/\D/', '', $phone);
if (strlen($phone_digits) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный номер телефона']);
    exit;
}

// Проверка email если указан
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный email']);
    exit;
}

// ===== ФОРМИРОВАНИЕ ПИСЬМА =====
$subject = "Новая заявка с сайта $site_name";

$body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; }
        .header { background: #FF9500; color: white; padding: 20px; border-radius: 5px; }
        .content { background: white; padding: 20px; margin-top: 20px; border-radius: 5px; }
        .field { margin: 15px 0; }
        .label { font-weight: bold; color: #FF9500; }
        .value { margin-top: 5px; padding: 10px; background: #f0f0f0; border-radius: 3px; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>$site_name</h2>
            <p>Новая заявка от клиента</p>
        </div>
        
        <div class=\"content\">
            <div class=\"field\">
                <span class=\"label\">Имя:</span>
                <div class=\"value\">$name</div>
            </div>
            
            <div class=\"field\">
                <span class=\"label\">Телефон:</span>
                <div class=\"value\"><a href=\"tel:$phone_digits\">$phone</a></div>
            </div>
";

if (!empty($email)) {
    $body .= "
            <div class=\"field\">
                <span class=\"label\">Email:</span>
                <div class=\"value\"><a href=\"mailto:$email\">$email</a></div>
            </div>
    ";
}

if (!empty($project_type)) {
    $body .= "
            <div class=\"field\">
                <span class=\"label\">Тип проекта:</span>
                <div class=\"value\">$project_type</div>
            </div>
    ";
}

if (!empty($message)) {
    $body .= "
            <div class=\"field\">
                <span class=\"label\">Сообщение:</span>
                <div class=\"value\">$message</div>
            </div>
    ";
}

$body .= "
        </div>
        
        <div class=\"footer\">
            <p>Телефон сайта: $site_phone</p>
            <p>Письмо получено: " . date('d.m.Y H:i:s') . "</p>
            <p>IP адрес клиента: " . $_SERVER['REMOTE_ADDR'] . "</p>
        </div>
    </div>
</body>
</html>
";

// ===== ОТПРАВКА ПИСЬМА =====
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: noreply@одноэтажники.рф\r\n";

if (!empty($email)) {
    $headers .= "Reply-To: $email\r\n";
}

// Попытка отправить письмо
$result = mail($recipient_email, $subject, $body, $headers);

if ($result) {
    // Письмо успешно отправлено
    
    // (опционально) Отправить копию клиенту если указан email
    if (!empty($email)) {
        $user_subject = "Спасибо за заявку - $site_name";
        $user_body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
</head>
<body>
    <p>Здравствуйте, $name!</p>
    <p>Спасибо за вашу заявку. Мы получили ваше сообщение и свяжемся с вами в ближайшее время.</p>
    <p>Телефон нашей компании: <strong>$site_phone</strong></p>
    <p>С уважением,<br>$site_name</p>
</body>
</html>
        ";
        mail($email, $user_subject, $user_body, $headers);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.'
    ]);
} else {
    // Ошибка при отправке
    error_log("Ошибка отправки письма с сайта $site_name от $name ($phone)");
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Ошибка при отправке. Попробуйте позже или позвоните нам.'
    ]);
}
?>