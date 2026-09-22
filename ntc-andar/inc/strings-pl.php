<?php
/**
 * Słownik polski - wersja źródłowa treści.
 *
 * Klucze z sufiksem _html celowo zawierają HTML (<em> to kursywa akcentowa w
 * kolorze teal, <br> to twardy podział wiersza z projektu) i są renderowane
 * przez ntc_e_raw(). Reszta idzie przez ntc_e() i jest escapowana.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

return array(

	/* ---------------------------------------------------------------- Nawigacja */
	'nav.about'                => 'O nas',
	'nav.quality'              => 'Jakość',
	'nav.offer'                => 'Oferta',
	'nav.how'                  => 'Jak działamy',
	'nav.contact'              => 'Kontakt',
	'nav.back'                 => 'Powrót do strony',
	'nav.lang_label'           => 'Zmień język na angielski',
	'nav.skip'                 => 'Przejdź do treści',
	'nav.menu'                 => 'Menu',

	/* -------------------------------------------------------- Strona główna: hero */
	'home.title'               => 'NTC Andar - dystrybucja surowców dla farmacji, suplementów i kosmetyków',
	'home.meta_desc'           => 'Import i dystrybucja substancji aktywnych (API), probiotyków i laktoferyny dla przemysłu farmaceutycznego, spożywczego i kosmetycznego. Certyfikat GDP, od 2000 roku.',
	'hero.badge'               => 'NTC Andar',
	'hero.title_html'          => 'Sprawdzone surowce<br/>dla <em>Twojej produkcji</em>',
	'hero.sub'                 => 'Dystrybutor substancji aktywnych dla przemysłu farmaceutycznego, spożywczego, weterynaryjnego i kosmetycznego. Od 2000 roku.',
	'hero.btn_offer'           => 'Poznaj ofertę',
	'hero.btn_quality'         => 'Standardy jakości',
	'hero.img_alt'             => 'Specjalistka w laboratorium farmaceutycznym',

	/* --------------------------------------------------------- Strona główna: o nas */
	'about.label'              => 'Kim jesteśmy?',
	'about.title_html'         => 'Polska, niezależna<br/>firma <em>rodzinna</em>',
	'about.p1'                 => 'Jesteśmy polską, niezależną firmą rodzinną, której początki sięgają 2000 roku. Od 25 lat rozwijamy działalność w oparciu o rodzinny kapitał i zarząd.',
	'about.p2'                 => 'Koncentrujemy się na wybranych branżach i produktach, dzięki czemu możemy zapewnić naszym Partnerom i Klientom wysoki poziom specjalizacji oraz obsługi.',
	'about.p3'                 => 'Specjalizujemy się w imporcie i dystrybucji substancji czynnych, innych kluczowych materiałów i surowców dla branży farmaceutycznej oraz specjalistycznych komponentów dla przemysłu spożywczego. Wybrane produkty znajdują również zastosowanie w branży kosmetycznej i weterynaryjnej.',
	'about.img_alt'            => 'Zespół NTC Andar w biurze',
	'about.accent_num'         => '25+',
	'about.accent_txt'         => 'lat doświadczenia',
	'about.stat1_num'          => '100+',
	'about.stat1_label'        => 'produktów w ofercie',
	'about.stat2_num'          => 'EU',
	'about.stat2_label'        => 'uprawnienia importowe',

	/* -------------------------------------------------- Strona główna: co nas wyróżnia */
	'why.label'                => 'Co nas wyróżnia?',
	'why.title_html'           => 'Standardy na poziomie<br/>farmaceutycznym',
	'why.intro'                => 'Niezależnie od branży - farmaceutycznej, spożywczej, kosmetycznej czy weterynaryjnej - działamy w oparciu o standardowe procedury operacyjne i system zarządzania jakością.',

	'why.1.title'              => 'Farmaceutyczna jakość',
	'why.1.text'               => 'Działamy zgodnie z farmaceutycznym systemem zarządzania jakością oraz zasadami GDP. Posiadamy uprawnienia do importu i dystrybucji substancji czynnych na terenie UE.',
	'why.2.title'              => 'Kwalifikowani dostawcy',
	'why.2.text'               => 'Wytwórcy substancji czynnych oraz kluczowi podwykonawcy podlegają formalnej kwalifikacji oraz okresowej ocenie.',
	'why.3.title'              => 'Substancje rzadkie i specjalistyczne',
	'why.3.text'               => 'W naszej ofercie znajduje się szeroka gama substancji unikatowych (m.in. produkty pochodzenia zwierzęcego, UPPZ kat. 3, wymagające kontrolowanych warunków).',
	'why.4.title'              => 'Elastyczność i dynamika',
	'why.4.text'               => 'Działamy elastycznie i sprawnie, realizując większość dostaw bezpośrednio od wytwórcy, zgodnie z indywidualnymi wymaganiami odbiorcy. Oferujemy także dedykowane poszukiwanie surowców rzadkich.',

	/* -------------------------------------------------------- Strona główna: oferta */
	'offer.label'              => 'Oferta',
	'offer.title_html'         => 'Nasze produkty<br/>i usługi',
	'offer.cta'                => 'Zapytaj o ofertę',
	'offer.more'               => 'Dowiedz się więcej',

	'offer.1.tag'              => 'Farmacja',
	'offer.1.title'            => 'Substancje czynne (API)',
	'offer.1.text'             => 'Import i dystrybucja substancji czynnych farmaceutycznych. Pełna dokumentacja rejestracyjna: ASMF / CEP, GMP, Written Confirmation.',
	'offer.2.tag'              => 'Nutraceutyka',
	'offer.2.title'            => 'Probiotyki',
	'offer.2.text'             => 'Bakterie kwasu mlekowego z francuskiego laboratorium Bioprox. Drożdże probiotyczne, bakterie rodzaju Bacillus.',
	'offer.3.tag'              => 'Substancje bioaktywne',
	'offer.3.title'            => 'Laktoferyna / Colostrum',
	'offer.3.text'             => 'Laktoferyna z Nowej Zelandii, od krów grass fed, oraz colostrum bydlęce o standaryzowanej zawartości immunoglobulin.',
	'offer.4.tag'              => 'Żywność & Suplementy',
	'offer.4.title'            => 'Białka mleka',
	'offer.4.text'             => 'Frakcje białek mleka do żywności funkcjonalnej, sportowej i preparatów odżywczych. Zapytaj o dostępne specyfikacje.',
	'offer.5.tag'              => 'Substancje bioaktywne',
	'offer.5.title'            => 'Kolagen',
	'offer.5.text'             => 'Hydrolizaty kolagenu, tripeptydy kolagenowe i żelatyna z wytwórni japońskiej firmy Jellice, dla przemysłu spożywczego.',
	'offer.6.tag'              => 'Przemysł & usługi',
	'offer.6.title'            => 'Maszyny / Usługi',
	'offer.6.text'             => 'Skup i sprzedaż używanych maszyn dla przemysłu spożywczego. Poszukiwania źródeł dostaw, organizacja audytów GMP u wytwórców i wsparcie dokumentacyjne projektów.',

	/* ------------------------------------------------------ Strona główna: statystyki */
	'stats.1.label'            => "lat doświadczenia\nna polskim rynku",
	'stats.2.label'            => "produktów oferowanych\nprzez firmę",
	'stats.3.label'            => "klientów polskich\ni zagranicznych",
	'stats.4.label'            => "krajów pochodzenia\nsubstancji",

	/* --------------------------------------------------------- Strona główna: klienci */
	'carousel.label'           => 'Nasi partnerzy',

	/* ---------------------------------------------------- Strona główna: jak działamy */
	'how.label'                => 'Jak działamy?',
	'how.title_html'           => 'Kompleksowa <em>obsługa</em>',
	'how.sub'                  => 'Współpracujemy z wyselekcjonowanymi wytwórcami, budując długoterminowe partnerstwa. Zapewniamy dostęp do pełnej dokumentacji jakościowej produktów oraz sprawny przepływ informacji między wytwórcami a odbiorcami.',
	'how.cta'                  => 'Skontaktuj się z nami',

	'how.1.title'              => 'Kwalifikacja dostawcy',
	'how.1.text'               => 'Formalny proces oceny wytwórców API i kluczowych podwykonawców z Europy i krajów trzecich.',
	'how.2.title'              => 'Indywidualne zamówienie',
	'how.2.text'               => 'Realizujemy dostawy bezpośrednio od wytwórcy, minimalizując ryzyko jakościowe.',
	'how.3.title'              => 'Pełna dokumentacja',
	'how.3.text'               => 'Zapewniamy kompletną dokumentację rejestracyjną, certyfikaty GMP i deklaracje wytwórcy.',
	'how.4.title'              => 'Nadzór nad łańcuchem dostaw',
	'how.4.text'               => 'Monitorujemy każdy etap transportu i magazynowania pod kątem wymagań jakościowych.',

	/* ------------------------------------------------- Strona główna: sekcja kontaktowa */
	'contact.label'            => 'Zapytaj o ofertę',
	'contact.title'            => 'Składniki Twojego sukcesu',
	'contact.sub'              => 'Naszą wizytówką jest stabilna pozycja na rynku, zaufanie klientów i kompleksowa oferta łącząca doradztwo z rzetelnym partnerstwem.',
	'contact.img_alt'          => 'Kontakt biznesowy NTC Andar',

	/* ------------------------------------------------------------------- Formularze */
	'form.company'             => 'Nazwa firmy',
	'form.company_req'         => 'Nazwa firmy *',
	'form.email'               => 'Adres e-mail',
	'form.email_req'           => 'Adres e-mail *',
	'form.phone'               => 'Telefon',
	'form.message'             => 'Wiadomość',
	'form.message_req'         => 'Wiadomość *',
	'form.name_req'            => 'Imię i nazwisko *',
	'form.name_ph'             => 'Jan Kowalski',
	'form.company_ph'          => 'Firma sp. z o.o.',
	'form.email_ph'            => 'email@firma.pl',
	'form.phone_ph'            => '+48 ___ ___ ___',
	'form.message_ph'          => 'Proszę opisać zapytanie...',
	'form.subject_req'         => 'Temat zapytania *',
	'form.subject'             => 'Temat zapytania',
	'form.subject_choose'      => 'Wybierz temat...',
	'form.subject_other'       => 'Inne',
	'form.subjects_default'    => "Substancje czynne (API)\nProbiotyki i postbiotyki\nLaktoferyna\nColostrum\nBiałka mleka\nKolagen\nMaszyny\nUsługi\nInne",
	'form.consent'             => 'Wyrażam zgodę na przetwarzanie danych osobowych w celu obsługi zapytania, zgodnie z Rozporządzeniem RODO.',
	'form.consent_req'         => 'Wyrażam zgodę na przetwarzanie danych osobowych w celu obsługi zapytania, zgodnie z Rozporządzeniem RODO. *',
	'form.submit_ask'          => 'Zapytaj o ofertę',
	'form.submit_send'         => 'Wyślij wiadomość',
	'form.sent_title'          => 'Dziękujemy!',
	'form.sent_text'           => 'Odpiszemy najszybciej jak to możliwe.',
	'form.sent_title_long'     => 'Dziękujemy za wiadomość!',
	'form.sent_text_long'      => 'Odpowiemy najszybciej jak to możliwe.',
	'form.demo_notice'         => 'Formularz jest w tej chwili makietą - nie wysyła jeszcze wiadomości.',

	/* ---------------------------------------------------------------------- Stopka */
	'footer.desc'              => 'Dystrybutor substancji aktywnych dla przemysłu farmaceutycznego, spożywczego, weterynaryjnego i kosmetycznego. Od 2000 roku.',
	'footer.nav_heading'       => 'Nawigacja',
	'footer.offer_heading'     => 'Oferta',
	'footer.contact_heading'   => 'Kontakt',
	'footer.machines'          => 'Maszyny',
	'footer.offer_api'         => 'Substancje czynne (API)',
	'footer.offer_probiotics'  => 'Probiotyki',
	'footer.offer_lactoferrin' => 'Laktoferyna',
	'footer.offer_colostrum'   => 'Colostrum',
	'footer.offer_proteins'    => 'Białka mleka',
	'footer.offer_collagen'    => 'Kolagen',
	'footer.offer_machines'    => 'Maszyny',
	'footer.offer_services'    => 'Usługi',
	'footer.copy'              => '© 2000-2026 NTC ANDAR Sp. z o.o. Wszelkie prawa zastrzeżone.',
	'footer.legal_quality'     => 'Polityka jakości',
	'footer.legal_privacy'     => 'Polityka prywatności',
	'footer.legal_terms'       => 'OWS',
	'footer.home'              => 'Strona główna',

	/* ------------------------------------------------------------ Podstrona: oferta */
	'offerpage.title'          => 'Oferta - NTC Andar',
	'offerpage.meta_desc'      => 'Substancje aktywne (API), probiotyki i laktoferyna z kwalifikowanych wytwórni. Pełna dokumentacja ASMF / CEP, GMP, Written Confirmation.',
	'offerpage.badge'          => 'NTC Andar',
	'offerpage.title_html'     => 'Oferta produktowa<br/>i usługowa',
	'offerpage.sub'            => 'Substancje aktywne, probiotyki, laktoferyna, maszyny i komponenty specjalne dla przemysłu farmaceutycznego, spożywczego i kosmetycznego.',

	'cat.api.label'            => 'Substancje czynne (API)',
	'cat.api.title'            => 'Import i dystrybucja substancji czynnych (API) z Europy, USA, Korei Południowej, Japonii, Chin i Indii.',
	'cat.api.p1'               => 'W naszej ofercie posiadamy szeroki wybór substancji czynnych farmaceutycznych (API), przeznaczonych dla różnych grup terapeutycznych, z kwalifikowanych wytwórni z Europy oraz krajów trzecich - przede wszystkim Chin, Indii i Japonii. Każdy wytwórca poddawany jest formalnej kwalifikacji zgodnie z naszym systemem zarządzania jakością.',
	'cat.api.p2'               => 'Jesteśmy certyfikowani oraz posiadamy uprawnienia importera i dystrybutora API zgodne z polskim prawem farmaceutycznym oraz wymaganiami GDP. Zapewniamy pełną wymaganą dokumentację rejestracyjną, np.: ASMF / CEP, certyfikaty GMP, Written Confirmation oraz wymagane deklaracje wytwórcy.',

	'cat.probiotyki.label'     => 'Probiotyki',
	'cat.probiotyki.title'     => 'Bakterie kwasu mlekowego od francuskiego producenta Bioprox Healthcare.',
	'cat.probiotyki.p1'        => 'W naszej ofercie znajdują się wysokiej jakości surowce probiotyczne w postaci bakterii kwasu mlekowego, produkowane przez renomowane francuskie laboratorium Bioprox Healthcare.',
	'cat.probiotyki.p2'        => 'Portfolio obejmuje szeroki wybór szczepów z rodzajów Lactobacillus i Bifidobacterium, a także innych bakterii probiotycznych, takich jak Enterococcus, Lactococcus, Pediococcus oraz Streptococcus.',

	'cat.laktoferyna.label'    => 'Laktoferyna',
	'cat.laktoferyna.title'    => 'Laktoferyna z Nowej Zelandii - naturalna glikoproteina o wysokiej bioaktywności.',
	'cat.laktoferyna.p1'       => 'Laktoferyna to naturalna glikoproteina z grupy transferryn, charakteryzująca się zdolnością do wiązania żelaza. Jest cenionym składnikiem bioaktywnym ze względu na swoje właściwości przeciwbakteryjne, przeciwwirusowe, przeciwgrzybicze oraz immunomodulacyjne.',
	'cat.laktoferyna.p2'       => 'Oferowana przez nas laktoferyna pochodzi z produkcji Tatua Co-operative Dairy Company z Nowej Zelandii i jest pozyskiwana bezpośrednio z odtłuszczonego mleka. Producent wytwarza ją w dwóch poziomach czystości: 90% oraz 95%.',

	'origin.img_alt'           => 'Mapa krajów pochodzenia surowców NTC Andar',
	'origin.summary'           => 'Surowce sprowadzamy z %d krajów.',
	/* ------------------------------------------------------ menu okruszkowe */
	'breadcrumbs.home'          => 'Strona główna',
	'breadcrumbs.label'         => 'Ścieżka nawigacji',

	/* ------------------------------------------------------------------ blog */
	'blog.title'               => 'Blog',
	'blog.badge'               => '',
	'blog.sub'                 => '',
	'blog.read_more'           => 'Czytaj dalej',
	'blog.back'                => 'Wróć do listy wpisów',
	'blog.prev'                => 'Poprzedni wpis',
	'blog.next'                => 'Następny wpis',
	'blog.nav_label'           => 'Sąsiednie wpisy',
	'blog.empty_title'         => 'Pierwszy wpis dopiero powstaje',
	'blog.empty_text'          => 'Zbieramy materiał na teksty o surowcach, dokumentacji i kwalifikacji dostawców. Do tego czasu piszcie wprost.',
	'blog.empty_cta'           => 'Napisz do nas',
	'blog.cta_head'            => 'Masz pytanie o surowiec?',
	'blog.cta_sub'             => 'Zadzwoń bezpośrednio lub napisz na e-mail.',

	'certs.opens_pdf'          => '(otwiera plik PDF w nowej karcie)',

	/* ---------------------------------------------------------------- autor */
	'autor.label'              => 'Autor wpisu',
	'autor.badge'              => 'Autor',
	'autor.posts'              => 'Wpisy tego autora',

	'table.name'               => 'Nazwa substancji',
	'table.cas'                => 'Nr CAS',
	'table.form'               => 'Forma',
	'table.docs'               => 'Dokumentacja',
	'table.origin'             => 'Kraj',
	'table.group'              => 'Grupa terapeutyczna',
	'table.maker'              => 'Wytwórca',
	'table.use'                => 'Zastosowanie',
	'site.tagline'             => 'Dystrybutor substancji czynnych i surowców dla przemysłu farmaceutycznego',
	'table.dev'                => 'w opracowaniu',
	'table.collection'         => 'Kolekcja',
	'table.postbiotic'         => 'Dostępny jako postbiotyk',
	'table.yes'                => 'tak',
	'table.no'                 => 'nie',
	'table.page'               => 'Strona',
	'table.prev'               => 'Poprzednia',
	'table.next'               => 'Następna',
	'table.of'                 => 'z',
	'table.search_ph'          => 'Szukaj substancji, kraju...',
	'table.count'              => 'pozycji',
	'table.no_results'         => 'Brak wyników dla',

	'form.powder'              => 'proszek',
	'form.powder_granules'     => 'proszek/granulat',
	'form.powder_premium'      => 'proszek premium',
	'form.lyophilised'         => 'proszek liofilizowany',

	'notfound.title'           => 'Nie znalazłeś tego, czego szukasz?',
	'notfound.text'            => 'Jeśli na liście nie ma substancji, której potrzebujesz - skontaktuj się z nami.',
	'notfound.cta'             => 'Zapytaj o substancję',

	'machines.label'           => 'Maszyny i urządzenia',
	'machines.title_html'      => 'Maszyny dla farmacji<br/>i <em>przemysłu spożywczego</em>',
	'machines.text'            => 'W zakresie używanych maszyn i urządzeń dla przemysłu farmaceutycznego, spożywczego i kosmetycznego współpracujemy jako wyłączny partner niemieckiej firmy Hauser Maschinen na rynku polskim. Odpowiadamy za komunikację oraz wspieramy w procesie identyfikacji odpowiednich urządzeń, pozyskiwania informacji technicznych oraz realizacji zakupu lub odsprzedaży maszyny używanej.',
	'machines.cta'             => 'Zobacz katalog Hauser Maschinen',
	'machines.img_alt'         => 'Maszyny i urządzenia dla przemysłu farmaceutycznego',

	/* ----------------------------------------------------------- Podstrona: kontakt */
	'contactpage.title'        => 'Kontakt - NTC Andar',
	'contactpage.meta_desc'    => 'Skontaktuj się z NTC Andar: ul. Hlonda 2A/90, 02-972 Warszawa. Formularz, telefon i e-mail do zespołu.',
	'contactpage.badge'        => 'Kontakt',
	'contactpage.title_html'   => 'Porozmawiajmy<br/>o <em>współpracy</em>',
	'contactpage.sub'          => 'Wybierz dogodną formę kontaktu: formularz, telefon lub e-mail.',

	'contactpage.info_title'   => 'Skontaktuj się z nami',
	'contactpage.info_sub'     => 'Naszą wizytówką jest stabilna pozycja na rynku, zaufanie klientów i kompleksowa oferta łącząca doradztwo z rzetelnym partnerstwem.',
	'contactpage.map_title'    => 'Mapa dojazdu do biura %s',
	'contactpage.row_address'  => 'Adres biura',
	'contactpage.row_phone'    => 'Telefon',
	'contactpage.row_email'    => 'E-mail',
	'contactpage.row_hours'    => 'Godziny pracy',
	'contactpage.hours_value'  => 'Pon. - Pt.  9:00 - 17:00',
	'contactpage.row_company'  => 'Dane firmowe',

	'contactpage.form_title'   => 'Wyślij zapytanie',
	'contactpage.form_sub'     => 'Wypełnij formularz - odezwiemy się z odpowiedzią na Państwa zapytanie.',

	'contactpage.map_label'    => 'Dojazd',
	'contactpage.map_title_html' => 'Znajdź nas w <em>Warszawie</em>',
	'contactpage.map_pin'      => 'ul. Hlonda 2A/90, Warszawa',
	'contactpage.map1_label'   => 'Samochodem',
	'contactpage.map1_title'   => 'Wilanów',
	'contactpage.map1_text'    => 'Parking ogólnodostępny przed budynkiem. Dojazd od ul. Hlonda i al. Rzeczypospolitej.',
	'contactpage.map3_label'   => 'Wizyta',
	'contactpage.map3_title'   => 'Umów spotkanie wcześniej',
	'contactpage.map3_text'    => 'Spotkania w biurze odbywają się po wcześniejszym umówieniu telefonicznym lub mailowym.',

	'contactpage.quick_head'   => 'Pilne zapytanie?',
	'contactpage.quick_sub'    => 'Zadzwoń bezpośrednio lub napisz na e-mail.',

	/* --------------------------------------------------------------------- Błędy */
	'404.title'                => 'Nie ma takiej strony',
	'404.text'                 => 'Adres jest nieaktualny albo zawiera literówkę. Wróć na stronę główną albo zajrzyj do oferty.',
	'404.home'                 => 'Strona główna',
);
