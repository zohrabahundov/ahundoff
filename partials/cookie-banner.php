<?php // Cookie-notice с opt-out (аудит п.8). Требует $t и $privacy_url из i18n.php.
      // Баннер скрыт по умолчанию; script.js показывает его, если выбор ещё не сделан.
      // Аналитика (Яндекс.Метрика) грузится только после согласия «Принять все». ?>
<div class="cookie-banner" id="cookie-banner" role="region" aria-label="<?= h($t['cookie_aria']) ?>" hidden>
    <div class="cookie-banner__inner">
        <p class="cookie-banner__text"><?= $t['cookie_text'] ?> <a href="<?= $privacy_url ?>" target="_blank" rel="noopener noreferrer"><?= $t['footer_privacy'] ?></a></p>
        <div class="cookie-banner__actions">
            <button type="button" class="btn btn--primary cookie-banner__btn" id="cookie-accept"><?= $t['cookie_accept'] ?></button>
            <button type="button" class="btn btn--secondary cookie-banner__btn" id="cookie-essential"><?= $t['cookie_essential'] ?></button>
        </div>
    </div>
</div>
