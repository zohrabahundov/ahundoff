#!/usr/bin/env python3
"""
Проверки self-hosted шрифтов.

1) Глиф ₽ (U+20BD) обязан быть в латинских subset-файлах: его требует unicode-range
   U+2000-206F, и без глифа символ рубля уходит в системный fallback (на минимальных
   Linux-сборках — «тофу»-квадрат). Требует fonttools+brotli; если их нет — SKIP.
2) Базовое покрытие: цифры, латиница, кириллица — на месте.
3) Кэш-бастинг: каждый url(...woff2) в styles.css и assets/fonts/fonts.css обязан
   иметь ?v= — nginx кэширует статику на год, без версии посетитель останется
   со старым шрифтом. Эта часть работает без внешних зависимостей.

Запуск: python3 tests/fonts.py
"""
import os
import re
import sys

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, 'assets', 'fonts')

RUBLE = 0x20BD
LATIN = ['inter-latin.woff2', 'unbounded-latin.woff2']
CYRILLIC = ['inter-cyrillic.woff2', 'unbounded-cyrillic.woff2']

fails = []
skips = []


def check(label, ok, extra=''):
    if ok:
        print(f'PASS: {label}')
    else:
        fails.append(label)
        print(f'FAIL: {label} {extra}')


# --- 1-2. содержимое шрифтов ---
try:
    from fontTools.ttLib import TTFont
except ImportError:
    skips.append('fonttools не установлен')
    print('SKIP: содержимое шрифтов (нужен fonttools+brotli: pip install -U fonttools brotli)')
else:
    for name in LATIN:
        cmap = TTFont(os.path.join(FONTS, name)).getBestCmap()
        check(f'{name}: глиф ₽ U+20BD присутствует', RUBLE in cmap)
        basic = set(range(0x30, 0x3A)) | set(range(0x41, 0x5B)) | set(range(0x61, 0x7B)) | {0x24, 0x20AC}
        check(f'{name}: цифры/латиница/$/€ на месте', basic <= set(cmap),
              sorted(hex(c) for c in basic - set(cmap)))
    for name in CYRILLIC:
        cmap = TTFont(os.path.join(FONTS, name)).getBestCmap()
        basic = set(range(0x410, 0x450)) | {0x401, 0x451, 0x2116}
        check(f'{name}: кириллица и № на месте', basic <= set(cmap),
              sorted(hex(c) for c in basic - set(cmap)))

# --- 3. кэш-бастинг ссылок на шрифты ---
for rel in ('styles.css', os.path.join('assets', 'fonts', 'fonts.css')):
    path = os.path.join(ROOT, rel)
    css = open(path, encoding='utf-8').read()
    urls = re.findall(r"url\('([^']+\.woff2[^']*)'\)", css)
    check(f'{rel}: ссылки на шрифты найдены', len(urls) > 0)
    unversioned = [u for u in urls if '?v=' not in u]
    # Кириллические файлы не менялись — им версия не обязательна, но латинские
    # пересобирались, значит обязаны быть с версией.
    bad = [u for u in unversioned if 'latin' in u]
    check(f'{rel}: латинские subset-ссылки с ?v=', not bad, bad)
    # Главное: без U+20BD в unicode-range браузер НЕ берёт ₽ из шрифта, даже если
    # глиф в файле есть — символ молча уходит в системный fallback.
    ranges = re.findall(r'unicode-range:\s*([^;]+);', css)
    latin_ranges = [r for r in ranges if 'U+0000-00FF' in r]
    check(f'{rel}: латинские unicode-range найдены', len(latin_ranges) == 2, len(latin_ranges))
    check(f'{rel}: U+20BD входит в unicode-range латинских граней',
          all('U+20BD' in r for r in latin_ranges),
          [r[:60] for r in latin_ranges if 'U+20BD' not in r])

print(f"\nИТОГО шрифты: {'OK' if not fails else 'FAIL=' + str(len(fails))}"
      + (f" (SKIP: {', '.join(skips)})" if skips else ''))
sys.exit(1 if fails else 0)
