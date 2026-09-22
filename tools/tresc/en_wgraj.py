# -*- coding: utf-8 -*-
"""Tworzy albo aktualizuje wersje angielskie stron i wpisów.

Słowniki leżą w en/s*.py (każdy z mapą T: tekst polski -> angielski).
Tekst, którego nie ma w słowniku, zostaje bez zmian i trafia do raportu -
dla nazw własnych to w porządku, dla zdań znaczy brak tłumaczenia.
"""
import glob
import importlib.util
import json
import os
import sys

import api
import jednostki

EN = os.path.join(os.path.dirname(os.path.abspath(__file__)), 'en')


def slownik():
    T = {}
    FRAZY.clear()
    for plik in sorted(glob.glob(os.path.join(EN, 's*.py'))):
        spec = importlib.util.spec_from_file_location(os.path.basename(plik)[:-3], plik)
        mod = importlib.util.module_from_spec(spec)
        spec.loader.exec_module(mod)
        T.update(mod.T)
        FRAZY.update(getattr(mod, 'FRAZY', {}))
    return T


# Zwykłe podmiany tekstu po tłumaczeniu jednostek - na fragmenty, których
# jednostki nie łapią (np. wstęp punktu, w którym zaczyna się lista zagnieżdżona).
FRAZY = {}


def wgraj(slugi=None, na_sucho=False):
    T = slownik()
    zrodla = json.load(open(os.path.join(EN, 'zrodla.json'), encoding='utf-8'))
    raport = {}

    for slug, z in zrodla.items():
        if slugi and slug not in slugi:
            continue

        brak = set()
        tresc = jednostki.przetlumacz(z['content'], T, brak)
        for pl, en in FRAZY.items():
            tresc = tresc.replace(pl, en)
        tytul = T.get(z['title'], z['title'])
        if z['title'] not in T:
            brak.add(z['title'])
        zajawka = T.get(z.get('excerpt', ''), z.get('excerpt', ''))
        raport[slug] = sorted(brak)

        if na_sucho:
            continue

        typ = 'pages' if z['typ'] == 'page' else 'posts'
        zrodlo = api.call('/wp-json/wp/v2/%s/%d' % (typ, z['id']),
                          params={'context': 'edit', '_fields': 'id,meta'})
        en_id = int((zrodlo.get('meta') or {}).get('_ntc_en_id') or 0)

        dane = {'title': tytul, 'content': tresc, 'excerpt': zajawka, 'status': 'publish',
                'meta': {'_ntc_zrodlo': z['id']}}

        if en_id:
            d = api.call('/wp-json/wp/v2/ntc_en/%d' % en_id, 'POST', dane)
        else:
            d = api.call('/wp-json/wp/v2/ntc_en', 'POST', dane)
            if 'id' in d:
                api.call('/wp-json/wp/v2/%s/%d' % (typ, z['id']), 'POST', {'meta': {'_ntc_en_id': d['id']}})

        print('%-24s -> wersja EN %s' % (slug, d.get('id', d)))

    return raport


if __name__ == '__main__':
    na_sucho = '--na-sucho' in sys.argv
    slugi = [a for a in sys.argv[1:] if not a.startswith('--')]
    r = wgraj(slugi or None, na_sucho)
    for slug, brak in r.items():
        if brak:
            print('\n[%s] bez tłumaczenia (%d):' % (slug, len(brak)))
            for b in brak:
                print('   ', b[:110])
