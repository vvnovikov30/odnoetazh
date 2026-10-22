<?php

/**
 * send-email.php — единый обработчик форм сайта ОдноЭтажники.РФ
 * Версия 3.0 (22.10.2026)
 *
 * Обрабатывает:
 *   1. Заявки на консультацию: name + phone (email / projectType / message — опционально).
 *   2. Комментарии к статьям блога: subject=comment + name + message (телефон не требуется).
 *
 * Безопасность:
 *   - только POST;
 *   - honeypot-поле website (плюс устаревшее user_phone_check): ответ «успех» без записи и письма;
 *   - form_ts: форма должна быть заполнена не быстрее 2 секунд;
 *   - rate-limit: не более 3 запросов с одного IP за 10 минут (HTTP 429), учёт под flock;
 *   - IP клиента берётся из заголовков прокси только при доверенном REMOTE_ADDR
 *     (loopback / private / Cloudflare), иначе — REMOTE_ADDR (защита от подделки XFF);
 *   - ПДн пишутся только ВНЕ public_html — dirname(__DIR__) . '/data/requests',
 *     формат NDJSON, append-only + LOCK_EX (история не теряется при параллельных запросах);
 *   - никаких Cc и автоответов клиенту: домен не превращается в спам-релей;
 *   - display_errors выключен, наружу уходит только JSON.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Сайт работает по Москве: время в письмах и NDJSON не должно зависеть от TZ хостинга
date_default_timezone_set('Europe/Moscow');

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// ===== КОНФИГУРАЦИЯ =====
const RATE_LIMIT_WINDOW = 600; // секунд (10 минут)
const RATE_LIMIT_MAX    = 3;   // запросов с одного IP за окно
const MIN_FILL_TIME     = 2;   // секунд: быстрее — почти наверняка бот

$siteName       = 'ОдноЭтажники.РФ';
$siteDomain     = 'xn--80ahhialrcec8b1i.xn--p1ai';
$recipientEmail = 'vvnovikov30@yandex.ru';       // как в боевом коде, не меняем
$noreplyEmail   = 'noreply@' . $siteDomain;      // корректный From для Reg.ru
$sitePhone      = '+7 985 096 10 86';

// Данные — строго за пределами вебрута: на хостинге это /home/<user>/data/requests
$dataDir = dirname(__DIR__) . '/data/requests';

// ===== ОТВЕТЫ =====
/**
 * Отдаёт JSON и завершает обработку.
 *
 * @param array<string, mixed> $payload
 */
function json_response(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/**
 * Фейковый «успех» для ботов: ни записи, ни письма.
 */
function bot_success(): never
{
    json_response([
        'success' => true,
        'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.',
    ]);
}

// ===== ВХОДНЫЕ ДАННЫЕ =====
function post_string(string $key): string
{
    $value = $_POST[$key] ?? '';
    return is_string($value) ? $value : '';
}

function to_utf8(string $value): string
{
    // PCRE с модификатором /u не зависит от mbstring
    if (preg_match('//u', $value) === 1) {
        return $value;
    }
    // Битый UTF-8 (обычно результат ручных запросов) — оставляем только ASCII
    return preg_replace('/[\x80-\xFF]/', '', $value) ?? '';
}

/**
 * Ограничивает строку по количеству СИМВОЛОВ (а не байт), не требуя mbstring.
 */
function limit_chars(string $value, int $maxLength): string
{
    if ($maxLength <= 0) {
        return '';
    }
    if (preg_match('/^.{0,' . $maxLength . '}/us', $value, $matches) === 1) {
        return $matches[0];
    }
    return substr($value, 0, $maxLength);
}

/**
 * Однострочное поле: без переводов строк и управляющих символов.
 */
function clean_line(string $value, int $maxLength): string
{
    $value = to_utf8($value);
    $value = preg_replace('/[\r\n\t]+/', ' ', $value) ?? '';
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value) ?? '';
    return limit_chars(trim($value), $maxLength);
}

/**
 * Многострочное поле: переводы строк сохраняются, управляющие символы — нет.
 */
function clean_text(string $value, int $maxLength): string
{
    $value = to_utf8($value);
    $value = str_replace(["\r\n", "\r"], "\n", $value);
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
    return limit_chars(trim($value), $maxLength);
}

// ===== ОПРЕДЕЛЕНИЕ IP КЛИЕНТА ЗА NGINX / CLOUDFLARE =====
function ip_in_cidr(string $ip, string $cidr): bool
{
    [$network, $bits] = array_pad(explode('/', $cidr, 2), 2, null);

    $ipBin  = @inet_pton($ip);
    $netBin = @inet_pton((string)$network);

    if ($ipBin === false || $netBin === false || strlen($ipBin) !== strlen($netBin)) {
        return false;
    }

    $maxBits = strlen($ipBin) * 8;
    $bits    = $bits === null ? $maxBits : (int)$bits;
    if ($bits < 0 || $bits > $maxBits) {
        return false;
    }

    $wholeBytes = intdiv($bits, 8);
    if ($wholeBytes > 0 && substr($ipBin, 0, $wholeBytes) !== substr($netBin, 0, $wholeBytes)) {
        return false;
    }

    $restBits = $bits % 8;
    if ($restBits === 0) {
        return true;
    }

    $mask = (0xFF << (8 - $restBits)) & 0xFF;
    return (ord($ipBin[$wholeBytes]) & $mask) === (ord($netBin[$wholeBytes]) & $mask);
}

/**
 * Доверенные прокси: только от них принимаем заголовки с реальным IP.
 * Список покрывает Nginx на loopback/private-сети (Reg.ru) и сети Cloudflare.
 */
function is_trusted_proxy(string $ip): bool
{
    static $ranges = [
        // loopback / приватные сети — Nginx на том же сервере
        '127.0.0.0/8', '10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16',
        '::1/128', 'fc00::/7', 'fe80::/10',
        // Cloudflare IPv4
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        // Cloudflare IPv6
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    foreach ($ranges as $cidr) {
        if (ip_in_cidr($ip, $cidr)) {
            return true;
        }
    }
    return false;
}

function normalize_ip(string $ip): string
{
    $ip = strtolower(trim($ip));
    if (strncmp($ip, '::ffff:', 7) === 0) {
        $v4 = substr($ip, 7);
        if (filter_var($v4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            return $v4;
        }
    }
    return $ip;
}

/**
 * Убирает порт из элемента X-Forwarded-For: "1.2.3.4:5678", "[2001:db8::1]:443".
 */
function strip_port(string $entry): string
{
    $entry = trim($entry, " \t\"'");

    if (preg_match('/^\[([0-9a-fA-F:.]+)\](?::\d+)?$/', $entry, $matches) === 1) {
        return $matches[1];
    }
    if (preg_match('/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $entry, $matches) === 1) {
        return $matches[1];
    }
    return $entry;
}

/**
 * Реальный IP клиента.
 *
 * Заголовки прокси принимаются только если сам прокси доверенный: иначе любой
 * клиент подделает X-Forwarded-For и обойдёт rate-limit.
 * Из X-Forwarded-For берётся ПОСЛЕДНИЙ адрес: его дописал наш прокси,
 * а левые элементы контролирует клиент.
 */
function get_client_ip(): string
{
    $remote = normalize_ip((string)($_SERVER['REMOTE_ADDR'] ?? ''));

    if ($remote === '' || filter_var($remote, FILTER_VALIDATE_IP) === false) {
        return '0.0.0.0';
    }

    if (!is_trusted_proxy($remote)) {
        return $remote;
    }

    $publicOnly = FILTER_VALIDATE_IP | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

    // Cloudflare
    $cfIp = strip_port((string)($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cfIp !== '' && filter_var($cfIp, $publicOnly) !== false) {
        return normalize_ip($cfIp);
    }

    // Nginx: X-Real-IP
    $realIp = strip_port((string)($_SERVER['HTTP_X_REAL_IP'] ?? ''));
    if ($realIp !== '' && filter_var($realIp, $publicOnly) !== false) {
        return normalize_ip($realIp);
    }

    // Nginx: X-Forwarded-For (справа налево — от ближайшего прокси к клиенту)
    $forwarded = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
    if ($forwarded !== '') {
        foreach (array_reverse(explode(',', $forwarded)) as $entry) {
            $candidate = strip_port($entry);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_IP) !== false) {
                return normalize_ip($candidate);
            }
        }
    }

    return $remote;
}

// ===== ХРАНИЛИЩЕ (ВНЕ ВЕБРУТА) =====
/**
 * Создаёт каталог данных и защитный .htaccess (на случай, если data окажется в вебруте).
 */
function ensure_data_dir(string $dir): bool
{
    if (!is_dir($dir) && !@mkdir($dir, 0750, true) && !is_dir($dir)) {
        error_log('send-email: cannot create data directory');
        return false;
    }

    $guard = $dir . '/.htaccess';
    if (!is_file($guard)) {
        $rules = "<IfModule mod_authz_core.c>\n    Require all denied\n</IfModule>\n"
               . "<IfModule !mod_authz_core.c>\n    Order deny,allow\n    Deny from all\n</IfModule>\n";
        @file_put_contents($guard, $rules, LOCK_EX);
        @chmod($guard, 0640);
    }

    return is_writable($dir);
}

/**
 * Атомарная запись строки NDJSON: O_APPEND + LOCK_EX, история не перезаписывается.
 *
 * @param array<string, mixed> $row
 */
function append_ndjson(string $file, array $row): bool
{
    $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($line === false) {
        error_log('send-email: json_encode failed');
        return false;
    }

    $bytes = @file_put_contents($file, $line . "\n", FILE_APPEND | LOCK_EX);
    if ($bytes === false) {
        error_log('send-email: cannot append to ' . basename($file));
        return false;
    }

    @chmod($file, 0640);
    return true;
}

/**
 * Rate-limit: не более RATE_LIMIT_MAX запросов с одного IP за RATE_LIMIT_WINDOW секунд.
 * Возвращает true, если лимит превышен.
 */
function rate_limit_exceeded(string $dir, string $ip): bool
{
    $file = $dir . '/rate_' . sha1($ip) . '.json';
    $handle = @fopen($file, 'c+');
    if ($handle === false) {
        // fail-open: недоступность счётчика не должна терять реальные заявки
        error_log('send-email: cannot open rate-limit file');
        return false;
    }

    if (!flock($handle, LOCK_EX)) {
        fclose($handle);
        return false;
    }

    $now  = time();
    $hits = [];
    $raw  = stream_get_contents($handle);
    if (is_string($raw) && $raw !== '') {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $hits = $decoded;
        }
    }

    $hits = array_values(array_filter(
        array_map('intval', $hits),
        static fn(int $timestamp): bool => $timestamp > $now - RATE_LIMIT_WINDOW
    ));

    if (count($hits) >= RATE_LIMIT_MAX) {
        flock($handle, LOCK_UN);
        fclose($handle);
        return true;
    }

    $hits[] = $now;
    rewind($handle);
    ftruncate($handle, 0);
    fwrite($handle, json_encode($hits));
    fflush($handle);
    flock($handle, LOCK_UN);
    fclose($handle);
    @chmod($file, 0640);

    return false;
}

/**
 * Редкая уборка старых файлов счётчика, чтобы каталог не разрастался.
 */
function cleanup_rate_files(string $dir): void
{
    if (random_int(1, 100) !== 1) {
        return;
    }

    $threshold = time() - 2 * RATE_LIMIT_WINDOW;
    foreach (glob($dir . '/rate_*.json') ?: [] as $file) {
        $mtime = @filemtime($file);
        if ($mtime !== false && $mtime < $threshold) {
            @unlink($file);
        }
    }
}

// ===== 1. ТОЛЬКО POST =====
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    json_response(['success' => false, 'message' => 'Только POST запросы'], 405);
}

// ===== 2. HONEYPOT =====
// Поле website скрыто CSS: человек его не видит, бот заполняет.
// user_phone_check — legacy-имя из версии 2.1, оставлено на случай старых ботов.
if (post_string('website') !== '' || post_string('user_phone_check') !== '') {
    bot_success();
}

// ===== 3. ВРЕМЯ ЗАПОЛНЕНИЯ =====
// form_ts ставится при РЕНДЕРЕ страницы, а не в момент POST.
$formTs = (int)post_string('form_ts');
if ($formTs <= 0 || (time() - $formTs) < MIN_FILL_TIME) {
    bot_success();
}

// ===== 4. ДАННЫЕ И ВАЛИДАЦИЯ =====
$name        = clean_line(post_string('name'), 100);
$phoneRaw    = clean_line(post_string('phone'), 32);
$email       = clean_line(post_string('email'), 254);
$projectType = clean_line(post_string('projectType'), 120);
$message     = clean_text(post_string('message'), 5000);
$subjectHint = strtolower(clean_line(post_string('subject'), 32));

$phoneDigits = preg_replace('/\D+/', '', $phoneRaw) ?? '';

// Комментарий: явный subject=comment, либо нет телефона, но есть сообщение.
$isComment = ($subjectHint === 'comment') || ($phoneDigits === '' && $message !== '');

if ($name === '') {
    json_response(['success' => false, 'message' => 'Пожалуйста, укажите ваше имя'], 400);
}

if ($isComment) {
    if ($message === '') {
        json_response(['success' => false, 'message' => 'Пожалуйста, напишите комментарий'], 400);
    }
} else {
    if ($phoneDigits === '') {
        json_response(['success' => false, 'message' => 'Укажите телефон для заявки'], 400);
    }
    if (strlen($phoneDigits) < 10 || strlen($phoneDigits) > 15) {
        json_response(['success' => false, 'message' => 'Некорректный номер телефона'], 400);
    }
}

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    json_response(['success' => false, 'message' => 'Некорректный email адрес'], 400);
}

// ===== 5. IP + RATE-LIMIT =====
$clientIp  = get_client_ip();
$storageOk = ensure_data_dir($dataDir);

if ($storageOk && rate_limit_exceeded($dataDir, $clientIp)) {
    json_response([
        'success' => false,
        'message' => 'Слишком много заявок с вашего адреса. Попробуйте через 10 минут или позвоните нам: ' . $sitePhone,
    ], 429);
}

if ($storageOk) {
    cleanup_rate_files($dataDir);
}

$userAgent = clean_line((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255);
$referer   = clean_line((string)($_SERVER['HTTP_REFERER'] ?? ''), 255);
$createdAt = date('Y-m-d H:i:s');

// ===== 6. КОММЕНТАРИЙ: запись без письма и без телефона =====
if ($isComment) {
    $saved = $storageOk && append_ndjson($dataDir . '/comments.ndjson', [
        'timestamp' => $createdAt,
        'type'      => 'comment',
        'ip'        => $clientIp,
        'name'      => $name,
        'email'     => $email,
        'message'   => $message,
        'user_agent' => $userAgent,
        'referer'   => $referer,
    ]);

    if (!$saved) {
        json_response([
            'success' => false,
            'message' => 'Не удалось сохранить комментарий. Попробуйте позже.',
        ], 500);
    }

    json_response([
        'success' => true,
        'message' => 'Спасибо за комментарий! Он будет опубликован после проверки модератора.',
    ]);
}

// ===== 7. ЗАЯВКА: атомарная запись + письмо владельцу =====
$saved = $storageOk && append_ndjson($dataDir . '/requests.ndjson', [
    'timestamp'    => $createdAt,
    'type'         => 'request',
    'ip'           => $clientIp,
    'name'         => $name,
    'phone'        => $phoneRaw,
    'phone_digits' => $phoneDigits,
    'email'        => $email,
    'projectType'  => $projectType,
    'message'      => $message,
    'user_agent'   => $userAgent,
    'referer'      => $referer,
]);

$esc = static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

$bodyLines = [
    '<!DOCTYPE html>',
    '<html><head><meta charset="UTF-8"></head>',
    '<body style="font-family: Arial, sans-serif; color: #333;">',
    '<h2>Новая заявка на консультацию</h2>',
    '<p><strong>Имя:</strong> ' . $esc($name) . '</p>',
    '<p><strong>Телефон:</strong> ' . $esc($phoneRaw) . '</p>',
];
if ($email !== '') {
    $bodyLines[] = '<p><strong>Email:</strong> ' . $esc($email) . '</p>';
}
if ($projectType !== '') {
    $bodyLines[] = '<p><strong>Тип проекта:</strong> ' . $esc($projectType) . '</p>';
}
if ($message !== '') {
    $bodyLines[] = '<p><strong>Сообщение:</strong><br>' . nl2br($esc($message)) . '</p>';
}
$bodyLines[] = '<hr>';
$bodyLines[] = '<p style="color: #999; font-size: 12px;">IP адрес: ' . $esc($clientIp) . '<br>';
$bodyLines[] = 'Время: ' . $createdAt . '</p>';
$bodyLines[] = '</body></html>';

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: ' . $noreplyEmail,
    'X-Mailer: PHP/' . PHP_VERSION,
];
// Reply-To только для валидного адреса; переводы строк в email уже вырезаны clean_line()
if ($email !== '') {
    $headers[] = 'Reply-To: ' . $email;
}

$subject = '=?UTF-8?B?' . base64_encode('Новая заявка с сайта ' . $siteName) . '?=';
// Ни Cc, ни автоответа клиенту — намеренно (см. отчёт §S4)
$mailSent = @mail($recipientEmail, $subject, implode("\n", $bodyLines), implode("\r\n", $headers));

if (!$mailSent) {
    error_log('send-email: mail() failed for request from ' . $clientIp . ' at ' . $createdAt);
}

// Если не удалось ни записать, ни отправить — честно сообщаем клиенту телефон.
if (!$saved && !$mailSent) {
    json_response([
        'success' => false,
        'message' => 'Не удалось отправить заявку. Позвоните нам: ' . $sitePhone,
    ], 500);
}

// Письмо отправлено и/или заявка записана в NDJSON — для клиента это успех.
json_response([
    'success' => true,
    'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.',
]);
