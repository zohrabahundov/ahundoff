<?php
// Общий шаблон главной страницы (RU/EN). Меняются только текста — см. lang/.
require __DIR__ . '/../i18n.php';
$canonical = $lang === 'en' ? 'https://ahundoff.ru/en.html' : 'https://ahundoff.ru/';
$og_url    = $lang === 'en' ? 'https://ahundoff.ru/en.html' : 'https://ahundoff.ru';
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <style>html { background: #030712; }</style>
    <meta name="description" content="<?= h($t['meta_description']) ?>">
    <meta name="keywords" content="<?= h($t['meta_keywords']) ?>">
    <meta name="author" content="<?= h($t['founder_name']) ?>">
    <meta name="robots" content="index, follow">
    
    <link rel="canonical" href="<?= $canonical ?>">
    <link rel="alternate" hreflang="ru" href="https://ahundoff.ru/">
    <link rel="alternate" hreflang="en" href="https://ahundoff.ru/en.html">
    <link rel="alternate" hreflang="x-default" href="https://ahundoff.ru/">
    
    <meta property="og:title" content="<?= h($t['site_title']) ?>">
    <meta property="og:description" content="<?= h($t['og_description']) ?>">
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $og_url ?>">
    <meta property="og:image" content="https://ahundoff.ru/og-image.jpg">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta name="twitter:image" content="https://ahundoff.ru/og-image.jpg">
    <meta property="og:locale" content="<?= h($t['og_locale']) ?>">
    <meta name="yandex-verification" content="18ffb19ae3c0a614" />
    
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= h($t['site_title']) ?>">
    <meta name="twitter:description" content="<?= h($t['og_description']) ?>">
    
    
    <title><?= $t['site_title'] ?></title>
    
    <link rel="stylesheet" href="/styles.css?v=2026-11-21-6">
    
    
    <link rel="icon" type="image/svg+xml" href="/favicon.svg?v=2">
    <link rel="alternate icon" type="image/x-icon" href="/favicon.ico?v=2">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    
    <script type="application/ld+json"><?= $t['schema_jsonld'] ?></script>

    </head>
<body>
    <!-- noscript-пиксель Метрики удалён (аудит п.8): без JS согласие не получить, аналитика не грузится -->
<a class="skip-link" href="#main"><?= $t['skip_link'] ?></a>
<?php include __DIR__ . '/../partials/header.php'; ?>

    <!-- 1. Hero -->
    <section class="hero" id="main">
        <canvas class="hero__grid" aria-hidden="true"></canvas>
        <div class="container">
            <div class="hero__content">
                <span class="hero__badge"><?= $t['hero_badge'] ?></span>
                <h1 class="hero__title"><?= $t['hero_title'] ?><span class="logo__dot">.</span></h1>
                <p class="hero__subtitle"><?= $t['hero_subtitle'] ?></p>
                <div class="hero__actions">
                    <a href="#contact" class="btn btn--primary"><?= $t['hero_cta'] ?></a>
                    <a href="#cases" class="btn btn--secondary"><?= $t['hero_btn_cases'] ?></a>
                </div>
            </div>
        </div>
    </section>

    <!-- 2. Stats -->
    <section class="stats" aria-label="<?= h($t['stats_aria']) ?>">
        <div class="container">
            <div class="stats__grid">
                <div class="stat">
                    <div class="stat__number" data-target="10" data-suffix="<?= h($t['stats_years_suffix']) ?>"><?= $t['stats_years_value'] ?></div>
                    <div class="stat__label">
                        <span class="tooltip" tabindex="0" data-tooltip="<?= h($t['stats_experience_tooltip']) ?>"><?= $t['stats_experience_label'] ?></span>
                    </div>
                </div>
                <div class="stat">
                    <div class="stat__number" data-target="<?= h($t['stats_portfolio_target']) ?>" data-suffix="<?= h($t['stats_portfolio_suffix']) ?>"><?= $t['stats_portfolio_value'] ?></div>
                    <div class="stat__label">
                        <span class="tooltip" tabindex="0" data-tooltip="<?= h($t['stats_portfolio_tooltip']) ?>"><?= $t['stats_portfolio_label'] ?></span>
                    </div>
                </div>
                <div class="stat">
                    <div class="stat__number" data-target="14" data-suffix="<?= h($t['stats_days_suffix']) ?>"><?= $t['stats_days_value'] ?></div>
                    <div class="stat__label">
                        <span class="tooltip" tabindex="0" data-tooltip="<?= h($t['stats_mvp_tooltip']) ?>"><?= $t['stats_mvp_label'] ?></span>
                    </div>
                </div>
                <div class="stat">
                    <div class="stat__number" data-target="100" data-suffix="%">100%</div>
                    <div class="stat__label">
                        <span class="tooltip" tabindex="0" data-tooltip="<?= h($t['stats_sla_tooltip']) ?>"><?= $t['stats_sla_label'] ?></span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 3. Services -->
    <section id="services" class="services">
        <div class="container">
            <h2 class="section-title"><?= $t['services_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['services_subtitle'] ?></p>
            <div class="services__grid">
                <article class="card service-card service-card--feature">
                    <div class="card-art">
                        <img src="assets/products/document-scanner.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_docs_title'] ?></h3>
                    <p class="card__text"><?= $t['svc_docs_text'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_docs_metric'] ?></span>
                        <span><?= $t['svc_docs_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_docs_price'] ?></span>
                    </div>
                </article>
                <article class="card service-card service-card--compact">
                    <div class="card-art">
                        <img src="assets/products/inspection-gauge.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_quality_title'] ?></h3>
                    <p class="card__text"><?= $t['svc_quality_text'] ?><span class="tooltip" tabindex="0" data-tooltip="<?= h($t['svc_quality_edge_tooltip']) ?>"><?= $t['svc_quality_edge_term'] ?></span><?= $t['svc_quality_text_tail'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_quality_metric'] ?></span>
                        <span><?= $t['svc_quality_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_quality_price'] ?></span>
                    </div>
                </article>
                <article class="card service-card service-card--compact service-card--accent">
                    <div class="card-art">
                        <img src="assets/products/video-camera.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_video_title'] ?></h3>
                    <p class="card__text"><?= $t['svc_video_text'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_video_metric'] ?></span>
                        <span><?= $t['svc_video_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_video_price'] ?></span>
                    </div>
                </article>
                <article class="card service-card service-card--wide">
                    <div class="card-art">
                        <img src="assets/products/product-search.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_search_title'] ?></h3>
                    <p class="card__text"><?= $t['svc_search_text'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_search_metric'] ?></span>
                        <span><?= $t['svc_search_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_search_price'] ?></span>
                    </div>
                </article>
                <article class="card service-card">
                    <div class="card-art">
                        <img src="assets/products/ct-scanner.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_med_title'] ?></h3>
                    <p class="card__text"><?= $t['svc_med_text'] ?><span class="tooltip" tabindex="0" data-tooltip="<?= h($t['svc_med_mis_tooltip']) ?>"><?= $t['svc_med_mis_term'] ?></span><?= $t['svc_med_text_tail'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_med_metric'] ?></span>
                        <span><?= $t['svc_med_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_med_price'] ?></span>
                    </div>
                </article>
                <article class="card service-card">
                    <div class="card-art">
                        <img src="assets/products/card-catalog.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['svc_rag_title_prefix'] ?><span class="tooltip" tabindex="0" data-tooltip="<?= h($t['svc_rag_tooltip']) ?>">RAG</span><?= $t['svc_rag_title_suffix'] ?></h3>
                    <p class="card__text"><?= $t['svc_rag_text'] ?></p>
                    <div class="service-card__meta">
                        <span><?= $t['svc_rag_metric'] ?></span>
                        <span><?= $t['svc_rag_term'] ?></span>
                        <span class="service-card__price"><?= $t['svc_rag_price'] ?></span>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- 4. Optimization -->
    <section id="optimization" class="optimization">
    <div class="container">
        <h2 class="section-title"><?= $t['opt_title'] ?><span class="logo__dot">.</span></h2>
        <p class="section-subtitle"><?= $t['opt_subtitle'] ?></p>

        <div class="optimization__grid">
            <article class="card optimization-card">
                <div class="card-art">
                    <img src="assets/optimization/security.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                </div>
                <h3 class="card__title"><?= $t['opt_security_title'] ?></h3>
                <p class="card__text"><?= $t['opt_security_text'] ?></p>
                <div class="optimization-card__meta">
                    <span><?= $t['opt_security_metric'] ?></span>
                    <span><?= $t['opt_security_term'] ?></span>
                    <span class="optimization-card__price"><?= $t['opt_security_price'] ?></span>
                </div>
            </article>

            <article class="card optimization-card">
                <div class="card-art">
                    <img src="assets/optimization/llm-cost.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                </div>
                <h3 class="card__title"><?= $t['opt_llm_title'] ?></h3>
                <p class="card__text"><?= $t['opt_llm_text'] ?></p>
                <div class="optimization-card__meta">
                    <span><?= $t['opt_llm_metric'] ?></span>
                    <span><?= $t['opt_llm_term'] ?></span>
                    <span class="optimization-card__price"><?= $t['opt_llm_price'] ?></span>
                </div>
            </article>

            <article class="card optimization-card">
                <div class="card-art">
                    <img src="assets/optimization/algorithms.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                </div>
                <h3 class="card__title"><?= $t['opt_algo_title'] ?></h3>
                <p class="card__text"><?= $t['opt_algo_text'] ?></p>
                <div class="optimization-card__meta">
                    <span><?= $t['opt_algo_metric'] ?></span>
                    <span class="optimization-card__price"><?= $t['opt_algo_price'] ?></span>
                </div>
            </article>

            <article class="card optimization-card">
                <div class="card-art">
                    <img src="assets/optimization/patents.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                </div>
                <h3 class="card__title"><?= $t['opt_patent_title'] ?></h3>
                <p class="card__text"><?= $t['opt_patent_text'] ?></p>
                <div class="optimization-card__meta">
                    <span><?= $t['opt_patent_metric'] ?></span>
                    <span class="optimization-card__price"><?= $t['opt_patent_price'] ?></span>
                </div>
            </article>
        </div>

        <div class="optimization__cta">
            <a href="#contact" class="btn btn--primary"><?= $t['opt_cta'] ?></a>
        </div>
    </div>
</section>

    <!-- 5. Industries -->
    <section id="industries" class="industries">
        <div class="container">
            <h2 class="section-title"><?= $t['industries_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['industries_subtitle'] ?></p>

            <div class="industries__grid">
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/bank-vault.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_fintech_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_fintech_text'] ?></p>
                </article>
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/machine-tool.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_manufacturing_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_manufacturing_text'] ?></p>
                </article>
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/freight-train.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_logistics_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_logistics_text'] ?></p>
                </article>
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/cash-register.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_retail_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_retail_text'] ?></p>
                </article>
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/microscope.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_healthcare_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_healthcare_text'] ?></p>
                </article>
                <article class="card industry-card">
                    <div class="card-art">
                        <img src="assets/industries/open-book.webp?v=cutout-1" width="800" height="320" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['ind_education_title'] ?></h3>
                    <p class="card__text"><?= $t['ind_education_text'] ?></p>
                </article>
            </div>
        </div>
    </section>

    <!-- 6. Cases -->
    <section id="cases" class="cases">
        <div class="container">
            <h2 class="section-title"><?= $t['cases_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['cases_subtitle'] ?></p>
            <div class="cases__grid">
                <article class="card case-card">
                    <div class="card-art">
                        <img src="assets/cases/scoring.webp?v=cutout-1" width="960" height="384" alt="" loading="lazy" decoding="async">
                    </div>
                    <div class="case-card__header">
                        <h3 class="card__title"><?= $t['case_scoring_title'] ?></h3>
                        <span class="case-card__btn case-card__btn--nda" aria-label="<?= h($t['case_scoring_nda_aria']) ?>">
                            <svg width="16" height="16" aria-hidden="true"><use href="#icon-lock"/></svg>
                            <span>NDA</span>
                        </span>
                    </div>
                    <p class="case-card__text"><strong><?= $t['label_industry'] ?></strong><?= $t['case_scoring_industry'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_task'] ?></strong><?= $t['case_scoring_task'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_result'] ?></strong><?= $t['case_scoring_result'] ?></p>
                    <div class="case-card__impact">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-quote"/></svg>
                        <p><?= $t['case_scoring_quote'] ?></p>
                    </div>
                </article>

                <article class="card case-card">
                    <div class="card-art">
                        <img src="assets/cases/industry.webp?v=cutout-1" width="960" height="384" alt="" loading="lazy" decoding="async">
                    </div>
                    <div class="case-card__header">
                        <h3 class="card__title"><?= $t['case_mbatr_title'] ?></h3>
                        <a href="https://mbatr.ru" target="_blank" rel="noopener noreferrer" class="case-card__btn" aria-label="<?= h($t['case_mbatr_link_aria']) ?>">
                            <span>mbatr.ru</span>
                            <svg width="16" height="16" aria-hidden="true"><use href="#icon-external"/></svg>
                        </a>
                    </div>
                    <p class="case-card__text"><strong><?= $t['label_industry'] ?></strong><?= $t['case_mbatr_industry'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_task'] ?></strong><?= $t['case_mbatr_task'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_result'] ?></strong><?= $t['case_mbatr_result'] ?></p>
                    <div class="case-card__impact">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-quote"/></svg>
                        <p><?= $t['case_mbatr_quote'] ?></p>
                    </div>
                </article>

                <article class="card case-card">
                    <div class="card-art">
                        <img src="assets/cases/connections.webp?v=cutout-1" width="960" height="384" alt="" loading="lazy" decoding="async">
                    </div>
                    <div class="case-card__header">
                        <h3 class="card__title"><?= $t['case_edinstvo_title'] ?></h3>
                        <a href="https://t.me/fcc_edinstvo_bot" target="_blank" rel="noopener noreferrer" class="case-card__btn" aria-label="<?= h($t['case_edinstvo_link_aria']) ?>">
                            <span><?= $t['case_edinstvo_client'] ?></span>
                            <svg width="16" height="16" aria-hidden="true"><use href="#icon-external"/></svg>
                        </a>
                    </div>
                    <p class="case-card__text"><strong><?= $t['label_industry'] ?></strong><?= $t['case_edinstvo_industry'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_task'] ?></strong><?= $t['case_edinstvo_task'] ?></p>
                    <p class="case-card__text"><strong><?= $t['label_result'] ?></strong><?= $t['case_edinstvo_result'] ?></p>
                    <div class="case-card__impact">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-quote"/></svg>
                        <p><?= $t['case_edinstvo_quote'] ?></p>
                    </div>
                </article>
            </div>
        </div>
    </section>

    <!-- 7. Trust / Founder -->
    <section id="founder" class="trust-section">
        <div class="container">
            <div class="trust-section__header">
                <h2 class="section-title"><?= $t['trust_title'] ?><span class="logo__dot">.</span></h2>
                <p class="section-subtitle"><?= $t['trust_subtitle'] ?></p>
            </div>
            
            <div class="why__grid">
                <div class="card why__item">
                    <div class="card-art">
                        <img src="assets/trust/balance.webp?v=cutout-1" width="880" height="352" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['trust_results_title'] ?></h3>
                    <p class="card__text"><?= $t['trust_results_text'] ?></p>
                </div>
                <div class="card why__item">
                    <div class="card-art">
                        <img src="assets/trust/bridge.webp?v=cutout-1" width="880" height="352" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['trust_reliability_title'] ?></h3>
                    <p class="card__text"><?= $t['trust_reliability_text'] ?></p>
                </div>
                <div class="card why__item">
                    <div class="card-art">
                        <img src="assets/trust/turbine.webp?v=cutout-1" width="880" height="352" alt="" loading="lazy" decoding="async">
                    </div>
                    <h3 class="card__title"><?= $t['trust_speed_title'] ?></h3>
                    <p class="card__text"><?= $t['trust_speed_text'] ?></p>
                </div>
            </div>

            <!-- Founder -->
            <div class="founder__guarantee">
                <div class="founder__grid">
                    <div class="founder__photo">
                        <div class="founder__image">
                            <img src="assets/trust/founder.jpg?v=1" alt="<?= h($t['founder_photo_alt']) ?>" loading="lazy" decoding="async" width="800" height="1143">
                        </div>
                        <div class="founder__photo-badge">
                            <svg width="16" height="16" aria-hidden="true"><use href="#icon-check"/></svg>
                            <span><?= $t['founder_badge'] ?></span>
                        </div>
                    </div>

                    <div class="founder__content">
                        <span class="founder__label"><?= $t['founder_guarantee_label'] ?></span>
                        <h2 class="founder__name"><?= $t['founder_name'] ?><span class="logo__dot">.</span></h2>
                        <p class="founder__role"><?= $t['founder_role'] ?></p>
                        
                        <div class="founder__bio">
                            <p><?= $t['founder_bio_intro'] ?><strong><?= $t['founder_portfolio_value'] ?></strong><?= $t['founder_bio_tail'] ?></p>
                            <p><?= $t['founder_patent_prefix'] ?><strong><?= $t['founder_patent_strong'] ?></strong><?= $t['founder_patent_tail'] ?></p>
                            <p><?= $t['founder_boutique_prefix'] ?><strong><?= $t['founder_boutique_strong'] ?></strong><?= $t['founder_boutique_tail'] ?></p>
                        </div>

                        <div class="founder__actions">
                            <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="btn btn--primary btn--sm">
                                <svg width="16" height="16" aria-hidden="true"><use href="#icon-telegram"/></svg>
                                <span><?= $t['founder_telegram_btn'] ?></span>
                            </a>
                            <a href="#contact" class="btn btn--secondary btn--sm"><?= $t['cta_discuss'] ?></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 8. Process -->
    <section id="process" class="process">
        <div class="container">
            <h2 class="section-title"><?= $t['process_title'] ?><span class="logo__dot">.</span></h2>
            <p class="section-subtitle"><?= $t['process_subtitle'] ?></p>
            <div class="process__timeline">
                <div class="process__line"></div>
                <div class="process__step">
                    <div class="process__dot">01</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['process_step1_title'] ?></h3>
                        <p class="process__text"><?= $t['process_step1_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">02</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['process_step2_title'] ?></h3>
                        <p class="process__text"><?= $t['process_step2_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">03</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['process_step3_title'] ?></h3>
                        <p class="process__text"><?= $t['process_step3_text'] ?></p>
                    </div>
                </div>
                <div class="process__step">
                    <div class="process__dot">04</div>
                    <div class="process__content">
                        <h3 class="process__title"><?= $t['process_step4_title'] ?></h3>
                        <p class="process__text"><?= $t['process_step4_text'] ?></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 9. Contact -->
    <section id="contact" class="contact">
        <div class="container">
            <div class="contact__content">
                <h2 class="section-title"><?= $t['contact_title'] ?><span class="logo__dot">?</span></h2>
                <p class="contact__text"><?= $t['contact_text'] ?></p>
                
                <div class="security-badges" role="list" aria-label="<?= h($t['contact_badges_aria']) ?>">
                    <div class="security-badge" role="listitem">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-shield"/></svg>
                        <span><?= $t['contact_badge_nda'] ?></span>
                    </div>
                    <div class="security-badge" role="listitem">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-lock"/></svg>
                        <span><?= $t['contact_badge_fz'] ?></span>
                    </div>
                    <div class="security-badge" role="listitem">
                        <svg width="16" height="16" aria-hidden="true"><use href="#icon-check"/></svg>
                        <span><?= $t['contact_badge_llm'] ?></span>
                    </div>
                </div>
                
                <form class="contact__form" id="contactForm" novalidate method="post" action="/send.php">
                    <input type="text" name="website" tabindex="-1" autocomplete="new-password" class="u-offscreen" aria-hidden="true">
                    <input type="hidden" name="form_time" id="formTime">
                    <noscript><p class="u-noscript-note"><?= $t['contact_noscript'] ?><a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a> / <a href="https://t.me/zohrab_ahundov">@zohrab_ahundov</a></p></noscript>
                    
                    <div class="form-group">
                        <label for="name" class="sr-only"><?= $t['form_name'] ?></label>
                        <input type="text" name="name" id="name" placeholder="<?= h($t['form_name']) ?>" required minlength="2" maxlength="100" autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="phone" class="sr-only"><?= $t['form_phone_label'] ?></label>
                        <input type="text" name="phone" id="phone" placeholder="<?= h($t['form_phone_placeholder']) ?>" required autocomplete="tel" inputmode="text" maxlength="40">
                    </div>
                    <div class="form-group">
                        <label for="message" class="sr-only"><?= $t['form_message_label'] ?></label>
                        <textarea name="message" id="message" placeholder="<?= h($t['form_message_placeholder']) ?>" rows="4" maxlength="2000"></textarea>
                    </div>
                    
                    <div class="form-consent">
                        <input type="checkbox" id="pd-consent" name="pd-consent" required>
                        <label for="pd-consent"><?= $t['form_consent_prefix'] ?><a href="<?= $privacy_url ?>" target="_blank" rel="noopener noreferrer"><?= $t['form_consent_link'] ?></a><?= $t['form_consent_suffix'] ?></label>
                    </div>
                    
                    <button type="submit" class="btn btn--primary btn--full" id="submitBtn"><?= $t['form_submit'] ?></button>
                    <div id="formMessage" class="form-message" role="alert" aria-live="polite"></div>
                </form>
                
                <!-- Блок подтверждения успешной отправки (скрыт по умолчанию) -->
                <div id="successBlock" class="form-success" role="status" aria-live="polite">
                    <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" class="form-success__icon">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                        <polyline points="22 4 12 14.01 9 11.01"/>
                    </svg>
                    <h3 class="form-success__title"><?= $t['success_title'] ?></h3>
                    <p class="form-success__text"><?= $t['success_text'] ?></p>
                    <button type="button" id="resetFormBtn" class="btn btn--secondary"><?= $t['success_again_btn'] ?></button>
                </div>
                
                <address class="contact__info">
                    <div class="contact__link-wrapper">
                        <a href="mailto:info@ahundoff.ru" class="contact__link">
                            <svg class="contact__icon" width="20" height="20" aria-hidden="true"><use href="#icon-mail"/></svg>
                            <span>info@ahundoff.ru</span>
                        </a>
                        <button class="copy-btn" data-copy="info@ahundoff.ru" aria-label="<?= h($t['success_copy_email']) ?>">
                            <svg width="16" height="16" aria-hidden="true"><use href="#icon-code"/></svg>
                        </button>
                    </div>
                    <a href="tel:+79295005785" class="contact__link">
                        <svg class="contact__icon" width="20" height="20" aria-hidden="true"><use href="#icon-phone"/></svg>
                        <span>+7 (929) 500-57-85</span>
                    </a>
                    <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="contact__link">
                        <svg class="contact__icon" width="20" height="20" aria-hidden="true"><use href="#icon-whatsapp"/></svg>
                        <span>WhatsApp</span>
                    </a>
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="contact__link">
                        <svg class="contact__icon" width="20" height="20" aria-hidden="true"><use href="#icon-telegram"/></svg>
                        <span>Telegram</span>
                    </a>
                </address>
            </div>
        </div>
    </section>
    
    <!-- Sticky Bar -->
    <div class="sticky-bar" role="complementary" aria-label="<?= h($t['sticky_aria']) ?>">
        <div class="container">
            <div class="sticky-bar__content">
                <div class="sticky-bar__text">
                    <svg class="sticky-bar__icon" width="20" height="20" aria-hidden="true"><use href="#icon-rocket"/></svg>
                    <span><?= $t['sticky_text'] ?></span>
                </div>
                <a href="#contact" class="btn btn--primary btn--sm"><?= $t['sticky_btn'] ?></a>
            </div>
        </div>
    </div>
    
    <!-- 10. Footer -->
<?php include __DIR__ . '/../partials/footer.php'; ?>

    <!-- Scroll Progress -->
    <div class="scroll-progress" aria-hidden="true"></div>

    <!-- Floating Actions -->
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
