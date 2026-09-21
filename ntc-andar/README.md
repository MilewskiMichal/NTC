# NTC Andar - motyw WordPress

Wdrożenie projektu z Claude Design (`NTC Andar - Pakiet.html`) jako motyw
WordPressa. Trzy strony, edytowalne w normalnym edytorze bloków, dwie wersje
językowe, bez płatnych wtyczek i bez zależności zewnętrznych w przeglądarce.

## Jak to jest zbudowane

Każda sekcja projektu jest **blokiem**. Wchodzisz w edycję strony, widzisz hero,
sekcję "O nas", kafelki oferty i tak dalej, klikasz w nagłówek i piszesz.
Zdjęcia wybierasz z biblioteki mediów, przyciski i odnośniki z panelu po prawej,
sekcje przestawiasz i usuwasz jak każde inne bloki.

Bloki są **renderowane po stronie PHP**, a nie zapisywane jako HTML w bazie.
Znaczy to tyle, że poprawka w kodzie działa wstecz na wszystkich stronach, które
ktoś już zapisał - przy blokach zapisujących HTML trzeba by je migrować albo
klikać od nowa.

Co gdzie mieszka:

| Rzecz | Miejsce w kokpicie |
|---|---|
| Treść sekcji, nagłówki, zdjęcia | Strony → edycja, bloki „NTC - …" |
| Katalog substancji (tabele w ofercie) | **Produkty** |
| Kategorie oferty | Produkty → Kategorie |
| Adres, telefon, e-mail, NIP | Wygląd → Dostosuj → **NTC - Dane firmowe** |
| Opis i nota w stopce | Wygląd → Dostosuj → **NTC - Stopka** |
| Tematy w formularzu | Wygląd → Dostosuj → **NTC - Formularze** |
| Menu w pasku i w stopce | Wygląd → Menu |
| Logo | Wygląd → Dostosuj → Tożsamość witryny |
| Napisy interfejsu po angielsku | Języki → Tłumaczenia ciągów (Polylang) |

W kodzie nie ma już żadnego adresu, numeru telefonu ani treści sekcji.

## Instalacja

1. Wgraj motyw: **Wygląd → Motywy → Dodaj nowy → Wyślij motyw** → plik ZIP →
   Zainstaluj → Włącz. (Albo przez FTP do `public_html/wp-content/themes/`.)
2. Utwórz trzy strony: „Strona główna", „Oferta", „Kontakt".
3. W każdej wstaw wzorzec z gotową treścią: w edytorze **+ → Wzorce → NTC Andar**
   → „NTC - Strona główna", „NTC - Oferta" albo „NTC - Kontakt". Wzorzec wstawia
   komplet sekcji razem z tekstami z makiety, od razu edytowalnych.
4. **Ustawienia → Czytanie** → strona startowa: „Strona statyczna" → wybierz
   „Strona główna".
5. **Ustawienia → Bezpośrednie odnośniki** → cokolwiek poza „Zwykłe".
6. **Wygląd → Menu** → zbuduj „Menu główne" i przypisz do lokalizacji. Dopóki
   tego nie zrobisz, motyw pokazuje pozycje z projektu.

Strona „Oferta" powinna mieć slug `oferta`, a „Kontakt" slug `kontakt` - po tym
motyw znajduje je, budując odnośniki w stopce i przyciskach.

## Produkty

Tabele w ofercie zaciągają się z typu treści **Produkty**. Jeden produkt to
jeden wpis: tytuł to nazwa substancji, do tego cztery pola (nr CAS, forma,
dokumentacja, pochodzenie) i kategoria.

- **Dokumentacja** - skróty po przecinku (`CEP, GMP, WC`). Każdy wyświetli się
  jako osobna plakietka.
- **Kolejność** - pole „Kolejność" w Atrybutach strony. Bez niej produkty idą
  alfabetycznie.
- Blok „NTC - Kategoria oferty" wybiera kategorię w panelu po prawej i sam
  buduje tabelę razem z wyszukiwarką.

### Dane demonstracyjne

Przy pierwszym włączeniu motyw zakłada trzy kategorie (`api`, `probiotyki`,
`laktoferyna`) i wstawia **20 pozycji z makiety**, żeby tabele miały co pokazać
przed dostarczeniem katalogu przez klienta.

**To nie jest oferta NTC Andar.** Nazwy substancji i numery CAS są prawdziwe (to
publiczne identyfikatory), ale przypisanie ich do oferty, kraje pochodzenia i
komplety dokumentacji zostały wymyślone na potrzeby projektu graficznego. Nie
nadają się do publikacji.

Dlatego każda taka pozycja jest oznaczona:

- na liście produktów ma plakietkę „dane demonstracyjne",
- w kokpicie wisi ostrzeżenie, dopóki choć jedna została,
- kasuje się je hurtem w **Produkty → Dane demonstracyjne**.

Ten sam ekran ma przycisk do ponownego wstawienia, gdyby motyw był włączony
wcześniej albo gdyby ktoś skasował je za wcześnie. Wstawianie jest idempotentne -
nie zdubluje tego, co już jest.

Usuwanie rusza wyłącznie wpisy z oznaczeniem `_ntc_demo`, więc produkty dopisane
ręcznie zostają nietknięte, nawet jeśli mają tę samą nazwę.

## Dwujęzyczność

Motyw jest przygotowany pod **Polylang** (darmowy).

1. Zainstaluj Polylang, dodaj język polski (domyślny) i angielski.
2. Przy każdej stronie kliknij „+" w kolumnie angielskiej, żeby zrobić
   tłumaczenie. Najprościej: Polylang potrafi zduplikować treść, a Ty podmieniasz
   teksty w blokach.
3. Napisy, których nie ma w edytorze (nagłówki tabeli, etykiety pól formularza,
   zgoda RODO, komunikat po wysłaniu) są w **Języki → Tłumaczenia ciągów**,
   grupa „NTC Andar".
4. Przełącznik w pasku pojawia się sam, na podstawie języków z Polylanga.

Bez Polylanga motyw też działa: wraca wtedy do własnego przełącznika `?lang=en`
i słowników w `inc/strings-pl.php` / `inc/strings-en.php`. To wersja awaryjna -
docelowo lepiej mieć Polylang, bo wtedy angielską treść też edytujesz w kokpicie.

## Zdjęcia

Zdjęcia wybierasz w blokach z biblioteki mediów. Blok bez wybranego zdjęcia
pokazuje firmowy placeholder (gradient teal z ukośnymi paskami) zamiast pustego
miejsca.

Można też podmienić placeholdery hurtem: motyw szuka pliku
`assets/img/<slug>.<jpg|jpeg|png|webp|avif>` i użyje go, jeśli blok nie ma
własnego zdjęcia. Slugi: `hero`, `about`, `contact`, `offer-api`,
`offer-probiotics`, `offer-lactoferrin`, `offer-machines`, `offer-components`,
`offer-docs`, `cat-api`, `cat-probiotyki`, `cat-laktoferyna`, `cat-maszyny`.

Żeby ściągnąć zdjęcia stockowe z prototypu: `bash tools/fetch-images.sh`. To są
wypełniacze z Unsplasha, nie materiały firmowe - przed publikacją warto je
podmienić, a dla tych, które zostaną, sprawdzić licencję.

## Formularze

Oba formularze (strona główna i kontakt) są **makietą**: walidują pola i
pokazują kartę „Dziękujemy", ale **nic nie wysyłają**. Pod przyciskiem jest
adnotacja, żeby nikt się nie nabrał.

Podpięcie prawdziwej wysyłki:

- **Wtyczką** (Contact Form 7, WPForms) - podmień wywołanie
  `ntc_the_contact_form()` w `inc/forms.php` na `do_shortcode()` wtyczki.
- **Ręcznie** - w `initForms()` w `assets/js/main.js` jest jedno oznaczone
  komentarzem miejsce. Trzeba wtedy dorzucić nonce i walidację po stronie
  serwera.

Po podpięciu wyłącz adnotację w **Dostosuj → NTC - Formularze**.

## Fonty

Barlow jest **hostowany w motywie** (`assets/fonts/`, 16 plików woff2), a nie
linkowany do `fonts.gstatic.com`. Wywołanie do Google z przeglądarki
użytkownika przekazuje jego IP stronie trzeciej, co w UE jest problemem pod
RODO.

Podzbiory latin i latin-ext (to drugie niesie ą, ć, ę, ł, ń, ś, ź, ż),
grubości 300-800 plus kursywa 400 i 700 do akcentów w nagłówkach. Barlow nie
ma wersji zmiennej, więc każda grubość to osobny plik - przeglądarka ściąga
tylko te, które strona faktycznie zamawia.

Rodzina jest wpięta przez zmienne `--font-display` i `--font-body` w `:root`
(`assets/css/main.css`), więc podmiana kroju to dwie linijki, a nie
przeszukiwanie arkuszy. Odświeżenie plików: `python3 tools/fetch-fonts.py`.

## Struktura

```
ntc-andar/
├── style.css              nagłówek motywu (style są w assets/css/)
├── functions.php          konfiguracja, kolejka assetów, menu
├── header.php  footer.php
├── page.php  front-page.php        rama dla bloków - the_content()
├── template-oferta.php             zachowane dla zgodności wstecznej
├── template-kontakt.php
├── index.php  404.php
├── inc/
│   ├── blocks.php         definicje 20 bloków + renderowanie w PHP
│   ├── patterns.php       trzy gotowe układy stron z treścią z makiety
│   ├── post-types.php     typ treści Produkty + kategorie + pola
│   ├── forms.php          dwa warianty formularza (makieta)
│   ├── customizer.php     dane firmowe, stopka, formularze
│   ├── polylang.php       integracja i rejestracja napisów interfejsu
│   ├── i18n.php           awaryjny przełącznik ?lang= bez Polylanga
│   ├── strings-pl.php     napisy interfejsu + treść startowa wzorców
│   ├── strings-en.php
│   └── helpers.php        adresy podstron, obrazki, ikony SVG, dane firmy
├── assets/
│   ├── css/  main.css, oferta.css, kontakt.css, editor.css, fonts.css
│   ├── js/   main.js, oferta.js, editor.js
│   ├── fonts/  6 x woff2
│   └── img/  logo + placeholder/
└── tools/
    ├── fetch-images.sh       zdjęcia z prototypu
    ├── fetch-fonts.py        odświeżenie fontów
    └── make-placeholders.py  regeneracja placeholderów
```

`assets/js/editor.js` jest napisany bez JSX i bez kroku budowania - motyw nie
ciągnie `node_modules` i wgrywa się przez FTP jak reszta plików.

CSS i JS schodzą warunkowo, według bloków użytych na stronie: `oferta.css` tam,
gdzie jest blok kategorii, `kontakt.css` tam, gdzie hero kontaktu.

## Czego nie ma z prototypu

- **Panel „Tweaks"** (kolor akcentu, styl hero) - narzędzie Claude Design, nie
  element strony.
- **React, ReactDOM i Babel z CDN-u** - prototyp kompilował JSX w przeglądarce.
  Tutaj front to ok. 6 KB własnego JS-u bez zależności.

## Co doszło ponad prototyp

- **Menu na telefonie.** Prototyp poniżej 600 px chował nawigację
  (`display: none`), więc z telefonu nie było jak dojść do oferty ani kontaktu.
- **Dostępność:** skip link, etykiety `<label>` przy polach, które w projekcie
  mają sam placeholder, `aria-live` przy liczniku wyników wyszukiwarki, widoczny
  focus, obsługa `prefers-reduced-motion`.
- **Progresywne działanie:** bez JS-u statystyki pokazują docelowe liczby,
  zakładki oferty działają jako kotwice, formularz zachowuje się jak zwykły
  formularz HTML.

## Zostało do ustalenia z klientem

- **Karuzela klientów** - nazwy we wzorcu (Polfa, Teva, Adamed…) to wypełniacz
  z prototypu. Trzeba je zastąpić rzeczywistymi referencjami, najlepiej za zgodą
  tych firm. Edytuje się je w bloku „NTC - Karuzela klientów".
- **Katalog produktów** - pozycje z makiety były przykładowe i nie zostały
  zaimportowane. Do wpisania w Produkty.
- **Mapa dojazdu** - jest grafika, nie osadzona mapa. Osadzenie Google Maps
  wymaga zgody w banerze cookies.
- **Dokumenty w stopce** - „Polityka prywatności" i OWS. Po utworzeniu stron
  zbuduj menu w lokalizacji „Stopka - dokumenty"; dopóki go nie ma, ten rząd
  odnośników w ogóle się nie pokazuje, zamiast prowadzić donikąd.
