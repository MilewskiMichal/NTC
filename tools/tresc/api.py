# -*- coding: utf-8 -*-
"""Cienka warstwa nad REST API WordPressa."""
import json, os, subprocess, time

ROOT = '/home/user/NTC'
SCRATCH = '/tmp/claude-0/-home-user-NTC/c0316b99-51c3-55b4-b8f6-63501cd0faa0/scratchpad'


def _env():
    env = {}
    with open(ROOT + '/tools/.env', encoding='utf-8') as fh:
        for line in fh:
            line = line.strip()
            if not line or line.startswith('#') or '=' not in line:
                continue
            k, v = line.split('=', 1)
            env[k.strip()] = v.strip().strip('"').strip("'")
    return env


ENV = _env()
SITE = ENV['NTC_SITE']


def call(sciezka, metoda='GET', dane=None, params=None, proby=4):
    url = SITE + sciezka
    if params:
        from urllib.parse import urlencode
        url += ('&' if '?' in url else '?') + urlencode(params)

    cmd = ['curl', '-sS', '-m', '120', '-X', metoda,
           '--user', '%s:%s' % (ENV['NTC_USER'], ENV['NTC_APP_PASS'])]

    if dane is not None:
        plik = os.path.join(SCRATCH, 'wp', '_body.json')
        with open(plik, 'w', encoding='utf-8') as fh:
            json.dump(dane, fh, ensure_ascii=False)
        cmd += ['-H', 'Content-Type: application/json', '--data-binary', '@' + plik]

    cmd.append(url)

    for proba in range(1, proby + 1):
        wynik = subprocess.run(cmd, capture_output=True, text=True)
        tresc = wynik.stdout.strip()
        try:
            return json.loads(tresc)
        except ValueError:
            if proba == proby:
                raise RuntimeError('Odpowiedź nie jest JSON-em: %s' % tresc[:300])
            time.sleep(proba * 5)


def batch(ops):
    """Wsadowe operacje przez wtyczkę wdrożeniową."""
    plik = os.path.join(SCRATCH, 'wp', '_batch.json')
    with open(plik, 'w', encoding='utf-8') as fh:
        json.dump({'ops': ops}, fh, ensure_ascii=False)

    wynik = subprocess.run(
        ['curl', '-sS', '-m', '180', '-X', 'POST',
         '--user', '%s:%s' % (ENV['NTC_USER'], ENV['NTC_APP_PASS']),
         '-H', 'X-NTC-Deploy-Key: ' + ENV['NTC_DEPLOY_KEY'],
         '-H', 'Content-Type: application/json',
         '--data-binary', '@' + plik,
         SITE + '/wp-json/ntc-deploy/v1/batch'],
        capture_output=True, text=True)

    try:
        return json.loads(wynik.stdout)
    except ValueError:
        raise RuntimeError('Wsad nie zwrócił JSON-a: %s' % wynik.stdout[:300])


def strona(ident):
    return call('/wp-json/wp/v2/pages/%s' % ident, params={'context': 'edit'})
