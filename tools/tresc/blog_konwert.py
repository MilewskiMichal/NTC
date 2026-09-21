# -*- coding: utf-8 -*-
"""Zamienia artykuł z Worda na bloki Gutenberga.

Nagłówki w plikach Klienta nie mają stylu Word-owego - są to akapity w
całości pogrubione. Stąd rozpoznawanie po pogrubieniu, a nie po stylu.
Punktory Word trzyma w numPr i to zachowujemy jako listę.
"""
import html
import re
import zipfile
from xml.etree import ElementTree as ET

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'


def akapity(sciezka):
    """Zwraca listę (rodzaj, html) - rodzaj to 'naglowek', 'punkt' albo 'tekst'."""
    korzen = ET.fromstring(zipfile.ZipFile(sciezka).read('word/document.xml'))
    out = []

    for p in korzen.iter(W + 'p'):
        kawalki, pelne, cale = [], True, ''

        for r in p.iter(W + 'r'):
            t = ''.join(n.text or '' for n in r.iter(W + 't'))
            if not t:
                continue
            rpr = r.find(W + 'rPr')
            pogrubiony = rpr is not None and rpr.find(W + 'b') is not None
            cale += t
            if not pogrubiony and t.strip():
                pelne = False
            kawalki.append((pogrubiony, t))

        if not cale.strip():
            continue

        lista = p.find(W + 'pPr/' + W + 'numPr') is not None

        # Sklejamy sąsiadujące kawałki o tym samym pogrubieniu - Word tnie
        # zdanie na kilka runów i bez tego wychodzi "**a****b**".
        scalone = []
        for pogrubiony, t in kawalki:
            if scalone and scalone[-1][0] == pogrubiony:
                scalone[-1][1] += t
            else:
                scalone.append([pogrubiony, t])

        if pelne and not lista:
            out.append(('naglowek', html.escape(cale.strip())))
            continue

        # Spacje na brzegach zostają poza znacznikiem, a nie w nim. Word potrafi
        # objąć pogrubieniem spację przed wyrazem i wtedy przycinanie całego
        # kawałka skleja go z poprzednim słowem ("z" + "ryb" = "zryb").
        czesci = []
        for pogrubiony, t in scalone:
            if not pogrubiony or not t.strip():
                czesci.append(html.escape(t))
                continue
            przed = t[:len(t) - len(t.lstrip())]
            po = t[len(t.rstrip()):]
            czesci.append('%s<strong>%s</strong>%s' % (przed, html.escape(t.strip()), po))

        tresc = re.sub(r'\s+', ' ', ''.join(czesci)).strip()
        out.append(('punkt' if lista else 'tekst', tresc))

    return out


def na_bloki(pozycje, podnaglowki=()):
    """Buduje treść Gutenberga; pierwszy nagłówek to tytuł wpisu, więc odpada.

    Word nie rozróżnia poziomów nagłówków - wszystkie są po prostu pogrubione -
    więc te, które mają być niższego rzędu, podaje się wprost w podnaglowki.
    """
    bloki, bufor, pierwszy = [], [], True

    def zamknij_liste():
        if not bufor:
            return
        srodek = '\n'.join(
            '<!-- wp:list-item -->\n<li>%s</li>\n<!-- /wp:list-item -->' % x for x in bufor)
        bloki.append('<!-- wp:list -->\n<ul class="wp-block-list">\n%s\n</ul>\n<!-- /wp:list -->' % srodek)
        bufor.clear()

    for rodzaj, tekst in pozycje:
        if rodzaj == 'punkt':
            bufor.append(tekst)
            continue
        zamknij_liste()
        if rodzaj == 'naglowek':
            if pierwszy:
                pierwszy = False
                continue
            poziom = 3 if html.unescape(tekst) in podnaglowki else 2
            bloki.append('<!-- wp:heading {"level":%d} -->\n<h%d>%s</h%d>\n<!-- /wp:heading -->'
                         % (poziom, poziom, tekst, poziom))
        else:
            bloki.append('<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % tekst)

    zamknij_liste()
    return '\n'.join(bloki) + '\n'


def tytul(pozycje):
    for rodzaj, tekst in pozycje:
        if rodzaj == 'naglowek':
            return html.unescape(tekst)
    return ''
