<?php
// Единая точка локализации. Обёртки страниц задают LANG ('ru'|'en') и подключают
// templates/*.php; партнёрка (только RU) подключает этот файл напрямую.
if (!defined('LANG')) define('LANG', 'ru');
$lang = LANG === 'en' ? 'en' : 'ru';
$t = require __DIR__ . '/lang/' . $lang . '.php';

// Ссылка на политику конфиденциальности на текущем языке и на альтернативном.
$privacy_url     = $lang === 'en' ? '/privacy-policy-en.html' : '/privacy-policy.html';
$alt_policy_url  = $lang === 'en' ? '/privacy-policy.html' : '/privacy-policy-en.html';
// Якорь формы контактов на главной странице текущего языка.
$home_contact    = $lang === 'en' ? '/en.html#contact' : '/#contact';

if (!function_exists('h')) {
    // Экранирование значений атрибутов (словари — доверенный контент).
    // ENT_COMPAT: атрибуты в двойных кавычках, апострофы оставляем как в исходнике.
    function h($s) { return htmlspecialchars($s, ENT_COMPAT, 'UTF-8'); }
}
