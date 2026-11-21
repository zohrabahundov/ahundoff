<?php
// Общий подвал (RU/EN). Требует $lang, $t и $privacy_url из i18n.php.
$home = in_array($_SERVER['SCRIPT_NAME'] ?? '', ['/', '/index.html', '/en.html'], true) ? '' : '/';
?>
<footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__brand">
                    <a href="/" class="logo">Ahundoff<span class="logo__dot">.</span></a>
                    <p class="footer__tagline"><?= $t['footer_tagline'] ?></p>
                    <p class="footer__legal"><?= $t['footer_legal'] ?></p>
                </div>

                <nav class="footer__links" aria-label="<?= h($t['footer_nav_aria']) ?>">
                    <a href="<?= $home ?>#services"><?= $t['nav_services'] ?></a>
                    <a href="<?= $home ?>#optimization"><?= $t['nav_optimization'] ?></a>
                    <a href="<?= $home ?>#industries"><?= $t['nav_industries'] ?></a>
                    <a href="<?= $home ?>#cases"><?= $t['nav_cases'] ?></a>
                    <a href="<?= $home ?>#founder"><?= $t['nav_founder'] ?></a>
                    <a href="<?= $home ?>#process"><?= $t['nav_process'] ?></a>
                    <a href="<?= $home ?>#contact"><?= $t['nav_contact'] ?></a>
                    <a href="/partners.html"><?= $t['footer_partners'] ?></a>
                    <a href="<?= $privacy_url ?>"><?= $t['footer_privacy'] ?></a>
                </nav>

                <div class="footer__socials">
                    <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="WhatsApp">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                    </a>
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Telegram">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-telegram"/></svg>
                    </a>
                </div>

                <div class="footer__copyright">© <?= date('Y') ?> Ahundoff Studio. <?= $t['footer_copyright'] ?></div>
            </div>
        </div>
    </footer>
