<?php
// Общий шаблон партнёрской программы (RU/EN). Тексты — в lang/.
require __DIR__ . '/../i18n.php';
$canonical = $lang === 'en' ? 'https://ahundoff.ru/partners-en.html' : 'https://ahundoff.ru/partners.html';
$og_url    = $canonical;
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <style>html { background: #030712; }</style>
    <meta name="description" content="<?= h($t['partner_meta_description']) ?>">
    <meta name="robots" content="index, follow">

    <link rel="canonical" href="<?= $canonical ?>">
    <link rel="alternate" hreflang="ru" href="https://ahundoff.ru/partners.html">
    <link rel="alternate" hreflang="en" href="https://ahundoff.ru/partners-en.html">
    <link rel="alternate" hreflang="x-default" href="https://ahundoff.ru/partners.html">

    <meta property="og:title" content="<?= h($t['partner_og_title']) ?>">
    <meta property="og:description" content="<?= h($t['partner_og_description']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $og_url ?>">
    <meta property="og:image" content="https://ahundoff.ru/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:image" content="https://ahundoff.ru/og-image.jpg">
    <meta property="og:locale" content="<?= h($t['og_locale']) ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($t['partner_og_title']) ?>">
    <meta name="twitter:description" content="<?= h($t['partner_twitter_description']) ?>">


    <title><?= $t['partner_title'] ?></title>

    <script type="application/ld+json"><?= $t['partner_jsonld'] ?></script>

    <link rel="stylesheet" href="/styles.css?v=2026-11-21-6">


    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">



</head>
<body>
    <!-- noscript-пиксель Метрики удалён (аудит п.8): аналитика только после согласия -->
<a class="skip-link" href="#main"><?= $t['skip_link'] ?></a>
<?php include __DIR__ . '/../partials/header.php'; ?>

    <!-- Hero -->
    <section class="partners-hero" id="main">
        <div class="container">
            <div class="partners-hero__content">
                <span class="partners-hero__badge"><?= $t['partner_hero_badge'] ?></span>
                <h1 class="partners-hero__title">
                    <?= $t['partner_hero_title_prefix'] ?><span class="highlight">15%</span><?= $t['partner_hero_title_suffix'] ?><span class="logo__dot">.</span>
                </h1>
                <p class="partners-hero__subtitle">
                    <?= $t['partner_hero_subtitle'] ?>
                </p>
                <div class="hero__actions hero__actions--center">
                    <a href="#apply" class="btn btn--primary"><?= $t['partner_cta_apply'] ?></a>
                    <a href="#how-it-works" class="btn btn--secondary"><?= $t['partner_how_title'] ?></a>
                </div>
                <p class="partners-hero__alt"><a href="<?= $alt_partners_url ?>"><?= $t['policy_alt_version_link'] ?></a></p>
            </div>
        </div>
    </section>

    <!-- Stats -->
    <section class="partners-stats" aria-label="<?= h($t['partner_stats_aria']) ?>">
        <div class="container">
            <div class="partners-stats__grid">
                <div class="stat">
                    <div class="partners-stat__number">15%</div>
                    <div class="partners-stat__label"><?= $t['partner_stat1_label'] ?></div>
                </div>
                <div class="stat">
                    <div class="partners-stat__number"><?= $t['partner_stat2_number'] ?></div>
                    <div class="partners-stat__label"><?= $t['partner_stat2_label'] ?></div>
                </div>
                <div class="stat">
                    <div class="partners-stat__number">100%</div>
                    <div class="partners-stat__label"><?= $t['partner_stat3_label'] ?></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Для кого -->
    <section class="partners-section">
        <div class="container">
            <h2 class="section-title"><?= $t['partner_forwhom_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['partner_forwhom_subtitle'] ?></p>
            <div class="for-whom__grid">
                <div class="card for-whom__item">
                    <div class="card__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-settings"/></svg>
                    </div>
                    <h3><?= $t['partner_whom1_title'] ?></h3>
                    <p><?= $t['partner_whom1_text'] ?></p>
                </div>
                <div class="card for-whom__item">
                    <div class="card__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-code"/></svg>
                    </div>
                    <h3><?= $t['partner_whom2_title'] ?></h3>
                    <p><?= $t['partner_whom2_text'] ?></p>
                </div>
                <div class="card for-whom__item">
                    <div class="card__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-briefcase"/></svg>
                    </div>
                    <h3><?= $t['partner_whom3_title'] ?></h3>
                    <p><?= $t['partner_whom3_text'] ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Как это работает -->
    <section id="how-it-works" class="partners-section partners-section--alt">
        <div class="container">
            <h2 class="section-title"><?= $t['partner_how_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['partner_how_subtitle'] ?></p>
            <div class="process__timeline">
                <div class="process__line"></div>
                <div class="process__step">
                    <div class="process__dot">01</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['partner_step1_title'] ?></h3>
                        <p class="process__text"><?= $t['partner_step1_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">02</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['partner_step2_title'] ?></h3>
                        <p class="process__text"><?= $t['partner_step2_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">03</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['partner_step3_title'] ?></h3>
                        <p class="process__text"><?= $t['partner_step3_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">04</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['partner_step4_title'] ?></h3>
                        <p class="process__text"><?= $t['partner_step4_text'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Преимущества -->
    <section class="partners-section">
        <div class="container">
            <h2 class="section-title"><?= $t['partner_benefits_title'] ?><span class="logo__dot">.</span></h2>
            <div class="benefits__grid">
                <div class="card benefit-item">
                    <div class="benefit-item__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-lock"/></svg>
                    </div>
                    <h3><?= $t['partner_ben1_title'] ?></h3>
                    <p><?= $t['partner_ben1_text'] ?></p>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-item__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-dollar"/></svg>
                    </div>
                    <h3><?= $t['partner_ben2_title'] ?></h3>
                    <p><?= $t['partner_ben2_text'] ?></p>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-item__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-zap"/></svg>
                    </div>
                    <h3><?= $t['partner_ben3_title'] ?></h3>
                    <p><?= $t['partner_ben3_text'] ?></p>
                </div>
                <div class="card benefit-item">
                    <div class="benefit-item__icon">
                        <svg width="24" height="24" aria-hidden="true"><use href="#icon-shield"/></svg>
                    </div>
                    <h3><?= $t['partner_ben4_title'] ?></h3>
                    <p><?= $t['partner_ben4_text'] ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="partners-section partners-section--alt">
        <div class="container">
            <h2 class="section-title"><?= $t['faq_title'] ?><span class="logo__dot">?</span></h2>
            <div class="partners-faq faq__grid">
                <div class="faq__item">
                    <button class="faq__question" aria-expanded="false" aria-controls="pfaq-1" id="pfaq-q-1">
                        <span><?= $t['partner_faq1_q'] ?></span>
                        <span class="faq__icon" aria-hidden="true">▶</span>
                    </button>
                    <div class="faq__answer" id="pfaq-1" role="region" aria-labelledby="pfaq-q-1">
                        <p><?= $t['partner_faq1_a'] ?></p>
                    </div>
                </div>
                <div class="faq__item">
                    <button class="faq__question" aria-expanded="false" aria-controls="pfaq-2" id="pfaq-q-2">
                        <span><?= $t['partner_faq2_q'] ?></span>
                        <span class="faq__icon" aria-hidden="true">▶</span>
                    </button>
                    <div class="faq__answer" id="pfaq-2" role="region" aria-labelledby="pfaq-q-2">
                        <p><?= $t['partner_faq2_a'] ?></p>
                    </div>
                </div>
                <div class="faq__item">
                    <button class="faq__question" aria-expanded="false" aria-controls="pfaq-3" id="pfaq-q-3">
                        <span><?= $t['partner_faq3_q'] ?></span>
                        <span class="faq__icon" aria-hidden="true">▶</span>
                    </button>
                    <div class="faq__answer" id="pfaq-3" role="region" aria-labelledby="pfaq-q-3">
                        <p><?= $t['partner_faq3_a'] ?></p>
                    </div>
                </div>
                <div class="faq__item">
                    <button class="faq__question" aria-expanded="false" aria-controls="pfaq-4" id="pfaq-q-4">
                        <span><?= $t['partner_faq4_q'] ?></span>
                        <span class="faq__icon" aria-hidden="true">▶</span>
                    </button>
                    <div class="faq__answer" id="pfaq-4" role="region" aria-labelledby="pfaq-q-4">
                        <p><?= $t['partner_faq4_a'] ?></p>
                    </div>
                </div>
                <div class="faq__item">
                    <button class="faq__question" aria-expanded="false" aria-controls="pfaq-5" id="pfaq-q-5">
                        <span><?= $t['partner_faq5_q'] ?></span>
                        <span class="faq__icon" aria-hidden="true">▶</span>
                    </button>
                    <div class="faq__answer" id="pfaq-5" role="region" aria-labelledby="pfaq-q-5">
                        <p><?= $t['partner_faq5_a'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Форма заявки -->
    <section id="apply" class="partners-section">
        <div class="container">
            <div class="partners-form-wrapper">
                <h2 class="section-title"><?= $t['partner_cta_apply'] ?><span class="logo__dot">.</span></h2>
                <p class="section-subtitle"><?= $t['partner_apply_subtitle'] ?></p>

                <form class="partners-form" id="partnersForm" novalidate method="post" action="/send.php">
                    <input type="text" name="website" tabindex="-1" autocomplete="new-password" class="u-offscreen" aria-hidden="true">
                    <input type="hidden" name="form_type" value="partner">
                    <input type="hidden" name="form_time" id="formTime">
                    <noscript><p class="u-noscript-note"><?= $t['contact_noscript'] ?><a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a> / <a href="https://t.me/zohrab_ahundov">@zohrab_ahundov</a></p></noscript>


                    <div class="form-group">
                        <label for="p-name" class="sr-only"><?= $t['form_name'] ?></label>
                        <input type="text" name="name" id="p-name" placeholder="<?= h($t['form_name']) ?>" required minlength="2" maxlength="100" autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="p-company" class="sr-only"><?= $t['partner_form_company_label'] ?></label>
                        <input type="text" name="company" id="p-company" placeholder="<?= h($t['partner_form_company_placeholder']) ?>" required minlength="2" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label for="p-contact" class="sr-only"><?= $t['partner_form_contact_label'] ?></label>
                        <input type="text" name="phone" id="p-contact" placeholder="<?= h($t['partner_form_contact_placeholder']) ?>" required autocomplete="tel" inputmode="text" maxlength="40">
                    </div>
                    <div class="form-group">
                        <label for="p-about" class="sr-only"><?= $t['partner_form_about_label'] ?></label>
                        <textarea name="about" id="p-about" placeholder="<?= h($t['partner_form_about_placeholder']) ?>" rows="4" maxlength="1000"></textarea>
                    </div>

                    <div class="form-consent">
                        <input type="checkbox" id="p-pd-consent" name="pd-consent" required>
                        <label for="p-pd-consent"><?= $t['partner_consent_prefix'] ?><a href="<?= $privacy_url ?>" target="_blank" rel="noopener noreferrer"><?= $t['form_consent_link'] ?></a><?= $t['partner_consent_suffix'] ?></label>
                    </div>

                    <button type="submit" class="btn btn--primary btn--full" id="p-submitBtn" data-default-label="<?= h($t['partner_submit']) ?>"><?= $t['partner_submit'] ?></button>
                    <div id="p-formMessage" class="form-message" role="alert" aria-live="polite"></div>
                </form>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="partners-cta">
        <div class="container">
            <div class="partners-cta__content">
                <h2><?= $t['partner_cta_title'] ?><span class="logo__dot">?</span></h2>
                <p><?= $t['partner_cta_text'] ?></p>
                <div class="partners-cta__actions">
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="btn btn--primary">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-telegram"/></svg>
                        <span><?= $t['partner_cta_telegram'] ?></span>
                    </a>
                    <a href="<?= $home_url ?>" class="btn btn--secondary"><?= $t['partner_cta_home'] ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>

    <!-- Scroll Progress -->
    <div class="scroll-progress" aria-hidden="true"></div>

    <!-- Floating Actions (единый столбец) -->
    <div class="floating-actions">
        <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="floating-action floating-action--telegram" aria-label="<?= h($t['floating_telegram_aria']) ?>">
            <svg width="24" height="24" aria-hidden="true"><use href="#icon-telegram"/></svg>
        </a>
        <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="floating-action floating-action--whatsapp" aria-label="<?= h($t['floating_whatsapp_aria']) ?>">
            <svg width="24" height="24" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
        </a>
        <button class="floating-action floating-action--top" aria-label="<?= h($t['floating_top_aria']) ?>">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="18 15 12 9 6 15"/>
            </svg>
        </button>
    </div>

    <?php include __DIR__ . '/../partials/cookie-banner.php'; ?>
    <script src="/script.js?v=2026-11-21-4" defer></script>

    </body>
</html>
