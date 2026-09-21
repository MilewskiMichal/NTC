# -*- coding: utf-8 -*-
"""Uwagi Klienta z 14.09.2026 - warstwa treści.

Sieroty na końcach wierszy załatwia filtr mikrotypograficzny w motywie, więc
tutaj zostają zmiany, których nie da się zrobić automatem: kolor akcentu w
nagłówkach, skreślone opisy, literówki i drobne przeredagowania.
"""
import re

# Nagłówki podstron: akcent kolorem obejmuje też przyimek, żeby nie wisiał
# samotnie przed kolorową częścią. Opisy pod nagłówkiem Klient skreślił na
# wszystkich podzakładkach ofertowych.
NAGLOWKI = {
    'substancje-czynne-api': 'Substancje czynne<br/><em>dla przemysłu farmaceutycznego</em>',
    'probiotyki':            'Probiotyki<br/>od <em>Bioprox Healthcare</em>',
    'laktoferyna':           'Laktoferyna<br/><em>z Nowej Zelandii</em>',
    'colostrum':             'Colostrum bydlęce<br/><em>o standaryzowanej zawartości IgG</em>',
    'bialka-mleka':          'Funkcjonalne białka mleka<br/><em>do wymagających formulacji</em>',
    'kolagen':               'Kolagen dopasowany<br/><em>do nowoczesnych formulacji</em>',
    'maszyny-i-uslugi':      'Maszyny i usługi<br/><em>dla przemysłu</em>',
}

# Podstrony, z których znika opis pod nagłówkiem.
BEZ_OPISU = set(NAGLOWKI)

KLIENT = re.compile(r'\bklient(a|ów|om|ami|owi|em|cie|ka|kę)?\b')


def duza_litera_klient(tekst):
    """Klient jako strona umowy - w korespondencji handlowej wielką literą."""
    return KLIENT.sub(lambda m: 'Klient' + (m.group(1) or ''), tekst)


# Zamiany tekstowe wspólne dla wszystkich stron.
GLOBALNE = [
    # Wyroby medyczne Klient skreślił z opisu zastosowań szczepów.
    ('suplementów diety, wyrobów medycznych oraz żywności funkcjonalnej',
     'suplementów diety oraz żywności funkcjonalnej'),
    # Doprecyzowanie udziału tripeptydów.
    ('surowce zawierające ponad 15% tripeptydów',
     'surowce zawierające ponad 15% udziałem tripeptydów'),
    ('po wysoko skoncentrowane rozwiązanie z ponad 50% tripeptydów',
     'po wysoko skoncentrowane rozwiązanie z ponad 50% udziałem tripeptydów'),
]

# Zamiany przypisane do konkretnych stron.
LOKALNE = {
    'oferta': [
        ('Bakterie kwasu mlekowego<br/>z francuskiego laboratorium <em>Bioprox</em>',
         'Bakterie i drożdże <em>probiotyczne</em>'),
        # Dopisek o wersji postbiotycznej, wprost z pliku Klienta.
        ('a także kultury należące do innych rodzajów, m.in. Lactococcus, '
         'Streptococcus i Enterococcus.',
         'a także kultury należące do innych rodzajów, m.in. Lactococcus, '
         'Streptococcus i Enterococcus. Wybrane z nich dostępne są w wersji '
         'postbiotycznej.'),
    ],
    'probiotyki': [
        ('Bakterie i drożdże probiotyczne <em>Unique</em>',
         'Bakterie i drożdże <em>probiotyczne</em>'),
        # Postbiotyki znikają z nazwy sekcji i z tematu formularza.
        ('Zapytaj o szczep probiotyczny', 'Zapytaj o szczep probiotyczny'),
    ],
}


def popraw(slug, tresc):
    import bloki

    bloki_strony = bloki.podziel(tresc)

    for i, (nazwa, atrybuty, _) in enumerate(bloki_strony):
        zmiany = {}

        if nazwa in ('ntc/page-hero', 'ntc/lp-hero'):
            if slug in NAGLOWKI:
                zmiany['title'] = NAGLOWKI[slug]
            if slug in BEZ_OPISU and atrybuty.get('sub'):
                zmiany['sub'] = ''

        if zmiany:
            bloki_strony[i] = bloki.podmien_atrybuty(bloki_strony[i], zmiany)

    wynik = bloki.zlacz(bloki_strony)

    for stare, nowe in GLOBALNE + LOKALNE.get(slug, []):
        wynik = wynik.replace(stare, nowe)

    return duza_litera_klient(wynik)
