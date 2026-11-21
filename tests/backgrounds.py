#!/usr/bin/env python3
"""
Шахматный порядок фонов секций главной страницы.

Инварианты:
  1) Секции чередуются тёмный/светлый, начиная с тёмного hero и заканчивая
     тёмным подвалом: hero(т) stats(с) Услуги(т) Отрасли(с) Кейсы(т)
     Экспертиза(с) Подход(т) Контакты(с) подвал(т).
  2) Группировка в базовом слое и в @layer flow СОВПАДАЕТ. Раньше они
     расходились, и режим с canvas-анимацией красил секции иначе, чем базовый,
     из-за чего склейки соседних полос были видны только в одном режиме.
  3) Каждая секция главной получает фон в обоих режимах (новая секция без
     фона = две соседние полосы одного цвета).

Работает без браузера: порядок секций берётся из HTML, цвета — из styles.css.
Запуск: TEST_HOST=http://127.0.0.1:8088 python3 tests/backgrounds.py
"""
import os
import re
import sys
import urllib.request
from html.parser import HTMLParser

HOST = os.environ.get('TEST_HOST', 'http://127.0.0.1:8088')
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
_raw = open(os.path.join(ROOT, 'styles.css'), encoding='utf-8').read()
# Комментарии вырезаем: иначе групповой селектор «склеивается» с текстом комментария.
CSS = re.sub(r'/\*.*?\*/', '', _raw, flags=re.S)

# Секции с собственным правилом фона (не входят в групповые селекторы).
FIXED = {'hero': 'dark', 'stats': 'light', 'footer': 'dark'}

fails = []


def check(label, ok, extra=''):
    if ok:
        print(f'PASS: {label}')
    else:
        fails.append(label)
        print(f'FAIL: {label} {extra}')


class Sections(HTMLParser):
    """Порядок секций и подвала в документе."""

    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.order = []

    def handle_starttag(self, tag, attrs):
        if tag not in ('section', 'footer'):
            return
        classes = dict(attrs).get('class', '').split()
        if classes:
            self.order.append(classes[0])


def group(pattern):
    m = re.search(pattern, CSS)
    if not m:
        return None
    return {c.strip().lstrip('.') for c in m.group(1).split(',') if c.strip()}


base_dark = group(r'([^{}]+)\{[^{}]*padding: 120px 0; background: var\(--bg-primary\)')
base_light = group(r'([^{}]+)\{[^{}]*padding: 120px 0; background: var\(--bg-secondary\)')
flow_dark = group(r'\.flow-enabled :is\(([^)]*)\)\s*\{\s*background: rgba\(3, 7, 18')
flow_light = group(r'\.flow-enabled :is\(([^)]*)\)\s*\{\s*background: rgba\(15, 23, 42')

check('styles.css: найдены обе базовые группы фона', bool(base_dark and base_light))
check('styles.css: найдены обе flow-группы фона', bool(flow_dark and flow_light))
if not all([base_dark, base_light, flow_dark, flow_light]):
    print('\nИТОГО фоны: FAIL (не удалось разобрать styles.css)')
    sys.exit(1)

check('базовый слой и @layer flow: тёмные группы совпадают', base_dark == flow_dark,
      f'{sorted(base_dark)} vs {sorted(flow_dark)}')
check('базовый слой и @layer flow: светлые группы совпадают', base_light == flow_light,
      f'{sorted(base_light)} vs {sorted(flow_light)}')
check('группы не пересекаются', not (base_dark & base_light), sorted(base_dark & base_light))

tone = dict(FIXED)
tone.update({c: 'dark' for c in base_dark})
tone.update({c: 'light' for c in base_light})

for path in ('/', '/en.html'):
    html = urllib.request.urlopen(HOST + path, timeout=15).read().decode('utf-8')
    p = Sections()
    p.feed(html)
    order = p.order
    unknown = [c for c in order if c not in tone]
    check(f'{path}: каждой секции назначен фон', not unknown, unknown)
    seq = [tone.get(c, '?') for c in order]
    glued = [f'{order[i-1]}+{order[i]}' for i in range(1, len(seq)) if seq[i] == seq[i - 1]]
    check(f'{path}: шахматный порядок без склеек', not glued, glued)
    check(f'{path}: начинается тёмным и заканчивается тёмным',
          seq[0] == 'dark' and seq[-1] == 'dark', seq)
    print(f'      порядок: {" ".join(order)}')
    print(f'      полосы:  {" ".join({"dark": "Т", "light": "С"}.get(t, "?") for t in seq)}')

print(f"\nИТОГО фоны: {'OK' if not fails else 'FAIL=' + str(len(fails))}")
sys.exit(1 if fails else 0)
