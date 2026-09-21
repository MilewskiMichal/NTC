<?php
/**
 * Wzorce bloków - gotowe układy trzech stron.
 *
 * Po przejściu na bloki treść nie jest już w kodzie, więc świeża instalacja
 * miałaby puste strony. Wzorzec wstawia komplet sekcji razem z tekstami z
 * projektu: wchodzisz w Wzorce, klikasz "NTC - Strona główna" i masz to samo,
 * co widziałeś w makiecie, tylko od razu edytowalne.
 *
 * Teksty biorą się ze słownika (inc/strings-pl.php), więc nie ma tu drugiej
 * kopii treści, która mogłaby się rozjechać z resztą motywu.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Buduje komentarz bloku.
 *
 * @param string      $name  Nazwa bloku.
 * @param array       $attrs Atrybuty.
 * @param string|null $inner Zawartość bloków potomnych albo null.
 * @return string
 */
function ntc_block( $name, $attrs = array(), $inner = null ) {
	$attrs = array_filter(
		$attrs,
		function ( $value ) {
			return null !== $value && '' !== $value;
		}
	);

	$json = $attrs
		? ' ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
		: '';

	if ( null === $inner ) {
		return "<!-- wp:{$name}{$json} /-->\n";
	}

	return "<!-- wp:{$name}{$json} -->\n{$inner}<!-- /wp:{$name} -->\n";
}

/**
 * Skrót: tekst ze słownika.
 *
 * @param string $key Klucz.
 * @return string
 */
function ntc_p( $key ) {
	return ntc_raw( $key );
}

/**
 * Zawartość wzorca strony głównej.
 *
 * @return string
 */
function ntc_pattern_home() {
	$features = '';

	$icons = array( 'flask', 'shield', 'molecule', 'bolt' );

	foreach ( $icons as $i => $icon ) {
		$n         = $i + 1;
		$features .= ntc_block(
			'ntc/feature',
			array(
				'icon'  => $icon,
				'title' => ntc_p( "why.{$n}.title" ),
				'text'  => ntc_p( "why.{$n}.text" ),
			)
		);
	}

	$cards = '';

	// Kolejność kafelków i to, dokąd prowadzą, ustalił klient: laktoferyna idzie
	// razem z kolostrum, a maszyny razem z usługami - obie pozycje prowadzą do
	// jednej podstrony, tylko w różne jej miejsca.
	$card_links = array(
		1 => ntc_page_url( 'oferta' ) . 'substancje-czynne-api/',
		2 => ntc_page_url( 'oferta' ) . 'probiotyki/',
		3 => ntc_page_url( 'oferta' ) . 'laktoferyna/',
		4 => ntc_page_url( 'oferta' ) . 'bialka-mleka/',
		5 => ntc_page_url( 'oferta' ) . 'kolagen/',
		6 => ntc_page_url( 'oferta' ) . 'maszyny-i-uslugi/',
	);

	foreach ( $card_links as $n => $url ) {
		$cards .= ntc_block(
			'ntc/offer-card',
			array(
				'tag'      => ntc_p( "offer.{$n}.tag" ),
				'title'    => ntc_p( "offer.{$n}.title" ),
				'text'     => ntc_p( "offer.{$n}.text" ),
				'linkText' => ntc_p( 'offer.more' ),
				'linkUrl'  => $url,
			)
		);
	}

	$stats   = '';
	// Liczby wprost od klienta - nie szacunki. Firma weszła w 25. rok
	// działalności, więc doświadczenie i liczba krajów pochodzenia podskoczyły.
	$numbers = array( 25, 400, 70, 25 );

	foreach ( $numbers as $i => $num ) {
		$n      = $i + 1;
		$stats .= ntc_block(
			'ntc/stat',
			array(
				'number' => $num,
				'suffix' => '+',
				'label'  => ntc_p( "stats.{$n}.label" ),
			)
		);
	}

	$steps = '';

	for ( $n = 1; $n <= 4; $n++ ) {
		$steps .= ntc_block(
			'ntc/step',
			array(
				'title' => ntc_p( "how.{$n}.title" ),
				'text'  => ntc_p( "how.{$n}.text" ),
			)
		);
	}

	return ntc_block(
		'ntc/hero',
		array(
			'badge'    => ntc_p( 'hero.badge' ),
			'title'    => ntc_p( 'hero.title_html' ),
			'sub'      => ntc_p( 'hero.sub' ),
			'btn1Text' => ntc_p( 'hero.btn_offer' ),
			'btn1Url'  => '#oferta',
			'btn2Text' => ntc_p( 'hero.btn_quality' ),
			'btn2Url'  => '#quality',
			'imageAlt' => ntc_p( 'hero.img_alt' ),
		)
	)
	. ntc_block(
		'ntc/about',
		array(
			'label'     => ntc_p( 'about.label' ),
			'title'     => ntc_p( 'about.title_html' ),
			'p1'        => ntc_p( 'about.p1' ),
			'p2'        => ntc_p( 'about.p2' ),
			'p3'        => ntc_p( 'about.p3' ),
			'accentNum' => ntc_p( 'about.accent_num' ),
			'accentTxt' => ntc_p( 'about.accent_txt' ),
			'fig1Num'   => ntc_p( 'about.stat1_num' ),
			'fig1Label' => ntc_p( 'about.stat1_label' ),
			'fig2Num'   => ntc_p( 'about.stat2_num' ),
			'fig2Label' => ntc_p( 'about.stat2_label' ),
			'imageAlt'  => ntc_p( 'about.img_alt' ),
		)
	)
	. ntc_block(
		'ntc/features',
		array(
			'label' => ntc_p( 'why.label' ),
			'title' => ntc_p( 'why.title_html' ),
			'intro' => ntc_p( 'why.intro' ),
		),
		$features
	)
	. ntc_block(
		'ntc/offer',
		array(
			'label'   => ntc_p( 'offer.label' ),
			'title'   => ntc_p( 'offer.title_html' ),
			'ctaText' => ntc_p( 'offer.cta' ),
		),
		$cards
	)
	. ntc_block( 'ntc/stats', array(), $stats )
	. ntc_block(
		'ntc/logos',
		array( 'label' => ntc_p( 'carousel.label' ) ),
		// Partnerzy, czyli wytwórcy, od których NTC kupuje - nie odbiorcy.
		// Kolejność idzie za udziałem w potwierdzonym katalogu API, dalej
		// dostawcy pozostałych kategorii. Na liście stoją tylko ci, do których
		// mamy logotyp: taśma z samymi nazwami wpisanymi tekstem wyglądała jak
		// niedokończona robota. Logotyp podpina się w bloku potomnym.
		implode(
			'',
			array_map(
				function ( $name ) {
					return ntc_block( 'ntc/partner', array( 'name' => $name ) );
				},
				array(
					'Supriya Lifesciences', 'Morepen', 'Procos', 'Alps Pharmaceutical',
					'Kingvit', 'Tatua', 'Unique Biotech', 'Bioprox', 'Jellice',
					'Bionatin', 'Hauser Maschinen', 'Amano Enzyme',
				)
			)
		)
	)
	. ntc_block(
		'ntc/steps',
		array(
			'label'   => ntc_p( 'how.label' ),
			'title'   => ntc_p( 'how.title_html' ),
			'sub'     => ntc_p( 'how.sub' ),
			'ctaText' => ntc_p( 'how.cta' ),
		),
		$steps
	)
	. ntc_block(
		'ntc/contact',
		array(
			'label'    => ntc_p( 'contact.label' ),
			'title'    => ntc_p( 'contact.title' ),
			'sub'      => ntc_p( 'contact.sub' ),
			'imageAlt' => ntc_p( 'contact.img_alt' ),
		)
	);
}

/**
 * Zawartość wzorca podstrony oferty.
 *
 * @return string
 */
function ntc_pattern_offer() {
	$out = ntc_block(
		'ntc/page-hero',
		array(
			'badge'    => ntc_p( 'offerpage.badge' ),
			'title'    => ntc_p( 'offerpage.title_html' ),
			'sub'      => ntc_p( 'offerpage.sub' ),
			'showTabs' => true,
		)
	);

	$cats = array( 'api', 'probiotyki', 'laktoferyna' );

	foreach ( $cats as $i => $slug ) {
		$out .= ntc_block(
			'ntc/category',
			array(
				'category' => $slug,
				'label'    => ntc_p( "cat.{$slug}.label" ),
				'title'    => ntc_p( "cat.{$slug}.title" ),
				'p1'       => ntc_p( "cat.{$slug}.p1" ),
				'p2'       => ntc_p( "cat.{$slug}.p2" ),
				// Co druga sekcja na jasnoszarym tle, ze zdjęciem po lewej.
				'alt'      => ( 0 !== $i % 2 ),
			)
		);
	}

	return $out . ntc_block(
		'ntc/machines',
		array(
			'label'    => ntc_p( 'machines.label' ),
			'title'    => ntc_p( 'machines.title_html' ),
			'text'     => ntc_p( 'machines.text' ),
			'ctaText'  => ntc_p( 'machines.cta' ),
			'ctaUrl'   => 'https://www.hauser-maschinen.de/pl/',
			'imageAlt' => ntc_p( 'machines.img_alt' ),
		)
	);
}

/**
 * Zawartość wzorca podstrony kontaktu.
 *
 * @return string
 */
function ntc_pattern_contact() {
	$cards = '';

	// Kafelek 2 (komunikacja miejska) klient wycofał - do biura przyjeżdża się
	// samochodem, a przystanki i tak się zmieniają szybciej niż strona.
	foreach ( array( 1, 3 ) as $n ) {
		$cards .= ntc_block(
			'ntc/map-card',
			array(
				'label' => ntc_p( "contactpage.map{$n}_label" ),
				'title' => ntc_p( "contactpage.map{$n}_title" ),
				'text'  => ntc_p( "contactpage.map{$n}_text" ),
			)
		);
	}

	return ntc_block(
		'ntc/lp-hero',
		array(
			'badge' => ntc_p( 'contactpage.badge' ),
			'title' => ntc_p( 'contactpage.title_html' ),
			'sub'   => ntc_p( 'contactpage.sub' ),
		)
	)
	. ntc_block(
		'ntc/contact-panel',
		array(
			'infoTitle' => ntc_p( 'contactpage.info_title' ),
			'infoSub'   => ntc_p( 'contactpage.info_sub' ),
			'formTitle' => ntc_p( 'contactpage.form_title' ),
			'formSub'   => ntc_p( 'contactpage.form_sub' ),
		)
	)
	. ntc_block(
		'ntc/map',
		array(
			'label'    => ntc_p( 'contactpage.map_label' ),
			'title'    => ntc_p( 'contactpage.map_title_html' ),
			'pinLabel' => ntc_p( 'contactpage.map_pin' ),
		),
		$cards
	)
	. ntc_block(
		'ntc/quick-cta',
		array(
			'head' => ntc_p( 'contactpage.quick_head' ),
			'sub'  => ntc_p( 'contactpage.quick_sub' ),
		)
	);
}

/**
 * Rejestracja wzorców.
 */
function ntc_register_patterns() {
	if ( ! function_exists( 'register_block_pattern' ) ) {
		return;
	}

	register_block_pattern_category(
		'ntc-andar',
		array( 'label' => 'NTC Andar' )
	);

	$patterns = array(
		'ntc-andar/strona-glowna' => array(
			'title'       => 'NTC - Strona główna',
			'description' => 'Komplet sekcji strony głównej: hero, o nas, co nas wyróżnia, oferta, statystyki, karuzela klientów, jak działamy, kontakt.',
			'content'     => ntc_pattern_home(),
		),
		'ntc-andar/oferta'        => array(
			'title'       => 'NTC - Oferta',
			'description' => 'Hero z zakładkami, trzy kategorie z tabelami produktów, ciemna sekcja maszyn.',
			'content'     => ntc_pattern_offer(),
		),
		'ntc-andar/kontakt'       => array(
			'title'       => 'NTC - Kontakt',
			'description' => 'Hero, panel z danymi i formularzem, sekcja dojazdu, pasek CTA.',
			'content'     => ntc_pattern_contact(),
		),
	);

	foreach ( $patterns as $name => $pattern ) {
		register_block_pattern(
			$name,
			array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array( 'ntc-andar' ),
				'content'     => $pattern['content'],
			)
		);
	}
}
add_action( 'init', 'ntc_register_patterns', 30 );
