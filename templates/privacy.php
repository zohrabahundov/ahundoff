<?php
// Общий шаблон политики конфиденциальности (RU/EN). Тексты — в lang/.
require __DIR__ . '/../i18n.php';
$canonical = $lang === 'en' ? 'https://ahundoff.ru/privacy-policy-en.html' : 'https://ahundoff.ru/privacy-policy.html';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <style>html { background: #030712; }</style>
    <meta name="description" content="<?= h($t['policy_meta_description']) ?>">
    <meta name="robots" content="noindex, follow">
    
    <link rel="canonical" href="<?= $canonical ?>">
    
    <title><?= $t['policy_title'] ?></title>
    
    
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="stylesheet" href="styles.css?v=2026-11-21-3">
</head>
<body>
    <a class="skip-link" href="#main"><?= $t['skip_link'] ?></a>
    <!-- Header -->
    <?php include __DIR__ . '/../partials/header.php'; ?>

    <!-- Page Hero -->
    <section class="policy-hero" id="main">
        <div class="container">
            <div class="policy-hero__content">
                <span class="policy-hero__badge"><?= $t['policy_badge'] ?></span>
                <h1 class="policy-hero__title"><?= $t['policy_h1'] ?></h1>
                <p class="policy-hero__meta"><?= $t['policy_updated'] ?><a href="<?= $alt_policy_url ?>"><?= $t['policy_alt_version_link'] ?></a>
                </p>
            </div>
        </div>
    </section>

    <!-- Policy Content -->
    <main class="policy">
        <div class="container">
            <div class="policy__layout">
                <!-- Оглавление -->
                <aside class="policy__toc">
                    <h2 class="policy__toc-title"><?= $t['policy_toc_title'] ?></h2>
                    <nav class="policy__toc-nav">
                        <a href="#general" class="policy__toc-link"><?= $t['policy_toc_general'] ?></a>
                        <a href="#operator" class="policy__toc-link"><?= $t['policy_toc_operator'] ?></a>
                        <a href="#data" class="policy__toc-link"><?= $t['policy_toc_data'] ?></a>
                        <a href="#purposes" class="policy__toc-link"><?= $t['policy_toc_purposes'] ?></a>
                        <a href="#legal-basis" class="policy__toc-link"><?= $t['policy_toc_legal'] ?></a>
                        <a href="#third-parties" class="policy__toc-link"><?= $t['policy_toc_sharing'] ?></a>
                        <a href="#cookies" class="policy__toc-link"><?= $t['policy_toc_cookies'] ?></a>
                        <a href="#storage" class="policy__toc-link"><?= $t['policy_toc_storage'] ?></a>
                        <a href="#rights" class="policy__toc-link"><?= $t['policy_toc_rights'] ?></a>
                        <a href="#security" class="policy__toc-link"><?= $t['policy_toc_security'] ?></a>
                        <a href="#changes" class="policy__toc-link"><?= $t['policy_toc_changes'] ?></a>
                        <a href="#contacts" class="policy__toc-link"><?= $t['policy_toc_contacts'] ?></a>
                    </nav>
                </aside>

                <!-- Основной контент -->
                <article class="policy__content">
                    
                    <section id="general" class="policy__section">
                        <h2><?= $t['policy_s1_title'] ?></h2>
                        <p><?= $t['policy_s1_intro'] ?><strong>ahundoff.ru</strong><?= $t['policy_s1_site_tail'] ?></p>
                        <p><?= $t['policy_s1_laws_intro'] ?></p>
                        <ul>
                            <li><?= $t['policy_s1_law152'] ?></li>
                            <li><?= $t['policy_s1_law149'] ?></li>
                            <li><?= $t['policy_s1_constitution'] ?></li>
                            <li><?= $t['policy_s1_other_laws'] ?></li>
                        </ul>
                        <p><?= $t['policy_s1_consent_note'] ?></p>
                    </section>

                    <section id="operator" class="policy__section">
                        <h2><?= $t['policy_s2_title'] ?></h2>
                        <p><?= $t['policy_s2_intro'] ?></p>
                        <div class="policy__info-card">
                            <p><strong><?= $t['policy_operator_name'] ?></strong></p>
                            <p><?= $t['policy_inn'] ?></p>
                            <p>Email: <a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a></p>
                            <p><?= $t['policy_phone_label'] ?><a href="tel:+79295005785">+7 (929) 500-57-85</a></p>
                            <p><?= $t['policy_address'] ?></p>
                        </div>
                    </section>

                    <section id="data" class="policy__section">
                        <h2><?= $t['policy_s3_title'] ?></h2>
                        <p><?= $t['policy_s3_intro'] ?></p>
                        
                        <h3><?= $t['policy_s3_1_title'] ?></h3>
                        <ul>
                            <li><strong><?= $t['policy_data_name'] ?></strong><?= $t['policy_data_name_purpose'] ?></li>
                            <li><strong><?= $t['policy_data_phone'] ?></strong><?= $t['policy_data_phone_purpose'] ?></li>
                            <li><strong><?= $t['policy_data_task'] ?></strong><?= $t['policy_data_task_purpose'] ?></li>
                            <li><strong><?= $t['policy_data_correspondence'] ?></strong><?= $t['policy_data_correspondence_purpose'] ?></li>
                        </ul>

                        <h3><?= $t['policy_s3_2_title'] ?></h3>
                        <ul>
                            <li><?= $t['policy_tech_ip'] ?></li>
                            <li><?= $t['policy_tech_browser'] ?></li>
                            <li><?= $t['policy_tech_cookies'] ?></li>
                            <li><?= $t['policy_tech_behavior'] ?></li>
                            <li><?= $t['policy_tech_datetime'] ?></li>
                        </ul>
                    </section>

                    <section id="purposes" class="policy__section">
                        <h2><?= $t['policy_s4_title'] ?></h2>
                        <p><?= $t['policy_s4_intro'] ?></p>
                        <ul>
                            <li><?= $t['policy_purpose_requests'] ?></li>
                            <li><?= $t['policy_purpose_diagnostics'] ?></li>
                            <li><?= $t['policy_purpose_proposals'] ?></li>
                            <li><?= $t['policy_purpose_contracts'] ?></li>
                            <li><?= $t['policy_purpose_analytics'] ?></li>
                            <li><?= $t['policy_purpose_legal'] ?></li>
                        </ul>
                        <p><strong><?= $t['policy_no_sell_note'] ?></strong></p>
                    </section>

                    <section id="legal-basis" class="policy__section">
                        <h2><?= $t['policy_s5_title'] ?></h2>
                        <p><?= $t['policy_s5_intro'] ?></p>
                        <ul>
                            <li><strong><?= $t['policy_basis_consent'] ?></strong><?= $t['policy_basis_consent_detail'] ?></li>
                            <li><strong><?= $t['policy_basis_contract'] ?></strong><?= $t['policy_basis_contract_detail'] ?></li>
                            <li><strong><?= $t['policy_basis_interest'] ?></strong><?= $t['policy_basis_interest_detail'] ?></li>
                            <li><strong><?= $t['policy_basis_legal'] ?></strong><?= $t['policy_basis_legal_detail'] ?></li>
                        </ul>
                    </section>

                    <section id="third-parties" class="policy__section">
                        <h2><?= $t['policy_s6_title'] ?></h2>
                        <p><?= $t['policy_s6_intro'] ?></p>
                        
                        <h3><?= $t['policy_s6_1_title'] ?></h3>
                        <p><?= $t['policy_s6_metrika_prefix'] ?><strong><?= $t['policy_metrika_name'] ?></strong><?= $t['policy_s6_metrika_detail'] ?></p>
                        <p><?= $t['policy_s6_metrika_link_intro'] ?><a href="https://yandex.ru/legal/confidential/" target="_blank" rel="noopener noreferrer">https://yandex.ru/legal/confidential/</a></p>
                        
                        <h3><?= $t['policy_s6_2_title'] ?></h3>
                        <p><?= $t['policy_s6_hosting_prefix'] ?><strong><?= $t['policy_hosting_name'] ?></strong><?= $t['policy_s6_hosting_detail'] ?></p>
                        
                        <h3><?= $t['policy_s6_3_title'] ?></h3>
                        <p><?= $t['policy_s6_legal_text'] ?></p>
                        
                        <h3><?= $t['policy_s6_4_title'] ?></h3>
                        <p><?= $t['policy_s6_crossborder_text'] ?></p>
                    </section>

                    <section id="cookies" class="policy__section">
                        <h2><?= $t['policy_s7_title'] ?></h2>
                        <p><?= $t['policy_s7_intro'] ?></p>
                        
                        <h3><?= $t['policy_s7_1_title'] ?></h3>
                        <ul>
                            <li><strong><?= $t['policy_cookie_essential'] ?></strong><?= $t['policy_cookie_essential_detail'] ?></li>
                            <li><strong><?= $t['policy_cookie_analytics'] ?></strong><?= $t['policy_cookie_analytics_detail'] ?></li>
                        </ul>
                        
                        <h3><?= $t['policy_s7_2_title'] ?></h3>
                        <p><?= $t['policy_s7_disable_text'] ?></p>
                    </section>

                    <section id="storage" class="policy__section">
                        <h2><?= $t['policy_s8_title'] ?></h2>
                        <ul>
                            <li><strong><?= $t['policy_retention_contract'] ?></strong><?= $t['policy_retention_contract_detail'] ?></li>
                            <li><strong><?= $t['policy_retention_analytics'] ?></strong><?= $t['policy_retention_analytics_detail'] ?></li>
                            <li><strong><?= $t['policy_retention_forms'] ?></strong><?= $t['policy_retention_forms_detail'] ?></li>
                        </ul>
                        <p><?= $t['policy_s8_deletion_note'] ?></p>
                    </section>

                    <section id="rights" class="policy__section">
                        <h2><?= $t['policy_s9_title'] ?></h2>
                        <p><?= $t['policy_s9_intro'] ?></p>
                        <ul>
                            <li><?= $t['policy_right_info'] ?></li>
                            <li><?= $t['policy_right_correct'] ?></li>
                            <li><?= $t['policy_right_delete'] ?></li>
                            <li><?= $t['policy_right_withdraw'] ?></li>
                            <li><?= $t['policy_right_object'] ?></li>
                            <li><?= $t['policy_right_appeal'] ?></li>
                        </ul>
                        <p><?= $t['policy_s9_request_prefix'] ?><a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a><?= $t['policy_s9_request_tail'] ?></p>
                    </section>

                    <section id="security" class="policy__section">
                        <h2><?= $t['policy_s10_title'] ?></h2>
                        <p><?= $t['policy_s10_intro'] ?></p>
                        <ul>
                            <li><?= $t['policy_sec_https'] ?></li>
                            <li><?= $t['policy_sec_access'] ?></li>
                            <li><?= $t['policy_sec_updates'] ?></li>
                            <li><?= $t['policy_sec_firewalls'] ?></li>
                            <li><?= $t['policy_sec_nda'] ?></li>
                        </ul>
                    </section>

                    <section id="changes" class="policy__section">
                        <h2><?= $t['policy_s11_title'] ?></h2>
                        <p><?= $t['policy_s11_text'] ?></p>
                        <p><?= $t['policy_s11_effective'] ?></p>
                    </section>

                    <section id="contacts" class="policy__section">
                        <h2><?= $t['policy_s12_title'] ?></h2>
                        <p><?= $t['policy_s12_text'] ?></p>
                        <div class="policy__info-card">
                            <p><strong><?= $t['policy_operator_name'] ?></strong></p>
                            <p>Email: <a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a></p>
                            <p><?= $t['policy_phone_label'] ?><a href="tel:+79295005785">+7 (929) 500-57-85</a></p>
                            <p>Telegram: <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer">@zohrab_ahundov</a></p>
                        </div>
                        <p><?= $t['policy_s12_response_time'] ?></p>
                    </section>
                </article>
            </div>
        </div>
    </main>

    <!-- CTA -->
    <section class="policy-cta">
        <div class="container">
            <div class="policy-cta__content">
                <h2><?= $t['policy_cta_title'] ?></h2>
                <p><?= $t['policy_cta_text'] ?></p>
                <a href="<?= $home_contact ?>" class="btn btn--primary"><?= $t['cta_discuss'] ?></a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include __DIR__ . '/../partials/footer.php'; ?>
    <script src="/script.js?v=2026-11-21-3" defer></script>
</body>
</html>
