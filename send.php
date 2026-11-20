<?php
/**
 * Ahundoff Studio - Обработчик форм
 * Поддерживает: обычные заявки (contact) и заявки партнеров (partner)
 * Защита: honeypot, timestamp, атомарный rate limiting, валидация, санитизация
 */

// ==========================================================================
// 1. КОНФИГУРАЦИЯ
// ==========================================================================

// Email для получения заявок
define('RECIPIENT_EMAIL', 'info@ahundoff.ru');
define('SENDER_EMAIL', 'noreply@ahundoff.ru');
define('SENDER_NAME', 'Ahundoff Studio');

// Пути к файлам (теперь внутри директории скрипта для гарантии прав на запись)
define('LOG_DIR', __DIR__ . '/logs');
define('LOG_FILE', LOG_DIR . '/leads.log');
define('RATE_LIMIT_FILE', LOG_DIR . '/rate_limit.json');
define('SPAM_LOG_FILE', LOG_DIR . '/spam.log');

// Настройки rate limiting
define('RATE_LIMIT_MAX', 5); // Максимум заявок
define('RATE_LIMIT_PERIOD', 3600); // За 1 час (в секундах)

// Минимальное время заполнения формы (защита от ботов)
define('MIN_FORM_TIME', 3); // 3 секунды

// ==========================================================================
// 2. ЗАГОЛОВКИ И НАСТРОЙКИ
// ==========================================================================

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

// PATCH 2026-11-21-2: локализация ответов (en.html отправляет lang=en)
$lang = (($_POST['lang'] ?? 'ru') === 'en') ? 'en' : 'ru';
$MSG = [
    'ru' => [
        'method' => 'Метод не разрешен',
        'forbidden' => 'Доступ запрещен',
        'rate' => 'Слишком много заявок. Попробуйте через час.',
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
        'mail_fail' => 'Ошибка отправки. Пожалуйста, напишите нам напрямую: info@ahundoff.ru или в Telegram.',
        'ok' => 'Заявка успешно отправлена'
    ],
    'en' => [
        'method' => 'Method not allowed',
        'forbidden' => 'Access denied',
        'rate' => 'Too many requests. Please try again in an hour.',
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
        'mail_fail' => 'Sending failed. Please contact us directly: info@ahundoff.ru or Telegram.',
        'ok' => 'Request sent successfully'
    ]
];


// Разрешаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => $MSG[$lang]['method']]);
    exit;
}

// Проверка AJAX-запроса
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => $MSG[$lang]['forbidden']]);
    exit;
}

// Проверка Origin/Referer (CSRF-защита: запрос должен идти с нашего домена)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$allowedHosts = ['ahundoff.ru', 'www.ahundoff.ru']; // PATCH 2026-11-21: whitelist домена
$originOk = false;
if ($origin !== '') {
    $originOk = in_array(parse_url($origin, PHP_URL_HOST), $allowedHosts, true);
} elseif ($referer !== '') {
    $originOk = in_array(parse_url($referer, PHP_URL_HOST), $allowedHosts, true);
}
if (!$originOk) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => $MSG[$lang]['forbidden']]);
    exit;
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
    // Если REMOTE_ADDR — внутренний адрес прокси хостинга, берём первый публичный IP
    // из X-Forwarded-For (его проставляет наш nginx). Публичный REMOTE_ADDR не подменяем — защита от спуфинга.
    if (filter_var($remote, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        foreach (explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '') as $candidate) {
            $candidate = trim($candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                return $candidate;
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
    // Убираем управляющие символы, кроме \t \n \r (переносы строк в сообщении сохраняем).
    // htmlspecialchars НЕ нужен: данные идут только в plain-text письмо и JSON-лог, в HTML не выводятся.
    return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
}

function logSpamAttempt(string $reason, array $data): void {
    ensureLogDir();
    // PATCH 2026-11-21: не пишем лог больше 5 МБ и обрезаем поля до 200 символов
    if (is_file(SPAM_LOG_FILE) && filesize(SPAM_LOG_FILE) > 5 * 1024 * 1024) {
        return;
    }
    $safe = [];
    foreach ($data as $k => $v) {
        if (is_string($v)) { $safe[$k] = mb_substr($v, 0, 200); }
    }
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reason' => $reason,
        'ip' => getClientIp(),
        'user_agent' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 200),
        'data' => $safe
    ];
    $logLine = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(SPAM_LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

function logLead(array $data, string $formType): void {
    ensureLogDir();
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $formType,
        'name' => $data['name'],
        'phone' => $data['phone'],
        'message' => $data['message'] ?? '',
        'company' => $data['company'] ?? '',
        'about' => $data['about'] ?? '',
        'consent' => isset($_POST['pd-consent']) ? '1' : '0',
        'policy_version' => '2026-11-21',
        'ip' => getClientIp(),
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

// Атомарная проверка и запись rate limit (решает проблему Race Condition)
function checkAndRecordRateLimit(): bool {
    ensureLogDir();
    $ip = getClientIp();
    $now = time();

    // 'c+' открывает файл для чтения и записи, создает его, если он не существует
    $fp = fopen(RATE_LIMIT_FILE, 'c+');
    if (!$fp) {
        return true; // В случае критической ошибки файла пропускаем проверку, чтобы не блокировать легитимных пользователей
    }

    // Эксклюзивная блокировка файла
    flock($fp, LOCK_EX);

    $content = stream_get_contents($fp);
    $data = $content ? json_decode($content, true) : [];
    // PATCH 2026-11-21: чистим старые записи всех IP, чтобы файл не рос бесконечно
    $data = is_array($data) ? $data : [];
    foreach ($data as $k => $timestamps) {
        $data[$k] = array_values(array_filter((array)$timestamps, fn($ts) => ($now - $ts) < RATE_LIMIT_PERIOD));
        if (empty($data[$k])) unset($data[$k]);
    }

    if (!isset($data[$ip])) {
        $data[$ip] = [];
    }

    // Фильтруем старые записи
    $requests = array_filter($data[$ip], fn($ts) => ($now - $ts) < RATE_LIMIT_PERIOD);

    // Проверяем лимит
    if (count($requests) >= RATE_LIMIT_MAX) {
        flock($fp, LOCK_UN);
        fclose($fp);
        return false;
    }

    // Добавляем текущий запрос и сохраняем
    $requests[] = $now;
    $data[$ip] = array_values($requests);

    ftruncate($fp, 0);
    rewind($fp);
    fwrite($fp, json_encode($data));
    fflush($fp);

    flock($fp, LOCK_UN);
    fclose($fp);

    return true;
}

// ==========================================================================
// 4. ПОЛУЧЕНИЕ И ВАЛИДАЦИЯ ДАННЫХ
// (проверки honeypot / timestamp выполняются ДО rate limiting,
//  чтобы спам-запросы не «сжигали» лимит IP реальных пользователей)
// ==========================================================================

$formType = $_POST['form_type'] ?? 'contact';
if (!in_array($formType, ['contact', 'partner'], true)) {
    $formType = 'contact';
}

// Honeypot проверка
// ВАЖНО: проверка выполняется ДО rate limiting и ДО валидации,
// чтобы бот не мог «сжигать» лимит IP реальных пользователей.
if (!empty($_POST['website'])) {
    logSpamAttempt('Honeypot triggered', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// Проверка timestamp
// form_time отсутствует или пуст — тоже считаем подозрительным (бот без JS),
// иначе проверка легко обходилась просто неполучкой этого поля.
$formTimeRaw = $_POST['form_time'] ?? null;
$formTime = (int)($formTimeRaw ?? 0);
$currentTime = time();

if ($formTimeRaw === null || $formTimeRaw === '' || $formTime <= 0) {
    logSpamAttempt('Missing or invalid form_time', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

if (($currentTime - $formTime) < MIN_FORM_TIME) {
    logSpamAttempt('Form filled too fast', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// Глупая защита от поддельного будущего timestamp
if ($formTime > $currentTime + 86400) {
    logSpamAttempt('Form time in the future', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// PATCH 2026-11-21: форма старше 24 часов — отсев (защита от replay)
if (($currentTime - $formTime) > 86400) {
    logSpamAttempt('Form time too old', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// ==========================================================================
// 5. ПРОВЕРКА RATE LIMITING (после спам-фильтров)
// ==========================================================================

if (!checkAndRecordRateLimit()) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => $MSG[$lang]['rate']
    ]);
    exit;
}

// Извлекаем и очищаем данные
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$message = sanitize($_POST['message'] ?? '');
$consent = isset($_POST['pd-consent']);

$phone = preg_replace('/[\r\n]/', '', $phone);
$name = preg_replace('/[\r\n]/', '', $name);

$company = '';
$about = '';
if ($formType === 'partner') {
    $company = sanitize($_POST['company'] ?? '');
    $about = sanitize($_POST['about'] ?? '');
}

// ==========================================================================
// 6. ВАЛИДАЦИЯ ОБЯЗАТЕЛЬНЫХ ПОЛЕЙ
// ==========================================================================

$errors = [];

if (empty($name)) {
    $errors[] = $MSG[$lang]['name_required'];
} elseif (mb_strlen($name) < 2) {
    $errors[] = $MSG[$lang]['name_short'];
} elseif (mb_strlen($name) > 100) {
    $errors[] = $MSG[$lang]['name_long'];
}

if (empty($phone)) {
    $errors[] = $MSG[$lang]['phone_required'];
} elseif (mb_strlen($phone) > 40) {
    $errors[] = $MSG[$lang]['phone_long'];
} else {
    // Разрешаем два формата: телефонный номер (не менее 10 цифр)
    // ИЛИ username Telegram вида @nickname (5–32 символа).
    // Раньше любой текст без 10 цифр отвергался, хотя форма явно принимает Telegram.
    $isTelegramUsername = preg_match('/^@[A-Za-z][A-Za-z0-9_]{4,31}$/', $phone);
    $phoneClean = preg_replace('/[^\d+]/', '', $phone);
    $isPhone = strlen($phoneClean) >= 10;
    if (!$isTelegramUsername && !$isPhone) {
        $errors[] = $MSG[$lang]['phone_invalid'];
    }
}

if (!$consent) {
    $errors[] = $MSG[$lang]['consent'];
}

if ($formType === 'partner' && empty($company)) {
    $errors[] = $MSG[$lang]['company'];
}

if (!empty($message) && mb_strlen($message) > 2000) {
    $errors[] = $MSG[$lang]['message_long'];
}

if (!empty($about) && mb_strlen($about) > 1000) {
    $errors[] = $MSG[$lang]['about_long'];
}

if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'error' => implode('. ', $errors)
    ]);
    exit;
}

// ==========================================================================
// 7. ФОРМИРОВАНИЕ ПИСЬМА
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
    $body .= "IP: " . (getClientIp()) . "\n\n";
    $body .= "📝 О СЕБЕ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= (!empty($about) ? $about : 'Не указано') . "\n\n";
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
    $body .= "IP: " . (getClientIp()) . "\n\n";
    $body .= "📝 СООБЩЕНИЕ\n";
    $body .= "─────────────────────────────────────\n";
    $body .= (!empty($message) ? $message : 'Не указано') . "\n\n";
    $body .= "═══════════════════════════════════════\n";
    $body .= "Действия:\n1. Связаться в течение 2 часов\n2. Провести бизнес-диагностику\n3. Подготовить коммерческое предложение\n";
    $body .= "═══════════════════════════════════════\n";
}

// ==========================================================================
// 8. ОТПРАВКА ПИСЬМА И ЛОГИРОВАНИЕ
// ==========================================================================

// Сначала логируем заявку, чтобы она точно сохранилась даже при сбое почты
logLead([
    'name' => $name,
    'phone' => $phone,
    'message' => $message,
    'company' => $company,
    'about' => $about
], $formType);

// Формируем корректные заголовки (без невалидного Reply-To с телефоном)
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

// ==========================================================================
// 9. ВОЗВРАТ ОТВЕТА
// ==========================================================================

if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => $MSG[$lang]['ok']
        // Параметр redirect удален, так как фронтенд теперь использует DOM-переключение
    ]);
} else {
    // Логируем ошибку в системный лог PHP
    error_log("Ahundoff Studio: Failed to send email for lead: $name ($phone)");

    // Честно сообщаем пользователю об ошибке, предлагая альтернативный канал связи
    echo json_encode([
        'success' => false,
        'error' => $MSG[$lang]['mail_fail']
    ]);
}

exit;
