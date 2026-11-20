<?php $home = ($_SERVER['SCRIPT_NAME'] ?? '') === '/en.html' ? '' : '/'; ?>
<footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__brand">
                    <a href="/" class="logo">Ahundoff<span class="logo__dot">.</span></a>
                    <p class="footer__tagline">AI solutions for business</p>
                    <p class="footer__legal">Sole Proprietor Zohrab Ahundov | INN 054204225330</p>
                </div>

                <nav class="footer__links" aria-label="Site navigation">
                    <a href="<?= $home ?>#services">Products</a>
                    <a href="<?= $home ?>#optimization">Optimization</a>
                    <a href="<?= $home ?>#industries">Industries</a>
                    <a href="<?= $home ?>#cases">Cases</a>
                    <a href="<?= $home ?>#founder">Expertise</a>
                    <a href="<?= $home ?>#process">Approach</a>
                    <a href="<?= $home ?>#contact">Contacts</a>
                    <a href="/partners.html">Partner program</a>
                    <a href="/privacy-policy-en.html">Privacy Policy</a>
                </nav>

                <div class="footer__socials">
                    <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="WhatsApp">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                    </a>
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Telegram">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-telegram"/></svg>
                    </a>
                </div>

                <div class="footer__copyright">© <?= date('Y') ?> Ahundoff Studio. All rights reserved.</div>
            </div>
        </div>
    </footer>
