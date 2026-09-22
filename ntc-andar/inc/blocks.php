<?php
/**
 * Bloki edytora.
 *
 * Każda sekcja projektu jest osobnym blokiem renderowanym po stronie PHP
 * (render_callback), a nie zapisanym HTML-em. Dzięki temu markup zostaje
 * dokładnie taki, jak w projekcie, a zmiana w szablonie działa wstecz na
 * stronach, które ktoś już zapisał - zapisany HTML trzeba by migrować.
 *
 * Atrybuty są zadeklarowane w jednym miejscu, w ntc_block_definitions(), i
 * stamtąd trafiają zarówno do register_block_type(), jak i do edytora
 * (wp_localize_script). Nie ma więc drugiej listy pól w JS-ie, która mogłaby
 * się rozjechać z PHP-em.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Skrót do atrybutu bloku.
 *
 * @param array  $attrs   Atrybuty.
 * @param string $key     Nazwa.
 * @param mixed  $default Wartość, gdy pusto.
 * @return mixed
 */
function ntc_a( $attrs, $key, $default = '' ) {
	return ( isset( $attrs[ $key ] ) && '' !== $attrs[ $key ] ) ? $attrs[ $key ] : $default;
}

/**
 * Adres obrazka bloku - wgrany z biblioteki mediów albo placeholder motywu.
 *
 * Materiały od klienta bywają wielkie (zdjęcia produktowe po 20 MB), więc gdy
 * blok pamięta ID załącznika, bierzemy przeskalowaną wersję zamiast oryginału.
 * Adres zostaje jako zapasowy - dla obrazków spoza biblioteki mediów.
 *
 * @param array  $attrs    Atrybuty bloku.
 * @param string $fallback Slug placeholdera z assets/img.
 * @param string $size     Rozmiar z biblioteki mediów.
 * @return string
 */
function ntc_block_img( $attrs, $fallback, $size = 'large' ) {
	$url = ntc_media_url( $attrs, $size );

	return $url ? $url : ntc_img( $fallback );
}

/**
 * Adres obrazka bloku bez podmiany na placeholder.
 *
 * @param array  $attrs Atrybuty bloku.
 * @param string $size  Rozmiar z biblioteki mediów.
 * @return string Pusty ciąg, gdy blok nie ma obrazka.
 */
/**
 * Klasa i styl kadrowania dla znacznika img.
 *
 * Zwraca gotowy fragment atrybutów - klasę "--zmiesc" dla logotypów, które
 * nie mogą być przycinane, i object-position, gdy redakcja wskazała, która
 * część zdjęcia ma zostać w ramce.
 *
 * @param array  $attrs Atrybuty bloku.
 * @param string $klasa Podstawowa klasa obrazka.
 * @return string
 */
function ntc_img_kadr( $attrs, $klasa ) {
	$klasy = $klasa;

	if ( 'contain' === ntc_a( $attrs, 'imageFit' ) ) {
		$klasy .= ' ntc-img--zmiesc';
	}

	$out = trim( $klasy ) ? ' class="' . esc_attr( trim( $klasy ) ) . '"' : '';
	$pos = trim( (string) ntc_a( $attrs, 'imagePos' ) );

	// Tylko wartości w rodzaju "50% 20%" albo "center top" - nic, co dałoby
	// się przemycić jako dowolny CSS.
	if ( $pos && preg_match( '/^[a-z0-9.% ]{1,40}$/i', $pos ) ) {
		$out .= ' style="object-position:' . esc_attr( $pos ) . '"';
	}

	return $out;
}

function ntc_media_url( $attrs, $size = 'large' ) {
	$id = (int) ntc_a( $attrs, 'imageId', 0 );

	if ( $id ) {
		$sized = wp_get_attachment_image_url( $id, $size );

		if ( $sized ) {
			return $sized;
		}
	}

	return (string) ntc_a( $attrs, 'imageUrl' );
}

/**
 * Kotwica sekcji - własna z bloku albo domyślna z kodu.
 *
 * Jedna podstrona bywa celem dwóch pozycji menu: "Maszyny" i "Usługi" prowadzą
 * do tego samego dokumentu, tylko w inne jego miejsca. Zamiast dzielić treść na
 * dwie podstrony pozwalamy redaktorowi nazwać sekcję i podlinkować ją z menu.
 *
 * @param array  $attrs   Atrybuty bloku.
 * @param string $default Kotwica, gdy pole jest puste.
 * @return string
 */
function ntc_section_id( $attrs, $default = '' ) {
	$id = sanitize_title( (string) ntc_a( $attrs, 'anchor' ) );

	return $id ? $id : $default;
}

/**
 * Tekst z RichText - dopuszczamy inline'owe formatowanie, resztę wycinamy.
 *
 * @param string $html Zawartość pola.
 * @return string
 */
function ntc_rich( $html ) {
	return wp_kses(
		(string) $html,
		array(
			'em'     => array(),
			'strong' => array(),
			'br'     => array(),
			'span'   => array( 'class' => array() ),
			'a'      => array(
				'href'   => array(),
				'target' => array(),
				'rel'    => array(),
			),
		)
	);
}

/** Kolumny, które tabela katalogu umie pokazać. */
function ntc_table_column_keys() {
	return array( 'name', 'group', 'cas', 'form', 'maker', 'origin', 'use', 'docs',
		'collection', 'postbiotic' );
}

/**
 * Lista kolumn z pola bloku, odsiana z literówek.
 *
 * Katalogi różnią się między sobą: przy API sensowne są wytwórca i grupa
 * terapeutyczna, przy probiotykach forma i liczba CFU. Zamiast trzech tabel
 * w kodzie trzymamy jedną z konfigurowalnym zestawem kolumn.
 *
 * @param string $spec Kolumny po przecinku.
 * @return array
 */
function ntc_table_columns( $spec ) {
	$allowed = ntc_table_column_keys();
	$cols    = array_values(
		array_intersect(
			array_map( 'trim', explode( ',', (string) $spec ) ),
			$allowed
		)
	);

	return $cols ? $cols : array( 'name', 'cas', 'form', 'docs', 'origin' );
}

/**
 * Własne nagłówki kolumn z pola bloku.
 *
 * Domyślne nazwy są pisane pod katalog substancji aktywnych i przy innych
 * katalogach potrafią mijać się z prawdą: w tabeli probiotyków kolumna "group"
 * trzyma rodzaj bakterii, a nie grupę terapeutyczną. Zamiast mnożyć kolumny
 * pozwalamy podmienić sam nagłówek, zapisem "klucz = nagłówek" w kolejnych
 * liniach pola.
 *
 * @param string $spec Zawartość pola bloku.
 * @return array<string,string>
 */
function ntc_column_labels( $spec ) {
	$out = array();

	foreach ( preg_split( '/[\n,]/', (string) $spec ) as $para ) {
		if ( false === strpos( $para, '=' ) ) {
			continue;
		}

		list( $key, $label ) = explode( '=', $para, 2 );
		$key                 = trim( $key );
		$label               = trim( $label );

		if ( $key && $label && in_array( $key, ntc_table_column_keys(), true ) ) {
			$out[ $key ] = $label;
		}
	}

	return $out;
}

/**
 * Nagłówek kolumny - własny albo domyślny ze słownika.
 *
 * @param string               $col    Nazwa kolumny.
 * @param array<string,string> $labels Nadpisania z bloku.
 * @return string
 */
function ntc_column_label( $col, $labels ) {
	return isset( $labels[ $col ] ) ? $labels[ $col ] : ntc_raw( 'table.' . $col );
}

/**
 * Zawartość jednej komórki tabeli.
 *
 * @param array  $row Wiersz z ntc_get_products().
 * @param string $col Nazwa kolumny.
 * @return string
 */
function ntc_table_cell( $row, $col ) {
	$value = isset( $row[ $col ] ) ? (string) $row[ $col ] : '';

	switch ( $col ) {
		case 'name':
			$out = '<span class="prod-name">' . esc_html( $value ) . '</span>';

			// Substancje w opracowaniu muszą być odróżnialne od dostępnych -
			// inaczej ktoś zapyta o ofertę na coś, czego jeszcze nie ma.
			if ( ! empty( $row['dev'] ) ) {
				$out .= ' <span class="prod-dev">' . esc_html( ntc_raw( 'table.dev' ) ) . '</span>';
			}

			return $out;

		case 'cas':
			return '<span class="prod-doc">' . esc_html( $value ) . '</span>';

		case 'group':
			// Pełna nazwa grupy bywa na pół linijki, więc w tabeli stoi skrót,
			// a całość wchodzi w title.
			$full = ! empty( $row['group_full'] ) ? $row['group_full'] : $value;

			return sprintf(
				'<span class="prod-group" title="%s">%s</span>',
				esc_attr( $full ),
				esc_html( $value )
			);

		case 'docs':
			$badges = array_filter( array_map( 'trim', explode( ',', $value ) ) );

			if ( ! $badges ) {
				return '';
			}

			$out = '';

			foreach ( $badges as $badge ) {
				$out .= '<span class="prod-doc">' . esc_html( $badge ) . '</span>';
			}

			return $out;

		case 'origin':
			return '<span class="prod-doc">' . esc_html( $value ) . '</span>';

		case 'collection':
			return '<span class="prod-doc">' . esc_html( $value ) . '</span>';

		case 'postbiotic':
			// Ptaszek albo krzyżyk zamiast słowa - kolumna jest wąska, a wzrok
			// i tak szuka w niej znaku, nie tekstu. Czytnik ekranu dostaje opis.
			$tak = in_array( mb_strtolower( trim( $value ) ), array( 'tak', 'yes', '1', 'tak.' ), true );

			return sprintf(
				'<span class="prod-flag prod-flag--%s" role="img" aria-label="%s">%s</span>',
				$tak ? 'tak' : 'nie',
				esc_attr( ntc_raw( $tak ? 'table.yes' : 'table.no' ) ),
				$tak ? '&#10003;' : '&#8211;'
			);

		default:
			return esc_html( $value );
	}
}

/**
 * Tekst, po którym szuka wyszukiwarka nad tabelą.
 *
 * Indeks obejmuje więcej niż widać w kolumnach: pełną nazwę grupy terapeutycznej
 * i numer CAS. Ktoś szukający "antiviral" albo wklejający CAS z maila ma trafić,
 * niezależnie od tego, które kolumny są akurat włączone.
 *
 * @param array $row Wiersz z ntc_get_products().
 * @return string
 */
function ntc_row_search_index( $row ) {
	$parts = array();

	foreach ( array( 'name', 'cas', 'group', 'group_full', 'maker', 'origin', 'use', 'form', 'docs' ) as $key ) {
		if ( ! empty( $row[ $key ] ) ) {
			$parts[] = $row[ $key ];
		}
	}

	return mb_strtolower( implode( ' ', $parts ) );
}

/**
 * Definicje wszystkich bloków motywu.
 *
 * @return array<string,array<string,mixed>>
 */
function ntc_block_definitions() {
	$text  = array( 'type' => 'string' );
	$img   = array(
		'imageId'  => array( 'type' => 'number' ),
		'imageUrl' => array( 'type' => 'string' ),
		'imageAlt' => array( 'type' => 'string' ),
		// Logotypy i certyfikaty muszą się zmieścić w całości, zdjęcia - wypełnić
		// kadr. Punkt kadrowania decyduje, która część zdjęcia zostaje w ramce.
		'imageFit' => array(
			'type'    => 'string',
			'default' => 'cover',
		),
		'imagePos' => array(
			'type'    => 'string',
			'default' => '',
		),
	);

	return array(

		/* ------------------------------------------------ strona główna */

		'ntc/hero' => array(
			'title'      => 'NTC - Hero',
			'icon'       => 'cover-image',
			'attributes' => array_merge(
				array(
					'badge'    => $text,
					'title'    => $text,
					'sub'      => $text,
					'btn1Text' => $text,
					'btn1Url'  => $text,
					'btn2Text' => $text,
					'btn2Url'  => $text,
				),
				$img
			),
			'render'     => 'ntc_render_hero',
		),

		'ntc/about' => array(
			'title'      => 'NTC - O nas',
			'icon'       => 'groups',
			'attributes' => array_merge(
				array(
					'label'     => $text,
					'title'     => $text,
					'p1'        => $text,
					'p2'        => $text,
					'p3'        => $text,
					'accentNum' => $text,
					'accentTxt' => $text,
					'fig1Num'   => $text,
					'fig1Label' => $text,
					'fig2Num'   => $text,
					'fig2Label' => $text,
				),
				$img
			),
			'render'     => 'ntc_render_about',
		),

		'ntc/features' => array(
			'title'      => 'NTC - Co nas wyróżnia',
			'icon'       => 'awards',
			'attributes' => array(
				'label' => $text,
				'title' => $text,
				'intro' => $text,
			),
			'inner'      => array( 'ntc/feature' ),
			'render'     => 'ntc_render_features',
		),

		'ntc/feature' => array(
			'title'      => 'NTC - Kafelek wyróżnika',
			'icon'       => 'shield',
			'parent'     => array( 'ntc/features' ),
			'attributes' => array(
				'icon'  => $text,
				'title' => $text,
				'text'  => $text,
			),
			'render'     => 'ntc_render_feature',
		),

		'ntc/offer' => array(
			'title'      => 'NTC - Oferta (kafelki)',
			'icon'       => 'grid-view',
			'attributes' => array(
				'label'   => $text,
				'title'   => $text,
				'ctaText' => $text,
				'ctaUrl'  => $text,
			),
			'inner'      => array( 'ntc/offer-card' ),
			'render'     => 'ntc_render_offer',
		),

		'ntc/offer-card' => array(
			'title'      => 'NTC - Kafelek oferty',
			'icon'       => 'index-card',
			'parent'     => array( 'ntc/offer' ),
			'attributes' => array_merge(
				array(
					'tag'      => $text,
					'title'    => $text,
					'text'     => $text,
					'linkText' => $text,
					'linkUrl'  => $text,
				),
				$img
			),
			'render'     => 'ntc_render_offer_card',
		),

		'ntc/stats' => array(
			'title'  => 'NTC - Statystyki',
			'icon'   => 'chart-bar',
			'inner'  => array( 'ntc/stat' ),
			'render' => 'ntc_render_stats',
		),

		'ntc/stat' => array(
			'title'      => 'NTC - Licznik',
			'icon'       => 'chart-line',
			'parent'     => array( 'ntc/stats' ),
			'attributes' => array(
				'number' => array(
					'type'    => 'number',
					'default' => 0,
				),
				'suffix' => $text,
				'label'  => $text,
			),
			'render'     => 'ntc_render_stat',
		),

		'ntc/logos' => array(
			'title'      => 'NTC - Partnerzy (karuzela)',
			'icon'       => 'controls-repeat',
			'attributes' => array(
				'label' => $text,
				'names' => $text,
			),
			'inner'      => array( 'ntc/partner' ),
			'render'     => 'ntc_render_logos',
		),

		'ntc/partner' => array(
			'title'      => 'NTC - Partner',
			'icon'       => 'building',
			'parent'     => array( 'ntc/logos' ),
			'attributes' => array_merge(
				array(
					'name' => $text,
					'url'  => $text,
				),
				$img
			),
			'render'     => 'ntc_render_partner',
		),

		'ntc/steps' => array(
			'title'      => 'NTC - Jak działamy',
			'icon'       => 'editor-ol',
			'attributes' => array(
				'label'   => $text,
				'title'   => $text,
				'sub'     => $text,
				'ctaText' => $text,
				'ctaUrl'  => $text,
			),
			'inner'      => array( 'ntc/step' ),
			'render'     => 'ntc_render_steps',
		),

		'ntc/step' => array(
			'title'      => 'NTC - Krok',
			'icon'       => 'arrow-right-alt',
			'parent'     => array( 'ntc/steps' ),
			'attributes' => array(
				'title' => $text,
				'text'  => $text,
			),
			'render'     => 'ntc_render_step',
		),

		'ntc/contact' => array(
			'title'      => 'NTC - Sekcja kontaktowa',
			'icon'       => 'email',
			'attributes' => array_merge(
				array(
					'label' => $text,
					'title' => $text,
					'sub'   => $text,
				),
				$img
			),
			'render'     => 'ntc_render_contact',
		),

		/* ------------------------------------------------------- oferta */

		'ntc/page-hero' => array(
			'title'      => 'NTC - Hero podstrony (z zakładkami)',
			'icon'       => 'align-full-width',
			'attributes' => array(
				'badge'    => $text,
				'title'    => $text,
				'sub'      => $text,
				'showTabs' => array(
					'type'    => 'boolean',
					'default' => true,
				),
			),
			'render'     => 'ntc_render_page_hero',
		),

		'ntc/category' => array(
			'title'      => 'NTC - Kategoria oferty',
			'icon'       => 'list-view',
			'attributes' => array_merge(
				array(
					'category'     => $text,
					'label'        => $text,
					'title'        => $text,
					'p1'           => $text,
					'p2'           => $text,
					'alt'          => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'showTable'    => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'showNotFound' => array(
						'type'    => 'boolean',
						'default' => true,
					),
					'ctaText'      => $text,
					'ctaUrl'       => $text,
					'columns'      => $text,
					'colLabels'    => $text,
					'perPage'      => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				$img
			),
			'render'     => 'ntc_render_category',
		),

		'ntc/machines' => array(
			'title'      => 'NTC - Maszyny',
			'icon'       => 'admin-tools',
			'attributes' => array_merge(
				array(
					'label'   => $text,
					'title'   => $text,
					'text'    => $text,
					'note'    => $text,
					'ctaText' => $text,
					'ctaUrl'  => $text,
					'dark'    => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				$img
			),
			'render'     => 'ntc_render_machines',
		),

		/* ------------------------------------------------------ kontakt */

		'ntc/lp-hero' => array(
			'title'      => 'NTC - Hero wyśrodkowany',
			'icon'       => 'align-center',
			'attributes' => array(
				'badge' => $text,
				'title' => $text,
				'sub'   => $text,
			),
			'render'     => 'ntc_render_lp_hero',
		),

		'ntc/contact-panel' => array(
			'title'      => 'NTC - Panel kontaktowy',
			'icon'       => 'id',
			'attributes' => array_merge(
				array(
					'infoTitle' => $text,
					'infoSub'   => $text,
					'formTitle' => $text,
					'formSub'   => $text,
				),
				$img
			),
			'render'     => 'ntc_render_contact_panel',
		),

		'ntc/map' => array(
			'title'      => 'NTC - Dojazd',
			'icon'       => 'location-alt',
			'attributes' => array(
				'label'    => $text,
				'title'    => $text,
				'pinLabel' => $text,
				'query'    => $text,
				'embedUrl' => $text,
			),
			'inner'      => array( 'ntc/map-card' ),
			'render'     => 'ntc_render_map',
		),

		'ntc/map-card' => array(
			'title'      => 'NTC - Kafelek dojazdu',
			'icon'       => 'info',
			'parent'     => array( 'ntc/map' ),
			'attributes' => array(
				'label' => $text,
				'title' => $text,
				'text'  => $text,
			),
			'render'     => 'ntc_render_map_card',
		),

		'ntc/quick-cta' => array(
			'title'      => 'NTC - Pasek CTA',
			'icon'       => 'megaphone',
			'attributes' => array(
				'head' => $text,
				'sub'  => $text,
			),
			'render'     => 'ntc_render_quick_cta',
		),

		/* ------------------------------------------------------- jakość */

		'ntc/checklist' => array(
			'title'      => 'NTC - Lista z ptaszkami',
			'icon'       => 'yes-alt',
			'attributes' => array_merge(
				array(
					'label' => $text,
					'title' => $text,
					'intro' => $text,
					'alt'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'flip'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
					'dark'  => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				$img
			),
			'inner'      => array( 'ntc/check-item' ),
			'render'     => 'ntc_render_checklist',
		),

		'ntc/check-item' => array(
			'title'      => 'NTC - Punkt listy',
			'icon'       => 'yes',
			'parent'     => array( 'ntc/checklist' ),
			'attributes' => array(
				'text' => $text,
			),
			'render'     => 'ntc_render_check_item',
		),

		'ntc/quote-band' => array(
			'title'      => 'NTC - Pas z cytatem',
			'icon'       => 'format-quote',
			'attributes' => array(
				'text' => $text,
			),
			'render'     => 'ntc_render_quote_band',
		),

		'ntc/certs' => array(
			'title'      => 'NTC - Certyfikaty',
			'icon'       => 'awards',
			'attributes' => array(
				'label' => $text,
				'title' => $text,
				'p1'    => $text,
				'p2'    => $text,
			),
			'inner'      => array( 'ntc/cert' ),
			'render'     => 'ntc_render_certs',
		),

		'ntc/product-form' => array(
			'title'      => 'NTC - Formularz pod kategorię',
			'icon'       => 'email-alt',
			'attributes' => array(
				'label'   => $text,
				'title'   => $text,
				'sub'     => $text,
				'subject' => $text,
			),
			'render'     => 'ntc_render_product_form',
		),

		'ntc/prose' => array(
			'title'      => 'NTC - Blok tekstowy',
			'icon'       => 'text-page',
			'attributes' => array_merge(
				array(
					'label' => $text,
					'title' => $text,
					'lead'  => $text,
					'alt'   => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
				$img
			),
			'inner'      => array(),
			'render'     => 'ntc_render_prose',
		),

		'ntc/origin-map' => array(
			'title'      => 'NTC - Mapa pochodzenia',
			'icon'       => 'admin-site-alt3',
			'attributes' => array_merge(
				array(
					'label'     => $text,
					'title'     => $text,
					'sub'       => $text,
					'countries' => $text,
					'showList'  => array(
						'type'    => 'boolean',
						'default' => true,
					),
				),
				$img
			),
			'render'     => 'ntc_render_origin_map',
		),

		'ntc/cert' => array(
			'title'      => 'NTC - Certyfikat',
			'icon'       => 'media-document',
			'parent'     => array( 'ntc/certs' ),
			'attributes' => array_merge(
				array(
					'issuer'   => $text,
					'line1'    => $text,
					'line1Url' => $text,
					'line2'    => $text,
					'line2Url' => $text,
					'line3'    => $text,
					'line3Url' => $text,
				),
				$img
			),
			'render'     => 'ntc_render_cert',
		),
	);
}

/**
 * Rejestracja bloków.
 */
function ntc_register_blocks() {
	foreach ( ntc_block_definitions() as $name => $def ) {
		// Kotwica jako zwykły atrybut, a nie ten dokładany przez wsparcie "anchor"
		// z rdzenia: tamten czyta id z zapisanego HTML-u, a bloki motywu składa
		// PHP przy wyświetlaniu, więc do render_callback nigdy by nie dotarł.
		$args = array(
			'api_version'     => 3,
			'title'           => $def['title'],
			'icon'            => isset( $def['icon'] ) ? $def['icon'] : 'block-default',
			'category'        => 'ntc-andar',
			'attributes'      => array_merge(
				array( 'anchor' => array( 'type' => 'string' ) ),
				isset( $def['attributes'] ) ? $def['attributes'] : array()
			),
			'render_callback' => $def['render'],
			'supports'        => array(
				'html'   => false,
				'anchor' => true,
			),
		);

		if ( isset( $def['parent'] ) ) {
			$args['parent'] = $def['parent'];
		}

		register_block_type( $name, $args );
	}
}
add_action( 'init', 'ntc_register_blocks' );

/**
 * Własna kategoria bloków, żeby nie ginęły wśród standardowych.
 */
function ntc_block_category( $categories ) {
	array_unshift(
		$categories,
		array(
			'slug'  => 'ntc-andar',
			'title' => 'NTC Andar',
			'icon'  => null,
		)
	);

	return $categories;
}
add_filter( 'block_categories_all', 'ntc_block_category' );

/* ==========================================================================
 * Renderowanie - markup jest przeniesiony 1:1 z szablonów sprzed przebudowy.
 * ========================================================================== */

/**
 * Dekoracyjne elipsy w tle sekcji hero.
 *
 * @param int   $count  Liczba elips.
 * @param array $box    viewBox.
 * @param float $stroke Krycie obrysu.
 * @param string $class Klasa SVG.
 */
function ntc_hero_lines( $count, $box, $stroke, $class ) {
	ob_start();
	?>
	<svg class="<?php echo esc_attr( $class ); ?>" viewBox="<?php echo esc_attr( $box ); ?>" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
		<?php for ( $i = 0; $i < $count; $i++ ) : ?>
			<ellipse
				cx="<?php echo esc_attr( 700 + $i * 80 ); ?>"
				cy="<?php echo esc_attr( 300 - $i * 30 ); ?>"
				rx="<?php echo esc_attr( 280 + $i * 100 ); ?>"
				ry="<?php echo esc_attr( 180 + $i * 50 ); ?>"
				fill="none" stroke="rgba(255,255,255,<?php echo esc_attr( $stroke ); ?>)" stroke-width="1"
				style="transform-origin:center;transform:rotate(<?php echo esc_attr( $i * 18 ); ?>deg)" />
		<?php endfor; ?>
	</svg>
	<?php
	return ob_get_clean();
}

/** Hero strony głównej. */
/**
 * Geometria zdjęcia w kształcie na nagłówku strony głównej.
 *
 * SVG kadruje zdjęcie tylko do środka albo do krawędzi, a nie do dowolnego
 * punktu. Przy panoramicznym kadrze z obiektem z boku (kapsułka po prawej
 * stronie zdjęcia) środek ucinał połowę obiektu, a krawędź - jego czubek.
 * Dlatego przy ustawionym punkcie kadrowania liczymy położenie zdjęcia sami,
 * tak samo jak robi to object-position w CSS: procent wolnego miejsca.
 *
 * @param array $attrs Atrybuty bloku.
 * @return array{x:float,y:float,w:float,h:float,par:string}
 */
function ntc_hero_kadr( $attrs ) {
	$domyslny = array( 'x' => -15, 'y' => -15, 'w' => 450, 'h' => 450, 'par' => 'xMidYMid slice' );
	$pos      = trim( (string) ntc_a( $attrs, 'imagePos' ) );
	$id       = (int) ntc_a( $attrs, 'imageId', 0 );

	if ( ! $pos || ! $id ) {
		return $domyslny;
	}

	$src = wp_get_attachment_image_src( $id, 'large' );

	if ( ! $src || empty( $src[1] ) || empty( $src[2] ) ) {
		return $domyslny;
	}

	$slowa = array( 'left' => 0, 'top' => 0, 'center' => 50, 'right' => 100, 'bottom' => 100 );
	$osie  = array();

	foreach ( preg_split( '/\s+/', strtolower( $pos ) ) as $czesc ) {
		if ( isset( $slowa[ $czesc ] ) ) {
			$osie[] = $slowa[ $czesc ];
		} elseif ( preg_match( '/^(\d{1,3}(?:\.\d+)?)%$/', $czesc, $m ) ) {
			$osie[] = min( 100, (float) $m[1] );
		}
	}

	$fx = isset( $osie[0] ) ? $osie[0] / 100 : 0.5;
	$fy = isset( $osie[1] ) ? $osie[1] / 100 : 0.5;

	// Skala "cover" dla kwadratu 450 x 450, w którym siedzi kształt.
	$skala = max( 450 / $src[1], 450 / $src[2] );
	$w     = $src[1] * $skala;
	$h     = $src[2] * $skala;

	return array(
		'x'   => round( -15 + ( 450 - $w ) * $fx, 2 ),
		'y'   => round( -15 + ( 450 - $h ) * $fy, 2 ),
		'w'   => round( $w, 2 ),
		'h'   => round( $h, 2 ),
		'par' => 'none',
	);
}

function ntc_render_hero( $attrs ) {
	$rings = array(
		array( 'fill' => 'none',                   'stroke' => 'rgba(27,191,168,0.12)',  'w' => '1',   'dash' => '' ),
		array( 'fill' => 'none',                   'stroke' => 'rgba(27,191,168,0.18)',  'w' => '1.2', 'dash' => '' ),
		array( 'fill' => 'none',                   'stroke' => 'rgba(255,255,255,0.20)', 'w' => '1',   'dash' => '8 10' ),
		array( 'fill' => 'rgba(255,255,255,0.06)', 'stroke' => 'rgba(255,255,255,0.30)', 'w' => '1.5', 'dash' => '' ),
	);

	$img  = ntc_block_img( $attrs, 'hero' );
	$alt  = ntc_a( $attrs, 'imageAlt', ntc_raw( 'hero.img_alt' ) );
	$kadr = ntc_hero_kadr( $attrs );

	ob_start();
	?>
	<section class="hero">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 5,
				'count'   => 85,
				'bias'    => 'wave',
				'opacity' => 0.28,
				'class'   => 'ntc-mesh ntc-mesh--hero',
			)
		);
		?>
		<svg class="hero-bg-lines" viewBox="0 0 1400 800" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
			<?php for ( $i = 0; $i < 5; $i++ ) : ?>
				<ellipse cx="<?php echo esc_attr( 700 + $i * 80 ); ?>" cy="<?php echo esc_attr( 400 - $i * 40 ); ?>"
					rx="<?php echo esc_attr( 300 + $i * 120 ); ?>" ry="<?php echo esc_attr( 200 + $i * 60 ); ?>"
					fill="none" stroke="rgba(255,255,255,0.7)" stroke-width="1"
					style="transform-origin:center;transform:rotate(<?php echo esc_attr( $i * 18 ); ?>deg)" />
			<?php endfor; ?>
		</svg>

		<div class="hero-inner">
			<div class="hero-content">
				<span class="hero-badge"><?php echo esc_html( ntc_a( $attrs, 'badge' ) ); ?></span>
				<h1 class="hero-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h1>
				<p class="hero-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></p>
				<div class="hero-btns">
					<?php if ( ntc_a( $attrs, 'btn1Text' ) ) : ?>
						<a href="<?php echo esc_url( ntc_a( $attrs, 'btn1Url', '#oferta' ) ); ?>" class="btn-primary"><?php echo esc_html( ntc_a( $attrs, 'btn1Text' ) ); ?></a>
					<?php endif; ?>
					<?php if ( ntc_a( $attrs, 'btn2Text' ) ) : ?>
						<a href="<?php echo esc_url( ntc_a( $attrs, 'btn2Url', '#quality' ) ); ?>" class="btn-outline"><?php echo esc_html( ntc_a( $attrs, 'btn2Text' ) ); ?></a>
					<?php endif; ?>
				</div>
			</div>

			<div class="hero-image-wrap">
				<svg class="hero-blob" viewBox="-90 -90 600 600" width="460" height="460"
					xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
					role="img" aria-label="<?php echo esc_attr( $alt ); ?>">
					<defs>
						<clipPath id="ntcBlobClip"><path d="<?php echo esc_attr( NTC_BLOB_PATH ); ?>" /></clipPath>
						<linearGradient id="ntcBlobGrad" x1="0" y1="1" x2="1" y2="0">
							<stop offset="0%" stop-color="#0ca882" stop-opacity="0.55" />
							<stop offset="50%" stop-color="#1bbfa8" stop-opacity="0.1" />
							<stop offset="100%" stop-color="#1bbfa8" stop-opacity="0" />
						</linearGradient>
					</defs>
					<?php foreach ( $rings as $i => $ring ) : ?>
						<path class="blob-ring-<?php echo esc_attr( $i + 1 ); ?>" d="<?php echo esc_attr( NTC_BLOB_PATH ); ?>"
							fill="<?php echo esc_attr( $ring['fill'] ); ?>" stroke="<?php echo esc_attr( $ring['stroke'] ); ?>"
							stroke-width="<?php echo esc_attr( $ring['w'] ); ?>"
							<?php echo $ring['dash'] ? 'stroke-dasharray="' . esc_attr( $ring['dash'] ) . '"' : ''; ?> />
					<?php endforeach; ?>
					<image href="<?php echo esc_url( $img ); ?>" xlink:href="<?php echo esc_url( $img ); ?>"
						x="<?php echo esc_attr( $kadr['x'] ); ?>" y="<?php echo esc_attr( $kadr['y'] ); ?>"
						width="<?php echo esc_attr( $kadr['w'] ); ?>" height="<?php echo esc_attr( $kadr['h'] ); ?>"
						clip-path="url(#ntcBlobClip)" preserveAspectRatio="<?php echo esc_attr( $kadr['par'] ); ?>" />
					<path d="<?php echo esc_attr( NTC_BLOB_PATH ); ?>" fill="url(#ntcBlobGrad)" clip-path="url(#ntcBlobClip)" />
				</svg>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Sekcja "O nas". */
function ntc_render_about( $attrs ) {
	ob_start();
	?>
	<section class="section about" id="about">
		<div class="section-inner">
			<div class="about-grid">
				<div class="about-image-wrap">
					<img class="about-img" src="<?php echo esc_url( ntc_block_img( $attrs, 'about' ) ); ?>"
						alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt' ) ); ?>" loading="lazy" />
					<?php if ( ntc_a( $attrs, 'accentNum' ) ) : ?>
						<div class="about-accent-card">
							<div class="about-accent-num"><?php echo esc_html( ntc_a( $attrs, 'accentNum' ) ); ?></div>
							<div class="about-accent-txt"><?php echo esc_html( ntc_a( $attrs, 'accentTxt' ) ); ?></div>
						</div>
					<?php endif; ?>
				</div>

				<div class="about-body">
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'p1' ) ); ?></p>
					<?php foreach ( array( 'p2', 'p3' ) as $key ) : ?>
						<?php if ( ntc_a( $attrs, $key ) ) : ?>
							<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, $key ) ); ?></p>
						<?php endif; ?>
					<?php endforeach; ?>

					<div class="about-figures">
						<?php foreach ( array( '1', '2' ) as $n ) : ?>
							<?php if ( ntc_a( $attrs, "fig{$n}Num" ) ) : ?>
								<div>
									<div class="about-figure-num"><?php echo esc_html( ntc_a( $attrs, "fig{$n}Num" ) ); ?></div>
									<div class="about-figure-label"><?php echo esc_html( ntc_a( $attrs, "fig{$n}Label" ) ); ?></div>
								</div>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Sekcja z długim tekstem prawnym.
 *
 * Polityka prywatności, polityka jakości czy OWS to zwykły dokument: nagłówki,
 * akapity i listy. Bloki NTC są pisane pod sekcje z projektu i żaden z nich
 * tego nie udźwignie, a goła treść z edytora rozlewa się na całą szerokość
 * ekranu. Ten blok daje jej ramy i typografię, a w środku redaktor układa
 * dokument standardowymi blokami WordPressa.
 *
 * @param array  $attrs   Atrybuty bloku.
 * @param string $content Bloki potomne.
 * @return string
 */
function ntc_render_prose( $attrs, $content ) {
	$kotwa = ntc_section_id( $attrs );
	$alt   = ntc_a( $attrs, 'alt', false );
	$foto  = ntc_media_url( $attrs );

	ob_start();
	?>
	<section class="section prose<?php echo $alt ? ' prose--alt' : ''; ?><?php echo $foto ? ' prose--z-foto' : ''; ?>"<?php echo $kotwa ? ' id="' . esc_attr( $kotwa ) . '"' : ''; ?>>
		<div class="section-inner">
			<?php if ( ntc_a( $attrs, 'label' ) ) : ?>
				<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
			<?php endif; ?>
			<?php if ( ntc_a( $attrs, 'title' ) ) : ?>
				<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
			<?php endif; ?>
			<?php if ( ntc_a( $attrs, 'lead' ) ) : ?>
				<p class="section-sub prose-lead"><?php echo esc_html( ntc_a( $attrs, 'lead' ) ); ?></p>
			<?php endif; ?>
			<div class="prose-grid">
				<div class="ntc-entry-content">
					<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render bloków potomnych. ?>
				</div>
				<?php if ( $foto ) : ?>
					<?php // Zdjęcie po tekście w kodzie: czytnik ekranu dostaje najpierw treść. ?>
					<div class="prose-media">
						<img<?php echo ntc_img_kadr( $attrs, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( $foto ); ?>"
							alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_a( $attrs, 'label' ) ) ); ?>" loading="lazy" />
					</div>
				<?php endif; ?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Formularz zapytania pod konkretną kategorią oferty.
 *
 * Na podstronach produktowych pytanie jest już zawężone: ktoś czyta o
 * probiotykach i chce zapytać o probiotyki. Temat przychodzi więc wybrany z
 * góry, a nie do wyklikania z listy, na której trzeba jeszcze znaleźć właściwą
 * pozycję. Pola id dostają przedrostek z kategorii, żeby dwa formularze na
 * jednej stronie nie wchodziły sobie w etykiety.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_product_form( $attrs ) {
	$subject = ntc_a( $attrs, 'subject' );
	$prefix  = 'ntc-' . sanitize_title( $subject ? $subject : 'zapytanie' );

	ob_start();
	?>
	<section class="section product-form">
		<div class="section-inner">
			<?php // contact-form-wrap niesie jasny wariant pól z kontakt.css - bez niego formularz miałby styl z ciemnej sekcji. ?>
			<div class="product-form-card contact-form-wrap">
				<div class="product-form-head" data-ntc-form-head>
					<?php if ( ntc_a( $attrs, 'label' ) ) : ?>
						<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<?php endif; ?>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<?php
					// Klient prosił, żeby osobne zdania stawały w osobnych
					// wierszach. Łamiemy po kropce kończącej zdanie, a nie po
					// każdej - inaczej skróty w rodzaju "m.in." rozbijałyby wiersz.
					$zdania = preg_split(
						'/(?<=[.!?])\s+(?=[A-ZĄĆĘŁŃÓŚŹŻ])/u',
						(string) ntc_a( $attrs, 'sub' ),
						-1,
						PREG_SPLIT_NO_EMPTY
					);
					?>
					<?php foreach ( $zdania as $zdanie ) : ?>
						<p class="section-sub"><?php echo esc_html( $zdanie ); ?></p>
					<?php endforeach; ?>
				</div>
				<?php
				ntc_the_contact_form(
					'full',
					array(
						'prefix'  => $prefix,
						'subject' => $subject,
					)
				);
				?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Sekcja "Co nas wyróżnia" - opakowanie. */
function ntc_render_features( $attrs, $content ) {
	ob_start();
	?>
	<section class="section why" id="<?php echo esc_attr( ntc_section_id( $attrs, 'quality' ) ); ?>">
		<div class="section-inner">
			<div class="why-head">
				<div>
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
				</div>
				<p class="why-intro"><?php echo esc_html( ntc_a( $attrs, 'intro' ) ); ?></p>
			</div>
			<div class="features-grid"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render bloków potomnych. ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Pojedynczy kafelek wyróżnika. */
function ntc_render_feature( $attrs ) {
	// Treść z wielu linii to wyliczenie, a nie akapit. Na stronie laktoferyny
	// każdy kafelek zbiera po kilka osobnych tez o działaniu białka i zlepione
	// w jeden blok tekstu przestają być czytelne.
	$linie = array_values(
		array_filter(
			array_map( 'trim', explode( "\n", (string) ntc_a( $attrs, 'text' ) ) ),
			'strlen'
		)
	);

	ob_start();
	?>
	<div class="feature-card">
		<div class="feature-icon"><?php ntc_the_icon( ntc_a( $attrs, 'icon', 'flask' ) ); ?></div>
		<div class="feature-title"><?php echo esc_html( ntc_a( $attrs, 'title' ) ); ?></div>
		<?php if ( count( $linie ) > 1 ) : ?>
			<ul class="feature-text feature-list">
				<?php foreach ( $linie as $linia ) : ?>
					<li><?php echo esc_html( $linia ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php else : ?>
			<div class="feature-text"><?php echo esc_html( implode( ' ', $linie ) ); ?></div>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Sekcja oferty na stronie głównej. */
function ntc_render_offer( $attrs, $content ) {
	ob_start();
	?>
	<section class="section offer" id="oferta">
		<div class="section-inner">
			<div class="offer-head">
				<div>
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
				</div>
				<?php if ( ntc_a( $attrs, 'ctaText' ) ) : ?>
					<a href="<?php echo esc_url( ntc_a( $attrs, 'ctaUrl', ntc_page_url( 'kontakt' ) ) ); ?>" class="btn-primary"><?php echo esc_html( ntc_a( $attrs, 'ctaText' ) ); ?></a>
				<?php endif; ?>
			</div>
			<div class="offer-grid"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Kafelek oferty. */
function ntc_render_offer_card( $attrs ) {
	ob_start();
	?>
	<article class="offer-card">
		<div class="offer-card-img">
			<img src="<?php echo esc_url( ntc_block_img( $attrs, 'offer-api' ) ); ?>"
				alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_a( $attrs, 'title' ) ) ); ?>" loading="lazy" />
		</div>
		<div class="offer-card-body">
			<?php if ( ntc_a( $attrs, 'tag' ) ) : ?>
				<span class="offer-card-tag"><?php echo esc_html( ntc_a( $attrs, 'tag' ) ); ?></span>
			<?php endif; ?>
			<h3 class="offer-card-title"><?php echo esc_html( ntc_a( $attrs, 'title' ) ); ?></h3>
			<p class="offer-card-text"><?php echo esc_html( ntc_a( $attrs, 'text' ) ); ?></p>
			<?php if ( ntc_a( $attrs, 'linkText' ) ) : ?>
				<a href="<?php echo esc_url( ntc_a( $attrs, 'linkUrl', ntc_page_url( 'kontakt' ) ) ); ?>" class="offer-card-link">
					<?php echo esc_html( ntc_a( $attrs, 'linkText' ) ); ?> <span aria-hidden="true">&rarr;</span>
				</a>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return ob_get_clean();
}

/** Pasek statystyk. */
function ntc_render_stats( $attrs, $content ) {
	ob_start();
	?>
	<section class="stats" id="stats">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 33,
				'count'   => 55,
				'height'  => 420,
				'bias'    => 'wave',
				'opacity' => 0.32,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="stats-inner">
			<div class="stats-grid"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Pojedynczy licznik. */
function ntc_render_stat( $attrs ) {
	$num = (int) ntc_a( $attrs, 'number', 0 );

	ob_start();
	?>
	<div class="stat-item">
		<div class="stat-num">
			<span class="stat-value" data-count-to="<?php echo esc_attr( $num ); ?>"><?php echo esc_html( $num ); ?></span><span class="stat-plus"><?php echo esc_html( ntc_a( $attrs, 'suffix', '+' ) ); ?></span>
		</div>
		<div class="stat-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
	</div>
	<?php
	return ob_get_clean();
}

/** Karuzela klientów. */
function ntc_render_logos( $attrs, $content ) {
	// Dzieci (bloki partnerów z logotypami) mają pierwszeństwo. Lista nazw
	// zostaje jako zapas, żeby sekcja działała, zanim ktoś wgra grafiki.
	if ( ! trim( (string) $content ) ) {
		$names = array_filter( array_map( 'trim', explode( "\n", (string) ntc_a( $attrs, 'names' ) ) ) );

		if ( ! $names ) {
			return '';
		}

		$content = '';

		foreach ( $names as $name ) {
			$content .= '<div class="carousel-logo">' . esc_html( $name ) . '</div>';
		}
	}

	// Taśma jedzie o -50% i wraca do zera, więc druga kopia zakrywa moment
	// przeskoku. Kopii nie czyta czytnik ekranu: dokładamy aria-hidden do
	// każdego kafelka, zamiast owijać całość, bo owijka rozbiłaby układ flex.
	// Podmiana po klasie, a nie po dosłownym '<div class="carousel-logo"':
	// kafelek z logotypem ma dwie klasy, więc dosłowne dopasowanie omijało go
	// i czytnik ekranu odczytywał wszystkich partnerów dwa razy.
	$echo = preg_replace( '/<div class="(carousel-logo[^"]*)"/', '<div class="$1" aria-hidden="true"', $content );

	ob_start();
	?>
	<section class="carousel-section">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 12,
				'count'   => 55,
				'height'  => 420,
				'bias'    => 'left',
				'opacity' => 0.35,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="carousel-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
		<div class="carousel-track-wrap">
			<div class="carousel-track">
				<?php
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render bloków potomnych.
				echo $content . $echo;
				?>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Pojedynczy partner w karuzeli.
 *
 * Bez wgranego logotypu pokazujemy nazwę tekstem. Logotypy dostawców dochodzą
 * partiami i sekcja nie może czekać na komplet.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_partner( $attrs ) {
	$logo = ntc_media_url( $attrs, 'medium' );
	$name = ntc_a( $attrs, 'name' );
	$url  = ntc_a( $attrs, 'url' );

	ob_start();
	?>
	<div class="carousel-logo<?php echo $logo ? ' carousel-logo--img' : ''; ?>">
		<?php if ( $url ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
		<?php endif; ?>

		<?php if ( $logo ) : ?>
			<img src="<?php echo esc_url( $logo ); ?>" alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', $name ) ); ?>" loading="lazy" />
		<?php else : ?>
			<?php echo esc_html( $name ); ?>
		<?php endif; ?>

		<?php if ( $url ) : ?>
			</a>
		<?php endif; ?>
	</div>
	<?php
	return ob_get_clean();
}

/** Sekcja "Jak działamy". */
function ntc_render_steps( $attrs, $content ) {
	ob_start();
	?>
	<section class="section how" id="<?php echo esc_attr( ntc_section_id( $attrs, 'jak' ) ); ?>">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 58,
				'count'   => 60,
				'bias'    => 'left',
				'opacity' => 0.4,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="section-inner">
			<div class="how-grid">
				<div class="how-intro">
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></p>
					<?php if ( ntc_a( $attrs, 'ctaText' ) ) : ?>
						<a href="<?php echo esc_url( ntc_a( $attrs, 'ctaUrl', ntc_page_url( 'kontakt' ) ) ); ?>" class="btn-primary"><?php echo esc_html( ntc_a( $attrs, 'ctaText' ) ); ?></a>
					<?php endif; ?>
				</div>
				<div class="how-steps"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Krok procesu.
 *
 * Numer nie jest ani atrybutem, ani licznikiem w PHP - rysuje go licznik CSS
 * (.how-steps w main.css). Dzięki temu numeracja poprawia się sama po
 * przestawieniu i skasowaniu kroku, a dwie sekcje "Jak działamy" na jednej
 * stronie liczą się niezależnie, zamiast kontynuować wspólny licznik.
 */
function ntc_render_step( $attrs ) {
	ob_start();
	?>
	<div class="how-step">
		<div class="how-step-num" aria-hidden="true"></div>
		<div>
			<div class="how-step-title"><?php echo esc_html( ntc_a( $attrs, 'title' ) ); ?></div>
			<div class="how-step-text"><?php echo esc_html( ntc_a( $attrs, 'text' ) ); ?></div>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/** Sekcja kontaktowa strony głównej. */
function ntc_render_contact( $attrs ) {
	ob_start();
	?>
	<section class="section contact" id="kontakt">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 77,
				'count'   => 60,
				'bias'    => 'bottom-left',
				'opacity' => 0.4,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="section-inner">
			<div class="contact-head">
				<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
				<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
				<p class="contact-head-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></p>
			</div>

			<div class="contact-grid">
				<div class="contact-blob-wrap">
					<div class="contact-blob">
						<div class="contact-blob-shape"></div>
						<div class="contact-blob-shape-2"></div>
						<div class="contact-blob-img">
							<img src="<?php echo esc_url( ntc_block_img( $attrs, 'contact' ) ); ?>"
								alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt' ) ); ?>" loading="lazy" />
						</div>
					</div>
				</div>

				<div>
					<?php
					// Adres, telefon i e-mail stały tu wcześniej pod formularzem, ale
					// stopka zaczyna się kilka pikseli niżej i powtarza je co do znaku.
					ntc_the_contact_form( 'home' );
					?>
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Sekcje kategorii obecne w treści strony.
 *
 * Zakładki w nagłówku oferty mają prowadzić do sekcji, które na tej stronie
 * faktycznie są. Wcześniej brały się z listy wszystkich kategorii produktów i
 * każda nowa kategoria - także taka, która żyje tylko na podstronie
 * produktowej - dokładała zakładkę prowadzącą w pustkę.
 *
 * @param array $bloki Wynik parse_blocks().
 * @return array<string,string> Slug sekcji => etykieta.
 */
function ntc_content_sections( $bloki ) {
	$out = array();

	foreach ( $bloki as $blok ) {
		$attrs = isset( $blok['attrs'] ) ? $blok['attrs'] : array();

		if ( 'ntc/category' === $blok['blockName'] && ! empty( $attrs['category'] ) ) {
			$out[ 'cat-' . $attrs['category'] ] = isset( $attrs['label'] ) ? $attrs['label'] : $attrs['category'];
		}

		if ( 'ntc/machines' === $blok['blockName'] ) {
			$slug         = ntc_section_id( $attrs, 'cat-maszyny' );
			$out[ $slug ] = isset( $attrs['label'] ) ? $attrs['label'] : ntc_raw( 'machines.label' );
		}

		if ( ! empty( $blok['innerBlocks'] ) ) {
			$out += ntc_content_sections( $blok['innerBlocks'] );
		}
	}

	return $out;
}

/** Hero podstrony oferty, z zakładkami do sekcji tej samej strony. */
function ntc_render_page_hero( $attrs ) {
	$cats = array();

	if ( ntc_a( $attrs, 'showTabs', true ) ) {
		$post = get_post();
		$cats = $post ? ntc_content_sections( parse_blocks( $post->post_content ) ) : array();

		// Blok poza stroną z sekcjami (np. podgląd wzorca) - wtedy zakładki z
		// listy kategorii są lepsze niż ich brak.
		if ( ! $cats ) {
			foreach ( ntc_product_categories() as $slug => $name ) {
				$cats[ 'cat-' . $slug ] = $name;
			}
		}
	}

	$tabs = '';

	if ( $cats ) {
		ob_start();
		?>
		<div class="tabs-bar" data-ntc-tabs>
			<?php $first = true; ?>
			<?php foreach ( $cats as $slug => $name ) : ?>
				<a class="tab-btn<?php echo $first ? ' active' : ''; ?>"
					href="#<?php echo esc_attr( $slug ); ?>"
					data-target="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $name ); ?></a>
				<?php $first = false; ?>
			<?php endforeach; ?>
		</div>
		<?php
		$tabs = ob_get_clean();
	}

	return ntc_sub_hero(
		ntc_a( $attrs, 'badge' ),
		ntc_a( $attrs, 'title' ),
		ntc_a( $attrs, 'sub' ),
		$tabs
	);
}

/**
 * Nagłówek podstrony: ciemny pas z siatką, nadtytułem i tytułem.
 *
 * Wyciągnięte z bloku, bo tego samego pasa potrzebują szablony bloga, które
 * blokiem nie są - listy wpisów nie składa się w edytorze.
 *
 * @param string $badge Nadtytuł.
 * @param string $title Tytuł, dopuszcza <em> i <br/>.
 * @param string $sub   Zdanie pod tytułem.
 * @param string $extra Dodatkowy HTML pod tekstem (np. pasek zakładek).
 * @return string
 */
function ntc_sub_hero( $badge, $title, $sub = '', $extra = '' ) {
	ob_start();
	?>
	<section class="hero hero--sub">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 21,
				'count'   => 90,
				'bias'    => 'wave',
				'opacity' => 0.32,
				'class'   => 'ntc-mesh ntc-mesh--hero',
			)
		);
		?>
		<div class="hero-inner">
			<?php
			// Okruszki tylko tam, gdzie strona ma nadrzędną, czyli na podstronach
			// produktowych. Na stronach najwyższego poziomu pokazywałyby jeden
			// odnośnik do strony głównej i nic poza tym.
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z ntc_breadcrumbs_html().
			echo ntc_breadcrumbs_html();
			?>
			<?php if ( $badge ) : ?>
				<div class="hero-badge"><?php echo esc_html( $badge ); ?></div>
			<?php endif; ?>
			<h1 class="hero-title"><?php echo ntc_rich( $title ); ?></h1>
			<?php if ( $sub ) : ?>
				<p class="hero-sub"><?php echo esc_html( $sub ); ?></p>
			<?php endif; ?>
			<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML składany wyżej. ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Sekcja kategorii z tabelą produktów. */
function ntc_render_category( $attrs ) {
	$slug = ntc_a( $attrs, 'category' );
	$rows = ntc_a( $attrs, 'showTable', true ) ? ntc_get_products( $slug ) : array();
	$alt  = ntc_a( $attrs, 'alt', false );

	ob_start();
	?>
	<section class="cat-section<?php echo $alt ? ' cat-section--alt' : ''; ?>" id="cat-<?php echo esc_attr( $slug ); ?>">
		<div class="section-inner">

			<div class="cat-grid">
				<div class="cat-text">
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'p1' ) ); ?></p>
					<?php if ( ntc_a( $attrs, 'p2' ) ) : ?>
						<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'p2' ) ); ?></p>
					<?php endif; ?>

					<?php if ( ntc_a( $attrs, 'ctaText' ) && ntc_a( $attrs, 'ctaUrl' ) ) : ?>
						<a href="<?php echo esc_url( ntc_a( $attrs, 'ctaUrl' ) ); ?>" class="btn-primary cat-cta">
							<?php echo esc_html( ntc_a( $attrs, 'ctaText' ) ); ?>
							<span class="cat-cta-arrow" aria-hidden="true">&rarr;</span>
						</a>
					<?php endif; ?>
				</div>
				<div class="cat-media">
					<img<?php echo ntc_img_kadr( $attrs, 'cat-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( ntc_block_img( $attrs, 'cat-' . $slug ) ); ?>"
						alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_a( $attrs, 'label' ) ) ); ?>" loading="lazy" />
				</div>
			</div>

			<?php if ( ntc_a( $attrs, 'showTable', true ) ) : ?>
				<?php
				$cols     = ntc_table_columns( ntc_a( $attrs, 'columns', 'name,cas,form,docs,origin' ) );
				$labels   = ntc_column_labels( ntc_a( $attrs, 'colLabels' ) );
				$per_page = (int) ntc_a( $attrs, 'perPage', 0 );
				?>
				<div class="product-block" data-ntc-table data-per-page="<?php echo esc_attr( $per_page ); ?>">
					<div class="search-bar">
						<label class="screen-reader-text" for="search-<?php echo esc_attr( $slug ); ?>"><?php ntc_e( 'table.search_ph' ); ?></label>
						<input class="search-input" type="search" id="search-<?php echo esc_attr( $slug ); ?>" data-ntc-search
							placeholder="<?php echo esc_attr( ntc_raw( 'table.search_ph' ) ); ?>" />
						<span class="search-count" data-ntc-count aria-live="polite">
							<?php echo esc_html( count( $rows ) . ' ' . ntc_raw( 'table.count' ) ); ?>
						</span>
					</div>

					<div class="table-wrap<?php echo $per_page ? '' : ' table-wrap--tall'; ?>">
						<table class="product-table">
							<thead>
								<tr>
									<?php foreach ( $cols as $col ) : ?>
										<th scope="col"><?php echo esc_html( ntc_column_label( $col, $labels ) ); ?></th>
									<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
								<?php foreach ( $rows as $row ) : ?>
									<tr data-ntc-row data-search="<?php echo esc_attr( ntc_row_search_index( $row ) ); ?>">
										<?php foreach ( $cols as $col ) : ?>
											<td><?php echo ntc_table_cell( $row, $col ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- treść budowana z esc_html w ntc_table_cell. ?></td>
										<?php endforeach; ?>
									</tr>
								<?php endforeach; ?>
								<tr class="table-empty" data-ntc-empty hidden><td colspan="<?php echo count( $cols ); ?>"></td></tr>
							</tbody>
						</table>
					</div>

					<?php if ( $per_page ) : ?>
						<div class="table-pager" data-ntc-pager hidden>
							<button type="button" class="pager-btn" data-ntc-prev><?php ntc_e( 'table.prev' ); ?></button>
							<span class="pager-state" data-ntc-pager-state aria-live="polite"></span>
							<button type="button" class="pager-btn" data-ntc-next><?php ntc_e( 'table.next' ); ?></button>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( ntc_a( $attrs, 'showNotFound', true ) ) : ?>
				<div class="not-found">
					<div>
						<div class="not-found-title"><?php ntc_e( 'notfound.title' ); ?></div>
						<p class="not-found-text"><?php ntc_e( 'notfound.text' ); ?></p>
					</div>
					<a href="<?php echo esc_url( ntc_page_url( 'kontakt' ) ); ?>" class="btn-primary"><?php ntc_e( 'notfound.cta' ); ?></a>
				</div>
			<?php endif; ?>

		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Ciemna sekcja maszyn. */
function ntc_render_machines( $attrs ) {
	ob_start();
	?>
	<section class="cat-section machines<?php echo ntc_a( $attrs, 'dark', false ) ? ' machines--dark' : ''; ?>" id="<?php echo esc_attr( ntc_section_id( $attrs, 'cat-maszyny' ) ); ?>">
		<div class="section-inner">
			<div class="cat-grid">
				<div class="cat-text">
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<?php
					// Opis maszyn na starej stronie to cztery akapity: skup, sprzedaż,
					// serwis i zaproszenie. Jedno pole tekstowe z podziałem na linie
					// zostawia to klientowi do edycji bez dokładania pól w bloku.
					$akapity = array_filter( array_map( 'trim', explode( "\n", (string) ntc_a( $attrs, 'text' ) ) ), 'strlen' );
					?>
					<?php foreach ( $akapity as $akapit ) : ?>
						<p class="section-sub"><?php echo esc_html( $akapit ); ?></p>
					<?php endforeach; ?>
					<?php if ( ntc_a( $attrs, 'ctaText' ) ) : ?>
						<a href="<?php echo esc_url( ntc_a( $attrs, 'ctaUrl', '#' ) ); ?>" target="_blank" rel="noopener noreferrer" class="btn-primary">
							<?php echo esc_html( ntc_a( $attrs, 'ctaText' ) ); ?> <span aria-hidden="true">&rarr;</span>
						</a>
					<?php endif; ?>
					<?php if ( ntc_a( $attrs, 'note' ) ) : ?>
						<p class="machines-note">
							<?php echo wp_kses( nl2br( esc_html( ntc_a( $attrs, 'note' ) ) ), array( 'br' => array() ) ); ?>
						</p>
					<?php endif; ?>
				</div>
				<div class="cat-media">
					<img<?php echo ntc_img_kadr( $attrs, 'cat-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( ntc_block_img( $attrs, 'cat-maszyny' ) ); ?>"
						alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_a( $attrs, 'label' ) ) ); ?>" loading="lazy" />
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Hero wyśrodkowany - podstrona kontaktu. */
function ntc_render_lp_hero( $attrs ) {
	ob_start();
	?>
	<section class="lp-hero">
		<?php echo ntc_hero_lines( 5, '0 0 1400 600', '0.7', 'lp-hero-bg' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		<div class="lp-hero-inner">
			<span class="lp-hero-badge"><?php echo esc_html( ntc_a( $attrs, 'badge' ) ); ?></span>
			<h1 class="lp-hero-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h1>
			<p class="lp-hero-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Panel kontaktowy: dane firmy + formularz. */
function ntc_render_contact_panel( $attrs ) {
	$co = ntc_company();

	$rows = array(
		// Dzielnica wypadła z adresu na prośbę klienta - w korespondencji i tak
		// jej nie używają, a wiersz robił się przez nią trzyczłonowy.
		array( 'pin',   ntc_raw( 'contactpage.row_address' ), esc_html( $co['name'] ) . '<br />' . esc_html( $co['street'] ) . '<br />' . esc_html( $co['city'] ) ),
		array( 'phone', ntc_raw( 'contactpage.row_phone' ), '<a href="' . esc_url( $co['phone_href'] ) . '">' . esc_html( $co['phone'] ) . '</a>' ),
		array( 'mail',  ntc_raw( 'contactpage.row_email' ), '<a href="mailto:' . esc_attr( $co['email'] ) . '">' . esc_html( $co['email'] ) . '</a>' ),
		array( 'clock', ntc_raw( 'contactpage.row_hours' ), esc_html( ntc_raw( 'contactpage.hours_value' ) ) ),
		array( 'doc',   ntc_raw( 'contactpage.row_company' ), 'NIP: ' . esc_html( $co['nip'] ) . ' &middot; REGON: ' . esc_html( $co['regon'] ) . '<br />KRS: ' . esc_html( $co['krs'] ) ),
	);

	ob_start();
	?>
	<section class="section">
		<div class="section-inner">
			<div class="contact-panel">

				<div class="contact-info">
					<div class="contact-info-title"><?php echo esc_html( ntc_a( $attrs, 'infoTitle' ) ); ?></div>
					<div class="contact-info-sub"><?php echo esc_html( ntc_a( $attrs, 'infoSub' ) ); ?></div>

					<?php foreach ( $rows as $row ) : ?>
						<div class="contact-row">
							<div class="contact-row-icon"><?php ntc_the_icon( $row[0] ); ?></div>
							<div>
								<div class="contact-row-label"><?php echo esc_html( $row[1] ); ?></div>
								<div class="contact-row-value"><?php echo wp_kses_post( $row[2] ); ?></div>
							</div>
						</div>
					<?php endforeach; ?>

					<?php
					// Zdjęcie pod danymi teleadresowymi - klient prosił, żeby motyw
					// dłoni ze strony głównej wracał także tutaj.
					$foto = ntc_media_url( $attrs );
					?>
					<?php if ( $foto ) : ?>
						<div class="contact-info-media">
							<img<?php echo ntc_img_kadr( $attrs, '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( $foto ); ?>"
								alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt' ) ); ?>" loading="lazy" />
						</div>
					<?php endif; ?>
				</div>

				<div class="contact-form-wrap">
					<div data-ntc-form-head>
						<div class="contact-form-title"><?php echo esc_html( ntc_a( $attrs, 'formTitle' ) ); ?></div>
						<div class="contact-form-sub"><?php echo esc_html( ntc_a( $attrs, 'formSub' ) ); ?></div>
					</div>
					<?php ntc_the_contact_form( 'full' ); ?>
				</div>

			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Sekcja dojazdu. */
function ntc_render_map( $attrs, $content ) {
	ob_start();
	?>
	<section class="section map-section">
		<div class="section-inner">
			<div class="map-head">
				<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
				<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
			</div>
			<div class="map-grid">
				<?php
				// Adres bierzemy z danych firmowych, więc mapa przesuwa się razem
				// z przeprowadzką, a nie zostaje z zaszytym punktem. Pole embedUrl
				// zostaje na wypadek, gdyby klient chciał wkleić własny odnośnik
				// z Map Google, np. z przypiętą wizytówką firmy.
				$co    = ntc_company();
				$adres = ntc_a( $attrs, 'query', $co['street'] . ', ' . $co['city'] );
				$mapa  = ntc_a( $attrs, 'embedUrl', 'https://www.google.com/maps?q=' . rawurlencode( $adres ) . '&hl=pl&z=16&output=embed' );
				?>
				<div class="map-frame">
					<iframe class="map-embed" src="<?php echo esc_url( $mapa ); ?>"
						title="<?php echo esc_attr( sprintf( ntc_raw( 'contactpage.map_title' ), $co['name'] ) ); ?>"
						loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					<div class="map-label"><?php echo esc_html( ntc_a( $attrs, 'pinLabel' ) ); ?></div>
				</div>
				<div class="map-info"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/** Kafelek dojazdu. */
function ntc_render_map_card( $attrs ) {
	ob_start();
	?>
	<div class="map-info-card">
		<div class="map-info-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
		<div class="map-info-title"><?php echo esc_html( ntc_a( $attrs, 'title' ) ); ?></div>
		<div class="map-info-text"><?php echo esc_html( ntc_a( $attrs, 'text' ) ); ?></div>
	</div>
	<?php
	return ob_get_clean();
}

/** Pasek "pilne zapytanie". */
function ntc_render_quick_cta( $attrs ) {
	$co = ntc_company();

	ob_start();
	?>
	<section class="quick-cta-wrap">
		<div class="quick-cta">
			<div>
				<div class="quick-cta-head"><?php echo esc_html( ntc_a( $attrs, 'head' ) ); ?></div>
				<div class="quick-cta-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></div>
			</div>
			<div class="quick-cta-row">
				<a href="<?php echo esc_url( $co['phone_href'] ); ?>" class="btn-white"><?php echo esc_html( $co['phone'] ); ?></a>
				<a href="mailto:<?php echo esc_attr( $co['email'] ); ?>" class="btn-outline-w"><?php echo esc_html( $co['email'] ); ?></a>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/* ==========================================================================
 * Jakość
 * ========================================================================== */

/**
 * Sekcja z listą punktów odhaczonych ptaszkiem.
 *
 * Polityka Jakości i polityka nadzoru to na starej stronie długie listy zdań,
 * a nie kafelki - kafelek na cztery linijki tekstu rozjeżdża siatkę. Stąd
 * osobny układ: tekst w kolumnie, ilustracja obok, naprzemiennie raz z lewej
 * raz z prawej (atrybut flip).
 *
 * @param array  $attrs   Atrybuty bloku.
 * @param string $content Wyrenderowane bloki potomne.
 * @return string
 */
function ntc_render_checklist( $attrs, $content ) {
	$alt   = ntc_a( $attrs, 'alt', false );
	$flip  = ntc_a( $attrs, 'flip', false );
	$ciemna = ntc_a( $attrs, 'dark', false );
	$kotwa = ntc_section_id( $attrs );

	// Ciemne tło wyklucza jasny wariant - dwa tła naraz nie mają sensu.
	$klasy = 'section checklist';
	if ( $ciemna ) {
		$klasy .= ' checklist--dark';
	} elseif ( $alt ) {
		$klasy .= ' checklist--alt';
	}

	ob_start();
	?>
	<section class="<?php echo esc_attr( $klasy ); ?>"<?php echo $kotwa ? ' id="' . esc_attr( $kotwa ) . '"' : ''; ?>>
		<div class="section-inner">
			<div class="checklist-grid<?php echo $flip ? ' checklist-grid--flip' : ''; ?>">
				<div class="checklist-text">
					<?php if ( ntc_a( $attrs, 'label' ) ) : ?>
						<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
					<?php endif; ?>
					<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
					<?php if ( ntc_a( $attrs, 'intro' ) ) : ?>
						<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'intro' ) ); ?></p>
					<?php endif; ?>
					<ul class="checklist-items"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render bloków potomnych. ?></ul>
				</div>
				<div class="checklist-media">
					<img<?php echo ntc_img_kadr( $attrs, 'checklist-img' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> src="<?php echo esc_url( ntc_block_img( $attrs, 'cat-api' ) ); ?>"
						alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_a( $attrs, 'title' ) ) ); ?>" loading="lazy" />
				</div>
			</div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Pojedynczy punkt listy.
 *
 * Tekst przechodzi przez ntc_rich, więc redaktor może pogrubić fragment albo
 * wstawić odnośnik do dokumentu, tak jak na starej stronie wyróżnione były
 * nazwy norm i rejestrów.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_check_item( $attrs ) {
	ob_start();
	?>
	<li class="checklist-item">
		<span class="checklist-tick" aria-hidden="true">
			<svg viewBox="0 0 20 20" width="20" height="20" fill="none">
				<circle cx="10" cy="10" r="10" fill="currentColor" />
				<path d="M6 10.2l2.6 2.6L14.4 7" stroke="#fff" stroke-width="2"
					stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</span>
		<span class="checklist-body"><?php echo ntc_rich( ntc_a( $attrs, 'text' ) ); ?></span>
	</li>
	<?php
	return ob_get_clean();
}

/**
 * Ciemny pas z jednym zdaniem na środku.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_quote_band( $attrs ) {
	ob_start();
	?>
	<section class="quote-band">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 44,
				'count'   => 70,
				'height'  => 400,
				'bias'    => 'wave',
				'opacity' => 0.3,
				'class'   => 'ntc-mesh ntc-mesh--band',
			)
		);
		?>
		<div class="quote-band-inner">
			<p class="quote-band-text"><?php echo ntc_rich( ntc_a( $attrs, 'text' ) ); ?></p>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Sekcja certyfikatów i uprawnień.
 *
 * @param array  $attrs   Atrybuty bloku.
 * @param string $content Wyrenderowane bloki potomne.
 * @return string
 */
function ntc_render_certs( $attrs, $content ) {
	ob_start();
	?>
	<section class="section certs">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 91,
				'count'   => 70,
				'bias'    => 'bottom-left',
				'opacity' => 0.38,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="section-inner">
			<?php if ( ntc_a( $attrs, 'label' ) ) : ?>
				<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
			<?php endif; ?>
			<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>

			<?php foreach ( array( 'p1', 'p2' ) as $key ) : ?>
				<?php if ( ntc_a( $attrs, $key ) ) : ?>
					<p class="certs-lead"><?php echo ntc_rich( ntc_a( $attrs, $key ) ); ?></p>
				<?php endif; ?>
			<?php endforeach; ?>

			<div class="certs-grid"><?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- render bloków potomnych. ?></div>
		</div>
	</section>
	<?php
	return ob_get_clean();
}

/**
 * Pojedynczy certyfikat: godło instytucji plus podpisy.
 *
 * Bez wgranego godła pokazujemy inicjały wydawcy zamiast pustego prostokąta -
 * skany certyfikatów dochodzą zwykle później niż reszta treści.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_cert( $attrs ) {
	$logo   = ntc_media_url( $attrs, 'medium' );
	$issuer = ntc_a( $attrs, 'issuer' );

	ob_start();
	?>
	<div class="cert-card">
		<div class="cert-mark">
			<?php if ( $logo ) : ?>
				<img src="<?php echo esc_url( $logo ); ?>"
					alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', $issuer ) ); ?>" loading="lazy" />
			<?php else : ?>
				<span class="cert-mark-text"><?php echo esc_html( $issuer ); ?></span>
			<?php endif; ?>
		</div>
		<?php
		// Na dotychczasowej stronie pod każdym godłem stały odnośniki do skanów
		// w PDF, nie sam tekst. Kto sprawdza dostawcę, chce zobaczyć dokument,
		// a nie przeczytać, że istnieje.
		foreach ( array( 'line1', 'line2', 'line3' ) as $key ) :
			$label = ntc_a( $attrs, $key );

			if ( ! $label ) {
				continue;
			}

			$file = ntc_a( $attrs, $key . 'Url' );
			?>
			<div class="cert-line">
				<?php if ( $file ) : ?>
					<a class="cert-file" href="<?php echo esc_url( $file ); ?>" target="_blank" rel="noopener">
						<?php echo esc_html( $label ); ?>
						<span class="cert-file-tag" aria-hidden="true">PDF</span>
						<span class="screen-reader-text"><?php ntc_e( 'certs.opens_pdf' ); ?></span>
					</a>
				<?php else : ?>
					<?php echo esc_html( $label ); ?>
				<?php endif; ?>
			</div>
		<?php endforeach; ?>
	</div>
	<?php
	return ob_get_clean();
}

/* ==========================================================================
 * Siatka połączeń - motyw z identyfikacji wizualnej
 * ========================================================================== */

/**
 * Deterministyczny generator liczb pseudolosowych.
 *
 * Świadomie nie mt_rand(): ten sam identyfikator ma zawsze dawać tę samą
 * siatkę. Inaczej grafika przeskakiwałaby przy każdym odświeżeniu i przy
 * każdym czyszczeniu cache, a klient zobaczyłby na stronie coś innego niż
 * przed chwilą akceptował. Przy okazji nie ruszamy globalnego stanu mt_rand,
 * z którego korzysta reszta WordPressa.
 *
 * @param int $seed Ziarno.
 * @return callable Funkcja zwracająca liczbę z przedziału 0-1.
 */
function ntc_mesh_rng( $seed ) {
	$state = max( 1, (int) $seed );

	return function () use ( &$state ) {
		// Park-Miller: krótki, bez zależności, wystarczający do rozrzucenia kropek.
		$state = ( $state * 48271 ) % 2147483647;

		return $state / 2147483647;
	};
}

/**
 * Siatka połączonych punktów z identyfikacji wizualnej NTC.
 *
 * Punkty rozrzucone z przewagą przy jednej krawędzi, połączone z najbliższymi
 * sąsiadami. Im dłuższe połączenie, tym bledsze - to daje wrażenie zagęszczenia
 * przy krawędzi i rozrzedzenia w głębi, tak jak w oryginalnej grafice.
 *
 * Kolor bierze się z currentColor, więc ta sama funkcja obsługuje wariant biały
 * na granatowym tle i szary na białym: wystarczy CSS-owe color.
 *
 * @param array $args Konfiguracja: seed, count, bias, opacity, class.
 * @return string
 */
function ntc_mesh( $args = array() ) {
	$args = array_merge(
		array(
			'seed'    => 7,
			'count'   => 80,
			'width'   => 1400,
			'height'  => 700,
			// left | bottom-left | wave - skąd nadciąga zagęszczenie.
			'bias'    => 'wave',
			'opacity' => 1.0,
			'class'   => 'ntc-mesh',
		),
		$args
	);

	$rand = ntc_mesh_rng( $args['seed'] );
	$w    = (int) $args['width'];
	$h    = (int) $args['height'];
	$pts  = array();

	for ( $i = 0; $i < (int) $args['count']; $i++ ) {
		$rx = $rand();
		$ry = $rand();

		switch ( $args['bias'] ) {
			case 'left':
				// Kwadrat losowej wartości ściąga punkty do lewej krawędzi.
				$x = $rx * $rx * $w;
				$y = $ry * $h;
				break;

			case 'bottom-left':
				$x = $rx * $rx * $w;
				$y = $h - $ry * $ry * $h;
				break;

			default:
				// Pas biegnący ukośnie przez kadr, z lekkim falowaniem.
				$x = $rx * $w;
				$y = ( 0.5 + 0.32 * sin( $rx * 6.2 ) ) * $h + ( $ry - 0.5 ) * 0.45 * $h;
				break;
		}

		$pts[] = array(
			'x' => (int) round( $x ),
			'y' => (int) round( max( 0, min( $h, $y ) ) ),
			'r' => round( 1.6 + $rand() * 3.4, 1 ),
			// Część kropek to obwódki bez wypełnienia - w oryginale przeplatają
			// się z pełnymi i to one dają wrażenie głębi.
			'o' => $rand() < 0.3,
			'a' => round( 0.25 + $rand() * 0.75, 2 ),
		);
	}

	$max   = $w * 0.16;
	$edges = array();

	foreach ( $pts as $i => $a ) {
		$near = array();

		foreach ( $pts as $j => $b ) {
			if ( $j <= $i ) {
				continue;
			}

			$d = sqrt( pow( $a['x'] - $b['x'], 2 ) + pow( $a['y'] - $b['y'], 2 ) );

			if ( $d < $max ) {
				$near[ $j ] = $d;
			}
		}

		// Trzech najbliższych sąsiadów wystarcza na trójkąty. Więcej robi się
		// zbitą plamą, mniej rozpada się na niepowiązane kreski.
		asort( $near );

		foreach ( array_slice( $near, 0, 3, true ) as $j => $d ) {
			$edges[] = array( $a, $pts[ $j ], round( ( 1 - $d / $max ) * 0.5, 3 ) );
		}
	}

	// Linie i kropki idą w kubełkach krycia, a nie po jednym elemencie na sztukę.
	// Osiemset osobnych <line> to prawie 100 kB HTML-u na każdej podstronie -
	// przy dekoracji, której nikt świadomie nie ogląda, to zły interes. Jedna
	// ścieżka na kubełek schodzi z tego do kilkunastu kilobajtów.
	$line_buckets = array();

	foreach ( $edges as $e ) {
		$bucket = (string) round( $e[2], 1 );

		if ( ! isset( $line_buckets[ $bucket ] ) ) {
			$line_buckets[ $bucket ] = '';
		}

		$line_buckets[ $bucket ] .= sprintf(
			'M%d %dL%d %d',
			$e[0]['x'],
			$e[0]['y'],
			$e[1]['x'],
			$e[1]['y']
		);
	}

	$dot_buckets = array();

	foreach ( $pts as $p ) {
		$bucket = ( $p['o'] ? 'o' : 'f' ) . round( $p['a'], 1 );

		if ( ! isset( $dot_buckets[ $bucket ] ) ) {
			$dot_buckets[ $bucket ] = array();
		}

		$dot_buckets[ $bucket ][] = $p;
	}

	ob_start();
	?>
	<svg class="<?php echo esc_attr( $args['class'] ); ?>" viewBox="0 0 <?php echo esc_attr( $w . ' ' . $h ); ?>"
		preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false"
		style="--mesh-opacity:<?php echo esc_attr( $args['opacity'] ); ?>">
		<g class="ntc-mesh-lines" stroke="currentColor" fill="none" stroke-width="1">
			<?php foreach ( $line_buckets as $opacity => $path ) : ?>
				<path d="<?php echo esc_attr( $path ); ?>" stroke-opacity="<?php echo esc_attr( $opacity ); ?>" />
			<?php endforeach; ?>
		</g>
		<?php
		// Jedna animowana warstwa na wszystkie kropki, a kubełki krycia siedzą
		// w środku. Animowanie każdego kubełka osobno dawało na stronie głównej
		// kilkadziesiąt niezależnie ruszanych grup, czyli tyle samo warstw do
		// złożenia na każdej klatce.
		?>
		<g class="ntc-mesh-dots">
			<?php foreach ( $dot_buckets as $bucket => $dots ) : ?>
				<?php $ring = 'o' === $bucket[0]; ?>
				<g <?php echo $ring ? 'fill="none" stroke="currentColor" stroke-width="1" stroke-opacity' : 'fill="currentColor" fill-opacity'; ?>="<?php echo esc_attr( substr( $bucket, 1 ) ); ?>">
					<?php foreach ( $dots as $d ) : ?>
						<circle cx="<?php echo esc_attr( $d['x'] ); ?>" cy="<?php echo esc_attr( $d['y'] ); ?>" r="<?php echo esc_attr( $d['r'] ); ?>"/>
					<?php endforeach; ?>
				</g>
			<?php endforeach; ?>
		</g>
	</svg>
	<?php
	return ob_get_clean();
}

/**
 * Mapa pochodzenia składników.
 *
 * Podpis pod mapą liczy kraje z katalogu, zamiast brać liczbę z pola tekstowego.
 * Powód jest praktyczny: przy wpisaniu ręcznie rozjeżdża się przy pierwszym
 * imporcie i nikt tego nie zauważa. Dziś licznik w statystykach mówi o
 * piętnastu krajach, potwierdzona lista API zna siedem, a mapa podświetla
 * jeszcze więcej. Ta liczba ma się poprawiać sama.
 *
 * @param array $attrs Atrybuty bloku.
 * @return string
 */
function ntc_render_origin_map( $attrs ) {
	// Kraje przychodzą listą z pola bloku, a nie z katalogu produktów.
	// Pierwsza wersja liczyła je z pola pochodzenia wszystkich produktów i to
	// było błędne założenie: katalog trzyma substancje aktywne, probiotyki
	// i laktoferynę, ale nie maszyny, kolagen ani kolostrum. Liczył więc
	// siedem krajów, podczas gdy potwierdzona lista klienta ma dwadzieścia
	// cztery. Licznik nadal nie jest wpisywany ręcznie: bierze się z długości
	// listy, więc dopisanie kraju od razu poprawia liczbę.
	$countries = array_values(
		array_filter(
			array_map( 'trim', preg_split( '/[\n,]/', (string) ntc_a( $attrs, 'countries' ) ) )
		)
	);

	$image = ntc_media_url( $attrs );

	ob_start();
	?>
	<section class="section origin">
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG budowany w ntc_mesh().
		echo ntc_mesh(
			array(
				'seed'    => 64,
				'count'   => 60,
				'bias'    => 'wave',
				'opacity' => 0.3,
				'class'   => 'ntc-mesh ntc-mesh--corner',
			)
		);
		?>
		<div class="section-inner">
			<div class="origin-head">
				<?php if ( ntc_a( $attrs, 'label' ) ) : ?>
					<div class="section-label"><?php echo esc_html( ntc_a( $attrs, 'label' ) ); ?></div>
				<?php endif; ?>
				<h2 class="section-title"><?php echo ntc_rich( ntc_a( $attrs, 'title' ) ); ?></h2>
				<?php if ( ntc_a( $attrs, 'sub' ) ) : ?>
					<p class="section-sub"><?php echo esc_html( ntc_a( $attrs, 'sub' ) ); ?></p>
				<?php endif; ?>
			</div>

			<?php if ( $image ) : ?>
				<img class="origin-map" src="<?php echo esc_url( $image ); ?>"
					alt="<?php echo esc_attr( ntc_a( $attrs, 'imageAlt', ntc_raw( 'origin.img_alt' ) ) ); ?>" loading="lazy" />
			<?php endif; ?>

			<?php if ( $countries && ntc_a( $attrs, 'showList', true ) ) : ?>
				<ul class="origin-counts">
					<?php foreach ( $countries as $country ) : ?>
						<li class="origin-count"><span class="origin-code"><?php echo esc_html( $country ); ?></span></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>

			<?php if ( $countries ) : ?>
				<p class="origin-note">
					<?php
					printf(
						/* translators: %d liczba krajów */
						esc_html( ntc_raw( 'origin.summary' ) ),
						count( $countries )
					);
					?>
				</p>
			<?php endif; ?>
		</div>
	</section>
	<?php
	return ob_get_clean();
}
