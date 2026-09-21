#!/usr/bin/env python3
"""Ściąga Barlow z Google Fonts i buduje lokalny assets/css/fonts.css.

Fonty są hostowane w motywie, a nie linkowane do fonts.gstatic.com - wywołanie
do Google z przeglądarki użytkownika przekazuje jego IP stronie trzeciej, co w
UE jest problemem pod RODO. Przy okazji strona nie zależy od cudzego CDN-u.

Pobieramy tylko podzbiory latin i latin-ext (latin-ext niesie ą, ć, ę, ł, ń,
ś, ź, ż). Barlow nie ma wersji zmiennej, więc każda grubość to osobny plik -
stąd waga w nazwie. Bierzemy tylko te grubości, które faktycznie są w
arkuszach, plus kursywę 400 i 700 do akcentów w nagłówkach.

Uruchomienie:  python3 tools/fetch-fonts.py
"""

import os
import re
import urllib.request

HERE = os.path.dirname(__file__)
FONT_DIR = os.path.normpath(os.path.join(HERE, "..", "assets", "fonts"))
CSS_PATH = os.path.normpath(os.path.join(HERE, "..", "assets", "css", "fonts.css"))

UA = ("Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
      "(KHTML, like Gecko) Chrome/120 Safari/537.36")

CSS_URL = ("https://fonts.googleapis.com/css2"
           "?family=Barlow:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400;1,700"
           "&display=swap")

KEEP_SUBSETS = {"latin", "latin-ext"}

HEADER = """/*
 * Self-hostowany Barlow z Google Fonts - latin + latin-ext, grubości 300-800.
 * Serwowane z motywu, więc przeglądarka użytkownika nie odpytuje fonts.gstatic.com
 * (RODO).
 *
 * Plik generowany - nie edytować ręcznie. Regeneracja: python3 tools/fetch-fonts.py
 */
"""

FACE = """/* {family} {style} {weight} - {subset} */
@font-face {{
  font-family: '{family}';
  font-style: {style};
  font-weight: {weight};
  font-display: swap;
  src: url('../fonts/{filename}') format('woff2');
  unicode-range: {urange};
}}"""


def get(url):
    return urllib.request.urlopen(
        urllib.request.Request(url, headers={"User-Agent": UA})
    ).read()


def main():
    os.makedirs(FONT_DIR, exist_ok=True)

    css = get(CSS_URL).decode()
    blocks = re.findall(r"/\*\s*([\w-]+)\s*\*/\s*(@font-face\s*\{.*?\})", css, re.S)

    faces = []

    for subset, block in blocks:
        if subset not in KEEP_SUBSETS:
            continue

        family = re.search(r"font-family:\s*'([^']+)'", block).group(1)
        style = re.search(r"font-style:\s*(\S+);", block).group(1)
        weight = re.search(r"font-weight:\s*([^;]+);", block).group(1).strip()
        src = re.search(r"url\((https://[^)]+)\)", block).group(1)
        urange = re.search(r"unicode-range:\s*([^;]+);", block).group(1).strip()

        # Waga w nazwie pliku: przy foncie statycznym każda grubość to osobny
        # plik i bez tego kolejne nadpisywałyby się nawzajem.
        filename = "{}-{}-{}-{}.woff2".format(
            family.lower(), style, weight.replace(" ", "-"), subset
        )

        data = get(src)
        with open(os.path.join(FONT_DIR, filename), "wb") as handle:
            handle.write(data)

        faces.append(FACE.format(
            family=family, style=style, weight=weight,
            subset=subset, filename=filename, urange=urange,
        ))

        print("{:34} {:>7} B  waga {}".format(filename, len(data), weight))

    if not faces:
        raise SystemExit("Google Fonts nie zwróciło żadnego pasującego @font-face.")

    with open(CSS_PATH, "w", encoding="utf-8") as handle:
        handle.write(HEADER + "\n" + "\n\n".join(faces) + "\n")

    print("\n{} krojów zapisanych, fonts.css przebudowany.".format(len(faces)))


if __name__ == "__main__":
    main()
