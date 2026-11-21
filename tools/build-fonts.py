#!/usr/bin/env python3
"""
Пересборка self-hosted subset-шрифтов Inter / Unbounded.

Зачем: в исходных subset-файлах (Google Fonts) не было глифа ₽ (U+20BD), хотя
unicode-range латинской грани (U+2000-206F) его заявляет. Браузер выбирал Inter,
не находил глиф и отдавал символ системному fallback — на минимальных Linux-сборках
получался «тофу»-квадрат, на остальных ОС рубль рисовался чужим шрифтом вразнобой
с цифрами. Затронуты были все цены на сайте.

Что делает скрипт:
  * берёт полные переменные шрифты тех же версий, что уже лежат в репозитории;
  * набор кодовых точек = всё, что было в старом subset, ПЛЮС U+20BD
    (покрытие не может сузиться — старые символы не теряются);
  * сохраняет набор layout-фич старого файла (kern/tnum/pnum/liga/...);
  * у Inter фиксирует ось opsz в значении по умолчанию (14) — в subset её нет;
  * ось wght оставляет как есть, чтобы веса рендерились ровно как раньше;
  * пишет именно WOFF2 (Subsetter.subset() НЕ применяет options.flavor —
    это делает только CLI pyftsubset, поэтому flavor задаётся явно).

Запуск:  python3 tools/build-fonts.py
Требует: fonttools >= 4.68, brotli.   pip install -U fonttools brotli

ВАЖНО: одного глифа недостаточно. U+20BD (8381) НЕ входит в стандартный
google-диапазон U+2000-206F (8192-8303), поэтому браузер не пытается взять ₽
из шрифта и молча отдаёт его системному fallback. В styles.css и
assets/fonts/fonts.css в unicode-range латинских граней добавлен U+20BD.
Проверяется тестом tests/fonts.py.

После пересборки обязательно поднять ?v= у url(...) в styles.css —
nginx кэширует статику на год.
"""
import os
import sys
import urllib.request

from fontTools.ttLib import TTFont
from fontTools.subset import Options, Subsetter
from fontTools.varLib.instancer import instantiateVariableFont

ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FONTS = os.path.join(ROOT, "assets", "fonts")
CACHE = os.path.join("/tmp", "ahundoff-font-src")

# Версии совпадают с name ID 5 у файлов, которые уже лежат в assets/fonts.
SOURCES = {
    "inter": (
        "Inter[opsz,wght].ttf",
        "https://raw.githubusercontent.com/google/fonts/main/ofl/inter/Inter%5Bopsz%2Cwght%5D.ttf",
        "Version 4.001",
    ),
    "unbounded": (
        "Unbounded[wght].ttf",
        "https://raw.githubusercontent.com/google/fonts/main/ofl/unbounded/Unbounded%5Bwght%5D.ttf",
        "Version 1.701",
    ),
}

# subset -> (исходник, фиксация осей)
JOBS = {
    "inter-latin.woff2": ("inter", {"opsz": 14}),
    "unbounded-latin.woff2": ("unbounded", {}),
}

EXTRA_CODEPOINTS = {0x20BD}  # ₽


def source_path(key):
    fname, url, want_version = SOURCES[key]
    os.makedirs(CACHE, exist_ok=True)
    path = os.path.join(CACHE, fname)
    if not os.path.exists(path):
        print(f"  скачиваю {fname} ...")
        urllib.request.urlretrieve(url, path)
    got = TTFont(path)["name"].getDebugName(5) or ""
    if not got.startswith(want_version):
        sys.exit(f"ВЕРСИЯ НЕ СОВПАЛА: {fname}: ожидалась {want_version}, получена {got}")
    return path


def build(out_name, key, pin):
    target = os.path.join(FONTS, out_name)
    old = TTFont(target)
    old_cps = set(old.getBestCmap())
    old_features = sorted(
        {
            fr.FeatureTag
            for tag in ("GSUB", "GPOS")
            if tag in old
            for fr in old[tag].table.FeatureList.FeatureRecord
        }
    )
    old_axes = [(a.axisTag, a.minValue, a.defaultValue, a.maxValue) for a in old["fvar"].axes]
    before = os.path.getsize(target)

    font = TTFont(source_path(key))
    if pin:
        font = instantiateVariableFont(font, pin, inplace=True, updateFontNames=False)
        # После инстансинга в gvar остаются только глифы с вариациями, а сабсеттер
        # обращается к нему по каждому сохранённому глифу — дополняем пустыми записями.
        gvar = font["gvar"]
        variations = dict(gvar.variations)
        for glyph in font.getGlyphOrder():
            variations.setdefault(glyph, [])
        gvar.variations = variations

    opts = Options()
    opts.layout_features = old_features
    opts.name_IDs = ["*"]
    opts.name_legacy = True
    opts.name_languages = ["*"]
    opts.notdef_outline = True
    opts.recommended_glyphs = True
    opts.recalc_timestamp = False
    sub = Subsetter(options=opts)
    sub.populate(unicodes=old_cps | EXTRA_CODEPOINTS)
    sub.subset(font)

    font.flavor = "woff2"  # обязательно: API сабсеттера flavor не выставляет
    font.save(target)

    new = TTFont(target)
    new_cps = set(new.getBestCmap())
    new_axes = [(a.axisTag, a.minValue, a.defaultValue, a.maxValue) for a in new["fvar"].axes]
    lost = old_cps - new_cps
    missing = EXTRA_CODEPOINTS - new_cps
    after = os.path.getsize(target)

    print(f"  {out_name}: {before/1024:.1f} -> {after/1024:.1f} КБ, "
          f"кодовых точек {len(old_cps)} -> {len(new_cps)}")
    if lost:
        sys.exit(f"ОШИБКА: потеряны символы {sorted(hex(c) for c in lost)}")
    if missing:
        sys.exit(f"ОШИБКА: не добавлены {sorted(hex(c) for c in missing)}")
    if old_axes != new_axes:
        sys.exit(f"ОШИБКА: оси изменились {old_axes} -> {new_axes}")

    # Полная сверка контуров и ширин по всем общим символам:
    # рендер всего, кроме добавленного ₽, обязан остаться прежним.
    glyf_old, glyf_new = old["glyf"], new["glyf"]
    cmap_old, cmap_new = old.getBestCmap(), new.getBestCmap()
    changed = []
    for cp in sorted(old_cps):
        go, gn = cmap_old.get(cp), cmap_new.get(cp)
        if not go or not gn:
            continue
        a, b = glyf_old[go], glyf_new[gn]
        if hasattr(a, "coordinates") and hasattr(b, "coordinates"):
            if list(a.coordinates) != list(b.coordinates):
                changed.append(hex(cp))
        if old["hmtx"][go] != new["hmtx"][gn]:
            changed.append(hex(cp) + ":width")
    if changed:
        sys.exit(f"ОШИБКА: изменились контуры/ширины: {changed}")
    print("     контуры и ширины всех прежних символов: идентичны")


def main():
    print("Пересборка subset-шрифтов (добавление U+20BD ₽):")
    for out_name, (key, pin) in JOBS.items():
        build(out_name, key, pin)
    print("Готово. Не забудьте поднять ?v= у url(...) в styles.css.")


if __name__ == "__main__":
    main()
