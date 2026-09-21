<?php
/**
 * Warstwa dwujęzyczna PL/EN.
 *
 * Język jest trzymany w URL-u (?lang=en), nie w ciasteczku ani w sesji. Dzięki
 * temu każdy adres jednoznacznie opisuje swoją treść: da się go zalinkować,
 * zaindeksować i zacache'ować (ciasteczko rozjeżdża cache w każdej wtyczce
 * cache'ującej). Wszystkie wewnętrzne linki przepuszczamy przez ntc_url(),
 * które dokleja bieżący język.
 *
 * Słowniki: inc/strings-pl.php i inc/strings-en.php - płaskie mapy klucz => tekst.
 * Klucz, którego brakuje w EN, spada na PL, żeby brak tłumaczenia nigdy nie
 * wyprodukował pustego miejsca na stronie.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_LANGS       = array( 'pl', 'en' );
const NTC_LANG_DEFAULT = 'pl';

/** Locale WordPressa dla każdego z języków - używane w atrybucie lang i hreflang. */
const NTC_LOCALES = array(
	'pl' => 'pl-PL',
	'en' => 'en',
);

/**
 * Bieżący język, wyliczany raz na żądanie.
 *
 * @return string 'pl' albo 'en'.
 */
function ntc_lang() {
	static $lang = null;

	if ( null !== $lang ) {
		return $lang;
	}

	$requested = isset( $_GET['lang'] ) ? strtolower( sanitize_key( wp_unslash( $_GET['lang'] ) ) ) : '';
	$lang      = in_array( $requested, NTC_LANGS, true ) ? $requested : NTC_LANG_DEFAULT;

	return $lang;
}

/** Czy bieżący język to angielski. */
function ntc_is_en() {
	return 'en' === ntc_lang();
}

/**
 * Słownik dla danego języka (ładowany leniwie, raz).
 *
 * @param string $lang Kod języka.
 * @return array<string,string>
 */
function ntc_strings( $lang ) {
	static $cache = array();

	if ( ! isset( $cache[ $lang ] ) ) {
		$file            = get_template_directory() . "/inc/strings-{$lang}.php";
		$cache[ $lang ] = is_readable( $file ) ? (array) require $file : array();
	}

	return $cache[ $lang ];
}

/**
 * Tłumaczenie surowe - zwraca tekst tak, jak leży w słowniku, bez escapowania.
 *
 * Używać wyłącznie dla kluczy, które celowo zawierają HTML (nagłówki z <em> i
 * <br>). Dla zwykłego tekstu jest ntc_t(), które escapuje.
 *
 * @param string $key Klucz w słowniku.
 * @return string
 */
function ntc_raw( $key ) {
	$pl = ntc_strings( NTC_LANG_DEFAULT );

	if ( ! isset( $pl[ $key ] ) ) {
		return '';
	}

	// Z Polylangiem tłumaczenia napisów interfejsu są edytowalne w kokpicie
	// (Języki → Tłumaczenia ciągów) i to one wygrywają. Bez wtyczki wracamy do
	// słownika w pliku.
	if ( function_exists( 'ntc_has_polylang' ) && ntc_has_polylang() ) {
		return ntc_pll( $pl[ $key ] );
	}

	$strings = ntc_strings( ntc_lang() );

	// Brak tłumaczenia - spadamy na polski, żeby nie zostawić dziury na stronie.
	return isset( $strings[ $key ] ) ? $strings[ $key ] : $pl[ $key ];
}

/**
 * Tłumaczenie do wstawienia w treść - escapowane.
 *
 * @param string $key Klucz w słowniku.
 * @return string
 */
function ntc_t( $key ) {
	return esc_html( ntc_raw( $key ) );
}

/** Echo dla ntc_t(). */
function ntc_e( $key ) {
	echo ntc_t( $key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ntc_t() escapuje.
}

/**
 * Echo dla ntc_raw() z listą dozwolonych tagów.
 *
 * Nagłówki w tym motywie zawierają <em> (kursywa akcentowa) i <br>, więc
 * przepuszczamy je przez wp_kses z wąską whitelistą zamiast ufać słownikowi.
 *
 * @param string $key Klucz w słowniku.
 */
function ntc_e_raw( $key ) {
	echo wp_kses(
		ntc_raw( $key ),
		array(
			'em'     => array(),
			'br'     => array(),
			'strong' => array(),
			'span'   => array( 'class' => array() ),
			'nobr'   => array(),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
				'class'  => array(),
			),
		)
	);
}

/**
 * Dokleja bieżący język do adresu.
 *
 * @param string $url Adres bezwzględny.
 * @return string
 */
function ntc_url( $url ) {
	if ( ! ntc_is_en() ) {
		return $url;
	}

	return add_query_arg( 'lang', 'en', $url );
}

/**
 * Adres bieżącej strony w podanym języku - dla przełącznika i hreflang.
 *
 * @param string $lang Kod języka.
 * @return string
 */
function ntc_lang_switch_url( $lang ) {
	// REQUEST_URI jest liczony od korzenia domeny i zawiera już ewentualny
	// podkatalog instalacji, więc sklejamy go z samym hostem. Przepuszczenie
	// go przez home_url() zdublowałoby podkatalog przy instalacji w /www/.
	$path = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '/';
	$host = isset( $_SERVER['HTTP_HOST'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) : '';

	if ( $host ) {
		$url = set_url_scheme( '//' . $host . $path );
	} else {
		$url = home_url( $path );
	}

	$url = remove_query_arg( 'lang', $url );

	return NTC_LANG_DEFAULT === $lang ? $url : add_query_arg( 'lang', $lang, $url );
}

/**
 * Atrybut lang dla <html>.
 */
function ntc_filter_language_attributes( $output ) {
	// Z Polylangiem locale ustawia WordPress i jest już poprawne.
	if ( function_exists( 'ntc_has_polylang' ) && ntc_has_polylang() ) {
		return $output;
	}

	$locale = NTC_LOCALES[ ntc_lang() ];

	return 'lang="' . esc_attr( $locale ) . '"';
}
add_filter( 'language_attributes', 'ntc_filter_language_attributes' );

/**
 * Alternatywne wersje językowe w <head>.
 */
function ntc_hreflang_tags() {
	// Polylang wypisuje własne hreflang - dwa komplety wykluczałyby się
	// nawzajem i myliły wyszukiwarki.
	if ( function_exists( 'ntc_has_polylang' ) && ntc_has_polylang() ) {
		return;
	}

	foreach ( NTC_LANGS as $lang ) {
		printf(
			'<link rel="alternate" hreflang="%1$s" href="%2$s" />' . "\n",
			esc_attr( NTC_LOCALES[ $lang ] ),
			esc_url( ntc_lang_switch_url( $lang ) )
		);
	}

	printf(
		'<link rel="alternate" hreflang="x-default" href="%s" />' . "\n",
		esc_url( ntc_lang_switch_url( NTC_LANG_DEFAULT ) )
	);
}
add_action( 'wp_head', 'ntc_hreflang_tags', 3 );

/**
 * ?lang= musi przetrwać przepisywanie adresów - rejestrujemy go jako query var,
 * żeby WordPress nie potraktował go jako parametru wyszukiwania ani nie uciął
 * przy przekierowaniu kanonicznym.
 */
function ntc_register_query_vars( $vars ) {
	$vars[] = 'lang';

	return $vars;
}
add_filter( 'query_vars', 'ntc_register_query_vars' );

/**
 * redirect_canonical potrafi zgubić ?lang= przy przekierowaniu na slash-końcowy
 * adres. Doklejamy go z powrotem.
 */
function ntc_keep_lang_on_canonical( $redirect_url, $requested_url ) {
	if ( ! isset( $_GET['lang'] ) || ! $redirect_url ) {
		return $redirect_url;
	}

	$lang = strtolower( sanitize_key( wp_unslash( $_GET['lang'] ) ) );

	if ( ! in_array( $lang, NTC_LANGS, true ) ) {
		return $redirect_url;
	}

	return add_query_arg( 'lang', $lang, $redirect_url );
}
add_filter( 'redirect_canonical', 'ntc_keep_lang_on_canonical', 10, 2 );

/**
 * Doklejanie języka do adresów w menu z kokpitu.
 *
 * Menu budowane w Wygląd → Menu ma adresy wpisane na sztywno, po polsku. Dopóki
 * nie ma Polylanga, awaryjny tryb ?lang=en traciłby na nich język: klik w
 * "Oferta" na angielskiej podstronie wracał do wersji polskiej.
 *
 * Filtr rusza wyłącznie odnośniki wewnętrzne i tylko w trybie awaryjnym. Z
 * Polylangiem wtyczka podaje własne, przetłumaczone menu i nie ma tu czego
 * poprawiać.
 *
 * Zostają etykiety: na angielskiej stronie pozycje menu są nadal po polsku,
 * bo w bazie jest jedno menu. To już naprawia dopiero Polylang, który trzyma
 * osobne menu na język.
 *
 * @param array   $atts Atrybuty odnośnika.
 * @param WP_Post $item Pozycja menu.
 * @return array
 */
function ntc_menu_link_lang( $atts, $item ) {
	if ( ! ntc_is_en() || empty( $atts['href'] ) || ntc_has_polylang() ) {
		return $atts;
	}

	$home = wp_parse_url( home_url(), PHP_URL_HOST );
	$host = wp_parse_url( $atts['href'], PHP_URL_HOST );

	if ( $host && $host !== $home ) {
		return $atts;
	}

	$atts['href'] = ntc_url( $atts['href'] );

	return $atts;
}
add_filter( 'nav_menu_link_attributes', 'ntc_menu_link_lang', 10, 2 );
