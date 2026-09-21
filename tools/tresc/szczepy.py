# -*- coding: utf-8 -*-
"""Połączona tabela szczepów probiotycznych: Bioprox i Unique razem.

Dane wprost z pliku poprawek Klienta z 14.09. Numer kolekcji wychodzi z nazwy
do osobnej kolumny, bo w tabeli Klienta stoi osobno - dotąd siedział w nazwie
w nawiasie i nie dało się po nim sortować ani filtrować osobno.
"""

# (rodzaj, szczep, kolekcja, postbiotyk, wytwórca, kraj)
SZCZEPY = [
    ('Lactobacillus', 'Lactobacillus acidophilus BIO6307', 'CNCM I-6030', True, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Levilactobacillus brevis BIO5542', 'CNCM I-5064', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus delbrueckii subsp. bulgaricus BIO6744', 'CNCM I-5435', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lacticaseibacillus casei BIO5773', 'CNCM I-5094', True, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus crispatus BIO6272', 'CNCM I-5095', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Limosilactobacillus fermentum BIO6529', 'CNCM I-5434', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus gasseri BIO6369', 'CNCM I-5076', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus helveticus BIO6497', 'CNCM I-5433', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus johnsonii BIO5467', 'CIRM BIA650', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactobacillus lactis BIO1890', 'CNCM I-5351', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lacticaseibacillus paracasei subsp. paracasei BIO5452', 'CNCM I-6028', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lactiplantibacillus plantarum BIO1096', 'CNCM I-4909', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Limosilactobacillus reuteri BIO5454', 'CNCM I-6029', True, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lacticaseibacillus rhamnosus BIO5326', 'CIRM BIA-1113', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Lacticaseibacillus rhamnosus BIO6870', 'ATCC 53103', True, 'Bioprox Healthcare', 'Francja'),
    ('Lactobacillus', 'Ligilactobacillus salivarius BIO6313', 'CNCM I-5114', False, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium adolescentis BIO5485', 'CNCM I-5092', False, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium bifidum BIO5480', 'CNCM I-5091', False, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium breve BIO6018', 'CNCM I-5112', True, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium longum subsp. infantis BIO5478', 'CNCM I-5090', False, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium animalis subsp. lactis BIO5764', 'CNCM I-5093', True, 'Bioprox Healthcare', 'Francja'),
    ('Bifidobacterium', 'Bifidobacterium longum subsp. longum BIO6283', 'CNCM I-5096', True, 'Bioprox Healthcare', 'Francja'),
    ('Enterococcus', 'Enterococcus lactis BIO4598', 'CNCM I-4913', False, 'Bioprox Healthcare', 'Francja'),
    ('Lactococcus', 'Lactococcus lactis BIO6722', 'CNCM I-5352', False, 'Bioprox Healthcare', 'Francja'),
    ('Pediococcus', 'Pediococcus acidilactici BIO6314', 'CNCM I-5098', False, 'Bioprox Healthcare', 'Francja'),
    ('Streptococcus', 'Streptococcus thermophilus BIO1488', 'CNCM I-4912', False, 'Bioprox Healthcare', 'Francja'),
    ('Bacillus', 'Bacillus coagulans Unique IS-2®', 'MTCC 5260', False, 'Unique Biotech', 'Indie'),
    ('Bacillus', 'Bacillus clausii UBBC-07®', 'MTCC 5472', False, 'Unique Biotech', 'Indie'),
    ('Saccharomyces', 'Saccharomyces boulardii Unique 28®', 'MTCC 5375', False, 'Unique Biotech', 'Indie'),
]


import re
import unicodedata


def slug(tekst):
    """Adres produktu - bez znaków towarowych i ogonków."""
    t = unicodedata.normalize('NFKD', tekst)
    t = ''.join(c for c in t if not unicodedata.combining(c))
    t = t.replace('\u00ae', '').replace('\u2122', '')
    return re.sub(r'[^a-zA-Z0-9]+', '-', t).strip('-').lower()


def operacje(kategoria='probiotyki'):
    """Wsad do wtyczki: jeden produkt na szczep."""
    ops = []

    for i, (rodzaj, nazwa, kolekcja, postbiotyk, wytworca, kraj) in enumerate(SZCZEPY, 1):
        ops.append({
            'op': 'product',
            'slug': slug(nazwa),
            'title': nazwa,
            'category': kategoria,
            'order': i,
            'meta': {
                '_ntc_group': rodzaj,
                '_ntc_collection': kolekcja,
                '_ntc_postbiotic': 'tak' if postbiotyk else '',
                '_ntc_maker': wytworca,
                '_ntc_origin': kraj,
                '_ntc_cas': '',
                '_ntc_form': '',
                '_ntc_docs': '',
                '_ntc_use': '',
            },
        })

    return ops


def slugi():
    return [slug(s[1]) for s in SZCZEPY]
