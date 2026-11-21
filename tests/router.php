<?php
// Роутер PHP built-in сервера: исполняет .html как PHP (как на Timeweb через .htaccess).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Авто-определение языка (аудит п.16) — эмуляция правила .htaccess на стенде.
if ($path === '/') {
    $cookie = $_SERVER['HTTP_COOKIE'] ?? '';
    $al = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
    if (stripos($cookie, 'lang_choice=') === false && preg_match('/^en/i', $al)) {
        header('Location: /en.html', true, 302);
        header('Vary: Accept-Language');
        header('Cache-Control: no-store');
        return true;
    }
}
if ($path === '/') $path = '/index.html';
// Как в .htaccess: служебные директории и bootstrap не открываются напрямую.
if (preg_match('#^/(partials|templates|lang)/#', $path) || $path === '/i18n.php') {
    http_response_code(403);
    echo 'Forbidden';
    return true;
}
$file = dirname(__DIR__) . $path;
if (preg_match('/\.html?$/', $path) && is_file($file)) {
    $_SERVER['SCRIPT_NAME'] = $path;
    $_SERVER['PHP_SELF'] = $path;
    include $file;
    return true;
}
return false;
