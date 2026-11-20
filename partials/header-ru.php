<?php $home = in_array($_SERVER['SCRIPT_NAME'] ?? '', ['/', '/index.html'], true) ? '' : '/'; ?>
<?php include __DIR__ . '/sprite.php'; ?>
<header class="header">
        <div class="container">
            <div class="header__content">
                <a href="/" class="logo" aria-label="Ahundoff Studio — на главную">Ahundoff<span class="logo__dot">.</span></a>
                <div class="header__lang">
                    <a href="/" class="active">RU</a>
                    <a href="/en.html">EN</a>
                </div>
                <button class="burger" aria-label="Открыть меню" aria-expanded="false" aria-controls="main-nav">
                    <span></span><span></span><span></span>
                </button>
                <nav class="nav" id="main-nav" aria-label="Главная навигация">
                    <a href="<?= $home ?>#services" class="nav__link">Продукты</a>
                    <a href="<?= $home ?>#optimization" class="nav__link">Оптимизация</a>
                    <a href="<?= $home ?>#industries" class="nav__link">Отрасли</a>
                    <a href="<?= $home ?>#cases" class="nav__link">Кейсы</a>
                    <a href="<?= $home ?>#founder" class="nav__link">Экспертиза</a>
                    <a href="<?= $home ?>#process" class="nav__link">Подход</a>
                    <a href="<?= $home ?>#contact" class="nav__link nav__link--cta">Контакты</a>
                </nav>
            </div>
        </div>
    </header>
