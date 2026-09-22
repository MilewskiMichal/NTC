# -*- coding: utf-8 -*-
"""Wyciąganie i podmiana tekstów do tłumaczenia w treści Gutenberga.

Jednostką jest wartość tekstowa atrybutu bloku albo wnętrze elementu
tekstowego HTML (akapit, punkt listy, nagłówek, komórka). Słownik jest
wspólny dla całego serwisu i kluczowany polskim tekstem, więc zdanie
powtórzone na kilku stronach tłumaczy się raz.
"""
import json
import re

KOMENTARZ = re.compile(r'<!--\s+wp:([a-z0-9/-]+)\s+(\{.*?\})\s*(/?)-->', re.S)
ELEMENT = re.compile(r'<(p|li|h[1-6]|td|th|figcaption|summary)(\s[^>]*)?>(.*?)</\1>', re.S)

# Atrybuty, które nie są tekstem dla czytelnika.
POMIJANE = re.compile(r'(url|href|id|category|columns|icon|anchor|classname|imagepos|'
                      r'imagefit|seed|lang|perpage|slug|kind|variant|style|mode)$', re.I)


def tekstowy(klucz, wartosc):
    return (isinstance(wartosc, str) and not POMIJANE.search(klucz)
            and re.search(r'[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]', wartosc)
            and not re.match(r'^https?://', wartosc))


def wyciagnij(tresc):
    """Zwraca listę tekstów do tłumaczenia, w kolejności wystąpienia."""
    out = []
    for m in KOMENTARZ.finditer(tresc):
        try:
            atr = json.loads(m.group(2))
        except ValueError:
            continue
        for k, v in atr.items():
            if tekstowy(k, v):
                out.append(v)
    bez_komentarzy = re.sub(r'<!--.*?-->', '', tresc, flags=re.S)
    for m in ELEMENT.finditer(bez_komentarzy):
        wnetrze = m.group(3).strip()
        if re.search(r'[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]', re.sub(r'<[^>]+>', '', wnetrze)):
            out.append(wnetrze)
    return out


def _json_atr(atr):
    s = json.dumps(atr, ensure_ascii=False, separators=(',', ':'))
    return s.replace('<', '\\u003c').replace('>', '\\u003e')


def przetlumacz(tresc, slownik, brakujace):
    """Podmienia teksty według słownika; brakujące dopisuje do zbioru."""
    def atrybuty(m):
        try:
            atr = json.loads(m.group(2))
        except ValueError:
            return m.group(0)
        for k, v in list(atr.items()):
            if tekstowy(k, v):
                if v in slownik:
                    atr[k] = slownik[v]
                else:
                    brakujace.add(v)
        return '<!-- wp:%s %s %s-->' % (m.group(1), _json_atr(atr), m.group(3))

    tresc = KOMENTARZ.sub(atrybuty, tresc)

    def element(m):
        wnetrze = m.group(3)
        klucz = wnetrze.strip()
        if not re.search(r'[A-Za-zĄĆĘŁŃÓŚŹŻąćęłńóśźż]', re.sub(r'<[^>]+>', '', klucz)):
            return m.group(0)
        if klucz in slownik:
            return '<%s%s>%s</%s>' % (m.group(1), m.group(2) or '', slownik[klucz], m.group(1))
        brakujace.add(klucz)
        return m.group(0)

    # Elementy tylko poza komentarzami bloków - w komentarzach siedzi JSON.
    czesci = re.split(r'(<!--.*?-->)', tresc, flags=re.S)
    czesci = [c if c.startswith('<!--') else ELEMENT.sub(element, c) for c in czesci]
    return ''.join(czesci)
