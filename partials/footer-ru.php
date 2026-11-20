<?php $home = in_array($_SERVER['SCRIPT_NAME'] ?? '', ['/', '/index.html'], true) ? '' : '/'; ?>
<footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__brand">
                    <a href="/" class="logo">Ahundoff<span class="logo__dot">.</span></a>
                    <p class="footer__tagline">AI-решения для бизнеса</p>
                    <p class="footer__legal">ИП Ахундов Зохраб Сейфуллаевич | ИНН 054204225330</p>
                </div>

                <nav class="footer__links" aria-label="Навигация по сайту">
                    <a href="<?= $home ?>#services">Продукты</a>
                    <a href="<?= $home ?>#optimization">Оптимизация</a>
                    <a href="<?= $home ?>#industries">Отрасли</a>
                    <a href="<?= $home ?>#cases">Кейсы</a>
                    <a href="<?= $home ?>#founder">Экспертиза</a>
                    <a href="<?= $home ?>#process">Подход</a>
                    <a href="<?= $home ?>#contact">Контакты</a>
                    <a href="/partners.html">Партнерская программа</a>
                    <a href="/privacy-policy.html">Политика конфиденциальности</a>
                </nav>

                <div class="footer__socials">
                    <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="WhatsApp">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                    </a>
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Telegram">
                        <svg width="20" height="20" aria-hidden="true"><use href="#icon-telegram"/></svg>
                    </a>
                </div>

                <div class="footer__copyright">© 2026 Ahundoff Studio. Все права защищены.</div>
            </div>
        </div>
    </footer>
