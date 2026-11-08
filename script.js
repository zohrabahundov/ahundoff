/**
 * Ahundoff Studio - Main JavaScript
 */

document.documentElement.classList.add('js');

(() => {
    'use strict';

    const Tracker = (() => {
        const YM_ID = 113749509;
        
        const track = (goalName) => {
            if (typeof ym !== 'undefined') {
                try {
                    ym(YM_ID, 'reachGoal', goalName);
                } catch (e) {
                    console.warn('Metrika tracking failed:', e);
                }
            }
        };

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

        return { track, initEvents };
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
                if (e.key === 'Escape' && nav.classList.contains('active')) {
                    toggleMenu(false);
                    burger.focus();
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
        const show = (message, isError = false) => {
            const toast = document.createElement('div');
            toast.className = `toast ${isError ? 'toast--error' : 'toast--success'}`;
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => toast.classList.add('toast--visible'), 10);
            setTimeout(() => {
                toast.classList.remove('toast--visible');
                setTimeout(() => toast.remove(), 300);
            }, 4000);
        };

        return { show };
    })();

    const PhoneMask = (() => {
        const init = () => {
            const phoneInput = document.getElementById('phone');
            if (!phoneInput) return;

            const formatPhone = (value) => {
                let digits = value.replace(/\D/g, '');
                
                if (digits.startsWith('8')) {
                    digits = '7' + digits.slice(1);
                }
                
                if (!digits.startsWith('7') && digits.length > 0) {
                    digits = '7' + digits;
                }
                
                digits = digits.slice(0, 11);
                
                let formatted = '';
                if (digits.length > 0) formatted = '+7';
                if (digits.length > 1) formatted += ' (' + digits.slice(1, 4);
                if (digits.length >= 5) formatted += ') ' + digits.slice(4, 7);
                if (digits.length >= 8) formatted += '-' + digits.slice(7, 9);
                if (digits.length >= 10) formatted += '-' + digits.slice(9, 11);
                
                return formatted;
            };

            phoneInput.addEventListener('input', (e) => {
                const cursorPosition = e.target.selectionStart;
                const oldValue = e.target.value;
                const newValue = formatPhone(e.target.value);
                e.target.value = newValue;
                
                const diff = newValue.length - oldValue.length;
                const newPosition = Math.max(0, cursorPosition + diff);
                e.target.setSelectionRange(newPosition, newPosition);
            });

            phoneInput.addEventListener('focus', (e) => {
                if (!e.target.value) {
                    e.target.value = '+7';
                }
            });

            phoneInput.addEventListener('blur', (e) => {
                if (e.target.value === '+7') {
                    e.target.value = '';
                }
            });

            phoneInput.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value === '+7') {
                    e.preventDefault();
                }
            });
        };

        return { init };
    })();

    const FormHandler = (() => {
        const init = () => {
            const form = document.getElementById('contactForm');
            if (!form) return;

            const submitBtn = document.getElementById('submitBtn');
            const formMessage = document.getElementById('formMessage');
            const formTimeInput = document.getElementById('formTime');

            if (formTimeInput) {
                formTimeInput.value = Math.floor(Date.now() / 1000);
            }

            const resetSubmitButton = () => {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Получить расчёт проекта';
            };

            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                
                const consentCheckbox = document.getElementById('pd-consent');
                if (consentCheckbox && !consentCheckbox.checked) {
                    Toast.show('Пожалуйста, дайте согласие на обработку персональных данных', true);
                    resetSubmitButton();
                    consentCheckbox.focus();
                    return;
                }

                submitBtn.disabled = true;
                submitBtn.textContent = 'Отправка...';
                formMessage.innerHTML = '';

                const formData = new FormData(form);

                try {
                    const response = await fetch('/send.php', {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });

                    const result = await response.json();

                    if (response.ok && result.success) {
                        Tracker.track('form_submit');
                        Toast.show('Заявка успешно отправлена!');
                        form.reset();
                        if (formTimeInput) formTimeInput.value = Math.floor(Date.now() / 1000);
                        
                        setTimeout(() => {
                            window.location.href = result.redirect || '/success.html';
                        }, 1500);
                    } else {
                        const errorMsg = typeof result.error === 'string' 
                            ? result.error.replace(/[<>]/g, '') 
                            : 'Ошибка отправки';
                        Toast.show(errorMsg, true);
                        resetSubmitButton();
                    }
                } catch (error) {
                    Toast.show('Ошибка сети. Напишите напрямую: info@ahundoff.ru', true);
                    resetSubmitButton();
                }
            });
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

            if (prefersReducedMotion) {
                stats.forEach(stat => stat.classList.add('visible'));
                return;
            }

            stats.forEach((stat, index) => {
                stat.dataset.animIndex = String(index);
            });

            const visibilityObserver = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const index = parseInt(entry.target.dataset.animIndex, 10) || 0;
                        setTimeout(() => {
                            entry.target.classList.add('visible');
                        }, index * 100);
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.3 });

            stats.forEach(stat => visibilityObserver.observe(stat));

            const counterObserver = new IntersectionObserver((entries, obs) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const counter = entry.target.querySelector('.stat__number');
                        if (counter && !counter.dataset.animated) {
                            counter.dataset.animated = 'true';
                            animateCounter(counter);
                        }
                        obs.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.5 });

            stats.forEach(stat => counterObserver.observe(stat));
        };

        return { init };
    })();

    const FAQ = (() => {
        const init = () => {
            const items = document.querySelectorAll('.faq__item');
            if (!items.length) return;

            items.forEach(item => {
                const question = item.querySelector('.faq__question');
                
                question.addEventListener('click', () => {
                    const isActive = item.classList.contains('active');

                    items.forEach(otherItem => {
                        if (otherItem !== item) {
                            otherItem.classList.remove('active');
                            otherItem.querySelector('.faq__question').setAttribute('aria-expanded', 'false');
                        }
                    });

                    item.classList.toggle('active');
                    question.setAttribute('aria-expanded', String(!isActive));

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

            const updateProgress = () => {
                const scrolled = (window.scrollY / (document.documentElement.scrollHeight - window.innerHeight)) * 100;
                progressBar.style.width = Math.min(scrolled, 100) + '%';
            };

            window.addEventListener('scroll', updateProgress, { passive: true });
            window.addEventListener('resize', updateProgress, { passive: true });
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
        const init = () => {
            document.querySelectorAll('.copy-btn').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    try {
                        await navigator.clipboard.writeText(btn.dataset.copy);
                        btn.classList.add('copied');
                        btn.innerHTML = '<svg width="16" height="16" aria-hidden="true"><use href="#icon-check"/></svg>';
                        Toast.show('Email скопирован в буфер обмена');
                        
                        setTimeout(() => {
                            btn.classList.remove('copied');
                            btn.innerHTML = '<svg width="16" height="16" aria-hidden="true"><use href="#icon-code"/></svg>';
                        }, 2000);
                    } catch (err) {
                        Toast.show('Не удалось скопировать', true);
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
            let animationId;
            let particles = [];
            let mouse = { x: 0, y: 0 };

            const resize = () => {
                canvas.width = canvas.offsetWidth;
                canvas.height = canvas.offsetHeight;
                initParticles();
            };

            const initParticles = () => {
                particles = [];
                const spacing = 60;
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
                if (document.hidden || reducedMotion.matches || document.body.classList.contains('flow-paused')) {
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

            window.addEventListener('resize', () => {
                cancelAnimationFrame(animationId);
                resize();
                draw();
            });

            canvas.addEventListener('mousemove', handleMouseMove);
            canvas.addEventListener('mouseleave', handleMouseLeave);
            const resume = () => { if (!animationId) draw(); };
            document.addEventListener('flow-motion-change', resume);
            document.addEventListener('visibilitychange', resume);
            reducedMotion.addEventListener('change', resume);
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

    document.addEventListener('DOMContentLoaded', () => {
        MobileMenu.init();
        SmoothScroll.init();
        FormHandler.init();
        ScrollAnimations.init();
        Counters.init();
        FAQ.init();
        Tracker.initEvents();
        
        ScrollProgress.init();
        BackToTop.init();
        CopyToClipboard.init();
        ProcessTimeline.init();
        StickyBar.init();
        PhoneMask.init();
        HeroGrid.init();
        MobileTooltip.init();
    });
})();
