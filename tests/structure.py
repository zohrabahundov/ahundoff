#!/usr/bin/env python3
"""Структурные проверки HTML (дубли ID, битые use/anchor/label/aria-controls, noscript в head).
Требует запущенный PHP-стенд. Запуск: TEST_HOST=http://127.0.0.1:8088 python3 tests/structure.py"""
import os, sys, urllib.request
from html.parser import HTMLParser
HOST = os.environ.get('TEST_HOST', 'http://127.0.0.1:8088')
PAGES = ['/', '/en.html', '/partners.html', '/partners-en.html', '/privacy-policy.html', '/privacy-policy-en.html', '/404.html']

class P(HTMLParser):
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.ids=[]; self.uses=[]; self.anchors=[]; self.labels=[]; self.ac=[]; self.in_head=False; self.ns_head=False
    def handle_starttag(self,t,a):
        a=dict(a)
        if t=='head': self.in_head=True
        if t=='noscript' and self.in_head: self.ns_head=True
        if 'id' in a: self.ids.append(a['id'])
        if t=='use': self.uses.append(a.get('href'))
        if t=='a' and a.get('href','').startswith('#'): self.anchors.append(a['href'])
        if t=='label' and 'for' in a: self.labels.append(a['for'])
        if 'aria-controls' in a: self.ac.append(a['aria-controls'])
    def handle_endtag(self,t):
        if t=='head': self.in_head=False

fail=0
for path in PAGES:
    html = urllib.request.urlopen(HOST+path, timeout=15).read().decode('utf-8')
    p=P(); p.feed(html); ids=set(p.ids)
    probs=[]
    dups=sorted({i for i in p.ids if p.ids.count(i)>1})
    if dups: probs.append(f'dupIDs={dups}')
    bu=sorted({u for u in p.uses if u and u.startswith('#') and u[1:] not in ids})
    if bu: probs.append(f'badUse={bu}')
    ba=[a for a in p.anchors if a!='#' and a[1:] not in ids]
    if ba: probs.append(f'badAnchors={ba}')
    bl=[l for l in p.labels if l not in ids]
    if bl: probs.append(f'badLabel={bl}')
    bac=[c for c in p.ac if c not in ids]
    if bac: probs.append(f'badAriaControls={bac}')
    if p.ns_head: probs.append('noscriptInHead')
    if probs: fail+=1; print(f'FAIL {path}: {", ".join(probs)}')
    else: print(f'PASS {path}')
# --- Авто-определение языка по Accept-Language (аудит п.16) ---
class _NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *a, **k): return None
_op = urllib.request.build_opener(_NoRedirect)
def _status(path, headers):
    r = urllib.request.Request(HOST+path, headers=headers)
    try:
        with _op.open(r, timeout=15) as resp: return resp.status, resp.headers.get('Location')
    except urllib.error.HTTPError as e: return e.code, e.headers.get('Location')
import urllib.error
checks = [
    ('lang: EN browser без cookie -> 302 /en.html', _status('/', {'Accept-Language':'en-US,en;q=0.9'}), (302, '/en.html')),
    ('lang: RU browser без cookie -> 200 /', _status('/', {'Accept-Language':'ru-RU,ru;q=0.9,en;q=0.8'}), (200, None)),
    ('lang: ручной выбор cookie=ru важнее Accept-Language', _status('/', {'Accept-Language':'en-US,en;q=0.9','Cookie':'lang_choice=ru'}), (200, None)),
    ('lang: прямой /en.html не редиректится', _status('/en.html', {'Accept-Language':'ru-RU,ru;q=0.9'}), (200, None)),
]
for label, got, want in checks:
    if got == want: print(f'PASS {label}')
    else: fail += 1; print(f'FAIL {label}: got {got}, want {want}')

print(f'\nИТОГО: {"OK" if fail==0 else str(fail)+" страниц с проблемами"}')
sys.exit(1 if fail else 0)