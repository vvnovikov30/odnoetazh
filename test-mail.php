<?php
/**
 * test-mail.php - Тест отправки почты
 * Удалите этот файл после проверки!
 */

$to = 'info@xn--80ahhialrcec8b1i.xn--p1ai';
$subject = 'Тест отправки почты';
$message = 'Если вы видите это письмо, то почта работает!';
$headers = "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: noreply@xn--80ahhialrcec8b1i.xn--p1ai\r\n";

$result = mail($to, $subject, $message, $headers);

if ($result) {
    echo "<h2 style='color: green;'>✅ Письмо отправлено успешно!</h2>";
    echo "<p>Проверьте почту info@xn--80ahhialrcec8b1i.xn--p1ai</p>";
} else {
    echo "<h2 style='color: red;'>❌ Ошибка отправки!</h2>";
    echo "<p>Функция mail() не работает. Свяжитесь с поддержкой хостинга.</p>";
}

echo "<hr>";
echo "<p><strong>PHP версия:</strong> " . phpversion() . "</p>";
echo "<p><strong>Система:</strong> " . php_uname() . "</p>";
?>