#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
apply_all_patches.py — все правки аудита ahundoff.ru от 21.11.2026 одним запуском.
Запуск:  python apply_all_patches.py --address "ВАШ АДРЕС ЕГРИП" --push
Без --push только применяет к локальному клону ./ahundoff (можно залить на Timeweb вручную).
Идемпотентен: повторный запуск пропускает уже применённое.
"""
import argparse, os, re, subprocess, sys

REPO_URL = "https://github.com/zohrabahundov/ahundoff.git"
VER_OLD, VER_NEW = "?v=2026-11-21", "?v=2026-11-21-2"
changed = []

def load(d, f):
    with open(os.path.join(d, f), encoding="utf-8") as fh: return fh.read()

def save(d, f, t):
    with open(os.path.join(d, f), "w", encoding="utf-8", newline="\n") as fh: fh.write(t)
    if f not in changed: changed.append(f)

def patch(d, f, old, new, required=True):
    t = load(d, f)
    if old in t:
        save(d, f, t.replace(old, new)); return True
    if new in t:
        print(f"  skip (уже применено): {f}: {old[:50]}..."); return False
    if required: sys.exit(f"ОШИБКА: не найден якорь в {f}:\n{old[:200]}")
    print(f"  warn: якорь не найден (пропущено): {f}: {old[:50]}"); return False

def rpatch(d, f, pattern, repl, flags=re.S, required=True):
    t = load(d, f)
    new_t, n = re.subn(pattern, repl, t, flags=flags)
    if n: save(d, f, new_t); return n
    if required: sys.exit(f"ОШИБКА: regex не сработал в {f}: {pattern[:80]}")
    return 0

def balance(code):
    code = re.sub(r"//[^\n]*", "", code)
    code = re.sub(r"/\*.*?\*/", "", code, flags=re.S)
    code = re.sub(r"'(?:\\.|[^'\\])*'", "''", code)
    code = re.sub(r'"(?:\\.|[^"\\])*"', '""', code)
    code = re.sub(r"`(?:\\.|[^`\\])*`", "``", code)
    st = []; pairs = {')': '(', ']': '[', '}': '{'}
    for ch in code:
        if ch in "([{": st.append(ch)
        elif ch in ")]}":
            if not st or st.pop() != pairs[ch]: return False
    return not st

EN_POLICY = """<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Privacy Policy of Ahundoff Studio: how we collect, use, and protect personal data in accordance with Russian Federal Law 152-FZ.">
    <meta name="robots" content="noindex, follow">
    <link rel="canonical" href="https://ahundoff.ru/privacy-policy-en.html">
    <title>Privacy Policy — Ahundoff</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="favicon.svg">
    <link rel="stylesheet" href="styles.css?v=2026-11-21-2">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="header__content">
                <a href="/en.html" class="logo">Ahundoff<span class="logo__dot">.</span></a>
                <button class="burger" aria-label="Open menu" aria-expanded="false" aria-controls="main-nav"><span></span><span></span><span></span></button>
                <nav class="nav" id="main-nav">
                    <a href="/en.html#services" class="nav__link">Solutions</a>
                    <a href="/en.html#cases" class="nav__link">Cases</a>
                    <a href="/en.html#founder" class="nav__link">Expertise</a>
                    <a href="/en.html#process" class="nav__link">Approach</a>
                    <a href="/en.html#contact" class="nav__link nav__link--cta">Discuss a project</a>
                </nav>
            </div>
        </div>
    </header>
    <section class="policy-hero">
        <div class="container">
            <div class="policy-hero__content">
                <span class="policy-hero__badge">Legal document</span>
                <h1 class="policy-hero__title">Privacy Policy</h1>
                <p class="policy-hero__meta">Last updated: November 21, 2026 · <a href="/privacy-policy.html">Русская версия</a></p>
            </div>
        </div>
    </section>
    <main class="policy">
        <div class="container">
            <div class="policy__layout">
                <aside class="policy__toc">
                    <h2 class="policy__toc-title">Contents</h2>
                    <nav class="policy__toc-nav">
                        <a href="#general" class="policy__toc-link">1. General provisions</a>
                        <a href="#operator" class="policy__toc-link">2. Data operator</a>
                        <a href="#data" class="policy__toc-link">3. Data we collect</a>
                        <a href="#purposes" class="policy__toc-link">4. Purposes of processing</a>
                        <a href="#legal-basis" class="policy__toc-link">5. Legal basis</a>
                        <a href="#third-parties" class="policy__toc-link">6. Data sharing</a>
                        <a href="#cookies" class="policy__toc-link">7. Cookies</a>
                        <a href="#storage" class="policy__toc-link">8. Data retention</a>
                        <a href="#rights" class="policy__toc-link">9. Your rights</a>
                        <a href="#security" class="policy__toc-link">10. Data security</a>
                        <a href="#changes" class="policy__toc-link">11. Policy changes</a>
                        <a href="#contacts" class="policy__toc-link">12. Contacts</a>
                    </nav>
                </aside>
                <article class="policy__content">
                    <section id="general" class="policy__section">
                        <h2>1. General provisions</h2>
                        <p>This Privacy Policy (the "Policy") defines how personal data of users of <strong>ahundoff.ru</strong> (the "Site") is processed and protected.</p>
                        <p>The Policy is developed in accordance with:</p>
                        <ul>
                            <li>Federal Law No. 152-FZ "On Personal Data" of July 27, 2006;</li>
                            <li>Federal Law No. 149-FZ "On Information, Information Technologies, and Information Protection" of July 27, 2006;</li>
                            <li>the Constitution of the Russian Federation;</li>
                            <li>other applicable laws and regulations of the Russian Federation.</li>
                        </ul>
                        <p>By using the Site, submitting feedback forms, or sending us messages, you confirm your agreement with this Policy.</p>
                    </section>
                    <section id="operator" class="policy__section">
                        <h2>2. Personal data operator</h2>
                        <p>The personal data operator is:</p>
                        <div class="policy__info-card">
                            <p><strong>Sole Proprietor Zohrab Seifullaevich Akhundov</strong></p>
                            <p>INN (taxpayer number): 054204225330</p>
                            <p>Email: <a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a></p>
                            <p>Phone: <a href="tel:+79295005785">+7 (929) 500-57-85</a></p>
                            <p>Address: __ADDRESS__</p>
                        </div>
                    </section>
                    <section id="data" class="policy__section">
                        <h2>3. Personal data we collect</h2>
                        <p>We process the following categories of data:</p>
                        <h3>3.1. Data you provide voluntarily</h3>
                        <ul>
                            <li><strong>Name</strong> — to personalize communication;</li>
                            <li><strong>Phone number or Telegram</strong> — to contact you and run business diagnostics;</li>
                            <li><strong>Business task description</strong> — to prepare a proposal;</li>
                            <li><strong>Correspondence data</strong> from email or messengers.</li>
                        </ul>
                        <h3>3.2. Technical data (collected automatically)</h3>
                        <ul>
                            <li>IP address;</li>
                            <li>browser and device data;</li>
                            <li>cookies;</li>
                            <li>on-site behavior (pages, time, transitions) via Yandex.Metrika;</li>
                            <li>access date and time.</li>
                        </ul>
                    </section>
                    <section id="purposes" class="policy__section">
                        <h2>4. Purposes of processing</h2>
                        <p>We process data solely for the following purposes:</p>
                        <ul>
                            <li>handling feedback form requests;</li>
                            <li>contacting you for a free business diagnostics session;</li>
                            <li>preparing proposals and project cost estimates;</li>
                            <li>entering into and performing service contracts;</li>
                            <li>improving the Site based on analytics;</li>
                            <li>complying with Russian legal requirements.</li>
                        </ul>
                        <p><strong>We do not sell your data and do not share it with third parties for advertising mailings.</strong></p>
                    </section>
                    <section id="legal-basis" class="policy__section">
                        <h2>5. Legal basis for processing</h2>
                        <ul>
                            <li><strong>User consent</strong> — when submitting a feedback form (Art. 6(1)(1) of 152-FZ);</li>
                            <li><strong>Contract performance</strong> — when entering into a service contract (Art. 6(1)(5) of 152-FZ);</li>
                            <li><strong>Legitimate interest of the operator</strong> — Site analytics and service improvement (Art. 6(1)(6) of 152-FZ);</li>
                            <li><strong>Legal requirement</strong> — in cases provided for by Russian law.</li>
                        </ul>
                    </section>
                    <section id="third-parties" class="policy__section">
                        <h2>6. Sharing data with third parties</h2>
                        <p>We do not share your personal data with third parties except in the following cases:</p>
                        <h3>6.1. Analytics services</h3>
                        <p>The Site uses <strong>Yandex.Metrika</strong>, a web analytics service provided by Yandex LLC (16 Lev Tolstoy St., Moscow, 119021, Russia). The service collects anonymized behavioral data to improve the Site.</p>
                        <p>Details on Yandex data processing: <a href="https://yandex.ru/legal/confidential/" target="_blank" rel="noopener noreferrer">https://yandex.ru/legal/confidential/</a></p>
                        <h3>6.2. Hosting provider</h3>
                        <p>The Site is hosted on servers of <strong>Timeweb LLC</strong> (7 Dimitrov St., Bldg. 2, Lit. A, St. Petersburg, 192283, Russia). The provider maintains the technical infrastructure and has no access to the content of your requests.</p>
                        <h3>6.3. Legal requirements</h3>
                        <p>We may disclose data upon requests from authorized government bodies in cases provided for by law.</p>
                        <h3>6.4. Cross-border transfer</h3>
                        <p>Personal data is stored and processed within the Russian Federation. No cross-border transfer is performed.</p>
                    </section>
                    <section id="cookies" class="policy__section">
                        <h2>7. Cookies</h2>
                        <p>The Site uses cookies for correct operation and to improve user experience.</p>
                        <h3>7.1. Types of cookies used</h3>
                        <ul>
                            <li><strong>Essential</strong> — provide basic Site functionality;</li>
                            <li><strong>Analytics</strong> — help understand how users interact with the Site (via Yandex.Metrika).</li>
                        </ul>
                        <h3>7.2. How to disable cookies</h3>
                        <p>You can disable cookies in your browser settings. Note that this may affect some Site features.</p>
                    </section>
                    <section id="storage" class="policy__section">
                        <h2>8. Data retention periods</h2>
                        <ul>
                            <li><strong>Site form submissions (name, contact, message, IP address)</strong> — up to 12 months from receipt or until consent is withdrawn;</li>
                            <li><strong>Contract-related client data</strong> — for the contract term plus 5 years after completion (tax law requirement);</li>
                            <li><strong>Web analytics data</strong> — up to 24 months in Yandex.Metrika.</li>
                        </ul>
                        <p>Once the processing purpose is achieved or consent is withdrawn, data is deleted or anonymized within 30 days.</p>
                    </section>
                    <section id="rights" class="policy__section">
                        <h2>9. Your rights</h2>
                        <p>Under Article 14 of 152-FZ you have the right to:</p>
                        <ul>
                            <li>obtain information about the processing of your data;</li>
                            <li>request correction of your data;</li>
                            <li>request blocking or deletion of your data;</li>
                            <li>withdraw consent to processing;</li>
                            <li>object to processing;</li>
                            <li>appeal the operator's actions to Roskomnadzor or a court.</li>
                        </ul>
                        <p>To exercise your rights, send a request to <a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a>. We respond within 10 business days.</p>
                    </section>
                    <section id="security" class="policy__section">
                        <h2>10. Data security</h2>
                        <p>We apply organizational and technical protection measures:</p>
                        <ul>
                            <li>encryption in transit (HTTPS/SSL);</li>
                            <li>restricted access to data;</li>
                            <li>regular software updates;</li>
                            <li>protection against unauthorized access (firewalls, authentication);</li>
                            <li>strict NDA regime for client projects.</li>
                        </ul>
                    </section>
                    <section id="changes" class="policy__section">
                        <h2>11. Policy changes</h2>
                        <p>We reserve the right to amend this Policy. The current version is always available on this page. Material changes will be announced on the Site.</p>
                        <p>Current version effective from November 21, 2026.</p>
                    </section>
                    <section id="contacts" class="policy__section">
                        <h2>12. Contacts for data-related questions</h2>
                        <p>For any questions regarding personal data processing, contact:</p>
                        <div class="policy__info-card">
                            <p><strong>Sole Proprietor Zohrab Seifullaevich Akhundov</strong></p>
                            <p>Email: <a href="mailto:info@ahundoff.ru">info@ahundoff.ru</a></p>
                            <p>Phone: <a href="tel:+79295005785">+7 (929) 500-57-85</a></p>
                            <p>Telegram: <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer">@zohrab_ahundov</a></p>
                        </div>
                        <p>Response time — within 10 business days.</p>
                    </section>
                </article>
            </div>
        </div>
    </main>
    <section class="policy-cta">
        <div class="container">
            <div class="policy-cta__content">
                <h2>Still have questions?</h2>
                <p>Contact us — we will answer any questions about privacy and cooperation.</p>
                <a href="/en.html#contact" class="btn btn--primary">Discuss a project</a>
            </div>
        </div>
    </section>
    <footer class="footer">
        <div class="container">
            <div class="footer__content">
                <div class="footer__brand">
                    <a href="/en.html" class="logo">Ahundoff<span class="logo__dot">.</span></a>
                    <p class="footer__tagline">AI solutions for business</p>
                    <p class="footer__legal">Sole Proprietor Zohrab Seifullaevich Akhundov | INN 054204225330</p>
                </div>
                <div class="footer__links">
                    <a href="/en.html#services">Solutions</a>
                    <a href="/en.html#cases">Cases</a>
                    <a href="/en.html#founder">Expertise</a>
                    <a href="/en.html#process">Approach</a>
                    <a href="/en.html#contact">Contacts</a>
                    <a href="/privacy-policy-en.html">Privacy Policy</a>
                </div>
                <div class="footer__socials">
                    <a href="https://wa.me/79295005785" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="WhatsApp"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg></a>
                    <a href="https://t.me/zohrab_ahundov" target="_blank" rel="noopener noreferrer" class="footer__social-link" aria-label="Telegram"><svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg></a>
                </div>
                <div class="footer__copyright">© 2026 Ahundoff Studio. All rights reserved.</div>
            </div>
        </div>
    </footer>
    <script src="/script.js?v=2026-11-21-2" defer></script>
</body>
</html>
"""

METRIKA_JS = """
/* Yandex.Metrika — единый загрузчик (вынесен из inline-скриптов всех страниц) */
(() => {
    const initYandexMetrika = () => {
        (function(m,e,t,r,i,k,a){
            m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
            m[i].l=1*new Date();
            for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
            k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
        })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=113749509', 'ym');
        ym(113749509, 'init', {
            ssr: true, webvisor: false, clickmap: true, ecommerce: "dataLayer",
            referrer: document.referrer, url: location.href,
            accurateTrackBounce: true, trackLinks: true
        });
        if (location.pathname.indexOf('success.html') !== -1) {
            ym(113749509, 'reachGoal', 'form_success_page');
        }
    };
    if ('requestIdleCallback' in window) { requestIdleCallback(initYandexMetrika); }
    else { setTimeout(initYandexMetrika, 2000); }
})();
"""

UTIL_CSS = """
/* PATCH 2026-11-21-2: утилитарные классы вместо inline-стилей */
.u-offscreen { position: absolute; left: -9999px; opacity: 0; }
.u-noscript-note { color: #F87171; margin: 0 0 1rem; }
.form-message { margin-top: 1rem; text-align: center; }
.form-success { display: none; text-align: center; padding: 2rem 0; }
.form-success__icon { color: var(--accent); margin: 0 auto 1.5rem; }
.form-success__title { font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-primary); }
.form-success__text { color: var(--text-secondary); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6; }
.hero__actions--center { justify-content: center; }
"""

MSG_PHP = """
// PATCH 2026-11-21-2: локализация ответов (en.html отправляет lang=en)
$lang = (($_POST['lang'] ?? 'ru') === 'en') ? 'en' : 'ru';
$MSG = [
    'ru' => [
        'method' => 'Метод не разрешен',
        'forbidden' => 'Доступ запрещен',
        'rate' => 'Слишком много заявок. Попробуйте через час.',
        'name_required' => 'Имя обязательно для заполнения',
        'name_short' => 'Имя слишком короткое',
        'name_long' => 'Имя слишком длинное',
        'phone_required' => 'Телефон или Telegram обязателен',
        'phone_long' => 'Слишком длинный контакт (максимум 40 символов)',
        'phone_invalid' => 'Некорректный контакт: укажите телефон или Telegram (@username)',
        'consent' => 'Необходимо согласие на обработку персональных данных',
        'company' => 'Укажите компанию или сферу деятельности',
        'message_long' => 'Сообщение слишком длинное',
        'about_long' => 'Описание слишком длинное',
        'mail_fail' => 'Ошибка отправки. Пожалуйста, напишите нам напрямую: info@ahundoff.ru или в Telegram.',
        'ok' => 'Заявка успешно отправлена'
    ],
    'en' => [
        'method' => 'Method not allowed',
        'forbidden' => 'Access denied',
        'rate' => 'Too many requests. Please try again in an hour.',
        'name_required' => 'Name is required',
        'name_short' => 'Name is too short',
        'name_long' => 'Name is too long',
        'phone_required' => 'Phone or Telegram is required',
        'phone_long' => 'Contact is too long (max 40 characters)',
        'phone_invalid' => 'Invalid contact: enter a phone number or Telegram (@username)',
        'consent' => 'Consent to personal data processing is required',
        'company' => 'Please specify your company or field of activity',
        'message_long' => 'Message is too long',
        'about_long' => 'Description is too long',
        'mail_fail' => 'Sending failed. Please contact us directly: info@ahundoff.ru or Telegram.',
        'ok' => 'Request sent successfully'
    ]
];
"""

SEND_REPL = [
    ("'error' => 'Метод не разрешен'", "'error' => $MSG[$lang]['method']"),
    ("'error' => 'Доступ запрещен'", "'error' => $MSG[$lang]['forbidden']"),
    ("'error' => 'Слишком много заявок. Попробуйте через час.'", "'error' => $MSG[$lang]['rate']"),
    ("$errors[] = 'Имя обязательно для заполнения';", "$errors[] = $MSG[$lang]['name_required'];"),
    ("$errors[] = 'Имя слишком короткое';", "$errors[] = $MSG[$lang]['name_short'];"),
    ("$errors[] = 'Имя слишком длинное';", "$errors[] = $MSG[$lang]['name_long'];"),
    ("$errors[] = 'Телефон или Telegram обязателен';", "$errors[] = $MSG[$lang]['phone_required'];"),
    ("$errors[] = 'Слишком длинный контакт (максимум 40 символов)';", "$errors[] = $MSG[$lang]['phone_long'];"),
    ("$errors[] = 'Некорректный контакт: укажите телефон или Telegram (@username)';", "$errors[] = $MSG[$lang]['phone_invalid'];"),
    ("$errors[] = 'Необходимо согласие на обработку персональных данных';", "$errors[] = $MSG[$lang]['consent'];"),
    ("$errors[] = 'Укажите компанию или сферу деятельности';", "$errors[] = $MSG[$lang]['company'];"),
    ("$errors[] = 'Сообщение слишком длинное';", "$errors[] = $MSG[$lang]['message_long'];"),
    ("$errors[] = 'Описание слишком длинное';", "$errors[] = $MSG[$lang]['about_long'];"),
    ("'error' => 'Ошибка отправки. Пожалуйста, напишите нам напрямую: info@ahundoff.ru или в Telegram.'", "'error' => $MSG[$lang]['mail_fail']"),
    ("'message' => 'Заявка успешно отправлена'", "'message' => $MSG[$lang]['ok']"),
]

STYLE_REPL = [
    ('style="position:absolute;left:-9999px;opacity:0;"', 'class="u-offscreen"'),
    ('style="position:absolute; left:-9999px;"', 'class="u-offscreen"'),
    ('<p style="color:#F87171;margin:0 0 1rem;">', '<p class="u-noscript-note">'),
    ('id="formMessage" style="margin-top:1rem;text-align:center;"', 'id="formMessage" class="form-message"'),
    ('id="p-formMessage" style="margin-top:1rem;text-align:center;"', 'id="p-formMessage" class="form-message"'),
    ('id="successBlock" style="display:none; text-align:center; padding: 2rem 0;"', 'id="successBlock" class="form-success"'),
    ('style="color: var(--accent); margin: 0 auto 1.5rem;"', 'class="form-success__icon"'),
    ('style="font-size: 1.5rem; margin-bottom: 1rem; color: var(--text-primary);"', 'class="form-success__title"'),
    ('style="color: var(--text-secondary); margin-bottom: 2rem; max-width: 500px; margin-left: auto; margin-right: auto; line-height: 1.6;"', 'class="form-success__text"'),
    ('class="hero__actions" style="justify-content: center;"', 'class="hero__actions hero__actions--center"'),
]

USD = [
    ("from 250,000 ₽", "from $3,000"), ("from 350,000 ₽", "from $4,300"),
    ("from 300,000 ₽", "from $3,700"), ("from 200,000 ₽", "from $2,400"),
    ("from 500,000 ₽", "from $6,100"), ("from 50,000 ₽", "from $600"),
    ("from 100,000 ₽", "from $1,200"), ("from 150,000 ₽", "from $1,800"),
    ("from 400,000 ₽", "from $4,900"),
]

FONT_URL = "https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Unbounded:wght@500;600;700&display=swap"

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--dir", default="ahundoff")
    ap.add_argument("--address", default="", help="Адрес регистрации ИП по ЕГРИП (для политик)")
    ap.add_argument("--push", action="store_true")
    ap.add_argument("--keep-success", action="store_true", help="не удалять success.html")
    a = ap.parse_args()
    d = a.dir
    if not os.path.isdir(os.path.join(d, ".git")):
        subprocess.run(["git", "clone", REPO_URL, d], check=True)
    subprocess.run(["git", "-C", d, "pull", "--ff-only"], check=False)

    print("== S1: flow.js -> script.js ==")
    flow = load(d, "flow.js") if os.path.exists(os.path.join(d, "flow.js")) else ""
    s = load(d, "script.js")
    if flow and "light-flow" not in s:
        save(d, "script.js", s.rstrip() + "\n\n" + flow)
    for f in ["index.html", "en.html"]:
        rpatch(d, f, r'[ \t]*<script src="[^"]*flow\.js[^"]*"[^>]*></script>\n?', "", required=False)
    if os.path.exists(os.path.join(d, "flow.js")):
        os.remove(os.path.join(d, "flow.js")); changed.append("flow.js (удалён)")

    print("== S2: единая Метрика ==")
    s = load(d, "script.js")
    if "initYandexMetrika" not in s:
        patch(d, "script.js", "document.documentElement.classList.add('js');",
              "document.documentElement.classList.add('js');\n" + METRIKA_JS)
    for f in ["index.html", "en.html", "partners.html"]:
        rpatch(d, f, r'[ \t]*<script type="text/javascript">\s*function initYandexMetrika\(\).*?</script>\n?', "", required=False)
    if not a.keep_success and os.path.exists(os.path.join(d, "success.html")):
        os.remove(os.path.join(d, "success.html")); changed.append("success.html (удалён)")

    print("== S3: шрифты без onload ==")
    font_pat = re.escape(f'<link rel="stylesheet" href="{FONT_URL}" media="print" onload="this.media=\'all\'">') + \
               r'\s*<noscript>\s*' + re.escape(f'<link rel="stylesheet" href="{FONT_URL}">') + r'\s*</noscript>'
    for f in ["index.html", "en.html", "partners.html"]:
        rpatch(d, f, font_pat, f'<link rel="stylesheet" href="{FONT_URL}">', required=False)

    print("== S4: inline-стили -> классы ==")
    css = load(d, "styles.css")
    if ".u-offscreen" not in css:
        save(d, "styles.css", css.rstrip() + "\n" + UTIL_CSS)
    for f in ["index.html", "en.html", "partners.html"]:
        t = load(d, f)
        for old, new in STYLE_REPL: t = t.replace(old, new)
        save(d, f, t)

    print("== S5: <style> партнёрки -> styles.css ==")
    p = load(d, "partners.html")
    m = re.search(r'<style>\s*(\.partners-hero\b.*?)</style>', p, re.S)
    if m:
        css = load(d, "styles.css")
        if ".partners-hero" not in css:
            save(d, "styles.css", css.rstrip() + "\n\n/* Стили страницы partners (вынесены из inline) */\n" + m.group(1).strip() + "\n")
        save(d, "partners.html", p.replace(m.group(0), ""))

    print("== F9: локализация формы ==")
    patch(d, "script.js",
          "                try {\n                    const r = await fetch('/send.php', { method:'POST', body:new FormData(form),\n                        headers:{'X-Requested-With':'XMLHttpRequest'} });",
          "                try {\n                    const formData = new FormData(form);\n                    formData.append('lang', I18N.lang);\n                    const r = await fetch('/send.php', { method:'POST', body:formData,\n                        headers:{'X-Requested-With':'XMLHttpRequest'} });", required=False)
    rpatch(d, "script.js", r'[ \t]*//Toast\.show\([^\n]*\);\n', "", required=False)  # R2
    sp = load(d, "send.php")
    if "$MSG" not in sp:
        patch(d, "send.php", "header('X-Frame-Options: DENY');", "header('X-Frame-Options: DENY');\n" + MSG_PHP)
    for old, new in SEND_REPL: patch(d, "send.php", old, new, required=False)

    print("== F3-F8: EN-версия, USD ==")
    for old, new in USD: patch(d, "en.html", old, new, required=False)
    patch(d, "en.html", '<div class="stat__number" data-target="50" data-suffix="B ₽">50B ₽</div>',
          '<div class="stat__number" data-target="610" data-suffix="M USD">610M USD</div>', required=False)
    patch(d, "en.html", "<strong>50 billion ₽</strong>", "<strong>610 million USD</strong>", required=False)
    patch(d, "en.html", "We fix the budget before work begins.</p>",
          "We fix the budget before work begins. USD prices are indicative at the current exchange rate; the final amount is fixed in the contract.</p>", required=False)
    patch(d, "en.html", '"@type": "ProfessionalService",', '"@type": "ProfessionalService",\n        "inLanguage": "en",', required=False)
    patch(d, "en.html", 'placeholder="+7 (___) ___-__-__ or @username"', 'placeholder="Phone (+country code) or @username"', required=False)

    print("== F10: EN-политика ==")
    addr = a.address.strip() or "[REGISTERED ADDRESS OF THE SOLE PROPRIETOR PER THE STATE REGISTER]"
    if not os.path.exists(os.path.join(d, "privacy-policy-en.html")):
        save(d, "privacy-policy-en.html", EN_POLICY.replace("__ADDRESS__", addr))
    patch(d, "en.html", '<a href="/privacy-policy.html">personal data</a>', '<a href="/privacy-policy-en.html">personal data</a>', required=False)
    patch(d, "partials/footer-en.php", '<a href="/privacy-policy.html">Privacy Policy</a>', '<a href="/privacy-policy-en.html">Privacy Policy</a>', required=False)

    print("== F11 + R1: RU-политика ==")
    patch(d, "privacy-policy.html", "Последнее обновление: 28 октября 2026 года",
          'Последнее обновление: 21 ноября 2026 года · <a href="/privacy-policy-en.html">English version</a>', required=False)
    rpatch(d, "privacy-policy.html", r'[ \t]*<li><strong>Данные из формы обратной связи</strong>[^\n]*</li>\n', "", required=False)
    pp = load(d, "privacy-policy.html")
    if "<script" not in pp:
        patch(d, "privacy-policy.html", "</body>", '    <script src="/script.js?v=2026-11-21-2" defer></script>\n</body>')
    if a.address.strip():
        patch(d, "privacy-policy.html", "Адрес: Российская Федерация", "Адрес: " + a.address.strip(), required=False)
    else:
        print("  ВНИМАНИЕ: --address не задан — адрес оператора в политиках остался заглушкой!")

    print("== F1/S6: .htaccess ==")
    patch(d, ".htaccess", 'ExpiresByType text/html "access plus 1 hour"', 'ExpiresByType text/html "access plus 0 seconds"', required=False)
    ht = load(d, ".htaccess")
    if "no-cache, must-revalidate" not in ht:
        patch(d, ".htaccess", "    Header unset X-Powered-By\n</IfModule>",
              "    Header unset X-Powered-By\n</IfModule>\n\n<FilesMatch \"\\.(html|htm)$\">\n    Header always set Cache-Control \"no-cache, must-revalidate\"\n</FilesMatch>")
    rpatch(d, ".htaccess", r"Header always set Content-Security-Policy \"[^\"]*\"",
           'Header always set Content-Security-Policy "default-src \'self\'; script-src \'self\' https://mc.yandex.ru https://cdn.jsdelivr.net \'unsafe-eval\'; style-src \'self\' https://fonts.googleapis.com \'unsafe-inline\'; font-src \'self\' https://fonts.gstatic.com; img-src \'self\' data: https:; connect-src \'self\' https://mc.yandex.ru https://fonts.googleapis.com https://fonts.gstatic.com; frame-ancestors \'self\'; base-uri \'self\'; form-action \'self\';"', required=False)

    print("== F2/S7: версии ассетов ==")
    for f in ["index.html", "en.html", "partners.html", "privacy-policy.html"]:
        t = load(d, f)
        t2 = re.sub(r"\?v=2026-11-21(?!-)", VER_NEW, t)
        if t2 != t: save(d, f, t2)

    print("== Проверки ==")
    en = load(d, "en.html"); s = load(d, "script.js"); sp = load(d, "send.php"); ht = load(d, ".htaccess")
    checks = [
        ("en.html без ₽", "₽" not in en),
        ("en.html без onload=", "onload=" not in en),
        ("en.html без inline JS", '<script type="text/javascript">' not in en),
        ("en.html USD-цены", "from $3,000" in en and "610M USD" in en),
        ("en.html ссылка на EN-политику", "privacy-policy-en.html" in en),
        ("index/en/partners без style=", all('style="' not in load(d, f) for f in ["index.html", "en.html", "partners.html"])),
        ("index/en/partners без inline Метрики", all("initYandexMetrika" not in load(d, f) for f in ["index.html", "en.html", "partners.html"])),
        ("script.js: Метрика + flow + lang", "initYandexMetrika" in s and "light-flow" in s and "formData.append('lang'" in s),
        ("script.js: синтаксис (баланс)", balance(s)),
        ("send.php: $MSG", "$MSG" in sp and "'error' => 'Доступ запрещен'" not in sp),
        (".htaccess: script-src без unsafe-inline", "script-src 'self' https://mc.yandex.ru https://cdn.jsdelivr.net 'unsafe-eval'" in ht),
        (".htaccess: HTML no-cache", "no-cache, must-revalidate" in ht),
        ("styles.css: утилиты + partners", ".u-offscreen" in load(d, "styles.css") and ".partners-hero" in load(d, "styles.css")),
        ("EN-политика создана", os.path.exists(os.path.join(d, "privacy-policy-en.html"))),
        ("flow.js удалён", not os.path.exists(os.path.join(d, "flow.js"))),
    ]
    ok = True
    for name, res in checks:
        print(("  OK  " if res else "  FAIL ") + name); ok = ok and res
    if not ok: sys.exit("Есть FAIL — не коммитьте, пришлите вывод мне.")

    print("\nИзменённые файлы:", ", ".join(changed))
    if a.push:
        subprocess.run(["git", "-C", d, "add", "-A"], check=True)
        subprocess.run(["git", "-C", d, "commit", "-m", "refactor+fix: consolidate JS/CSS, EN localization, USD prices, CSP hardening, cache policy (audit 21.11.2026)"], check=True)
        subprocess.run(["git", "-C", d, "push", "origin", "main"], check=True)
        print("Запушено в main.")
    else:
        print("Без --push: изменения только в локальном клоне. Залейте файлы на Timeweb или запустите с --push.")

if __name__ == "__main__":
    main()
