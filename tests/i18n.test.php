<?php
/**
 * Офлайн-тест парности локалей (не требует стенда).
 * Guards the DRY goal: RU/EN словари обязаны иметь одинаковые ключи,
 * а каждый ключ, используемый в шаблонах/partials, — присутствовать в обоих.
 * Запуск: php tests/i18n.test.php
 */
$root = dirname(__DIR__);
$ru = require $root . '/lang/ru.php';
$en = require $root . '/lang/en.php';
$fail = 0;
function check($cond, $label) { global $fail; if ($cond) { echo "PASS: $label\n"; } else { echo "FAIL: $label\n"; $fail++; } }

check(array_keys($ru) === array_keys($en), 'наборы и порядок ключей ru/en совпадают');
check(count($ru) > 0, 'словарь не пуст (' . count($ru) . ' ключей)');

$emptyRu = array_keys(array_filter($ru, fn($v) => trim((string)$v) === ''));
$emptyEn = array_keys(array_filter($en, fn($v) => trim((string)$v) === ''));
check(!$emptyRu, 'нет пустых значений в ru' . ($emptyRu ? ': ' . implode(',', $emptyRu) : ''));
check(!$emptyEn, 'нет пустых значений в en' . ($emptyEn ? ': ' . implode(',', $emptyEn) : ''));

// Все ключи, используемые в общей разметке.
$files = ['templates/index.php', 'templates/privacy.php', 'partials/header.php', 'partials/footer.php'];
$used = [];
foreach ($files as $f) {
    preg_match_all("/\\\$t\['([a-z0-9_]+)'\]/", file_get_contents($root . '/' . $f), $m);
    foreach ($m[1] as $k) $used[$k] = true;
}
$used = array_keys($used);
$missingRu = array_diff($used, array_keys($ru));
$missingEn = array_diff($used, array_keys($en));
check(!$missingRu, 'все ключи разметки есть в ru' . ($missingRu ? ': ' . implode(',', $missingRu) : ''));
check(!$missingEn, 'все ключи разметки есть в en' . ($missingEn ? ': ' . implode(',', $missingEn) : ''));

// Неиспользуемые ключи — предупреждение (не провал): могут быть заделом.
$unused = array_diff(array_keys($ru), $used);
if ($unused) echo 'NOTE: неиспользуемые ключи (' . count($unused) . '): ' . implode(',', $unused) . "\n";

// Обёртки задают язык и подключают общий шаблон.
$wrappers = [
    'index.html' => ['ru', 'templates/index.php'],
    'en.html' => ['en', 'templates/index.php'],
    'privacy-policy.html' => ['ru', 'templates/privacy.php'],
    'privacy-policy-en.html' => ['en', 'templates/privacy.php'],
    'partners.html' => ['ru', 'templates/partners.php'],
    'partners-en.html' => ['en', 'templates/partners.php'],
];
foreach ($wrappers as $file => [$lang, $tpl]) {
    $src = file_get_contents($root . '/' . $file);
    check(strpos($src, "define('LANG', '$lang')") !== false, "$file определяет LANG=$lang");
    check(strpos($src, $tpl) !== false, "$file подключает $tpl");
}

// JSON-LD в обоих словарях — валидный JSON.
foreach (['ru' => $ru, 'en' => $en] as $lang => $dict) {
    if (isset($dict['schema_jsonld'])) {
        json_decode($dict['schema_jsonld']);
        check(json_last_error() === JSON_ERROR_NONE, "schema_jsonld ($lang) — валидный JSON");
    }
}

echo "\nИТОГО i18n: " . ($fail ? "FAIL=$fail" : 'OK') . "\n";
exit($fail ? 1 : 0);
