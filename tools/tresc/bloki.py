# -*- coding: utf-8 -*-
"""Dzielenie treści Gutenberga na bloki najwyższego poziomu.

Bloki dynamiczne bywają samozamykające (/-->), a bywają z treścią w środku,
więc prosty podział po liniach gubi zawartość. Tutaj liczymy zagnieżdżenie.
"""
import json
import re

OTWARCIE = re.compile(r'<!--\s+wp:([a-z0-9/-]+)(\s+(\{.*?\}))?\s*(/)?-->', re.S)
ZAMKNIECIE = re.compile(r'<!--\s+/wp:([a-z0-9/-]+)\s+-->')


def podziel(tresc):
    """Zwraca listę bloków najwyższego poziomu jako (nazwa, atrybuty, tekst)."""
    bloki = []
    poz = 0
    while True:
        m = OTWARCIE.search(tresc, poz)
        if not m:
            break
        nazwa = m.group(1)
        atrybuty = json.loads(m.group(3)) if m.group(3) else {}

        if m.group(4):  # samozamykający
            bloki.append((nazwa, atrybuty, tresc[m.start():m.end()]))
            poz = m.end()
            continue

        # Szukamy zamknięcia pasującego do tego bloku, licząc zagnieżdżenia.
        glebokosc = 1
        kursor = m.end()
        while glebokosc:
            nast_o = OTWARCIE.search(tresc, kursor)
            nast_z = ZAMKNIECIE.search(tresc, kursor)
            if not nast_z:
                raise ValueError('Brak zamknięcia bloku %s' % nazwa)
            if nast_o and nast_o.start() < nast_z.start():
                if not nast_o.group(4):
                    glebokosc += 1
                kursor = nast_o.end()
                continue
            glebokosc -= 1
            kursor = nast_z.end()

        bloki.append((nazwa, atrybuty, tresc[m.start():kursor]))
        poz = kursor

    return bloki


def zlacz(bloki):
    return '\n'.join(b[2] for b in bloki) + '\n'


def podmien_atrybuty(blok, zmiany):
    """Zwraca blok z nadpisanymi atrybutami."""
    nazwa, atrybuty, tekst = blok
    nowe = dict(atrybuty)
    nowe.update(zmiany)
    # Ostre nawiasy uciekają tak, jak robi to serializer Gutenberga. Bez tego
    # zapis działa tylko dlatego, że WordPress poprawia to po nas.
    surowe = json.dumps(nowe, ensure_ascii=False, separators=(',', ':'))
    surowe = surowe.replace('<', '\\u003c').replace('>', '\\u003e')
    stary = OTWARCIE.search(tekst)
    glowa = '<!-- wp:%s %s %s-->' % (nazwa, surowe, '/' if stary.group(4) else '')
    return (nazwa, nowe, glowa + tekst[stary.end():])
