# -*- coding: utf-8 -*-
"""Rozmieszczenie zdjęć wg pliku wizualizacje.docx.

Klient zaznaczył, że układ na podzakładkach produktowych jest przybliżony i
można go poprawiać graficznie - byle zdjęcie nie powędrowało do innej
podzakładki. Trzymamy się więc przypisania do stron, a kolejność w obrębie
strony dobieramy tak, żeby zdjęcia przeplatały się z tekstem.
"""
import json
import re

# Nowe pliki w bibliotece mediów.
M = {
    'kapsulka':    (706, 'kapsulka-rozpuszczajaca-sie.jpg', 'Kapsułka rozpuszczająca się w proszek'),
    'api':         (707, 'kapsulki-substancje-czynne.jpg', 'Kapsułki wysypane z butelki'),
    'mikrobiom':   (708, 'mikrobiom-jelitowy.jpg', 'Dłonie na brzuchu - zdrowie jelit'),
    'unique28':    (709, 'unique-28-boulardii.png', 'Saccharomyces boulardii Unique 28'),
    'ubbc07':      (710, 'ubbc-07-clausii.png', 'Bacillus clausii UBBC-07'),
    'is2':         (711, 'unique-is2-coagulans.webp', 'Bacillus coagulans Unique IS-2'),
    'laktoferyna': (712, 'laktoferyna-proszek-scaled.jpg', 'Laktoferyna w proszku'),
    'grassfed':    (713, 'tatua-grass-fed-scaled.png', 'Certyfikat Grass-Fed firmy Tatua'),
    'gly':         (714, 'peptydy-kolagenowe-gly.jpg', 'Peptyd kolagenowy z resztą glicyny'),
    'collameta':   (715, 'collameta-logo.png', 'Logo Collameta'),
    'colostrum':   (716, 'colostrum-platy.jpg', 'Colostrum bydlęce w postaci płatów'),
    'kolagen':     (717, 'kolagen-proszek-tabletki.jpg', 'Kolagen w proszku i tabletkach'),
    'maszyny':     (718, 'maszyny-farmaceutyczne.png', 'Maszyny dla przemysłu farmaceutycznego'),
    'uscisk':      (719, 'uscisk-dloni.jpg', 'Uścisk dłoni po zawarciu umowy'),
    'siec':        (720, 'dlonie-polaczone-siec-scaled.jpg', 'Połączone dłonie z motywem sieci NTC'),
    'uslugi':      (721, 'uslugi-lancuch-dostaw.jpg', 'Dłonie z kapsułką i przesyłką na tle mapy świata'),
}

BAZA = 'https://ntc-strona.salescons.pl/wp-content/uploads/2026/09/'


def atrybuty(klucz):
    ident, plik, alt = M[klucz]
    return {'imageId': ident, 'imageUrl': BAZA + plik, 'imageAlt': alt}


def podmien_w_bloku(tekst, klucz):
    """Nadpisuje trójkę imageId/imageUrl/imageAlt w pojedynczym bloku."""
    a = atrybuty(klucz)
    for pole, wartosc in a.items():
        wzor = r'"%s":(?:"[^"]*"|\d+|null)' % pole
        nowy = '"%s":%s' % (pole, json.dumps(wartosc, ensure_ascii=False))
        if re.search(wzor, tekst):
            tekst = re.sub(wzor, nowy.replace('\\', '\\\\'), tekst, count=1)
        else:
            # Bloku bez pola obrazka trzeba je dopisać przed zamknięciem atrybutów.
            tekst = re.sub(r'(\{.*?)(\}\s*/?-->)', r'\1,' + nowy.replace('\\', '\\\\') + r'\2',
                           tekst, count=1, flags=re.S)
    return tekst
