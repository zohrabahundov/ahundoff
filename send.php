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

// Разрешаем только POST-запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не разрешен']);
    exit;
}

// Проверка AJAX-запроса
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещен']);
    exit;
}

// ==========================================================================
// 3. ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ==========================================================================

function ensureLogDir(): void {
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
}

function sanitize($value): string {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    $value = strip_tags($value);
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function logSpamAttempt(string $reason, array $data): void {
    ensureLogDir();
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reason' => $reason,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
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
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
    ];
    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

// Атомарная проверка и запись rate limit (решает проблему Race Condition)
function checkAndRecordRateLimit(): bool {
    ensureLogDir();
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
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
// 4. ПРОВЕРКА RATE LIMITING
// ==========================================================================

if (!checkAndRecordRateLimit()) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'error' => 'Слишком много заявок. Попробуйте через час.'
    ]);
    exit;
}

// ==========================================================================
// 5. ПОЛУЧЕНИЕ И ВАЛИДАЦИЯ ДАННЫХ
// ==========================================================================

$formType = $_POST['form_type'] ?? 'contact';

// Honeypot проверка
if (!empty($_POST['website'])) {
    logSpamAttempt('Honeypot triggered', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// Проверка timestamp
$formTime = (int)($_POST['form_time'] ?? 0);
$currentTime = time();

if ($formTime > 0 && ($currentTime - $formTime) < MIN_FORM_TIME) {
    logSpamAttempt('Form filled too fast', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
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
    $errors[] = 'Имя обязательно для заполнения';
} elseif (mb_strlen($name) < 2) {
    $errors[] = 'Имя слишком короткое';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Имя слишком длинное';
}

if (empty($phone)) {
    $errors[] = 'Телефон или Telegram обязателен';
} else {
    $phoneClean = preg_replace('/[^\d+]/', '', $phone);
    if (strlen($phoneClean) < 10) {
        $errors[] = 'Некорректный номер телефона';
    }
}

if (!$consent) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

if ($formType === 'partner' && empty($company)) {
    $errors[] = 'Укажите компанию или сферу деятельности';
}

if (!empty($message) && mb_strlen($message) > 2000) {
    $errors[] = 'Сообщение слишком длинное';
}

if (!empty($about) && mb_strlen($about) > 1000) {
    $errors[] = 'Описание слишком длинное';
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
    $body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
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
    $body .= "IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "\n\n";
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
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Priority' => '1',
];

$headerString = '';
foreach ($headers as $key => $value) {
    $headerString .= "$key: $value\r\n";
}

$additionalParams = '-f ' . SENDER_EMAIL;
$mailSent = mail(RECIPIENT_EMAIL, $subject, $body, $headerString, $additionalParams);

// ==========================================================================
// 9. ВОЗВРАТ ОТВЕТА
// ==========================================================================

if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно отправлена'
        // Параметр redirect удален, так как фронтенд теперь использует DOM-переключение
    ]);
} else {
    // Логируем ошибку в системный лог PHP
    error_log("Ahundoff Studio: Failed to send email for lead: $name ($phone)");
    
    // Честно сообщаем пользователю об ошибке, предлагая альтернативный канал связи
    echo json_encode([
        'success' => false,
        'error' => 'Ошибка отправки. Пожалуйста, напишите нам напрямую: info@ahundoff.ru или в Telegram.'
    ]);
}

exit;
