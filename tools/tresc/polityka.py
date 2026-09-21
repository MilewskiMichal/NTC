# -*- coding: utf-8 -*-
"""Nowy tekst Polityki Jakości z 30.06.2025.

Klient prosił o podmianę treści przy zachowaniu obecnego układu, więc tekst
wchodzi w te same trzy sekcje i te same wyróżnienia - kolorem marki idą nazwy
norm, aktów prawnych i procedur, tak jak dotąd. Nowy jest ostatni punkt o
odpowiedzialności Zarządu; w pliku Klienta jest w całości pogrubiony, ale
wyróżnianie całego akapitu zjadłoby czytelność listy, więc kolorem idzie sama
teza, a reszta zdania zostaje zwykłym tekstem.
"""
import json
import bloki

SEKCJE = [
    # (indeks bloku na stronie, punkty)
    (1, [
        'Polityka Jakości swoim zakresem obejmuje działalność NTC ANDAR Sp. z o. o. '
        'polegającą na <strong>imporcie i dystrybucji czynnych substancji farmaceutycznych '
        'do produktów leczniczych dla ludzi oraz produktów leczniczych weterynaryjnych</strong>, '
        'jak również pozostałych materiałów wyjściowych i surowców produkcyjnych dla branży '
        'farmaceutycznej, spożywczej, kosmetycznej, weterynaryjnej oraz dla branż pokrewnych.',

        'Polityka Jakości jest realizowana poprzez opracowanie, realizację oraz nadzór nad '
        'zakładowym <strong>Systemem Zarządzania Jakością (SZJ)</strong>, który strukturyzuje '
        'istniejące procesy oraz zapewnia utrzymanie bezpieczeństwa produktów oraz ich '
        'wymaganej jakości.',

        'SZJ oparty jest o założenia normy <strong>EN ISO 9001:2015</strong>. Jest opisany, '
        'realizowany, audytowany oraz ustawicznie doskonalony. Obejmuje on działania konieczne '
        'do zapewnienia, że realizowane procesy importu, magazynowania, dystrybucji, transportu '
        'oraz realizacji zamówień spełniają wymagania prawne oraz oczekiwane wymagania '
        'ilościowe i jakościowe.',

        'W szczególności SZJ gwarantuje pełne wypełnianie przez Spółkę wymogów wynikających z '
        '<strong>Prawa Farmaceutycznego, Dobrej Praktyki Wytwarzania oraz Dobrej Praktyki '
        'Dystrybucji Substancji Czynnych</strong> Wykorzystywanych jako Materiały Wyjściowe '
        'Przeznaczone do Wytwarzania Produktów Leczniczych oraz <strong>Dobrej Praktyki '
        'Dystrybucyjnej</strong> dotyczącej substancji czynnych stosowanych jako materiały '
        'wyjściowe w weterynaryjnych produktach leczniczych.',
    ]),
    (2, [
        'System dokumentacji jest wykorzystywany w celu ustanowienia, kontroli, monitorowania '
        'i rejestrowania działań, które bezpośrednio lub pośrednio mają wpływ na wszystkie '
        'aspekty jakości.',

        'Spółka gromadzi i przechowuje dokumentację, pozwalającą na <strong>śledzenie łańcuchów '
        'dostaw</strong> w celu umożliwienia pełnej identyfikowalności procesów transportu, '
        'magazynowania i warunków środowiskowych (jeśli dotyczy), w jakich importowany bądź '
        'dystrybuowany materiał lub surowiec znajdował się na każdym etapie procesu realizacji '
        'zamówienia zgodnie z ustaloną procedurą monitorowania ścieżki dystrybucji.',

        'Zapewnienie jakości API realizowane jest przez wdrożony <strong>system nadzoru nad '
        'odchyleniami</strong> oraz kontrolę zmian. W stosownych przypadkach podejmowane są '
        'działania korygujące i zapobiegawcze.',

        'Obowiązujące <strong>procedury reklamacji</strong> oraz <strong>nadzoru nad substancją '
        'niezgodną</strong> szczegółowo opisują tryb postępowania oraz odpowiedzialność w tym '
        'zakresie. Reklamacje oraz postępowania wyjaśniające podlegają dokumentowaniu oraz są '
        'efektywnie realizowane.',

        'Dostawcy kluczowych usług oraz dostawcy czynnych substancji farmaceutycznych podlegają '
        '<strong>kwalifikacji</strong>.',

        'Wszystkie obszary działania Spółki objęte systemem zapewnienia jakości podlegają '
        'regularnym przeglądom i audytom.',
    ]),
    (3, [
        'Wszyscy pracownicy posiadają <strong>odpowiednie kwalifikacje i doświadczenie '
        'zawodowe</strong>, podlegają ustawicznym szkoleniom oraz są zaangażowani w sprawy '
        'jakości. Każdy pracownik jest odpowiedzialny za realizowanie zadań wynikających z '
        'niniejszej Polityki Jakości w zakresie wynikającym z jego obowiązków na danym '
        'stanowisku pracy.',

        'W strukturze organizacyjnej wyodrębnia się <strong>osobę z najwyższego kierownictwa '
        'odpowiedzialną za koordynację prac związanych z nadzorowaniem systemu zarządzania '
        'poprzez jakość</strong>, która odpowiada za organizację Zarządzania Jakością oraz '
        'nadzór nad wdrożeniem i funkcjonowaniem systemu zgodnie z obowiązującymi przepisami '
        'prawa.',
    ]),
]

# Ostatni akapit Polityki - ten o odpowiedzialności Zarządu - do listy nie
# wchodzi. Stoi już w pasku cytatu tuż pod nią, w brzmieniu identycznym z
# nowym plikiem, więc powtórzony dwa razy pod rząd wyglądałby na pomyłkę.


def atrybut(dane):
    """JSON do atrybutu bloku - z ucieczką ostrych nawiasów.

    Niezamienione < zamknęłoby komentarz HTML, w którym siedzi blok, i
    Gutenberg przestałby go widzieć.
    """
    surowe = json.dumps(dane, ensure_ascii=False, separators=(',', ':'))
    return surowe.replace('<', '\\u003c').replace('>', '\\u003e')


def zbuduj(tresc):
    b = bloki.podziel(tresc)

    for idx, punkty in SEKCJE:
        nazwa, atrybuty, tekst = b[idx]
        glowa = tekst.split('\n')[0]
        srodek = '\n'.join(
            '<!-- wp:ntc/check-item %s /-->' % atrybut({'text': p}) for p in punkty)
        b[idx] = (nazwa, atrybuty, '%s\n%s\n<!-- /wp:ntc/checklist -->' % (glowa, srodek))

    return bloki.zlacz(b)
