# -*- coding: utf-8 -*-
"""Dłuższy tekst na podzakładce API, wprost z pliku Klienta.

Krótka wersja (u Klienta na pomarańczowo) siedzi już na ogólnej stronie
Oferty, więc tutaj idzie wyłącznie tekst rozwinięty. Nagłówki sekcji są
Klienta, dopisane zostały tylko krótkie etykiety nad nimi - tak jak na
pozostałych podstronach produktowych.
"""
import json
import bloki

SEKCJE = [
    ('Współpraca', 'Wsparcie na każdym etapie', False, [
        'W ramach współpracy zapewniamy wsparcie na każdym etapie procesu - od wyboru '
        'odpowiedniego źródła API i kwalifikacji wytwórcy, poprzez import i organizację '
        'dostaw, aż po zapewnienie wymaganej dokumentacji jakościowej i regulacyjnej. '
        'Dzięki doświadczeniu w międzynarodowym łańcuchu dostaw możemy odpowiadać zarówno '
        'na potrzeby producentów produktów leczniczych, jak i innych podmiotów działających '
        'w branży farmaceutycznej.',
    ]),
    ('Jakość', 'Jakość i zgodność regulacyjna', True, [
        'Posiadamy odpowiednie certyfikaty oraz uprawnienia do prowadzenia działalności '
        'w zakresie importu i dystrybucji substancji czynnych, zgodnie z obowiązującymi '
        'przepisami polskiego prawa farmaceutycznego oraz wymaganiami Dobrej Praktyki '
        'Dystrybucyjnej (GDP). Stosowane przez nas procedury mają na celu zapewnienie '
        'odpowiedniej jakości, bezpieczeństwa i identyfikowalności substancji czynnych '
        'na każdym etapie łańcucha dostaw.',
    ]),
    ('Portfolio', 'Szerokie portfolio i elastyczne podejście', False, [
        'Nasza oferta obejmuje substancje czynne stosowane w różnych obszarach '
        'terapeutycznych. Dzięki współpracy z producentami z wielu regionów świata możemy '
        'poszukiwać rozwiązań dopasowanych do indywidualnych potrzeb Klientów - zarówno '
        'pod względem parametrów jakościowych i regulacyjnych, jak i dostępności oraz '
        'ciągłości dostaw.',
        'Rozumiemy, że dla producentów farmaceutycznych kluczowe znaczenie ma nie tylko '
        'jakość samego API, ale również przewidywalność dostaw, bezpieczeństwo źródła oraz '
        'sprawna komunikacja na każdym etapie współpracy. Dlatego stawiamy na długoterminowe '
        'relacje z dostawcami i Klientami, transparentność procesu oraz indywidualne '
        'podejście do każdego projektu.',
    ]),
    ('Bezpieczeństwo', 'Bezpieczny łańcuch dostaw API', True, [
        'Bezpieczeństwo i jakość łańcucha dostaw są podstawą naszej działalności. '
        'Monitorujemy współpracę z kwalifikowanymi wytwórcami oraz stosujemy procedury '
        'mające na celu ograniczenie ryzyka związanego z dostawami substancji czynnych. '
        'Szczególną uwagę zwracamy na zgodność dostaw z uzgodnionymi wymaganiami '
        'jakościowymi, właściwe warunki transportu i przechowywania oraz pełną '
        'identyfikowalność procesu.',
    ]),
]

# Wstęp trafia do bloku kategorii, obok zdjęcia i tabeli surowców.
P1 = ('Oferujemy kompleksowe usługi w zakresie importu, dostaw i dystrybucji substancji '
      'czynnych farmaceutycznych (API), przeznaczonych do wytwarzania produktów leczniczych '
      'dla ludzi oraz produktów leczniczych weterynaryjnych. <strong>Współpracujemy z '
      'kwalifikowanymi wytwórcami z Europy, Stanów Zjednoczonych, Korei Południowej, '
      'Japonii, Chin i Indii,</strong> zapewniając dostęp do szerokiego portfolio substancji '
      'czynnych reprezentujących różne grupy terapeutyczne.')

P2 = ('Naszym priorytetem jest zapewnienie stabilnego i bezpiecznego źródła dostaw API, '
      'spełniającego wymagania jakościowe i regulacyjne obowiązujące na rynku europejskim. '
      'Każdy wytwórca, z którym podejmujemy współpracę, podlega formalnemu procesowi '
      'kwalifikacji. Proces ten obejmuje ocenę producenta pod kątem jego kompetencji, '
      'standardów wytwarzania, systemu jakości, zgodności z wymaganiami GMP oraz '
      'dostępności i kompletności dokumentacji wymaganej dla danego rynku.')

FORM_SUB = ('Jeżeli poszukują Państwo sprawdzonego dostawcy API, zapraszamy do kontaktu. '
            'Przygotujemy rozwiązanie dopasowane do indywidualnych wymagań jakościowych, '
            'regulacyjnych i zakupowych.')


def prose(label, tytul, alt, akapity):
    glowa = json.dumps({'label': label, 'title': tytul, 'alt': alt},
                       ensure_ascii=False, separators=(',', ':'))
    srodek = '\n'.join(
        '<!-- wp:paragraph -->\n<p>%s</p>\n<!-- /wp:paragraph -->' % a for a in akapity)
    return '<!-- wp:ntc/prose %s -->\n%s\n<!-- /wp:ntc/prose -->' % (glowa, srodek)


def zbuduj(tresc):
    b = bloki.podziel(tresc)
    hero, kategoria, formularz = b[0], b[1], b[-1]

    kategoria = bloki.podmien_atrybuty(kategoria, {'p1': P1, 'p2': P2})
    formularz = bloki.podmien_atrybuty(formularz, {'sub': FORM_SUB})

    czesci = [hero[2], kategoria[2]]
    czesci += [prose(*s) for s in SEKCJE]
    czesci.append(formularz[2])

    return '\n'.join(czesci) + '\n'
