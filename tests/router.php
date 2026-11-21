<?php
// Роутер PHP built-in сервера: исполняет .html как PHP (как на Timeweb через .htaccess).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
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
