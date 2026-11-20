# Ahundoff Studio — сайт

Статический сайт (HTML/CSS/JS) с PHP-обработчиком заявок `send.php`. Хостинг — Timeweb
(nginx + Apache, `.html` исполняется как PHP через `.htaccess`, шаблоны в `partials/`).

## Структура

| Файл | Назначение |
|---|---|
| `index.html`, `en.html` | Главная (RU/EN) |
| `partners.html` | Партнёрская программа |
| `privacy-policy*.html` | Политика конфиденциальности (RU/EN) |
| `vizitka-studio.html` + `vizitka.js` | Генератор визиток (препресс, QR, экспорт SVG/PNG) |
| `mail.html` | Шаблон email-рассылки (не публичная страница) |
| `404.html` | Брендированная страница ошибки |
| `send.php` | Обработчик форм (contact / partner) |
| `script.js`, `styles.css` | Общие JS и CSS |
| `partials/` | header/footer/sprite (PHP-инклюды) |
| `assets/fonts/` | Self-hosted шрифты Inter / Unbounded (variable, cyrillic+latin) |
| `assets/vendor/qrcode.min.js` | Self-hosted QR-библиотека (qrcode-generator 1.4.4) |
| `tests/` | Регрессионные тесты (см. ниже) |

## Локальный запуск

```bash
# PHP-стенд (исполняет .html как PHP, письма складывает в tests/mail.out)
php -d sendmail_path="$PWD/tests/mailcatch.sh" -S 0.0.0.0:8088 -t . tests/router.php
# или: npm run serve
```

Открыть http://127.0.0.1:8088/.

## Тесты

Требуется запущенный стенд (выше) и `npm install` (для jsdom).

```bash
npm install
TEST_HOST=http://127.0.0.1:8088 bash tests/send.test.sh   # 35 тестов обработчика форм
TEST_HOST=http://127.0.0.1:8088 node tests/dom.test.js    # 37 DOM-тестов (jsdom)
TEST_HOST=http://127.0.0.1:8088 python3 tests/structure.py# структурные проверки HTML
# всё вместе: npm test
```

`send.test.sh` проверяет: методы/доступ, CSRF-защиту (Origin/Referer + кастомный заголовок),
honeypot/timestamp, валидацию полей, инъекции (CRLF/XSS), невалидный UTF-8, двухуровневый
rate limit и заголовки ответа. `dom.test.js` — формы, маску телефона, меню, FAQ, тосты,
i18n, аналитику, автоповтор при `form_expired`.

## Деплой

1. Загрузить файлы на Timeweb (корень сайта). `.htaccess` и `logs/.htaccess` обязательны.
2. **Версионирование ассетов:** при изменении `styles.css`/`script.js`/`vizitka.js` поднимать
   `?v=...` во всех HTML (сейчас `2026-11-21-3`). nginx кэширует статику на год — без смены
   версии пользователи не увидят обновлений. Изображения без версии (например `founder.jpg`)
   при замене тоже требуют `?v=`.
3. Убедиться, что `logs/` недоступна извне (403) и права на запись есть у PHP.
4. HTTPS и редирект с www включаются в панели хостинга (в `.htaccess` редирект HTTPS
   закомментирован во избежание циклов).

## Безопасность и эксплуатация (важно)

- **Ротация токенов.** GitHub PAT, передававшийся в переписке, следует отозвать и перевыпустить.
- **Персональные данные (152-ФЗ).** `logs/leads.log` хранит заявки в открытом виде. Политика
  заявляет хранение ≤12 месяцев — настройте регулярную очистку/ротацию (cron), т.к. код
  ротирует только по размеру (5 МБ). При отзыве согласия удаляйте запись вручную.
- **Почта.** SPF есть, DMARC в режиме `p=none`. После мониторинга переведите в `quarantine`/`reject`,
  проверьте DKIM. `mail()` синхронный; при сбое заявка сохраняется в лог, но владелец не
  уведомляется — рекомендуется fallback-уведомление в Telegram.
- **Rate limit и прокси.** `getClientIp()` берёт правый публичный IP из `X-Forwarded-For`
  (его дописывает nginx Timeweb). Если конфигурация прокси изменится — перепроверьте.
- **CSP.** `script-src 'self' https://mc.yandex.ru` (без `unsafe-eval`). Новые сторонние скрипты
  требуют явного добавления в CSP; inline-скрипты запрещены — выносите во внешние файлы.
- **HSTS** `includeSubDomains` — убедитесь, что все поддомены работают по HTTPS.

## Что исправлено в аудите 21.11.2026

Кратко (полный отчёт — в задаче аудита):

- **send.php:** обход rate limit через подделку X-Forwarded-For; падение в 500 на невалидном
  UTF-8; согласие по `isset` вместо значения; «сжигание» лимита ошибками валидации; тихая
  потеря лидов при устаревшей форме; слабая валидация телефона; ПДн в spam/error-логах;
  отсутствие ротации логов и лимита числа IP.
- **script.js:** потеря целей Метрики до инициализации; стирание Telegram без `@`; наложение
  тостов; зависание кнопки при обрыве сети; каскадные ошибки инициализации; работа анимаций
  вне экрана; отсутствие фокус-ловушки меню и fallback копирования.
- **HTML/CSS:** битые preload шрифтов (404) и зависимость от Google Fonts (заменено на
  self-hosted); контраст primary-кнопок (3.68→5.17); невалидный `noscript` в `head`;
  дубль `inLanguage` в JSON-LD EN; неверный URL в JSON-LD partners; разрозненные header/footer
  политик; отсутствие skip-link, safe-area, 404-страницы.
- **.htaccess:** двойной `Cache-Control`; избыточный CSP (`unsafe-eval`, jsdelivr, широкий
  `img-src`); блокировка `.well-known`; дубль `/index.html`.
- **vizitka:** страница была полностью сломана собственным CSP (inline-скрипт + cdnjs) —
  скрипт вынесен во внешний файл, QR и шрифты self-hosted.
