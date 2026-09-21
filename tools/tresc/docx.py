# -*- coding: utf-8 -*-
"""Wyciąga tekst z docx z zachowaniem akapitów, pogrubień i kolorów."""
import re, sys, zipfile
from xml.etree import ElementTree as ET

W = '{http://schemas.openxmlformats.org/wordprocessingml/2006/main}'

def czytaj(sciezka, znaczniki=True):
    with zipfile.ZipFile(sciezka) as z:
        xml = z.read('word/document.xml')
    korzen = ET.fromstring(xml)
    out = []
    for p in korzen.iter(W + 'p'):
        kawalki = []
        for r in p.iter(W + 'r'):
            t = ''.join(n.text or '' for n in r.iter(W + 't'))
            if not t:
                continue
            rpr = r.find(W + 'rPr')
            b = rpr is not None and rpr.find(W + 'b') is not None
            kolor = ''
            if rpr is not None:
                c = rpr.find(W + 'color')
                if c is not None:
                    kolor = c.get(W + 'val', '')
            if znaczniki:
                if kolor and kolor.lower() not in ('000000', 'auto'):
                    t = '{#%s}%s{/}' % (kolor, t)
                if b:
                    t = '**%s**' % t
            kawalki.append(t)
        linia = ''.join(kawalki).strip()
        styl = p.find(W + 'pPr/' + W + 'pStyle')
        if styl is not None and znaczniki:
            s = styl.get(W + 'val', '')
            if s.startswith('Heading') or s.startswith('Nagwek'):
                linia = '### ' + linia
        if linia:
            out.append(linia)
    return '\n'.join(out)

if __name__ == '__main__':
    print(czytaj(sys.argv[1]))
