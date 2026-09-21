# -*- coding: utf-8 -*-
"""Wgrywa pliki graficzne do biblioteki mediów WordPressa."""
import json
import os
import subprocess

import api

def wgraj(sciezka, nazwa, alt, tytul=None):
    """Zwraca (id, url) wgranego pliku; pomija, jeśli taki plik już jest."""
    istnieje = api.call('/wp-json/wp/v2/media', params={
        'search': os.path.splitext(nazwa)[0], 'per_page': 5, '_fields': 'id,slug,source_url'})

    for m in istnieje:
        if m['slug'] == os.path.splitext(nazwa)[0]:
            return m['id'], m['source_url']

    typ = {'jpg': 'image/jpeg', 'jpeg': 'image/jpeg', 'png': 'image/png',
           'webp': 'image/webp'}[nazwa.rsplit('.', 1)[1].lower()]

    wynik = subprocess.run(
        ['curl', '-sS', '-m', '300', '-X', 'POST',
         '--user', '%s:%s' % (api.ENV['NTC_USER'], api.ENV['NTC_APP_PASS']),
         '-H', 'Content-Disposition: attachment; filename="%s"' % nazwa,
         '-H', 'Content-Type: ' + typ,
         '--data-binary', '@' + sciezka,
         api.SITE + '/wp-json/wp/v2/media'],
        capture_output=True, text=True)

    d = json.loads(wynik.stdout)

    if 'id' not in d:
        raise RuntimeError('Nie udało się wgrać %s: %s' % (nazwa, wynik.stdout[:200]))

    api.call('/wp-json/wp/v2/media/%d' % d['id'], 'POST',
             {'alt_text': alt, 'title': tytul or alt})

    return d['id'], d['source_url']
