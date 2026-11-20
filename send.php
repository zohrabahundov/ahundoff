<?php
/**
 * Ahundoff Studio - Обработчик форм
 * Поддерживает: обычные заявки (contact) и заявки партнеров (partner)
 * Защита: honeypot, timestamp, двухуровневый rate limiting, валидация, санитизация
 *
 * Аудит-рефакторинг 21.11.2026:
 *  - getClientIp(): берём ПРАВЫЙ публичный IP из X-Forwarded-For (его дописывает наш nginx),
 *    а не первый — первый контролируется клиентом и позволял обходить rate limit.
 *  - Валидация UTF-8 на входе: невалидные байты больше не роняют sanitize() в TypeError (500).
 *  - Согласие проверяется по значению ('on'), а не по isset(): pd-consent=0 больше не считается согласием.
 *  - Rate limit разделён: «попытки» (30/час, анти-брут) и «отправки» (5/час, только успешные).
 *    Ошибки валидации больше не «сжигают» лимит реальных отправок.
 *  - Устаревшая форма (>24ч) возвращает честную ошибку form_expired (фронтенд обновляет время и повторяет),
 *    а не фейковый успех с тихой потерей лида.
 *  - Телефон: строгий формат ^\+?\d{10,15}$ после удаления пробелов/скобок/дефисов.
 *  - Минимизация ПДн: spam.log пишет только длины полей, error_log — без имени/телефона.
 *  - Ротация leads.log (>5 МБ) и ограничение числа IP в rate_limit.json.
 */

// ==========================================================================
// 1. КОНФИГУРАЦИЯ
// ==========================================================================

define('RECIPIENT_EMAIL', 'info@ahundoff.ru');
define('SENDER_EMAIL', 'noreply@ahundoff.ru');
define('SENDER_NAME', 'Ahundoff Studio');

define('LOG_DIR', __DIR__ . '/logs');
define('LOG_FILE', LOG_DIR . '/leads.log');
define('RATE_LIMIT_FILE', LOG_DIR . '/rate_limit.json');
define('SPAM_LOG_FILE', LOG_DIR . '/spam.log');

// Двухуровневый rate limiting
define('RATE_LIMIT_SENDS', 5);      // успешных заявок с одного IP
define('RATE_LIMIT_ATTEMPTS', 30);  // всего попыток (включая ошибки валидации) с одного IP
define('RATE_LIMIT_PERIOD', 3600);  // за 1 час
define('RATE_LIMIT_MAX_IPS', 5000); // потолок числа IP в файле (защита от раздувания)

define('MIN_FORM_TIME', 3);         // минимальное время заполнения, сек
define('MAX_FORM_AGE', 86400);      // форма старше 24ч требует повторной отправки
define('LOG_ROTATE_SIZE', 5 * 1024 * 1024); // 5 МБ

// ==========================================================================
// 2. ЗАГОЛОВКИ, ЛОКАЛИЗАЦИЯ, БАЗОВЫЕ ПРОВЕРКИ
// ==========================================================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store');

$lang = (($_POST['lang'] ?? 'ru') === 'en') ? 'en' : 'ru';
$MSG = [
    'ru' => [
        'method' => 'Метод не разрешен',
        'forbidden' => 'Доступ запрещен',
        'rate' => 'Слишком много заявок. Попробуйте через час.',
        'rate_attempts' => 'Слишком много попыток. Попробуйте через час.',
        'encoding' => 'Некорректная кодировка данных. Пожалуйста, заполните форму заново.',
        'name_required' => 'Имя обязательно для заполнения',
        'name_short' => 'Имя слишком короткое',
        'name_long' => 'Имя слишком длинное',
        'phone_required' => 'Телефон или Telegram обязателен',
        'phone_long' => 'Слишком длинный контакт (максимум 40 символов)',
        'phone_invalid' => 'Некорректный контакт: укажите телефон или Telegram (@username)',
        'consent' => 'Необходимо согласие на обработку персональных данных',
        'company' => 'Укажите компанию или сферу деятельности',
        'message_long' => 'Сообщение слишком длинное',
        'about_long' => 'Описание слишком длинное',
        'form_expired' => 'Форма устарела. Пожалуйста, отправьте заявку ещё раз.',
        'mail_fail' => 'Ошибка отправки. Пожалуйста, напишите нам напрямую: info@ahundoff.ru или в Telegram.',
        'ok' => 'Заявка успешно отправлена',
        'thanks' => 'Спасибо!'
    ],
    'en' => [
        'method' => 'Method not allowed',
        'forbidden' => 'Access denied',
        'rate' => 'Too many requests. Please try again in an hour.',
        'rate_attempts' => 'Too many attempts. Please try again in an hour.',
        'encoding' => 'Invalid data encoding. Please fill out the form again.',
        'name_required' => 'Name is required',
        'name_short' => 'Name is too short',
        'name_long' => 'Name is too long',
        'phone_required' => 'Phone or Telegram is required',
        'phone_long' => 'Contact is too long (max 40 characters)',
        'phone_invalid' => 'Invalid contact: enter a phone number or Telegram (@username)',
        'consent' => 'Consent to personal data processing is required',
        'company' => 'Please specify your company or field of activity',
        'message_long' => 'Message is too long',
        'about_long' => 'Description is too long',
        'form_expired' => 'The form has expired. Please submit your request again.',
        'mail_fail' => 'Sending failed. Please contact us directly: info@ahundoff.ru or Telegram.',
        'ok' => 'Request sent successfully',
        'thanks' => 'Thank you!'
    ]
];

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => $MSG[$lang]['method']], 405);
}

// Проверка AJAX-запроса (кастомный заголовок требует CORS preflight -> защита от CSRF)
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    respond(['success' => false, 'error' => $MSG[$lang]['forbidden']], 403);
}

// Проверка Origin/Referer (запрос должен идти с нашего домена)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedHosts = ['ahundoff.ru', 'www.ahundoff.ru'];
$originOk = false;
if ($origin !== '') {
    $originOk = in_array(parse_url($origin, PHP_URL_HOST), $allowedHosts, true);
} elseif ($referer !== '') {
    $originOk = in_array(parse_url($referer, PHP_URL_HOST), $allowedHosts, true);
}
if (!$originOk) {
    respond(['success' => false, 'error' => $MSG[$lang]['forbidden']], 403);
}

// ==========================================================================
// 3. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ==========================================================================

function ensureLogDir(): void {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0750, true);
    }
}

function getClientIp(): string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    // Если REMOTE_ADDR — внутренний адрес прокси хостинга, берём ПРАВЫЙ публичный IP
    // из X-Forwarded-For: наш nginx дописывает реальный IP клиента в конец цепочки
    // (proxy_add_x_forwarded_for). Левые записи контролируются клиентом и могут быть подделаны.
    if (filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        $candidates = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));
        for ($i = count($candidates) - 1; $i >= 0; $i--) {
            if (filter_var($candidates[$i], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $candidates[$i];
            }
        }
    }
    return $remote;
}

function sanitize($value): string {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    $value = strip_tags($value);
    // /u-модификатор возвращает null на невалидном UTF-8 — страхуемся оператором ??.
    // Невалидный UTF-8 отсекается на входе (см. ниже), здесь — defence in depth.
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? '';
}

function logSpamAttempt(string $reason, array $data): void {
    ensureLogDir();
    if (is_file(SPAM_LOG_FILE) && filesize(SPAM_LOG_FILE) > LOG_ROTATE_SIZE) {
        @rename(SPAM_LOG_FILE, SPAM_LOG_FILE . '.1'); // ротация вместо тихого отказа
    }
    // Минимизация ПДн: значения персональных полей не пишем, только их длины.
    $safe = [];
    foreach ($data as $k => $v) {
        if (!is_string($v)) { continue; }
        if (in_array($k, ['name', 'phone', 'message', 'company', 'about'], true)) {
            $safe[$k . '_len'] = mb_strlen($v);
        } else {
            $safe[$k] = mb_substr($v, 0, 100);
        }
    }
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reason' => $reason,
        'ip' => getClientIp(),
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
        'data' => $safe
    ];
    file_put_contents(SPAM_LOG_FILE, json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

function logLead(array $data, string $formType): void {
    ensureLogDir();
    // Ротация при превышении размера (храним одно предыдущее поколение).
    if (is_file(LOG_FILE) && filesize(LOG_FILE) > LOG_ROTATE_SIZE) {
        @rename(LOG_FILE, LOG_FILE . '.1');
    }
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $formType,
        'name' => $data['name'],
        'phone' => $data['phone'],
        'message' => $data['message'] ?? '',
        'company' => $data['company'] ?? '',
        'about' => $data['about'] ?? '',
        'consent' => '1', // логируем только заявки с подтверждённым согласием
        'policy_version' => '2026-11-21',
        'ip' => getClientIp(),
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200)
    ];
    file_put_contents(LOG_FILE, json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Атомарная работа с бакетом rate limit.
 * $record = true  — проверить и сразу записать попытку (для бакета attempts).
 * $record = false — только проверить (для бакета sends; запись после успешной отправки).
 */
function rateLimitBucket(string $bucket, int $max, bool $record): bool {
    ensureLogDir();
    $ip = getClientIp();
    $now = time();

    $fp = fopen(RATE_LIMIT_FILE, 'c+');
    if (!$fp) {
        error_log('Ahundoff: rate limit file unavailable, failing open');
        return true;
    }
    flock($fp, LOCK_EX);

    $content = stream_get_contents($fp);
    $data = $content ? json_decode($content, true) : [];
    $data = is_array($data) ? $data : [];

    // Чистим протухшие записи всех IP и ограничиваем размер файла.
    foreach ($data as $k => $buckets) {
        if (!is_array($buckets)) { unset($data[$k]); continue; }
        foreach ($buckets as $b => $timestamps) {
            $data[$k][$b] = array_values(array_filter((array)$timestamps, fn($ts) => ($now - $ts) < RATE_LIMIT_PERIOD));
            if (empty($data[$k][$b])) unset($data[$k][$b]);
        }
        if (empty($data[$k])) unset($data[$k]);
    }
    if (count($data) > RATE_LIMIT_MAX_IPS) {
        // Удаляем IP с самыми старыми последними активностями
        $lastActivity = [];
        foreach ($data as $k => $buckets) {
            $max = 0;
            foreach ($buckets as $timestamps) {
                foreach ((array)$timestamps as $ts) { if ($ts > $max) $max = $ts; }
            }
            $lastActivity[$k] = $max;
        }
        asort($lastActivity);
        $keep = array_slice(array_keys($lastActivity), -RATE_LIMIT_MAX_IPS, null, true);
        $data = array_intersect_key($data, array_flip($keep));
    }

    $requests = $data[$ip][$bucket] ?? [];
    if (count($requests) >= $max) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    if ($record) {
        $requests[] = $now;
        $data[$ip][$bucket] = array_values($requests);
        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, json_encode($data));
        fflush($fp);
    }

    flock($fp, LOCK_UN);
    fclose($fp);
    return true;
}

function recordSend(): void {
    ensureLogDir();
    $ip = getClientIp();
    $now = time();
    $fp = fopen(RATE_LIMIT_FILE, 'c+');
    if (!$fp) return;
    flock($fp, LOCK_EX);
    $content = stream_get_contents($fp);
    $data = $content ? json_decode($content, true) : [];
    $data = is_array($data) ? $data : [];
    $data[$ip]['sends'][] = $now;
    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);
}

// ==========================================================================
// 4. ПОЛУЧЕНИЕ ДАННЫХ И АНТИСПАМ (до rate limiting, чтобы боты не «жгли» лимит)
// ==========================================================================

$formType = $_POST['form_type'] ?? 'contact';
if (!in_array($formType, ['contact', 'partner'], true)) {
    $formType = 'contact';
}

// Honeypot
if (!empty($_POST['website'])) {
    logSpamAttempt('Honeypot triggered', $_POST);
    respond(['success' => true, 'message' => $MSG[$lang]['thanks']]);
}

// Timestamp
$formTimeRaw = $_POST['form_time'] ?? null;
$formTime = (int)($formTimeRaw ?? 0);
$currentTime = time();

if ($formTimeRaw === null || $formTimeRaw === '' || $formTime <= 0) {
    logSpamAttempt('Missing or invalid form_time', $_POST);
    respond(['success' => true, 'message' => $MSG[$lang]['thanks']]);
}
if (($currentTime - $formTime) < MIN_FORM_TIME) {
    // Сюда же попадают будущие timestamp (отрицательная разница) — отдельная проверка не нужна.
    logSpamAttempt('Form filled too fast or future timestamp', $_POST);
    respond(['success' => true, 'message' => $MSG[$lang]['thanks']]);
}
if (($currentTime - $formTime) > MAX_FORM_AGE) {
    // Честная ошибка: фронтенд обновит form_time и повторит отправку (лид не теряется).
    logSpamAttempt('Form time too old', $_POST);
    respond(['success' => false, 'code' => 'form_expired', 'error' => $MSG[$lang]['form_expired']], 400);
}

// ==========================================================================
// 5. ВАЛИДАЦИЯ КОДИРОВКИ (защита от TypeError/500 на невалидном UTF-8)
// ==========================================================================

foreach (['name', 'phone', 'message', 'company', 'about'] as $field) {
    if (isset($_POST[$field]) && is_string($_POST[$field]) && !mb_check_encoding($_POST[$field], 'UTF-8')) {
        respond(['success' => false, 'error' => $MSG[$lang]['encoding']], 400);
    }
}

// ==========================================================================
// 6. RATE LIMITING: бакет попыток (анти-брут, включает ошибки валидации)
// ==========================================================================

if (!rateLimitBucket('attempts', RATE_LIMIT_ATTEMPTS, true)) {
    respond(['success' => false, 'error' => $MSG[$lang]['rate_attempts']], 429);
}

// ==========================================================================
// 7. ИЗВЛЕЧЕНИЕ И ВАЛИДАЦИЯ ДАННЫХ
// ==========================================================================

$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$message = sanitize($_POST['message'] ?? '');
// Согласие — строго по значению чекбокса, а не по факту наличия поля.
$consent = isset($_POST['pd-consent']) && $_POST['pd-consent'] === 'on';

$phone = preg_replace('/[\r\n]/', '', $phone);
$name = preg_replace('/[\r\n]/', '', $name);

$company = '';
$about = '';
if ($formType === 'partner') {
    $company = sanitize($_POST['company'] ?? '');
    $about = sanitize($_POST['about'] ?? '');
}

$errors = [];

if ($name === '') {
    $errors[] = $MSG[$lang]['name_required'];
} elseif (mb_strlen($name) < 2) {
    $errors[] = $MSG[$lang]['name_short'];
} elseif (mb_strlen($name) > 100) {
    $errors[] = $MSG[$lang]['name_long'];
}

if ($phone === '') {
    $errors[] = $MSG[$lang]['phone_required'];
} elseif (mb_strlen($phone) > 40) {
    $errors[] = $MSG[$lang]['phone_long'];
} else {
    // Telegram: @username (5–32 символа). Телефон: 10–15 цифр, опциональный ведущий +.
    $isTelegramUsername = preg_match('/^@[A-Za-z][A-Za-z0-9_]{4,31}$/', $phone);
    $phoneNormalized = preg_replace('/[\s\-()]/', '', $phone);
    $isPhone = preg_match('/^\+?\d{10,15}$/', $phoneNormalized);
    if (!$isTelegramUsername && !$isPhone) {
        $errors[] = $MSG[$lang]['phone_invalid'];
    }
}

if (!$consent) {
    $errors[] = $MSG[$lang]['consent'];
}
if ($formType === 'partner' && $company === '') {
    $errors[] = $MSG[$lang]['company'];
}
if ($message !== '' && mb_strlen($message) > 2000) {
    $errors[] = $MSG[$lang]['message_long'];
}
if ($about !== '' && mb_strlen($about) > 1000) {
    $errors[] = $MSG[$lang]['about_long'];
}

if (!empty($errors)) {
    respond(['success' => false, 'error' => implode('. ', $errors)]);
}

// ==========================================================================
// 8. RATE LIMITING: бакет успешных отправок (ошибки валидации его не расходуют)
// ==========================================================================

if (!rateLimitBucket('sends', RATE_LIMIT_SENDS, false)) {
    respond(['success' => false, 'error' => $MSG[$lang]['rate']], 429);
}

// ==========================================================================
// 9. ФОРМИРОВАНИЕ ПИСЬМА
// ==========================================================================

if ($formType === 'partner') {
    $subject = '🤝 Новая заявка партнера: ' . $name;
    $body = "═══════════════════════════════════════\n";
    $body .= "НОВАЯ ЗАЯВКА ПАРТНЕРА\n";
    $body .= "═══════════════════════════════════════\n\n";
    $body .= "👤 КОНТАКТНАЯ ИНФОРМАЦИЯ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= "Имя: $name\n";
    $body .= "Компания/сфера: $company\n";
    $body .= "Контакт: $phone\n";
    $body .= "Дата: " . date('d.m.Y H:i:s') . "\n";
    $body .= "IP: " . getClientIp() . "\n\n";
    $body .= "📝 О СЕБЕ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= ($about !== '' ? $about : 'Не указано') . "\n\n";
    $body .= "═══════════════════════════════════════\n";
    $body .= "Действия:\n1. Связаться в течение 24 часов\n2. Обсудить условия партнерства\n3. Отправить агентский договор\n";
    $body .= "═══════════════════════════════════════\n";
} else {
    $subject = '🚀 Новая заявка с сайта: ' . $name;
    $body = "═══════════════════════════════════════\n";
    $body .= "НОВАЯ ЗАЯВКА С САЙТА\n";
    $body .= "═══════════════════════════════════════\n\n";
    $body .= "👤 КОНТАКТНАЯ ИНФОРМАЦИЯ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= "Имя: $name\n";
    $body .= "Контакт: $phone\n";
    $body .= "Дата: " . date('d.m.Y H:i:s') . "\n";
    $body .= "IP: " . getClientIp() . "\n\n";
    $body .= "📝 СООБЩЕНИЕ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= ($message !== '' ? $message : 'Не указано') . "\n\n";
    $body .= "═══════════════════════════════════════\n";
    $body .= "Действия:\n1. Связаться в течение 2 часов\n2. Провести бизнес-диагностику\n3. Подготовить коммерческое предложение\n";
    $body .= "═══════════════════════════════════════\n";
}

// ==========================================================================
// 10. ОТПРАВКА И ЛОГИРОВАНИЕ
// ==========================================================================

// Сначала логируем заявку — она сохранится даже при сбое почты.
logLead([
    'name' => $name,
    'phone' => $phone,
    'message' => $message,
    'company' => $company,
    'about' => $about
], $formType);

$headers = [
    'From' => SENDER_NAME . ' <' . SENDER_EMAIL . '>',
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Priority' => '1',
];
$headerString = '';
foreach ($headers as $key => $value) {
    $headerString .= "$key: $value\r\n";
}

$additionalParams = '-f ' . SENDER_EMAIL;
$mailSent = mail(RECIPIENT_EMAIL, mb_encode_mimeheader($subject, 'UTF-8', 'B'), $body, $headerString, $additionalParams);

if ($mailSent) {
    recordSend(); // засчитываем только успешную отправку
    respond(['success' => true, 'message' => $MSG[$lang]['ok']]);
} else {
    // Без ПДн в системном логе: только тип формы и IP.
    error_log('Ahundoff Studio: mail() failed, type=' . $formType . ', ip=' . getClientIp());
    respond(['success' => false, 'error' => $MSG[$lang]['mail_fail']], 502);
}
