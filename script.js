/**
 * Ahundoff Studio - Main JavaScript
 */

document.documentElement.classList.add('js');

/* Yandex.Metrika — загрузка ТОЛЬКО после согласия на аналитические cookie (аудит п.8).
   Раньше грузилась сразу для всех; теперь управляет модуль CookieConsent/Metrika ниже:
   consent='all' -> грузим сразу, 'essential'/нет выбора -> не грузим, показываем баннер.
   Цели, отправленные до согласия, накапливаются в Tracker.pending и шлются после accept. */


(() => {
    'use strict';

    // MODULE: i18n — локализация динамических строк.
    // Исправление уязвимости локализации: en.html подключает этот же script.js,
    // а раньше все runtime-строки (кнопка, тосты, ошибки) были захардкожены по-русски,
    // из-за чего на английской версии сайта появлялся русский текст.
    const I18N = (() => {
        const lang = (document.documentElement.getAttribute('lang') || 'ru').toLowerCase().split('-')[0];
        const STRINGS = {
            ru: {
                submitLabel: 'Получить расчёт проекта',
                sending: 'Отправка...',
                consentRequired: 'Пожалуйста, дайте согласие на обработку персональных данных',
                sendError: 'Ошибка отправки',
                networkError: 'Ошибка сети. Напишите напрямую: info@ahundoff.ru',
                emailCopied: 'Email скопирован в буфер обмена',
                copyFailed: 'Не удалось скопировать'
            },
            en: {
                submitLabel: 'Get a project estimate',
                sending: 'Sending...',
                consentRequired: 'Please consent to the processing of your personal data',
                sendError: 'Submission failed',
                networkError: 'Network error. Contact us directly: info@ahundoff.ru',
                emailCopied: 'Email copied to clipboard',
                copyFailed: 'Copy failed'
            }
        };
        const t = (key) => (STRINGS[lang] && STRINGS[lang][key]) || STRINGS.ru[key] || key;
        return { lang, t };
    })();

    // MODULE: cookie-consent — выбор пользователя по аналитическим cookie (аудит п.8).
    const CookieConsent = (() => {
        const KEY = 'ahundoff_cookie_consent';
        const get = () => { try { return localStorage.getItem(KEY); } catch (e) { return null; } };
        const set = (v) => { try { localStorage.setItem(KEY, v); } catch (e) {} };
        // Удаляем уже установленные cookie Метрики (_ym_*) при отказе от аналитики.
        const clearYmCookies = () => {
            try {
                const host = location.hostname;
                const domains = ['', host, '.' + host, '.' + host.split('.').slice(-2).join('.')];
                document.cookie.split(';').forEach(c => {
                    const name = c.split('=')[0].trim();
                    if (/^_ym_/.test(name)) {
                        domains.forEach(d => {
                            document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/' + (d ? '; domain=' + d : '');
                        });
                    }
                });
            } catch (e) {}
        };
        return { KEY, get, set, clearYmCookies };
    })();

    // MODULE: metrika — загрузчик Яндекс.Метрики, вызывается только при согласии.
    const Metrika = (() => {
        const YM_ID = 113749509;
        let loaded = false;
        const load = () => {
            if (loaded) return;
            loaded = true;
            (function(m,e,t,r,i,k,a){
                m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();
                for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
                k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)
            })(window, document,'script','https://mc.yandex.ru/metrika/tag.js?id=' + YM_ID, 'ym');
            ym(YM_ID, 'init', {
                ssr: true, webvisor: false, clickmap: true, ecommerce: "dataLayer",
                referrer: document.referrer, url: location.href,
                accurateTrackBounce: true, trackLinks: true
            });
            Tracker.flush(); // цели, накопленные до согласия
        };
        return { load, isLoaded: () => loaded, YM_ID };
    })();

    const Tracker = (() => {
        const YM_ID = 113749509;
        const pending = []; // цели до согласия: шлём после accept, сбрасываем при essential

        const track = (goalName) => {
            if (typeof ym !== 'undefined') {
                try {
                    ym(YM_ID, 'reachGoal', goalName);
                } catch (e) {
                    console.warn('Metrika tracking failed:', e);
                }
            } else if (CookieConsent.get() !== 'essential') {
                pending.push(goalName); // ждём согласия (если отказ — очередь сбросится)
            }
        };
        const flush = () => {
            while (pending.length) {
                const g = pending.shift();
                if (typeof ym !== 'undefined') { try { ym(YM_ID, 'reachGoal', g); } catch (e) {} }
            }
        };
        const discard = () => { pending.length = 0; };

        const initEvents = () => {
            document.querySelectorAll('a[href^="tel:"]').forEach(link => {
                link.addEventListener('click', () => track('click_phone'));
            });
            document.querySelectorAll('a[href^="mailto:"]').forEach(link => {
                link.addEventListener('click', () => track('click_email'));
            });

            const headerCta = document.querySelector('.nav__link--cta');
            if (headerCta) {
                headerCta.addEventListener('click', () => track('click_cta_header'));
            }

            document.querySelectorAll('a[href*="mbatr.ru"], a[href*="fcc_edinstvo_bot"]').forEach(link => {
                link.addEventListener('click', () => track('click_case'));
            });

            // Трекинг кликов по плавающим кнопкам (единый столбец)
            document.querySelectorAll('.floating-action--telegram, .floating-action--whatsapp').forEach(link => {
                link.addEventListener('click', () => {
                    const type = link.classList.contains('floating-action--telegram') ? 'telegram' : 'whatsapp';
                    track(`click_messenger_${type}`);
                });
            });

            const contactSection = document.getElementById('contact');
            if (contactSection) {
                createObserver(contactSection, () => track('scroll_to_contact'), { threshold: 0.5 });
            }
        };

        return { track, initEvents, flush, discard };
    })();

    const createObserver = (target, callback, options = {}) => {
        const defaultOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px',
            once: true,
            ...options
        };

        const observer = new IntersectionObserver((entries, obs) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    callback(entry, obs);
                    if (defaultOptions.once) obs.unobserve(entry.target);
                }
            });
        }, defaultOptions);

        observer.observe(target);
        return observer;
    };

    const MobileMenu = (() => {
        const init = () => {
            const burger = document.querySelector('.burger');
            const nav = document.querySelector('.nav');
            if (!burger || !nav) return;

            const toggleMenu = (show) => {
                const isActive = show !== undefined ? show : !nav.classList.contains('active');
                
                nav.classList.toggle('active', isActive);
                burger.classList.toggle('active', isActive);
                burger.setAttribute('aria-expanded', String(isActive));
                
                document.body.style.overflow = isActive ? 'hidden' : '';
            };

            burger.addEventListener('click', (e) => {
                e.stopPropagation();
                toggleMenu();
            });

            document.querySelectorAll('.nav__link').forEach(link => {
                link.addEventListener('click', () => toggleMenu(false));
            });

            document.addEventListener('click', (e) => {
                if (nav.classList.contains('active') && !nav.contains(e.target) && !burger.contains(e.target)) {
                    toggleMenu(false);
                }
            });

            document.addEventListener('keydown', (e) => {
                if (!nav.classList.contains('active')) return;
                if (e.key === 'Escape') {
                    toggleMenu(false);
                    burger.focus();
                    return;
                }
                // Аудит 21.11.2026: фокус-ловушка внутри открытого меню (WCAG 2.4.3).
                if (e.key === 'Tab') {
                    const focusables = [burger, ...nav.querySelectorAll('a[href]')];
                    const first = focusables[0];
                    const last = focusables[focusables.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault(); last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault(); first.focus();
                    }
                }
            });
        };

        return { init };
    })();

    const SmoothScroll = (() => {
        const init = () => {
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', (e) => {
                    const href = anchor.getAttribute('href');
                    if (href === '#' || !href) return;
                    
                    const target = document.querySelector(href);
                    if (target) {
                        e.preventDefault();
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        // Аудит 21.11.2026: переносим фокус на цель для клавиатурных пользователей.
                        if (!target.hasAttribute('tabindex')) target.setAttribute('tabindex', '-1');
                        target.focus({ preventScroll: true });

                        if (history.pushState) {
                            history.pushState(null, null, href);
                        }
                    }
                });
            });
        };

        return { init };
    })();

    const Toast = (() => {
        // Аудит 21.11.2026: единый контейнер-стек вместо наложения тостов друг на друга,
        // кнопка закрытия, увеличенное время для ошибок, корректные ARIA-роли.
        let container = null;
        const getContainer = () => {
            if (!container || !document.body.contains(container)) {
                container = document.createElement('div');
                container.className = 'toast-container';
                container.setAttribute('aria-live', 'polite');
                container.setAttribute('aria-relevant', 'additions');
                document.body.appendChild(container);
            }
            return container;
        };
        const show = (message, isError = false) => {
            const toast = document.createElement('div');
            toast.className = `toast ${isError ? 'toast--error' : 'toast--success'}`;
            toast.setAttribute('role', isError ? 'alert' : 'status');

            const text = document.createElement('span');
            text.className = 'toast__text';
            text.textContent = message;
            toast.appendChild(text);

            const close = document.createElement('button');
            close.type = 'button';
            close.className = 'toast__close';
            close.setAttribute('aria-label', I18N.lang === 'en' ? 'Close' : 'Закрыть');
            close.textContent = '×';
            toast.appendChild(close);

            getContainer().appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('toast--visible'));

            const dismiss = () => {
                toast.classList.remove('toast--visible');
                setTimeout(() => toast.remove(), 300);
            };
            close.addEventListener('click', dismiss);
            setTimeout(dismiss, isError ? 8000 : 4000);
        };

        return { show };
    })();

    const PhoneMask = (() => {
        // Универсальное поле контакта: телефон РФ (+7), международный номер (+...) или Telegram (@username).
        const attach = (input) => {
            const formatRu = (value) => {
                let digits = value.replace(/\D/g, '');
                if (digits.startsWith('8')) digits = '7' + digits.slice(1);
                if (!digits.startsWith('7') && digits.length > 0) digits = '7' + digits;
                digits = digits.slice(0, 11);
                let out = '';
                if (digits.length > 0) out = '+7';
                if (digits.length > 1) out += ' (' + digits.slice(1, 4);
                if (digits.length >= 5) out += ') ' + digits.slice(4, 7);
                if (digits.length >= 8) out += '-' + digits.slice(7, 9);
                if (digits.length >= 10) out += '-' + digits.slice(9, 11);
                return out;
            };
            const mode = (v) => {
                const t = v.trim();
                if (t.startsWith('@')) return 'telegram';
                // Аудит 21.11.2026: если вводят буквы без цифр (Telegram без @) — не стираем,
                // а автоматически подставляем @. Раньше ввод "durov" превращался в пустую строку.
                if (/^[A-Za-z]/.test(t) && !/\d/.test(t)) return 'telegram';
                const d = t.replace(/\D/g, '');
                if (t.startsWith('+') && d.length > 0 && !d.startsWith('7')) return 'intl';
                return 'ru';
            };
            input.addEventListener('input', (e) => {
                const el = e.target;
                const v = el.value;
                if (v.trim() === '+') return; // даём начать международный номер
                const m = mode(v);
                if (m === 'telegram') {
                    el.value = '@' + v.replace(/[^A-Za-z0-9_]/g, '').slice(0, 32);
                    return;
                }
                if (m === 'intl') {
                    el.value = '+' + v.replace(/\D/g, '').slice(0, 15);
                    return;
                }
                // РФ-маска с сохранением позиции курсора
                const cursor = el.selectionStart || 0;
                let digitsBefore = 0;
                for (let i = 0; i < cursor; i++) if (/\d/.test(v[i])) digitsBefore++;
                const oldDigits = v.replace(/\D/g, '');
                const wasSevenAdded = oldDigits.length > 0 && !oldDigits.startsWith('7') && !oldDigits.startsWith('8');
                const target = digitsBefore + (wasSevenAdded ? 1 : 0);
                const nv = formatRu(v);
                let pos = nv.length, count = 0;
                for (let i = 0; i < nv.length; i++) {
                    if (/\d/.test(nv[i])) count++;
                    if (count === target) { pos = i + 1; break; }
                }
                if (pos < nv.length && /[\s\-()]/.test(nv[pos])) pos++;
                el.value = nv;
                el.setSelectionRange(pos, pos);
            });
            input.addEventListener('blur', (e) => {
                const v = e.target.value;
                if (v === '+7' || v === '+7 (' || v === '+' || v === '@') e.target.value = '';
            });
        };
        const init = () => {
            ['phone', 'p-contact'].forEach(id => {
                const el = document.getElementById(id);
                if (el) attach(el);
            });
        };
        return { init };
    })();

    const FormHandler = (() => {
        const bind = (cfg) => {
            const form = document.getElementById(cfg.form);
            if (!form || form.dataset.bound) return;
            form.dataset.bound = '1';
            const submitBtn = document.getElementById(cfg.submit);
            const formMessage = document.getElementById(cfg.message);
            const consent = document.getElementById(cfg.consent);
            const successBlock = cfg.success ? document.getElementById(cfg.success) : null;
            const timeInput = form.querySelector('input[name="form_time"]');
            const setTime = () => { if (timeInput) timeInput.value = Math.floor(Date.now()/1000); };
            setTime();
            // Аудит 21.11.2026: обновляем метку времени при возврате на вкладку и при первом вводе,
            // чтобы форма, открытая >24ч, не отклонялась сервером как устаревшая.
            window.addEventListener('focus', setTime);
            form.addEventListener('input', setTime, { once: true });

            const resetBtn = cfg.reset ? document.getElementById(cfg.reset) : null;
            const showForm = () => { if (successBlock) successBlock.style.display='none';
                form.style.display='flex'; form.reset(); setTime(); formMessage.innerHTML=''; };
            if (resetBtn) resetBtn.addEventListener('click', showForm);

            const send = async (isRetry) => {
                const formData = new FormData(form);
                formData.append('lang', I18N.lang);
                // Таймаут запроса: кнопка не зависает навсегда при обрыве сети.
                const controller = new AbortController();
                const timer = setTimeout(() => controller.abort(), 15000);
                try {
                    const r = await fetch('/send.php', { method:'POST', body:formData,
                        headers:{'X-Requested-With':'XMLHttpRequest'}, signal: controller.signal });
                    const res = await r.json();
                    if (r.ok && res.success) { Tracker.track(cfg.goal);
                        if (successBlock) { form.style.display='none'; successBlock.style.display='block';
                            successBlock.scrollIntoView({behavior:'smooth',block:'center'}); }
                        else { Toast.show(I18N.lang==='en'?'Request sent! We will contact you soon.':'Заявка отправлена! Мы свяжемся с вами.'); showForm(); }
                    } else if (res.code === 'form_expired' && !isRetry) {
                        // Форма устарела — обновляем время и повторяем один раз (лид не теряется).
                        setTime();
                        await send(true);
                        return;
                    } else { Toast.show(typeof res.error==='string'?res.error.replace(/[<>]/g,''):I18N.t('sendError'), true); }
                } catch (err) { console.error(err); Toast.show(I18N.t('networkError'), true); }
                finally { clearTimeout(timer); }
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                if (consent && !consent.checked) { Toast.show(I18N.t('consentRequired'), true); consent.focus(); return; }
                submitBtn.disabled = true; submitBtn.textContent = I18N.t('sending'); formMessage.innerHTML='';
                await send(false);
                submitBtn.disabled = false; submitBtn.textContent = submitBtn.dataset.defaultLabel || I18N.t('submitLabel');
            });
        };
        const init = () => {
            bind({form:'contactForm', submit:'submitBtn', message:'formMessage', consent:'pd-consent',
                  success:'successBlock', reset:'resetFormBtn', goal:'form_submit'});
            bind({form:'partnersForm', submit:'p-submitBtn', message:'p-formMessage', consent:'p-pd-consent',
                  success:null, reset:null, goal:'partner_submit'});
        };
        return { init };
    })();

    const ScrollAnimations = (() => {
        const init = () => {
            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            
            const animatedElements = document.querySelectorAll(
                '.service-card, .case-card, .why__item, .industry-card'
            );

            animatedElements.forEach(el => {
                if (prefersReducedMotion) {
                    el.classList.add('animated');
                    return;
                }

                createObserver(el, (entry) => {
                    entry.target.classList.add('animated');
                });
            });
        };

        return { init };
    })();

    const Counters = (() => {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const easeOutExpo = (t) => t === 1 ? 1 : 1 - Math.pow(2, -10 * t);

        const animateCounter = (element) => {
            const target = parseFloat(element.dataset.target) || 0;
            const suffix = element.dataset.suffix || '';
            const duration = 2000;
            const startTime = performance.now();

            const update = (currentTime) => {
                const elapsed = currentTime - startTime;
                const progress = Math.min(elapsed / duration, 1);
                const easedProgress = easeOutExpo(progress);
                const currentValue = Math.floor(easedProgress * target);

                element.textContent = currentValue + suffix;

                if (progress < 1) {
                    requestAnimationFrame(update);
                } else {
                    element.textContent = target + suffix;
                }
            };

            requestAnimationFrame(update);
        };

        const init = () => {
            const stats = document.querySelectorAll('.stat');
            if (!stats.length) return;

            // Для пользователей с отключенной анимацией сразу показываем финальные значения
            if (prefersReducedMotion) {
                stats.forEach(stat => {
                    stat.classList.add('visible');
                    const counter = stat.querySelector('.stat__number');
                    if (counter && !counter.dataset.animated) {
                        counter.dataset.animated = 'true';
                        counter.textContent = counter.dataset.target + counter.dataset.suffix;
                    }
                });
                return;
            }

            // ЕДИНЫЙ наблюдатель вместо двух
            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const stat = entry.target;
                        const index = parseInt(stat.dataset.animIndex, 10) || 0;
                        
                        // 1. Анимация появления карточки
                        setTimeout(() => {
                            stat.classList.add('visible');
                        }, index * 100);

                        // 2. Запуск счетчика с небольшой дополнительной задержкой (+300мс), 
                        // чтобы цифры начинали бежать, когда карточка уже проявилась (выглядит дороже)
                        setTimeout(() => {
                            const counter = stat.querySelector('.stat__number');
                            if (counter && !counter.dataset.animated) {
                                counter.dataset.animated = 'true';
                                animateCounter(counter);
                            }
                        }, index * 100 + 300);

                        // Перестаем наблюдать за элементом после срабатывания
                        obs.unobserve(stat);
                    }
                });
            }, { threshold: 0.3 }); // Один порог срабатывания

            // Инициализируем индексы и запускаем наблюдение
            stats.forEach((stat, index) => {
                stat.dataset.animIndex = String(index);
                observer.observe(stat);
            });
        };

        return { init };
    })();

    const FAQ = (() => {
        const init = () => {
            const items = document.querySelectorAll('.faq__item');
            if (!items.length) return;

            items.forEach(item => {
                const question = item.querySelector('.faq__question');
                // Исходное состояние: свёрнутые ответы скрыты от скринридеров.
                const answer = item.querySelector('.faq__answer');
                if (answer && !item.classList.contains('active')) answer.setAttribute('aria-hidden', 'true');

                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');

                    items.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.faq__question').setAttribute('aria-expanded', 'false');
                            // Аудит 21.11.2026: скрываем свёрнутый ответ от скринридеров.
                            const otherAnswer = otherItem.querySelector('.faq__answer');
                            if (otherAnswer) otherAnswer.setAttribute('aria-hidden', 'true');
                        }
                    });

                    item.classList.toggle('active');
                    question.setAttribute('aria-expanded', String(!isActive));
                    const answer = item.querySelector('.faq__answer');
                    if (answer) answer.setAttribute('aria-hidden', String(isActive));

                    if (!isActive) Tracker.track('faq_open');
                });
            });
        };

        return { init };
    })();

    const ScrollProgress = (() => {
        const init = () => {
            const progressBar = document.querySelector('.scroll-progress');
            if (!progressBar) return;

            // Аудит 21.11.2026: троттлинг через rAF вместо записи стиля на каждое scroll-событие.
            let ticking = false;
            const updateProgress = () => {
                const max = document.documentElement.scrollHeight - window.innerHeight;
                const scrolled = max > 0 ? (window.scrollY / max) * 100 : 0;
                progressBar.style.width = Math.min(scrolled, 100) + '%';
                ticking = false;
            };
            const onScroll = () => {
                if (!ticking) { ticking = true; requestAnimationFrame(updateProgress); }
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', onScroll, { passive: true });
            updateProgress();
        };

        return { init };
    })();

    // ==========================================================================
    // MODULE: Back to Top (обновлённый для единого столбца)
    // ==========================================================================
    const BackToTop = (() => {
        const init = () => {
            const btn = document.querySelector('.floating-action--top');
            if (!btn) return;

            const toggleButton = () => {
                if (window.scrollY > 300) {
                    btn.classList.add('visible');
                } else {
                    btn.classList.remove('visible');
                }
            };

            window.addEventListener('scroll', toggleButton, { passive: true });
            toggleButton();

            btn.addEventListener('click', () => {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        };

        return { init };
    })();

    const CopyToClipboard = (() => {
        // Аудит 21.11.2026: fallback через execCommand для http/старых браузеров,
        // где navigator.clipboard недоступен.
        const copyText = async (text) => {
            if (navigator.clipboard) {
                try { await navigator.clipboard.writeText(text); return; }
                catch (e) { /* отказ API (permissions/http) — пробуем fallback ниже */ }
            }
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            const ok = document.execCommand('copy');
            ta.remove();
            if (!ok) throw new Error('copy failed');
        };
        const init = () => {
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();

                    try {
                        await copyText(btn.dataset.copy);
                        btn.classList.add('copied');
                        btn.innerHTML = '<svg width="16" height="16" aria-hidden="true"><use href="#icon-check"/></svg>';
                        Toast.show(I18N.t('emailCopied'));

                        setTimeout(() => {
                            btn.classList.remove('copied');
                            btn.innerHTML = '<svg width="16" height="16" aria-hidden="true"><use href="#icon-code"/></svg>';
                        }, 2000);
                    } catch (err) {
                        Toast.show(I18N.t('copyFailed'), true);
                    }
                });
            });
        };

        return { init };
    })();

    const ProcessTimeline = (() => {
        const init = () => {
            const steps = document.querySelectorAll('.process__step');
            if (!steps.length) return;

            const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

            if (prefersReducedMotion) {
                steps.forEach(step => step.classList.add('animated'));
                return;
            }

            const observer = new IntersectionObserver((entries, obs) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const step = entry.target;
                        const index = Array.from(steps).indexOf(step);
                        setTimeout(() => {
                            step.classList.add('animated');
                        }, index * 200);
                        obs.unobserve(step);
                    }
                });
            }, { threshold: 0.3 });

            steps.forEach(step => observer.observe(step));
        };

        return { init };
    })();

     const StickyBar = (() => {
        const init = () => {
            const stickyBar = document.querySelector('.sticky-bar');
            const footer = document.querySelector('.footer');
            if (!stickyBar || !footer) return;

            // Создаем наблюдатель за появлением футера в нижней части экрана
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        // Футер виден: скрываем панель
                        stickyBar.style.opacity = '0';
                        stickyBar.style.pointerEvents = 'none';
                        stickyBar.style.transform = 'translateY(100%)'; // Плавный уезд вниз
                    } else {
                        // Футер не виден: показываем панель
                        stickyBar.style.opacity = '1';
                        stickyBar.style.pointerEvents = 'auto';
                        stickyBar.style.transform = 'translateY(0)'; // Возврат на место
                    }
                });
            }, {
                root: null, // наблюдение относительно окна браузера
                rootMargin: '0px 0px -80px 0px', // срабатывает чуть раньше, чем футер полностью перекроет панель (высота панели ~70px)
                threshold: 0 // срабатывает сразу, как только хоть 1 пиксель футера пересек границу
            });

            // Начинаем наблюдение за футером
            observer.observe(footer);
        };

        return { init };
    })();

    const HeroGrid = (() => {
        const init = () => {
            const canvas = document.querySelector('.hero__grid');
            if (!canvas) return;

            if (window.innerWidth <= 768) {
                canvas.style.display = 'none';
                return;
            }

            const ctx = canvas.getContext('2d');
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            let animationId = 0;
            let particles = [];
            let mouse = { x: -1000, y: -1000 };
            // Аудит 21.11.2026: не рисуем, когда hero вне экрана — экономим CPU/батарею.
            let heroVisible = true;

            const resize = () => {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                initParticles();
            };

            const initParticles = () => {
                particles = [];
                const spacing = Math.max(60, Math.ceil(Math.sqrt((canvas.width * canvas.height) / 900)));
                const cols = Math.ceil(canvas.width / spacing);
                const rows = Math.ceil(canvas.height / spacing);

                for (let i = 0; i < cols; i++) {
                    for (let j = 0; j < rows; j++) {
                        particles.push({
                            x: i * spacing + spacing / 2,
                            y: j * spacing + spacing / 2,
                            baseX: i * spacing + spacing / 2,
                            baseY: j * spacing + spacing / 2,
                            size: 1,
                            opacity: 0.3
                        });
                    }
                }
            };

            const draw = () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                if (document.hidden || !heroVisible || reducedMotion.matches || document.body.classList.contains('flow-paused')) {
                    animationId = 0;
                    return;
                }

                particles.forEach(p => {
                    const dx = mouse.x - p.x;
                    const dy = mouse.y - p.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    const maxDistance = 150;

                    if (distance < maxDistance) {
                        const force = (maxDistance - distance) / maxDistance;
                        p.size = 1 + force * 2;
                        p.opacity = 0.3 + force * 0.7;
                    } else {
                        p.size = 1;
                        p.opacity = 0.3;
                    }

                    ctx.beginPath();
                    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
                    ctx.fillStyle = `rgba(59, 130, 246, ${p.opacity})`;
                    ctx.fill();
                });

                for (let i = 0; i < particles.length; i++) {
                    for (let j = i + 1; j < particles.length; j++) {
                        const dx = particles[i].x - particles[j].x;
                        const dy = particles[i].y - particles[j].y;
                        const distance = Math.sqrt(dx * dx + dy * dy);

                        if (distance < 80) {
                            ctx.beginPath();
                            ctx.moveTo(particles[i].x, particles[i].y);
                            ctx.lineTo(particles[j].x, particles[j].y);
                            ctx.strokeStyle = `rgba(59, 130, 246, ${0.1 * (1 - distance / 80)})`;
                            ctx.lineWidth = 0.5;
                            ctx.stroke();
                        }
                    }
                }

                animationId = requestAnimationFrame(draw);
            };

            const handleMouseMove = (e) => {
                const rect = canvas.getBoundingClientRect();
                mouse.x = e.clientX - rect.left;
                mouse.y = e.clientY - rect.top;
            };

            const handleMouseLeave = () => {
                mouse.x = -1000;
                mouse.y = -1000;
            };

            resize();
            draw();

            // Аудит 21.11.2026: debounce resize вместо пересчёта частиц на каждое событие.
            let resizeTimer = 0;
            window.addEventListener('resize', () => {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(() => {
                    cancelAnimationFrame(animationId);
                    resize();
                    draw();
                }, 150);
            }, { passive: true });

            // Пауза, когда hero не виден (IntersectionObserver).
            if ('IntersectionObserver' in window) {
                new IntersectionObserver((entries) => {
                    heroVisible = entries[0].isIntersecting;
                    if (heroVisible && !animationId) draw();
                }, { threshold: 0 }).observe(canvas);
            }

            window.addEventListener('mousemove', handleMouseMove, { passive: true });
            document.addEventListener('mouseleave', handleMouseLeave);
            const resume = () => { if (!animationId) draw(); };
            document.addEventListener('flow-motion-change', resume);
            document.addEventListener('visibilitychange', resume);
            // addEventListener на MediaQueryList поддерживается не везде (старый Safari).
            if (typeof reducedMotion.addEventListener === 'function') reducedMotion.addEventListener('change', resume);
            else if (typeof reducedMotion.addListener === 'function') reducedMotion.addListener(resume);
        };

        return { init };
    })();

    const MobileTooltip = (() => {
        const init = () => {
            const tooltips = document.querySelectorAll('.tooltip');
            if (!tooltips.length) return;

            const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
            
            if (!isTouchDevice) return;

            let activeTooltip = null;

            const handleInteraction = (e, tooltip) => {
                e.preventDefault();
                e.stopPropagation();

                if (activeTooltip === tooltip) {
                    tooltip.classList.remove('tooltip--active');
                    activeTooltip = null;
                    return;
                }

                tooltips.forEach(t => t.classList.remove('tooltip--active'));
                tooltip.classList.add('tooltip--active');
                activeTooltip = tooltip;
            };

            tooltips.forEach(tooltip => {
                tooltip.addEventListener('click', (e) => handleInteraction(e, tooltip));
                tooltip.addEventListener('touchstart', (e) => handleInteraction(e, tooltip), { passive: false });
            });

            document.addEventListener('click', (e) => {
                if (!e.target.closest('.tooltip')) {
                    tooltips.forEach(t => t.classList.remove('tooltip--active'));
                    activeTooltip = null;
                }
            });
        };

        return { init };
    })();

    // MODULE: cookie-banner — UI уведомления с opt-out (аудит п.8).
    const CookieBanner = (() => {
        const init = () => {
            const banner = document.getElementById('cookie-banner');
            if (!banner) return;
            const acceptBtn = document.getElementById('cookie-accept');
            const essentialBtn = document.getElementById('cookie-essential');
            const show = () => { banner.hidden = false; document.body.classList.add('cookie-banner-open'); };
            const hide = () => { banner.hidden = true; document.body.classList.remove('cookie-banner-open'); };
            const choose = (value) => {
                CookieConsent.set(value);
                hide();
                if (value === 'all') {
                    Metrika.load();
                } else {
                    CookieConsent.clearYmCookies();
                    Tracker.discard();
                    // Если Метрика уже была загружена в этой сессии — перезагружаем страницу,
                    // чтобы гарантированно не осталось активного трекера.
                    if (Metrika.isLoaded()) location.reload();
                }
            };
            if (acceptBtn) acceptBtn.addEventListener('click', () => choose('all'));
            if (essentialBtn) essentialBtn.addEventListener('click', () => choose('essential'));
            document.querySelectorAll('[data-cookie-settings]').forEach(el => {
                el.addEventListener('click', (e) => { e.preventDefault(); show(); });
            });
            if (!CookieConsent.get()) show();
        };
        return { init };
    })();

    // Стартовое применение согласия (сразу, не дожидаясь DOMContentLoaded):
    // 'all' -> грузим Метрику немедленно (как раньше); 'essential' -> чистим её cookie.
    (() => {
        const consent = CookieConsent.get();
        if (consent === 'all') Metrika.load();
        else if (consent === 'essential') CookieConsent.clearYmCookies();
        // Фиксируем язык страницы для серверного авто-определения (Accept-Language + cookie).
        try { document.cookie = 'lang_choice=' + I18N.lang + '; path=/; max-age=31536000; SameSite=Lax'; } catch (e) {}
    })();

    document.addEventListener('DOMContentLoaded', () => {
        // Аудит 21.11.2026: каждый модуль инициализируется изолированно —
        // ошибка в одном не блокирует остальные (раньше карточки могли остаться невидимыми).
        const modules = [
            ['MobileMenu', MobileMenu], ['SmoothScroll', SmoothScroll], ['FormHandler', FormHandler],
            ['ScrollAnimations', ScrollAnimations], ['Counters', Counters], ['FAQ', FAQ],
            ['Tracker', { init: Tracker.initEvents }], ['ScrollProgress', ScrollProgress],
            ['BackToTop', BackToTop], ['CopyToClipboard', CopyToClipboard], ['ProcessTimeline', ProcessTimeline],
            ['StickyBar', StickyBar], ['PhoneMask', PhoneMask], ['HeroGrid', HeroGrid], ['MobileTooltip', MobileTooltip],
            ['CookieBanner', CookieBanner]
        ];
        modules.forEach(([name, mod]) => {
            try { mod.init(); } catch (err) { console.error(`[Ahundoff] ${name} init failed:`, err); }
        });
    });
})();

/* Silver and cobalt light paths. All geometry stays in the background. */
(() => {
    try {
        const hero = document.querySelector('.hero');
        if (!hero) return;

        const canvas = document.createElement('canvas');
        canvas.className = 'light-flow';
        canvas.setAttribute('aria-hidden', 'true');
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        document.body.prepend(canvas);
        document.body.classList.add('flow-enabled');

        const reduced = matchMedia('(prefers-reduced-motion: reduce)');
        let width = 0;
        let height = 0;
        let frame = 0;
        let lastTime = 0;
        let time = 0;
        let scroll = window.scrollY;
        const timeline = document.querySelector('.process__timeline');
        let timelineVisible = false;
        let timelineHeight = 0;

        // Сохраняем ссылки на наблюдатели для возможности очистки
        let timelineObserver = null;
        let timelineResizeObserver = null;

        function point(y, strand, phase) {
            const world = (y + scroll * .42) / height;
            const spread = strand * (.006 + .014 * Math.sin(world * 3.2 + .8));
            return width * (.72 + .20 * Math.sin(world * 3.8 - .5)
                + .075 * Math.sin(world * 7 + phase * .18)
                + spread + .012 * Math.sin(world * 6 + phase + strand * .12));
        }

        function path(start, end, strand, phase) {
            ctx.beginPath();
            for (let y = start; y <= end; y += 9) {
                const x = point(y, strand, phase);
                if (y === start) ctx.moveTo(x, y);
                else ctx.lineTo(x, y);
            }
        }

        function draw() {
            ctx.clearRect(0, 0, width, height);
            ctx.globalCompositeOperation = 'lighter';
            const mobile = width < 700;
            const strands = mobile ? 14 : 26;

            // Broad, faint light underneath the fine individual fibers.
            ctx.shadowColor = '#3172cb';
            ctx.shadowBlur = mobile ? 22 : 45;
            ctx.strokeStyle = 'rgba(54, 111, 209, .12)';
            ctx.lineWidth = mobile ? 28 : 60;
            path(-12, height + 18, 0, time);
            ctx.stroke();
            ctx.shadowBlur = 0;

            for (let i = 0; i < strands; i++) {
                const strand = i - strands / 2;
                const silver = i % 4 === 0;
                ctx.lineWidth = silver ? .85 : 1.1;
                const intensity = .24 + .14 * Math.sin(i * 2.1 + time * .6) ** 2;
                ctx.strokeStyle = silver
                    ? `rgba(194, 215, 239, ${intensity})`
                    : `rgba(69, 137, 239, ${intensity})`;
                path(-12, height + 18, strand, time);
                ctx.stroke();

                // The short bright packet follows precisely the same strand.
                const start = ((time * .10 + i * .13) % 1.3) * height - height * .2;
                const length = height * .13;
                const gradient = ctx.createLinearGradient(0, start, 0, start + length);
                gradient.addColorStop(0, 'rgba(85,155,255,0)');
                gradient.addColorStop(.75, silver ? 'rgba(216,232,250,.65)' : 'rgba(99,169,255,.55)');
                gradient.addColorStop(1, 'rgba(132,187,255,0)');
                ctx.strokeStyle = gradient;
                ctx.lineWidth = silver ? 1.4 : 1.8;
                path(start, start + length, strand, time);
                ctx.stroke();
            }
            ctx.globalCompositeOperation = 'source-over';
            if (timeline && timelineVisible) {
                timeline.style.setProperty('--flow-packet-y', `${((time * .13) % 1) * Math.max(0, timelineHeight - 100)}px`);
            }
        }

        function tick(now) {
            frame = 0;
            if (document.hidden || reduced.matches) return;
            if (now - lastTime >= (width < 700 ? 50 : 33)) {
                time += Math.min((now - lastTime) / 1000, .06);
                lastTime = now;
                scroll += (window.scrollY - scroll) * .14;
                draw();
            }
            frame = requestAnimationFrame(tick);
        }

        function sync() {
            cancelAnimationFrame(frame);
            frame = 0;
            const running = !reduced.matches;
            document.body.classList.toggle('flow-paused', !running);
            document.dispatchEvent(new Event('flow-motion-change'));
            draw();
            if (running && !document.hidden) {
                lastTime = performance.now();
                frame = requestAnimationFrame(tick);
            }
        }

        function resize() {
            width = window.innerWidth;
            height = window.innerHeight;
            const ratio = Math.min(window.devicePixelRatio || 1, width < 700 ? 1 : 1.5);
            canvas.width = Math.round(width * ratio);
            canvas.height = Math.round(height * ratio);
            ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
            scroll = window.scrollY;
            draw();
        }

        // Функция очистки наблюдателей (для SPA или динамического удаления)
        function cleanup() {
            cancelAnimationFrame(frame);
            frame = 0;
            if (timelineObserver) {
                timelineObserver.disconnect();
                timelineObserver = null;
            }
            if (timelineResizeObserver) {
                timelineResizeObserver.disconnect();
                timelineResizeObserver = null;
            }
            reduced.removeEventListener('change', sync);
            document.removeEventListener('visibilitychange', sync);
            window.removeEventListener('resize', resize);
            if (canvas.parentNode) {
                canvas.parentNode.removeChild(canvas);
            }
            document.body.classList.remove('flow-enabled', 'flow-paused');
        }

        // Делаем cleanup доступным извне (опционально)
        window.cleanupFlowAnimation = cleanup;

        reduced.addEventListener('change', sync);
        document.addEventListener('visibilitychange', sync);
        window.addEventListener('resize', resize, { passive: true });
        
        if (timeline) {
            timelineObserver = new IntersectionObserver(entries => {
                timelineVisible = entries[0].isIntersecting;
            });
            timelineObserver.observe(timeline);
            
            timelineResizeObserver = new ResizeObserver(() => { 
                timelineHeight = timeline.offsetHeight; 
            });
            timelineResizeObserver.observe(timeline);
        }
        
        resize();
        sync();
        
    } catch (error) {
        console.warn('Flow animation error:', error);
    }
})();
