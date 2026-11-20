<?php $home = ($_SERVER['SCRIPT_NAME'] ?? '') === '/en.html' ? '' : '/'; ?>
<?php include __DIR__ . '/sprite.php'; ?>
<header class="header">
        <div class="container">
            <div class="header__content">
                <a href="/" class="logo" aria-label="Ahundoff Studio — Home">Ahundoff<span class="logo__dot">.</span></a>
                <div class="header__lang">
                    <a href="/">RU</a>
                    <a href="/en.html" class="active">EN</a>
                </div>
                <button class="burger" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav">
                    <span></span><span></span><span></span>
                </button>
                <nav class="nav" id="main-nav" aria-label="Main navigation">
                    <a href="<?= $home ?>#services" class="nav__link">Products</a>
                    <a href="<?= $home ?>#optimization" class="nav__link">Optimization</a>
                    <a href="<?= $home ?>#industries" class="nav__link">Industries</a>
                    <a href="<?= $home ?>#cases" class="nav__link">Cases</a>
                    <a href="<?= $home ?>#founder" class="nav__link">Expertise</a>
                    <a href="<?= $home ?>#process" class="nav__link">Approach</a>
                    <a href="<?= $home ?>#contact" class="nav__link nav__link--cta">Contacts</a>
                </nav>
            </div>
        </div>
    </header>
