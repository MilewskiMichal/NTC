# -*- coding: utf-8 -*-
"""Zmiany atrybutów bloków na dowolnym poziomie zagnieżdżenia.

bloki.podmien_atrybuty działa na blokach najwyższego poziomu; kafelki,
liczniki czy karty dojazdu siedzą w środku sekcji, więc tutaj przepisujemy
bezpośrednio nagłówki komentarzy bloków.
"""
import json
import re

import api

OTWARCIE = re.compile(r'<!--\s+wp:([a-z0-9/-]+)(\s+(\{.*?\}))?\s*(/)?-->', re.S)

EN = {16: 785, 557: 787, 8: 788, 549: 789, 57: 790, 58: 791, 552: 792, 455: 793,
      309: 794, 555: 795, 55: 796, 11: 797, 3: 798, 703: 799, 623: 801, 685: 802, 686: 803}


def koduj(attrs):
    surowe = json.dumps(attrs, ensure_ascii=False, separators=(',', ':'))
    return surowe.replace('<', '\\u003c').replace('>', '\\u003e').replace('--', '\\u002d\\u002d')


def zmien(tresc, nazwa, pasuje, zmiany):
    """Zwraca (treść, liczba zmienionych bloków). Wartość None usuwa atrybut,
    funkcja dostaje starą wartość i zwraca nową."""
    licz = [0]

    def podmien(m):
        if m.group(1) != nazwa:
            return m.group(0)
        attrs = json.loads(m.group(3)) if m.group(3) else {}
        if not pasuje(attrs):
            return m.group(0)
        for k, v in zmiany.items():
            if v is None:
                attrs.pop(k, None)
            elif callable(v):
                attrs[k] = v(attrs.get(k))
            else:
                attrs[k] = v
        licz[0] += 1
        glowa = '<!-- wp:%s ' % nazwa + (koduj(attrs) + ' ' if attrs else '')
        return glowa + ('/' if m.group(4) else '') + '-->'

    return OTWARCIE.sub(podmien, tresc), licz[0]


def tresc(typ, ident):
    return api.call('/wp-json/wp/v2/%s/%d' % (typ, ident), params={'context': 'edit'})['content']['raw']


def zapisz(typ, ident, nowa):
    r = api.call('/wp-json/wp/v2/%s/%d' % (typ, ident), 'POST', {'content': nowa})
    if 'id' not in r:
        raise RuntimeError('Zapis %s/%d: %s' % (typ, ident, str(r)[:300]))
    return r


def popraw(typ, ident, operacje, na_sucho=False):
    """operacje: lista (nazwa_bloku, pasuje, zmiany, oczekiwana_liczba)
    albo ('tekst', stary, nowy, oczekiwana_liczba) dla zwykłej podmiany."""
    t = tresc(typ, ident)
    for op in operacje:
        if op[0] == 'tekst':
            _, stary, nowy, ile = op
            n = t.count(stary)
            t = t.replace(stary, nowy)
        else:
            nazwa, pasuje, zmiany, ile = op
            t, n = zmien(t, nazwa, pasuje, zmiany)
        if ile is not None and n != ile:
            raise RuntimeError('%s/%d: %s - zmian %d, oczekiwano %d' % (typ, ident, op[0] if op[0] != 'tekst' else op[1][:60], n, ile))
    if not na_sucho:
        zapisz(typ, ident, t)
    return t
