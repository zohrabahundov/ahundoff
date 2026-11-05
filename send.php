<?php
/**
 * Ahundoff Studio - Обработчик форм
 * Поддерживает: обычные заявки (contact) и заявки партнеров (partner)
 * Защита: honeypot, timestamp, rate limiting, валидация, санитизация
 */

// ==========================================================================
// 1. КОНФИГУРАЦИЯ
// ==========================================================================

// Email для получения заявок
define('RECIPIENT_EMAIL', 'info@ahundoff.ru');
define('SENDER_EMAIL', 'noreply@ahundoff.ru');
define('SENDER_NAME', 'Ahundoff Studio');

// Пути к файлам
define('LOG_FILE', dirname(__DIR__) . '/logs/leads.log');
define('RATE_LIMIT_FILE', __DIR__ . '/logs/rate_limit.json');

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
// 3. RATE LIMITING (защита от массовых отправок)
// ==========================================================================

function checkRateLimit(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!file_exists(RATE_LIMIT_FILE)) {
        return true;
    }
    
    $data = json_decode(file_get_contents(RATE_LIMIT_FILE), true);
    if (!$data || !isset($data[$ip])) {
        return true;
    }
    
    $requests = $data[$ip];
    $now = time();
    
    // Удаляем старые записи
    $requests = array_filter($requests, function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_PERIOD;
    });
    
    // Проверяем лимит
    if (count($requests) >= RATE_LIMIT_MAX) {
        return false;
    }
    
    // Добавляем текущий запрос
    $requests[] = $now;
    $data[$ip] = array_values($requests);
    
    file_put_contents(RATE_LIMIT_FILE, json_encode($data), LOCK_EX);
    
    return true;
}

function recordRequest(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    
    if (!file_exists(RATE_LIMIT_FILE)) {
        $data = [];
    } else {
        $data = json_decode(file_get_contents(RATE_LIMIT_FILE), true) ?: [];
    }
    
    if (!isset($data[$ip])) {
        $data[$ip] = [];
    }
    
    $data[$ip][] = time();
    
    // Оставляем только последние записи
    $now = time();
    $data[$ip] = array_filter($data[$ip], function($timestamp) use ($now) {
        return ($now - $timestamp) < RATE_LIMIT_PERIOD;
    });
    
    file_put_contents(RATE_LIMIT_FILE, json_encode($data), LOCK_EX);
}

// Проверка rate limiting
if (!checkRateLimit()) {
    http_response_code(429);
    echo json_encode([
        'success' => false, 
        'error' => 'Слишком много заявок. Попробуйте через час.'
    ]);
    exit;
}

// ==========================================================================
// 4. ПОЛУЧЕНИЕ И ВАЛИДАЦИЯ ДАННЫХ
// ==========================================================================

// Получаем тип формы
$formType = $_POST['form_type'] ?? 'contact';

// Honeypot проверка (если заполнено - это бот)
if (!empty($_POST['website'])) {
    logSpamAttempt('Honeypot triggered', $_POST);
    // Возвращаем успех, чтобы бот не понял, что его поймали
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// Проверка timestamp (форма заполнялась слишком быстро)
$formTime = (int)($_POST['form_time'] ?? 0);
$currentTime = time();

if ($formTime > 0 && ($currentTime - $formTime) < MIN_FORM_TIME) {
    logSpamAttempt('Form filled too fast', $_POST);
    echo json_encode(['success' => true, 'message' => 'Спасибо!']);
    exit;
}

// Санитизация входных данных
function sanitize($value): string {
    if (!is_string($value)) {
        return '';
    }
    $value = trim($value);
    $value = strip_tags($value);
    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    return $value;
}

// Извлекаем и очищаем данные
$name = sanitize($_POST['name'] ?? '');
$phone = sanitize($_POST['phone'] ?? '');
$message = sanitize($_POST['message'] ?? '');
$consent = isset($_POST['pd-consent']) ? true : false;

$phone = preg_replace('/[\r\n]/', '', $phone);
$name = preg_replace('/[\r\n]/', '', $name);

// Дополнительные поля для партнерской формы
$company = '';
$about = '';
if ($formType === 'partner') {
    $company = sanitize($_POST['company'] ?? '');
    $about = sanitize($_POST['about'] ?? '');
}

// ==========================================================================
// 5. ВАЛИДАЦИЯ ОБЯЗАТЕЛЬНЫХ ПОЛЕЙ
// ==========================================================================

$errors = [];

// Валидация имени
if (empty($name)) {
    $errors[] = 'Имя обязательно для заполнения';
} elseif (mb_strlen($name) < 2) {
    $errors[] = 'Имя слишком короткое';
} elseif (mb_strlen($name) > 100) {
    $errors[] = 'Имя слишком длинное';
}

// Валидация телефона
if (empty($phone)) {
    $errors[] = 'Телефон или Telegram обязателен';
} else {
    // Убираем всё кроме цифр и символа +
    $phoneClean = preg_replace('/[^\d+]/', '', $phone);
    if (strlen($phoneClean) < 10) {
        $errors[] = 'Некорректный номер телефона';
    }
}

// Валидация согласия на обработку ПДн
if (!$consent) {
    $errors[] = 'Необходимо согласие на обработку персональных данных';
}

// Дополнительные проверки для партнерской формы
if ($formType === 'partner') {
    if (empty($company)) {
        $errors[] = 'Укажите компанию или сферу деятельности';
    }
}

// Валидация сообщения (не обязательно, но если есть - проверяем длину)
if (!empty($message) && mb_strlen($message) > 2000) {
    $errors[] = 'Сообщение слишком длинное';
}

if (!empty($about) && mb_strlen($about) > 1000) {
    $errors[] = 'Описание слишком длинное';
}

// Если есть ошибки - возвращаем их
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'error' => implode('. ', $errors)
    ]);
    exit;
}

// ==========================================================================
// 6. ФОРМИРОВАНИЕ ПИСЬМА
// ==========================================================================

if ($formType === 'partner') {
    // Письмо для партнерской заявки
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
    $body .= "Действия:\n";
    $body .= "1. Связаться в течение 24 часов\n";
    $body .= "2. Обсудить условия партнерства\n";
    $body .= "3. Отправить агентский договор\n";
    $body .= "═══════════════════════════════════════\n";
    
} else {
    // Письмо для обычной заявки
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
    $body .= "Действия:\n";
    $body .= "1. Связаться в течение 2 часов\n";
    $body .= "2. Провести бизнес-диагностику\n";
    $body .= "3. Подготовить коммерческое предложение\n";
    $body .= "═══════════════════════════════════════\n";
}

// ==========================================================================
// 7. ОТПРАВКА ПИСЬМА
// ==========================================================================

$headers = [
    'From' => SENDER_NAME . ' <' . SENDER_EMAIL . '>',
    'Reply-To' => $phone . ' <' . SENDER_EMAIL . '>',
    'X-Mailer' => 'PHP/' . phpversion(),
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'X-Priority' => '1', // Высокий приоритет
];

$headerString = '';
foreach ($headers as $key => $value) {
    $headerString .= "$key: $value\r\n";
}

// Добавляем параметр для mail()
$additionalParams = '-f ' . SENDER_EMAIL;

// Отправляем письмо
$mailSent = mail(RECIPIENT_EMAIL, $subject, $body, $headerString, $additionalParams);

// ==========================================================================
// 8. ЛОГИРОВАНИЕ ЗАЯВКИ
// ==========================================================================

function logLead(array $data, string $formType): void {
    // Создаем директорию, если её нет
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
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
    
    // Записываем в JSON Lines формат
    $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents(LOG_FILE, $logLine, FILE_APPEND | LOCK_EX);
}

// Логируем успешную заявку
logLead([
    'name' => $name,
    'phone' => $phone,
    'message' => $message,
    'company' => $company,
    'about' => $about
], $formType);

// Записываем запрос в rate limit
recordRequest();

// ==========================================================================
// 9. ВОЗВРАТ ОТВЕТА
// ==========================================================================

if ($mailSent) {
    echo json_encode([
        'success' => true,
        'message' => 'Заявка успешно отправлена',
        'redirect' => '/success.html'
    ]);
} else {
    // Даже если mail() не сработал, мы всё равно логируем заявку
    // и возвращаем успех (заявка сохранена в логах)
    error_log("Failed to send email for lead: $name ($phone)");
    
    echo json_encode([
        'success' => true,
        'message' => 'Заявка принята',
        'redirect' => '/success.html'
    ]);
}

exit;

// ==========================================================================
// ВСПОМОГАТЕЛЬНЫЕ ФУНКЦИИ
// ==========================================================================

function logSpamAttempt(string $reason, array $data): void {
    $logDir = dirname(LOG_FILE);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $spamLog = $logDir . '/spam.log';
    
    $entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'reason' => $reason,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        'data' => $data
    ];
    
    $logLine = json_encode($entry, JSON_UNESCAPED_UNICODE) . "\n";
    file_put_contents($spamLog, $logLine, FILE_APPEND | LOCK_EX);
}
