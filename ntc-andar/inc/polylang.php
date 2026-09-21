<?php
/**
 * Integracja z Polylangiem.
 *
 * Po przejściu na bloki treść stron mieszka w bazie, więc tłumaczy się ją
 * tak, jak każdą inną treść w Polylangu: osobna strona polska, osobna
 * angielska, przełącznik między nimi. Motyw nie musi o tym nic wiedzieć.
 *
 * W kodzie zostają tylko napisy interfejsu, których nie da się wpisać w
 * edytorze: nagłówki tabeli produktów, etykiety pól formularza, zgoda RODO,
 * komunikat po wysłaniu. Te rejestrujemy w Polylangu (Języki → Tłumaczenia
 * ciągów), więc też są edytowalne z kokpitu.
 *
 * Bez Polylanga motyw działa dalej - wraca wtedy do własnego przełącznika
 * ?lang=en i słowników w inc/strings-*.php.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/** Czy Polylang jest aktywny. */
function ntc_has_polylang() {
	return function_exists( 'pll__' ) && function_exists( 'pll_register_string' );
}

/**
 * Napisy interfejsu przekazywane Polylangowi.
 *
 * Świadomie nie rejestrujemy tu treści sekcji - ta jest w blokach i tłumaczy
 * się przez duplikat strony. Rejestracja treści dublowałaby to samo w dwóch
 * miejscach i nikt by nie wiedział, które wygrywa.
 *
 * @return array<int,string> Klucze ze słownika.
 */
function ntc_translatable_ui_strings() {
	return array(
		'nav.about', 'nav.quality', 'nav.offer', 'nav.how', 'nav.contact',
		'nav.back', 'nav.menu', 'nav.skip', 'nav.lang_label',

		'table.name', 'table.cas', 'table.form', 'table.docs', 'table.origin',
		'table.search_ph', 'table.count', 'table.no_results',

		'notfound.title', 'notfound.text', 'notfound.cta',

		'form.company', 'form.company_req', 'form.email', 'form.email_req',
		'form.phone', 'form.message', 'form.message_req', 'form.name_req',
		'form.name_ph', 'form.company_ph', 'form.email_ph', 'form.phone_ph',
		'form.message_ph', 'form.subject', 'form.subject_choose',
		'form.consent', 'form.consent_req', 'form.submit_ask', 'form.submit_send',
		'form.sent_title', 'form.sent_text', 'form.sent_title_long',
		'form.sent_text_long', 'form.demo_notice',

		'contactpage.row_address', 'contactpage.row_phone', 'contactpage.row_email',
		'contactpage.row_hours', 'contactpage.hours_value', 'contactpage.row_company',

		'footer.nav_heading', 'footer.offer_heading', 'footer.contact_heading',
		'footer.home',

		'404.title', '404.text', '404.home',
	);
}

/**
 * Rejestracja napisów w panelu tłumaczeń Polylanga.
 */
function ntc_register_polylang_strings() {
	if ( ! ntc_has_polylang() ) {
		return;
	}

	$pl = ntc_strings( NTC_LANG_DEFAULT );

	foreach ( ntc_translatable_ui_strings() as $key ) {
		if ( ! isset( $pl[ $key ] ) ) {
			continue;
		}

		$multiline = ( false !== strpos( $pl[ $key ], "\n" ) );

		pll_register_string( $key, $pl[ $key ], 'NTC Andar', $multiline );
	}

	// Teksty z Dostosuj, które też powinny mieć wersję angielską.
	$mods = array(
		'ntc_footer_desc'      => 'Stopka - opis',
		'ntc_footer_copy'      => 'Stopka - prawa autorskie',
		'ntc_contact_subjects' => 'Formularz - tematy zapytania',
	);

	foreach ( $mods as $mod => $label ) {
		$value = get_theme_mod( $mod );

		if ( $value ) {
			pll_register_string( $label, $value, 'NTC Andar', true );
		}
	}
}
add_action( 'init', 'ntc_register_polylang_strings', 20 );

/**
 * Przepuszcza napis przez tłumaczenia Polylanga.
 *
 * @param string $value Napis w wersji polskiej.
 * @return string
 */
function ntc_pll( $value ) {
	if ( ! $value || ! ntc_has_polylang() ) {
		return $value;
	}

	return pll__( $value );
}

/**
 * Lista języków do przełącznika.
 *
 * Z Polylangiem bierzemy jego konfigurację, bez niego - własną, dwuelementową.
 *
 * @return array<int,array<string,string>> Pozycje: slug, name, url, current.
 */
function ntc_language_links() {
	if ( ntc_has_polylang() && function_exists( 'pll_the_languages' ) ) {
		$raw = pll_the_languages(
			array(
				'raw'                    => 1,
				'hide_if_no_translation' => 0,
				'echo'                   => 0,
			)
		);

		if ( is_array( $raw ) && $raw ) {
			$out = array();

			foreach ( $raw as $lang ) {
				$out[] = array(
					'slug'    => isset( $lang['slug'] ) ? $lang['slug'] : '',
					'name'    => isset( $lang['name'] ) ? $lang['name'] : '',
					'url'     => isset( $lang['url'] ) ? $lang['url'] : '',
					'current' => ! empty( $lang['current_lang'] ),
				);
			}

			return $out;
		}
	}

	$current = ntc_lang();
	$out     = array();

	foreach ( NTC_LANGS as $lang ) {
		$out[] = array(
			'slug'    => $lang,
			'name'    => strtoupper( $lang ),
			'url'     => ntc_lang_switch_url( $lang ),
			'current' => $lang === $current,
		);
	}

	return $out;
}
