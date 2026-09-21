# -*- coding: utf-8 -*-
"""Wgrywa trzy artykuły Klienta w miejsce tekstów zastępczych.

Word nie rozróżnia poziomów nagłówków ani nie odróżnia nagłówka od zdania
wyróżnionego pogrubieniem, więc jedno i drugie podajemy wprost - przy trzech
plikach to pewniejsze niż zgadywanie po długości czy kropce na końcu.
"""
import sys
sys.path.insert(0, '/tmp/claude-0/-home-user-NTC/c0316b99-51c3-55b4-b8f6-63501cd0faa0/scratchpad/wp')

import blog_konwert as bk
import api

UPLOAD = '/root/.claude/uploads/c0316b99-51c3-55b4-b8f6-63501cd0faa0/'

ARTYKULY = [
    {
        'plik': '594546db-blog_-_Probiotyki_i_postbiotyki.docx',
        # Klient pisał, że temat i zajawka na kafelku są w porządku, więc
        # zostają te, które już są - podmieniamy wyłącznie treść.
        'slug': 'probiotyki-i-postbiotyki-czym-sie-roznia',
        'tytul': None,
        'zajawka': None,
        'podnaglowki': ('Stabilność', 'Przechowywanie i transport', 'Możliwości zastosowania'),
        'nie_naglowki': (),
    },
    {
        'plik': 'f7a20078-blog_-_Jellicol.docx',
        'slug': 'jellicol-platforma-peptydow-kolagenowych',
        'tytul': 'Jellicol® - jedna marka, kompletna platforma peptydów kolagenowych',
        'zajawka': 'Dlaczego średnia masa cząsteczkowa to dopiero początek i co naprawdę '
                   'odróżnia poszczególne hydrolizaty kolagenowe.',
        'podnaglowki': ('Jellicol® MN – Nutritional', 'Jellicol® MF – Functional',
                        'Jellicol® MFH – Functional + Handling',
                        'Jellicol® MA – Advanced Absorption'),
        'nie_naglowki': (
            'To właśnie profil peptydowy pozwala lepiej zrozumieć różnice pomiędzy '
            'poszczególnymi hydrolizatami.',
            'nie jeden kolagen dla wszystkich, ale właściwy profil peptydów dla '
            'właściwego produktu.',
        ),
    },
    {
        'plik': 'f3134ca6-blog_-_Grass_fed.docx',
        'slug': 'grass-fed-pochodzenie-surowca-bialka-mleka',
        'tytul': 'Grass-Fed - od zielonych pastwisk do wysokiej jakości białek mleka',
        'zajawka': 'Skąd bierze się powtarzalność białek mleka i laktoferyny oraz dlaczego '
                   'historia surowca zaczyna się na pastwisku w Nowej Zelandii.',
        'podnaglowki': (),
        'nie_naglowki': (),
    },
]


def tresc(art):
    pozycje = bk.akapity(UPLOAD + art['plik'])

    # Zdania wyróżnione pogrubieniem wracają do akapitów - inaczej wchodziłyby
    # w środek tekstu jako nagłówki sekcji, których nie otwierają.
    poprawione = [
        ('tekst', '<strong>%s</strong>' % t) if r == 'naglowek' and t in art['nie_naglowki']
        else (r, t)
        for r, t in pozycje
    ]

    return bk.na_bloki(poprawione, art['podnaglowki'])


def main():
    ops = []
    for art in ARTYKULY:
        dane = {'op': 'post', 'slug': art['slug'], 'content': tresc(art)}
        if art['tytul']:
            dane['title'] = art['tytul']
        if art['zajawka']:
            dane['excerpt'] = art['zajawka']
        ops.append(dane)
        print('%-45s %6d znaków' % (art['slug'], len(dane['content'])))
    return ops


if __name__ == '__main__':
    for op in main():
        print()
        print(op['slug'], '->', op['content'][:200].replace('\n', ' '))
