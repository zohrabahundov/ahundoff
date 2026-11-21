<?php
// Общая шапка (RU/EN). Требует $lang и $t из i18n.php.
$home = in_array($_SERVER['SCRIPT_NAME'] ?? '', ['/', '/index.html', '/en.html'], true) ? '' : '/';
include __DIR__ . '/sprite.php';
?>
<header class="header">
        <div class="container">
            <div class="header__content">
                <a href="/" class="logo" aria-label="<?= h($t['header_logo_aria']) ?>">Ahundoff<span class="logo__dot">.</span></a>
                <div class="header__lang">
                    <a href="/"<?= $lang === 'ru' ? ' class="active"' : '' ?>>RU</a>
                    <a href="/en.html"<?= $lang === 'en' ? ' class="active"' : '' ?>>EN</a>
                </div>
                <button class="burger" aria-label="<?= h($t['header_burger_aria']) ?>" aria-expanded="false" aria-controls="main-nav">
                    <span></span><span></span><span></span>
                </button>
                <nav class="nav" id="main-nav" aria-label="<?= h($t['header_nav_aria']) ?>">
                    <a href="<?= $home ?>#services" class="nav__link"><?= $t['nav_services'] ?></a>
                    <a href="<?= $home ?>#optimization" class="nav__link"><?= $t['nav_optimization'] ?></a>
                    <a href="<?= $home ?>#industries" class="nav__link"><?= $t['nav_industries'] ?></a>
                    <a href="<?= $home ?>#cases" class="nav__link"><?= $t['nav_cases'] ?></a>
                    <a href="<?= $home ?>#founder" class="nav__link"><?= $t['nav_founder'] ?></a>
                    <a href="<?= $home ?>#process" class="nav__link"><?= $t['nav_process'] ?></a>
                    <a href="<?= $home ?>#contact" class="nav__link nav__link--cta"><?= $t['nav_contact'] ?></a>
                </nav>
            </div>
        </div>
    </header>
