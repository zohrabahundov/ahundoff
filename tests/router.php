<?php
// Роутер PHP built-in сервера: исполняет .html как PHP (как на Timeweb через .htaccess).
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if ($path === '/') $path = '/index.html';
$file = dirname(__DIR__) . $path;
if (preg_match('/\.html?$/', $path) && is_file($file)) {
    $_SERVER['SCRIPT_NAME'] = $path;
    $_SERVER['PHP_SELF'] = $path;
    include $file;
    return true;
}
return false;
