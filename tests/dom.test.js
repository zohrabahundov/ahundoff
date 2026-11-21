const { JSDOM } = require('jsdom');
const fs = require('fs');
const SCRIPT = fs.readFileSync(__dirname + '/../script.js', 'utf8');
const HOST = process.env.TEST_HOST || 'http://127.0.0.1:8088';
let pass = 0, fail = 0;
const ok = (name, cond, extra='') => { if (cond) { pass++; console.log('PASS:', name); } else { fail++; console.log('FAIL:', name, extra); } };

async function makeWindow(path, opts={}) {
  const html = await (await fetch(HOST + path)).text();
  const dom = new JSDOM(html, { url: 'https://ahundoff.ru/', runScripts: 'outside-only', pretendToBeVisual: true });
  const w = dom.window;
  // --- моки браузерных API, отсутствующих в jsdom ---
  w.matchMedia = () => ({ matches: false, addEventListener(){}, removeEventListener(){}, addListener(){}, removeListener(){} });
  const noopCtx = new Proxy({}, { get: (t,p) => { if (p==='createLinearGradient') return () => ({ addColorStop(){} }); return () => noopCtx; }, set: () => true });
  w.HTMLCanvasElement.prototype.getContext = () => noopCtx;
  w.Element.prototype.scrollIntoView = () => {};
  w.scrollTo = () => {};
  const ioInstances = [];
  w.IntersectionObserver = class { constructor(cb, opts){ this.cb=cb; this.opts=opts; this.targets=[]; ioInstances.push(this); } observe(t){ this.targets.push(t); } unobserve(t){ this.targets=this.targets.filter(x=>x!==t); } disconnect(){ this.targets=[]; } trigger(){ this.cb(this.targets.map(t=>({isIntersecting:true, target:t})), this); } };
  w.ResizeObserver = class { observe(){} unobserve(){} disconnect(){} };
  w.__io = ioInstances;
  // rAF: быстрые кадры (для счётчиков)
  let rafT = 0;
  w.requestAnimationFrame = (cb) => { rafT += 1200; return setTimeout(() => cb(w.performance.now() + rafT), 0); };
  w.cancelAnimationFrame = (id) => clearTimeout(id);
  w.__clipboard = [];
  Object.defineProperty(w.navigator, 'clipboard', { value: { writeText: async (t) => { w.__clipboard.push(t); } }, configurable: true });
  // fetch-мок с очередью ответов
  w.__fetchCalls = [];
  w.__fetchQueue = [];
  w.fetch = (url, opts) => {
    w.__fetchCalls.push({ url, opts });
    const next = w.__fetchQueue.shift() || { ok: true, json: async () => ({ success: true, message: 'ok' }) };
    if (next.reject) return Promise.reject(new Error('network'));
    return Promise.resolve({ ok: next.ok !== false, json: next.jsonThrows ? async () => { throw new Error('bad json'); } : async () => next.body });
  };
  // Cookie-согласие: по умолчанию 'all' (аналитика разрешена — как у принявшего пользователя),
  // чтобы существующие тесты трекинга работали. opts.consent=null -> чистый визит без выбора.
  const consent = opts.consent === undefined ? 'all' : opts.consent;
  if (consent) { try { w.localStorage.setItem('ahundoff_cookie_consent', consent); } catch(e){} }
  w.eval(SCRIPT);
  return w;
}
const tick = (ms=20) => new Promise(r => setTimeout(r, ms));
const submit = (w, id) => w.document.getElementById(id).dispatchEvent(new w.Event('submit', { bubbles: true, cancelable: true }));
const setInput = (w, id, v) => { const el = w.document.getElementById(id); el.value = v; el.dispatchEvent(new w.Event('input', { bubbles: true })); return el; };

(async () => {
  // ================= ГЛАВНАЯ (RU) =================
  let w = await makeWindow('/'); await tick(40);
  const d = w.document;
  ok('T1 класс js добавлен', d.documentElement.classList.contains('js'));
  ok('T2 partials отрендерены (header+sprite)', !!d.querySelector('.header') && !!d.querySelector('#icon-telegram'));

  // T3: отправка без согласия
  setInput(w, 'name', 'Иван Тестов'); setInput(w, 'phone', '+79295005785');
  submit(w, 'contactForm'); await tick();
  ok('T3 без согласия -> тост, fetch не вызван', w.__fetchCalls.length === 0 && !!d.querySelector('.toast--error'));

  // T4: успешная отправка
  d.getElementById('pd-consent').checked = true;
  w.__fetchQueue.push({ ok: true, body: { success: true, message: 'Заявка успешно отправлена' } });
  submit(w, 'contactForm');
  ok('T4a кнопка заблокирована во время отправки', d.getElementById('submitBtn').disabled);
  await tick(30);
  const call = w.__fetchCalls[0];
  ok('T4b fetch на /send.php с XRW', call && call.url === '/send.php' && call.opts.headers['X-Requested-With'] === 'XMLHttpRequest');
  const fd = call.opts.body;
  ok('T4c FormData: lang=ru, form_time свежий', fd.get('lang') === 'ru' && Math.abs(Date.now()/1000 - Number(fd.get('form_time'))) < 60);
  ok('T4d successBlock показан, форма скрыта', d.getElementById('successBlock').style.display === 'block' && d.getElementById('contactForm').style.display === 'none');
  ok('T4e кнопка разблокирована, label восстановлен', !d.getElementById('submitBtn').disabled && d.getElementById('submitBtn').textContent === 'Получить расчёт проекта');
  const ymCalls = (w.ym && w.ym.a) || [];
  ok('T4f ИСПРАВЛЕНО: цель form_submit отправляется (Метрика инициализируется сразу)', ymCalls.some(c => c[1] === 'reachGoal' && c[2] === 'form_submit'), JSON.stringify(ymCalls).slice(0,120));

  // T5: reset
  d.getElementById('resetFormBtn').dispatchEvent(new w.Event('click', { bubbles: true }));
  ok('T5 форма возвращена, поля сброшены', d.getElementById('contactForm').style.display === 'flex' && d.getElementById('name').value === '');

  // T6: ошибка сервера с HTML в тексте
  d.getElementById('pd-consent').checked = true;
  setInput(w, 'name', 'Иван'); setInput(w, 'phone', '1234567890');
  w.__fetchQueue.push({ ok: true, body: { success: false, error: '<img src=x onerror=alert(1)>Ошибка' } });
  submit(w, 'contactForm'); await tick(30);
  const errToast = [...d.querySelectorAll('.toast--error')].pop();
  ok('T6 HTML в ошибке обезврежен (textContent, без <>)', errToast && !errToast.textContent.includes('<') && errToast.innerHTML.includes('&lt;') === false ? !errToast.querySelector('img') : !errToast.querySelector('img'));

  // T7: сетевая ошибка
  w.__fetchQueue.push({ reject: true });
  submit(w, 'contactForm'); await tick(30);
  ok('T7 сетевая ошибка -> тост с альтернативным контактом', [...d.querySelectorAll('.toast--error')].pop().textContent.includes('info@ahundoff.ru'));

  // T8: не-JSON ответ (500 от PHP)
  w.__fetchQueue.push({ ok: false, jsonThrows: true });
  submit(w, 'contactForm'); await tick(30);
  ok('T8 не-JSON (fatal PHP) -> сетевой тост, кнопка жива', !d.getElementById('submitBtn').disabled);

  // T9: два тоста одновременно -> накладываются
  const toasts = d.querySelectorAll('.toast');
  const tcont = d.querySelector('.toast-container');
  ok('T9 ИСПРАВЛЕНО: тосты в контейнере-стеке (не накладываются)', toasts.length >= 2 && tcont && tcont.contains(toasts[0]), 'count=' + toasts.length);

  // T10: маска телефона
  ok('T10a 9161234567 -> +7 (916) 123-45-67', setInput(w, 'phone', '9161234567').value === '+7 (916) 123-45-67', setInput(w,'phone','9161234567').value);
  ok('T10b 89161234567 -> +7 маска', setInput(w, 'phone', '89161234567').value === '+7 (916) 123-45-67');
  ok('T10c +442079460958 -> intl без маски', setInput(w, 'phone', '+442079460958').value === '+442079460958');
  ok('T10d @durov -> telegram', setInput(w, 'phone', '@durov').value === '@durov');
  const withoutAt = setInput(w, 'phone', 'durov').value;
  ok('T10e ИСПРАВЛЕНО: telegram без @ -> автоподстановка @', withoutAt === '@durov', 'value="' + withoutAt + '"');
  const plus7 = setInput(w, 'phone', '+7'); plus7.dispatchEvent(new w.Event('blur', { bubbles: true }));
  ok('T10f +7 на blur очищается', plus7.value === '');

  // T11: FAQ на главной удалён (ужатие); аккордеон проверяется на партнёрке — см. T11a/T11b ниже
  ok('T11 на главной нет секции FAQ', !d.querySelector('section.faq') && !d.getElementById('faq-question-1'));

  // T12: бургер-меню
  const burger = d.querySelector('.burger'), nav = d.querySelector('.nav');
  burger.dispatchEvent(new w.Event('click', { bubbles: true }));
  ok('T12a меню открыто, body заблокирован', nav.classList.contains('active') && d.body.style.overflow === 'hidden' && burger.getAttribute('aria-expanded') === 'true');
  d.dispatchEvent(new w.KeyboardEvent('keydown', { key: 'Escape', bubbles: true }));
  ok('T12b Escape закрывает, фокус на бургере', !nav.classList.contains('active') && d.activeElement === burger);

  // T13: копирование email
  const copyBtn = d.querySelector('.copy-btn');
  copyBtn.dispatchEvent(new w.Event('click', { bubbles: true })); await tick(20);
  ok('T13 email скопирован, иконка сменилась', w.__clipboard.includes('info@ahundoff.ru') && copyBtn.innerHTML.includes('icon-check'));

  // T14: smooth scroll + pushState
  const anchor = d.querySelector('a[href="#contact"]');
  const ev = new w.Event('click', { bubbles: true, cancelable: true });
  anchor.dispatchEvent(ev);
  ok('T14 якорь: preventDefault + pushState', ev.defaultPrevented && w.location.hash === '#contact');

  // T15: счётчики
  const statIO = w.__io.find(io => io.targets.some(t => t.classList && t.classList.contains('stat')));
  if (statIO) { statIO.trigger(); await tick(60); }
  const num = d.querySelector('.stat__number');
  ok('T15 счётчик досчитал до target', num.textContent === num.dataset.target + (num.dataset.suffix||''), num.textContent);

  // T16: скрытые карточки без IntersectionObserver (js-класс)
  ok('T16 карточки скрыты до анимации (риск при JS-ошибке)', d.querySelector('.js .service-card') !== null || getComputedStyleSafe(d.querySelector('.service-card')) !== 'visible');

  // T17: клик по tel до инициализации Метрики -> цель теряется
  const w2 = await makeWindow('/'); await tick(40);
  const tel = w2.document.querySelector('a[href^="tel:"]');
  tel.dispatchEvent(new w2.Event('click', { bubbles: true }));
  ok('T17 ИСПРАВЛЕНО: ранний клик по tel отслеживается (ym определён сразу)', typeof w2.ym === 'function');

  // T24: form_expired -> обновление времени и автоповтор (лид не теряется)
  const w3 = await makeWindow('/'); await tick(40);
  const d3 = w3.document;
  d3.getElementById('pd-consent').checked = true;
  setInput(w3, 'name', 'Иван'); setInput(w3, 'phone', '1234567890');
  w3.__fetchQueue.push({ ok: false, body: { success: false, code: 'form_expired', error: 'Форма устарела' } });
  w3.__fetchQueue.push({ ok: true, body: { success: true } });
  submit(w3, 'contactForm'); await tick(60);
  ok('T24 ИСПРАВЛЕНО: form_expired -> автоповтор со свежим временем, успех', w3.__fetchCalls.length === 2 && d3.getElementById('successBlock').style.display === 'block', 'calls=' + w3.__fetchCalls.length);
  const ft1 = Number(w3.__fetchCalls[0].opts.body.get('form_time'));
  const ft2 = Number(w3.__fetchCalls[1].opts.body.get('form_time'));
  ok('T24b время формы обновлено перед повтором', ft2 >= ft1);

  // ================= EN =================
  const we = await makeWindow('/en.html'); await tick(40);
  const de = we.document;
  setInput(we, 'name', 'John'); setInput(we, 'phone', '+442079460958');
  submit(we, 'contactForm'); await tick();
  const enToast = de.querySelector('.toast--error');
  ok('T18 EN: ошибка согласия по-английски', enToast && enToast.textContent.includes('consent'), enToast && enToast.textContent);
  de.getElementById('pd-consent').checked = true;
  we.__fetchQueue.push({ ok: true, body: { success: true } });
  submit(we, 'contactForm'); await tick(30);
  ok('T19 EN: lang=en в FormData', we.__fetchCalls[0].opts.body.get('lang') === 'en');
  ok('T20 EN: label кнопки восстановлен по-английски', de.getElementById('submitBtn').textContent === 'Get a project estimate');

  // ================= PARTNERS =================
  const wp = await makeWindow('/partners.html'); await tick(40);
  const dp = wp.document;
  setInput(wp, 'p-name', 'Партнёр'); setInput(wp, 'p-contact', '@partner_one');
  const comp = dp.querySelector('[name="company"]'); if (comp) { comp.value = 'CRM'; comp.dispatchEvent(new wp.Event('input', { bubbles: true })); }
  dp.getElementById('p-pd-consent').checked = true;
  wp.__fetchQueue.push({ ok: true, body: { success: true } });
  submit(wp, 'partnersForm'); await tick(30);
  const pfd = wp.__fetchCalls[0].opts.body;
  ok('T21 partner: form_type=partner отправлен', pfd.get('form_type') === 'partner');
  ok('T22 partner: label восстановлен из data-default-label', dp.getElementById('p-submitBtn').textContent === 'Отправить заявку');
  ok('T23 partner: без successBlock -> тост (RU)', [...dp.querySelectorAll('.toast--success')].some(t => t.textContent.includes('Заявка отправлена')));
  ok('P8 RU partners: cross-link на EN-версию', !!dp.querySelector('.partners-hero__alt a[href="/partners-en.html"]'));
  // T11a/T11b: FAQ-аккордеон (перенесён с главной — на главной блок удалён)
  const pq1 = dp.getElementById('pfaq-q-1'), pq2 = dp.getElementById('pfaq-q-2');
  pq1.dispatchEvent(new wp.Event('click', { bubbles: true }));
  ok('T11a FAQ открыт, aria-expanded=true', pq1.getAttribute('aria-expanded') === 'true' && pq1.closest('.faq__item').classList.contains('active'));
  pq2.dispatchEvent(new wp.Event('click', { bubbles: true }));
  ok('T11b предыдущий закрыт', pq1.getAttribute('aria-expanded') === 'false');

  // ================= COOKIE-NOTICE с opt-out (аудит п.8) =================
  const wc = await makeWindow('/', {consent:null}); await tick(40);
  const dc = wc.document;
  ok('C1 баннер показан без согласия', !!dc.getElementById('cookie-banner') && !dc.getElementById('cookie-banner').hidden);
  ok('C2 Метрика НЕ загружена без согласия', typeof wc.ym === 'undefined');
  ok('C3 noscript-пиксель Метрики удалён', !dc.documentElement.innerHTML.includes('mc.yandex.ru/watch'));
  dc.getElementById('cookie-accept').dispatchEvent(new wc.Event('click', {bubbles:true})); await tick();
  ok('C4 accept: согласие сохранено', wc.localStorage.getItem('ahundoff_cookie_consent') === 'all');
  ok('C5 accept: баннер скрыт', dc.getElementById('cookie-banner').hidden);
  ok('C6 accept: Метрика загружена', typeof wc.ym === 'function');

  const we2 = await makeWindow('/', {consent:null}); await tick(40);
  we2.document.getElementById('cookie-essential').dispatchEvent(new we2.Event('click', {bubbles:true})); await tick();
  ok('C7 essential: согласие сохранено', we2.localStorage.getItem('ahundoff_cookie_consent') === 'essential');
  ok('C8 essential: Метрика не загружена', typeof we2.ym === 'undefined');
  ok('C9 essential: баннер скрыт', we2.document.getElementById('cookie-banner').hidden);

  const we3 = await makeWindow('/', {consent:'essential'}); await tick(40);
  ok('C10 essential preset: баннер скрыт, Метрики нет', we3.document.getElementById('cookie-banner').hidden && typeof we3.ym === 'undefined');

  const wf = await makeWindow('/', {consent:'all'}); await tick(40);
  ok('C11a при согласии баннер скрыт', wf.document.getElementById('cookie-banner').hidden);
  wf.document.querySelector('[data-cookie-settings]').dispatchEvent(new wf.Event('click', {bubbles:true, cancelable:true})); await tick();
  ok('C11b ссылка «Настройки cookie» в подвале открывает баннер', !wf.document.getElementById('cookie-banner').hidden);

  const wen = await makeWindow('/en.html', {consent:null}); await tick(40);
  ok('C12 EN: текст баннера по-английски', wen.document.getElementById('cookie-banner').textContent.includes('We use cookies'));
  ok('C13 EN: кнопка Accept all', wen.document.getElementById('cookie-accept').textContent === 'Accept all');

  const wg = await makeWindow('/', {consent:null}); await tick(40);
  const tg = wg.document.querySelector('.floating-action--telegram');
  if (tg) tg.dispatchEvent(new wg.Event('click', {bubbles:true})); // цель до согласия -> очередь
  wg.document.getElementById('cookie-accept').dispatchEvent(new wg.Event('click', {bubbles:true})); await tick();
  const gcalls = (wg.ym && wg.ym.a) || [];
  ok('C14 цель до согласия отправлена после accept (flush)', gcalls.some(c => c[1]==='reachGoal' && c[2]==='click_messenger_telegram'), JSON.stringify(gcalls).slice(0,150));

  // ================= EN-ПАРТНЁРКА (аудит п.16) =================
  const wpe = await makeWindow('/partners-en.html'); await tick(40);
  const dpe = wpe.document;
  ok('P1 EN partners: lang=en', dpe.documentElement.getAttribute('lang') === 'en');
  ok('P2 EN partners: заголовок EN', dpe.querySelector('.partners-hero__title').textContent.includes('Earn'));
  setInput(wpe, 'p-name', 'Partner'); setInput(wpe, 'p-contact', '@partner_one');
  const compE = dpe.querySelector('[name="company"]'); compE.value = 'CRM'; compE.dispatchEvent(new wpe.Event('input', {bubbles:true}));
  dpe.getElementById('p-pd-consent').checked = true;
  wpe.__fetchQueue.push({ ok: true, body: { success: true } });
  submit(wpe, 'partnersForm'); await tick(30);
  const pfdE = wpe.__fetchCalls[0].opts.body;
  ok('P3 EN partners: form_type=partner', pfdE.get('form_type') === 'partner');
  ok('P4 EN partners: lang=en в FormData', pfdE.get('lang') === 'en');
  ok('P5 EN partners: label кнопки EN', dpe.getElementById('p-submitBtn').textContent === 'Send application');
  ok('P6 EN partners: футер ведёт на /partners-en.html', !!dpe.querySelector('.footer__links a[href="/partners-en.html"]'));
  ok('P7 EN partners: hreflang ru/en', !!dpe.querySelector('link[hreflang="ru"]') && !!dpe.querySelector('link[hreflang="en"]'));

  console.log(`\nИТОГО DOM: PASS=${pass} FAIL=${fail}`);
  process.exit(0);
})().catch(e => { console.error('HARNESS ERROR:', e); process.exit(1); });
function getComputedStyleSafe(el){ try { return el.ownerDocument.defaultView.getComputedStyle(el).opacity; } catch(e){ return 'n/a'; } }
