<?php

/**
 * send-email.php — единый обработчик форм сайта ОдноЭтажники.РФ
 * Версия 3.1 (24.10.2026) — комплексная антиспам-защита + теневой лог блокировок.
 *
 * Обрабатывает:
 *   1. Заявки на консультацию: name + phone (email / projectType / message — опционально).
 *   2. Комментарии к статьям блога: subject=comment + name + message (телефон не требуется).
 *
 * Антиспам-барьеры. Любое срабатывание — Silent Drop: бот получает HTTP 200
 * {"status":"success"} (плюс legacy-поле success), но письмо не отправляется
 * и запись в журнал заявок не создаётся:
 *   1. Honeypot user_middle_name: поле ОБЯЗАНО присутствовать в POST и быть строго пустым
 *      (скрыто CSS, человек его не видит и не заполняет).
 *   2. Тайминг form_loaded_at: метка ставится при РЕНДЕРЕ страницы; форма должна
 *      заполняться не быстрее MIN_FILL_TIME = 3 секунд. Legacy form_ts поддерживается.
 *   3. Фильтр ссылок: http(s)://, www., домены с TLD, t.me / telegram.me / tg://, @ник.
 *   4. Телефон: ровно 11 цифр с префиксом 7 или 8 (комментарии телефона не требуют).
 *   5. Адаптивная кириллица: сообщение длиннее 15 символов должно содержать не менее
 *      30 % кириллицы среди букв. Бренды (Rehau, Rockwool) в русском тексте проходят,
 *      англоязычная реклама — блокируется.
 *   6. Теневое логирование блокировок: NDJSON вне вебрута —
 *      dirname(__DIR__) . '/data/logs/spam_blocked.log'.
 *
 * Безопасность:
 *   - только POST;
 *   - legacy-ловушки website / user_phone_check (пустые значения) тоже проверяются;
 *   - rate-limit: не более 3 запросов с одного IP за 10 минут (HTTP 429), учёт под flock;
 *     заблокированные антиспамом запросы счётчик не расходуют;
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
const MIN_FILL_TIME     = 3;   // секунд: форма заполнена быстрее — почти наверняка бот

// Honeypot: поле есть в каждой форме, но скрыто CSS. Бот заполняет — человек не видит.
const HONEYPOT_FIELD = 'user_middle_name';

// Адаптивная проверка языка: длинное сообщение должно быть в основном на кириллице.
// Порог 30 % пропускает названия брендов (Rehau, Rockwool) в русском тексте.
const CYRILLIC_MIN_LENGTH = 16;  // проверяем сообщения длиннее 15 символов
const CYRILLIC_MIN_RATIO  = 0.30;

$siteName       = 'ОдноЭтажники.РФ';
$siteDomain     = 'xn--80ahhialrcec8b1i.xn--p1ai';
$recipientEmail = 'vvnovikov30@yandex.ru';       // как в боевом коде, не меняем
$noreplyEmail   = 'noreply@' . $siteDomain;      // корректный From для Reg.ru
$sitePhone      = '+7 985 096 10 86';

// Данные — строго за пределами вебрута: на хостинге это /home/<user>/data/requests
$dataDir = dirname(__DIR__) . '/data/requests';

// Теневой лог спама — тоже вне public_html, рядом с журналом заявок.
// На хостинге: /home/<user>/data/logs/spam_blocked.log
$spamLogDir  = dirname(__DIR__) . '/data/logs';
$spamLogFile = $spamLogDir . '/spam_blocked.log';

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

// ===== АНТИСПАМ-ЭВРИСТИКИ =====

/**
 * Компактное значение для теневого лога: одна строка, ограничение по символам.
 */
function log_value(string $value, int $maxLength = 300): string
{
    return limit_chars(clean_line($value, $maxLength), $maxLength);
}

/**
 * Теневое логирование заблокированного запроса: NDJSON-строка в data/logs/spam_blocked.log.
 *
 * Файл лежит ВНЕ вебрута и дополнительно закрыт .htaccess (ensure_data_dir).
 * Ошибки записи лога не должны влиять на ответ клиенту и на приём заявок.
 *
 * @param array<string, mixed> $context
 */
function log_spam_block(string $logFile, string $reason, array $context): void
{
    if (!ensure_data_dir(dirname($logFile))) {
        return;
    }

    // Служебные поля идут первыми: контекст не может их перезаписать ($context + $row).
    $row = [
        'timestamp'  => date('Y-m-d H:i:s'),
        'reason'     => $reason,
        'ip'         => get_client_ip(),
        'user_agent' => log_value((string)($_SERVER['HTTP_USER_AGENT'] ?? ''), 255),
        'referer'    => log_value((string)($_SERVER['HTTP_REFERER'] ?? ''), 255),
    ] + $context;

    append_ndjson($logFile, $row);
}

/**
 * Silent Drop: бот получает правдоподобный «успех», но письмо не уходит
 * и запись в requests.ndjson не создаётся. Причина — только в теневом логе.
 *
 * status=success — контракт ТЗ, success=true — совместимость с main.js
 * (он проверяет result.success и показывает пользователю сообщение).
 *
 * @param array<string, mixed> $context
 */
function silent_drop(string $logFile, string $reason, array $context = []): never
{
    log_spam_block($logFile, $reason, $context);
    json_response([
        'status'  => 'success',
        'success' => true,
        'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.',
    ]);
}

/**
 * Контекст запроса для теневого лога. Поля уже очищены clean_line()/clean_text().
 *
 * @return array<string, mixed>
 */
function spam_context(string $name, string $phone, string $email, string $message): array
{
    return [
        'name'    => $name,
        'phone'   => $phone,
        'email'   => $email,
        'message' => limit_chars($message, 300),
    ];
}

/**
 * Признак рекламной ссылки в тексте: URL, домен с TLD, Telegram, @ник.
 *
 * Домены ищутся только по известным зонам, поэтому «СП 22.13330», «90.8 м²»
 * и «т.д.» ложных срабатываний не дают.
 */
function contains_link(string $text): bool
{
    if ($text === '') {
        return false;
    }

    $patterns = [
        '~https?://~iu',                                                // полный URL
        '~www\.~iu',                                                    // www. без схемы
        '~tg://~iu',                                                    // Telegram deep link
        '~(?:t\.me|telegram\.me|wa\.me|whatsapp\.com|vk\.com|instagram\.com)~iu',
        // домен: spam-shop.com, example.ru, сайт.рф
        '~(?<![a-zа-яё0-9@._-])(?:[a-zа-яё0-9](?:[a-zа-яё0-9-]{0,61}[a-zа-яё0-9])?\.)+'
        . '(?:ru|com|net|org|info|biz|xyz|online|site|shop|store|pro|su|io|me|tv|club|top|app|dev|'
        . 'tech|space|website|by|kz|ua|рф)(?![a-zа-яё0-9-])~iu',
        // @username вне e-mail: перед @ не должно быть символов локальной части адреса
        '~(?<![a-z0-9._%+-])@[a-z][a-z0-9_]{4,31}~iu',
    ];

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text) === 1) {
            return true;
        }
    }

    return false;
}

/**
 * Доля кириллицы среди букв текста (0.0–1.0). Цифры и пунктуация не учитываются,
 * поэтому технические сообщения («СП 22.13330 и ГОСТ 31996») остаются «русскими».
 */
function cyrillic_ratio(string $text): float
{
    $letters = preg_match_all('/\p{L}/u', $text);
    if ($letters === false || $letters < 1) {
        return 0.0;
    }

    $cyrillic = preg_match_all('/\p{Cyrillic}/u', $text);
    if ($cyrillic === false) {
        return 0.0;
    }

    return $cyrillic / $letters;
}

/**
 * Сколько секунд прошло с момента рендера формы.
 *
 * Актуальное поле — form_loaded_at, legacy form_ts поддерживается для совместимости
 * со старыми страницами. null означает, что метки нет или она испорчена: для бота
 * это блокировка, потому что честная форма всегда присылает timestamp.
 */
function form_elapsed_seconds(): ?int
{
    $raw = trim(post_string('form_loaded_at'));
    if ($raw === '') {
        $raw = trim(post_string('form_ts'));
    }

    if ($raw === '' || preg_match('/^\d{1,19}$/', $raw) !== 1) {
        return null;
    }

    $stamp = (float)$raw;
    if ($stamp > 1.0e12) {
        $stamp /= 1000.0; // метка в миллисекундах (Date.now())
    }

    return time() - (int)$stamp;
}

/**
 * Только цифры номера: «+7 (999) 123-45-67» → 79991234567.
 */
function normalize_phone_digits(string $value): string
{
    return preg_replace('/\D+/', '', $value) ?? '';
}

/**
 * Российский номер: ровно 11 цифр, первая — 7 или 8.
 */
function is_valid_phone(string $digits): bool
{
    return preg_match('/^[78]\d{10}$/D', $digits) === 1;
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

// ===== 2. ВХОДНЫЕ ДАННЫЕ =====
$name        = clean_line(post_string('name'), 100);
$phoneRaw    = clean_line(post_string('phone'), 32);
$email       = clean_line(post_string('email'), 254);
$projectType = clean_line(post_string('projectType'), 120);
$message     = clean_text(post_string('message'), 5000);
$subjectHint = strtolower(clean_line(post_string('subject'), 32));

$phoneDigits = normalize_phone_digits($phoneRaw);
$clientIp    = get_client_ip();
$spamContext = spam_context($name, $phoneRaw, $email, $message);

// ===== 3. HONEYPOT =====
// user_middle_name есть в каждой форме, но скрыт CSS (position:absolute; left:-9999px).
// Человек поле не видит и не заполняет, бот заполняет «по аналогии с именем».
// Контракт: поле ОБЯЗАНО присутствовать в POST и быть строго пустым.
$honeypotValue = $_POST[HONEYPOT_FIELD] ?? null;
if (!array_key_exists(HONEYPOT_FIELD, $_POST)
    || !is_string($honeypotValue)
    || trim($honeypotValue) !== ''
) {
    silent_drop($spamLogFile, 'honeypot', $spamContext + [
        'honeypot_value' => is_string($honeypotValue) ? log_value($honeypotValue, 100) : '',
    ]);
}

// Legacy-ловушки прошлых версий: если бот их заполнил — блокируем так же молча.
if (trim(post_string('website')) !== '' || trim(post_string('user_phone_check')) !== '') {
    silent_drop($spamLogFile, 'honeypot_legacy', $spamContext);
}

// ===== 4. ВРЕМЯ ЗАПОЛНЕНИЯ =====
// form_loaded_at ставится при РЕНДЕРЕ страницы, а не в момент POST.
// Мгновенный POST (< MIN_FILL_TIME секунд) — бот; отсутствие метки — тоже.
$fillSeconds = form_elapsed_seconds();
if ($fillSeconds === null || $fillSeconds < MIN_FILL_TIME) {
    silent_drop($spamLogFile, 'timing', $spamContext + [
        'form_loaded_at' => log_value(post_string('form_loaded_at'), 32),
        'elapsed'        => $fillSeconds,
    ]);
}

// ===== 5. ФИЛЬТР ССЫЛОК =====
// Спам почти всегда содержит URL, домен или телеграм-контакт.
if (contains_link($name) || contains_link($message) || contains_link($projectType)) {
    silent_drop($spamLogFile, 'links', $spamContext);
}

// ===== 6. ТЕЛЕФОН =====
// Комментарий: явный subject=comment, либо нет телефона, но есть сообщение.
$isComment = ($subjectHint === 'comment') || ($phoneDigits === '' && $message !== '');

// Телефон заявки: ровно 11 цифр с префиксом 7 или 8.
// Номер неверного формата — типичный признак бота, поэтому Silent Drop, а не 400.
if (!$isComment && $phoneDigits !== '' && !is_valid_phone($phoneDigits)) {
    silent_drop($spamLogFile, 'phone_invalid', $spamContext + [
        'phone_digits' => log_value($phoneDigits, 20),
    ]);
}

// ===== 7. АДАПТИВНАЯ КИРИЛЛИЦА =====
// Сообщения короче 16 символов не проверяем: «Rehau», «Смета», «Ипотека».
// В длинном тексте кириллицы должно быть не менее 30 % среди букв: названия
// брендов (Rehau, Rockwool, Knauf) в русском тексте порог проходят,
// англоязычная реклама — нет.
$messageChars  = preg_match_all('/./us', $message) ?: 0;
$cyrillicShare = cyrillic_ratio($message);
if ($messageChars >= CYRILLIC_MIN_LENGTH && $cyrillicShare < CYRILLIC_MIN_RATIO) {
    silent_drop($spamLogFile, 'language', $spamContext + [
        'cyrillic_ratio' => round($cyrillicShare, 3),
        'message_length' => $messageChars,
    ]);
}

// ===== 8. ОБЯЗАТЕЛЬНЫЕ ПОЛЯ =====
if ($name === '') {
    json_response(['success' => false, 'message' => 'Пожалуйста, укажите ваше имя'], 400);
}

if ($isComment) {
    if ($message === '') {
        json_response(['success' => false, 'message' => 'Пожалуйста, напишите комментарий'], 400);
    }
} else {
    // Пустой телефон — не заполнено обязательное поле (боты сюда не доходят:
    // их отсекают honeypot и тайминг, а неверный формат номера — блок выше).
    if ($phoneDigits === '') {
        json_response(['success' => false, 'message' => 'Укажите телефон для заявки'], 400);
    }
}

if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
    json_response(['success' => false, 'message' => 'Некорректный email адрес'], 400);
}

// ===== 9. RATE-LIMIT =====
// Считаются только запросы, прошедшие антиспам: бот не может «выжечь» лимит
// реальному клиенту, а сам лимит остаётся защитой от залпа.
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

// ===== 10. КОММЕНТАРИЙ: запись без письма и без телефона =====
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

// ===== 11. ЗАЯВКА: атомарная запись + письмо владельцу =====
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
