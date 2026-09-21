#!/usr/bin/env python3
"""Generuje placeholdery SVG do assets/img/placeholder/.

Motyw pokazuje je dopóki w assets/img/ nie ma zdjęcia o tym samym slugu
(patrz ntc_img() w inc/helpers.php). Stylistyka - gradient teal plus ukośne
paski i monospace'owa etykieta - jest przeniesiona z prototypów, gdzie
dokładnie tak wyglądały miejsca na zdjęcia, zanim weszły fotografie.

Uruchomienie:  python3 tools/make-placeholders.py
"""

import os

OUT = os.path.join(os.path.dirname(__file__), "..", "assets", "img", "placeholder")

# Jasny wariant - na białym i lodowym tle sekcji.
LIGHT = {
    "from": "#e8f4f1",
    "to": "#d0eae5",
    "stripe": "rgba(27,191,168,0.09)",
    "label": "#7aab9c",
    "glyph": "rgba(27,191,168,0.30)",
}

# Ciemny wariant - hero, sekcja kontaktowa, sekcja maszyn.
DARK = {
    "from": "#0d5068",
    "to": "#0ca882",
    "stripe": "rgba(255,255,255,0.07)",
    "label": "rgba(255,255,255,0.55)",
    "glyph": "rgba(255,255,255,0.32)",
}

# slug -> (szerokość, wysokość, etykieta, paleta)
IMAGES = {
    "hero":              (700, 700, "specjalistka / laboratorium", DARK),
    "contact":           (500, 500, "kontakt / biuro",             DARK),
    "cat-maszyny":       (700, 525, "maszyny / linia produkcyjna", DARK),

    "about":             (800, 600, "zespół / biuro Wilanów",      LIGHT),
    "offer-api":         (600, 300, "substancje aktywne / API",    LIGHT),
    "offer-probiotics":  (600, 300, "probiotyki / kultury",        LIGHT),
    "offer-lactoferrin": (600, 300, "laktoferyna / białko",        LIGHT),
    "offer-machines":    (600, 300, "maszyny przemysłowe",         LIGHT),
    "offer-components":  (600, 300, "komponenty kosmetyczne",      LIGHT),
    "offer-docs":        (600, 300, "dokumentacja / audyt",        LIGHT),
    "cat-api":           (700, 525, "substancje aktywne / API",    LIGHT),
    "cat-probiotyki":    (700, 525, "probiotyki / kultury",        LIGHT),
    "cat-laktoferyna":   (700, 525, "laktoferyna / białko",        LIGHT),
}

# Uproszczona kolba - ten sam rysunek co ikona "flask" w inc/helpers.php.
GLYPH = ("M9 3h6 M10 3v6.5L4.3 18.4A1.8 1.8 0 0 0 5.8 21h12.4a1.8 1.8 0 0 0 "
         "1.5-2.6L14 9.5V3 M7.8 15h8.4")

TEMPLATE = """<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {w} {h}" width="{w}" height="{h}" role="img" aria-label="{label}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{c[from]}"/>
      <stop offset="100%" stop-color="{c[to]}"/>
    </linearGradient>
    <pattern id="stripes" width="36" height="36" patternUnits="userSpaceOnUse" patternTransform="rotate(-45)">
      <rect width="18" height="36" fill="{c[stripe]}"/>
    </pattern>
  </defs>
  <rect width="{w}" height="{h}" fill="url(#bg)"/>
  <rect width="{w}" height="{h}" fill="url(#stripes)"/>
  <g transform="translate({gx} {gy}) scale({gs})" fill="none" stroke="{c[glyph]}" stroke-width="1.5"
     stroke-linecap="round" stroke-linejoin="round">
    <path d="{glyph}"/>
  </g>
  <text x="50%" y="{ty}" text-anchor="middle"
        font-family="ui-monospace, SFMono-Regular, Menlo, monospace" font-size="{fs}"
        letter-spacing="1.2" fill="{c[label]}">{label}</text>
</svg>
"""


def build(slug, w, h, label, colors):
    short = min(w, h)

    # Kolba zajmuje 18% krótszego boku; ikona ma viewBox 24x24, stąd skala.
    glyph_size = short * 0.18
    scale = glyph_size / 24.0

    # Kolba nad środkiem, etykieta pod nią - razem tworzą wyśrodkowany blok.
    glyph_top = h / 2 - glyph_size * 0.85

    return TEMPLATE.format(
        w=w,
        h=h,
        c=colors,
        label=label,
        glyph=GLYPH,
        gx=round((w - glyph_size) / 2, 1),
        gy=round(glyph_top, 1),
        gs=round(scale, 4),
        ty=round(glyph_top + glyph_size + short * 0.075, 1),
        fs=max(11, round(short / 34)),
    )


def main():
    os.makedirs(OUT, exist_ok=True)

    for slug, (w, h, label, colors) in IMAGES.items():
        path = os.path.join(OUT, slug + ".svg")

        with open(path, "w", encoding="utf-8") as handle:
            handle.write(build(slug, w, h, label, colors))

        print("{:22} {}x{}".format(slug + ".svg", w, h))

    print("\n{} placeholderów w {}".format(len(IMAGES), os.path.normpath(OUT)))


if __name__ == "__main__":
    main()
