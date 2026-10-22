<?php
/**
 * send-email.php - Отправка писем с контактных форм + сохранение в файл
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Только POST запросы']);
    exit;
}

// ===== КОНФИГУРАЦИЯ =====
$recipient_email = 'info@xn--80ahhialrcec8b1i.xn--p1ai';  // Punycode версия
$site_name = 'ОдноЭтажники.РФ';
$site_phone = '+7 985 096 10 86';
$site_domain = 'xn--80ahhialrcec8b1i.xn--p1ai';  // Punycode версия
$requests_file = __DIR__ . '/requests.txt';
$json_file = __DIR__ . '/requests.json';

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

$phone_digits = preg_replace('/\D/', '', $phone);
if (strlen($phone_digits) < 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный номер телефона']);
    exit;
}

if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Некорректный email']);
    exit;
}

// ===== СОХРАНЕНИЕ В ФАЙЛ =====
$request_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'ip' => $_SERVER['REMOTE_ADDR'],
    'name' => $name,
    'phone' => $phone,
    'email' => $email,
    'projectType' => $project_type,
    'message' => $message
];

$file_entry = "=== ЗАЯВКА " . date('Y-m-d H:i:s') . " ===\n";
$file_entry .= "Имя: $name\n";
$file_entry .= "Телефон: $phone\n";
$file_entry .= "Email: $email\n";
$file_entry .= "Тип проекта: $project_type\n";
$file_entry .= "Сообщение: $message\n";
$file_entry .= "IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
$file_entry .= "---\n\n";

@file_put_contents($requests_file, $file_entry, FILE_APPEND);

$json_requests = [];
if (file_exists($json_file)) {
    $content = file_get_contents($json_file);
    $json_requests = json_decode($content, true) ?? [];
}
$json_requests[] = $request_data;
@file_put_contents($json_file, json_encode($json_requests, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

// ===== ФОРМИРОВАНИЕ ПИСЬМА =====
$subject = "=?UTF-8?B?" . base64_encode("Новая заявка с сайта $site_name") . "?=";

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
                <div class=\"value\">" . nl2br($message) . "</div>
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
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: noreply@$site_domain\r\n";
$headers .= "Reply-To: $recipient_email\r\n";
$headers .= "X-Priority: 3\r\n";
$headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

if (!empty($email)) {
    $headers .= "Cc: $email\r\n";
}

$mail_sent = @mail($recipient_email, $subject, $body, $headers);

// Отправить копию клиенту
if (!empty($email)) {
    $user_subject = "=?UTF-8?B?" . base64_encode("Спасибо за заявку - $site_name") . "?=";
    $user_body = "
<!DOCTYPE html>
<html>
<head>
    <meta charset=\"UTF-8\">
    <style>
        body { font-family: Arial, sans-serif; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #FF9500; color: white; padding: 20px; border-radius: 5px; }
        .content { padding: 20px; }
    </style>
</head>
<body>
    <div class=\"container\">
        <div class=\"header\">
            <h2>Спасибо за заявку!</h2>
        </div>
        <div class=\"content\">
            <p>Здравствуйте, $name!</p>
            <p>Спасибо за вашу заявку. Мы получили ваше сообщение и свяжемся с вами в ближайшее время.</p>
            <p><strong>Телефон нашей компании:</strong> $site_phone</p>
            <p>С уважением,<br>$site_name</p>
        </div>
    </div>
</body>
</html>
    ";
    $user_headers = "MIME-Version: 1.0\r\n";
    $user_headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $user_headers .= "From: noreply@$site_domain\r\n";
    @mail($email, $user_subject, $user_body, $user_headers);
}

// ===== ОТВЕТ КЛИЕНТУ =====
echo json_encode([
    'success' => true,
    'message' => 'Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.'
]);
?>