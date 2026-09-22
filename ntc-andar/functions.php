<?php
/**
 * Motyw NTC Andar - konfiguracja.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

define( 'NTC_VERSION', '2.0.0' );

require_once get_template_directory() . '/inc/i18n.php';
require_once get_template_directory() . '/inc/polylang.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/typografia.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/post-types.php';
require_once get_template_directory() . '/inc/demo-content.php';
require_once get_template_directory() . '/inc/forms.php';
require_once get_template_directory() . '/inc/blocks.php';
require_once get_template_directory() . '/inc/patterns.php';
require_once get_template_directory() . '/inc/blog.php';
require_once get_template_directory() . '/inc/autor.php';
require_once get_template_directory() . '/inc/tlumaczenia.php';

/**
 * Podstawowe wsparcie funkcji WordPressa.
 */
function ntc_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'align-wide' );

	// Blokom nie dajemy edytora kolorów ani rozmiarów czcionek - projekt ma
	// zamkniętą paletę i typografię, a rozjechanie ich jednym kliknięciem
	// byłoby łatwiejsze niż naprawienie.
	add_theme_support( 'disable-custom-colors' );
	add_theme_support( 'disable-custom-font-sizes' );
	add_theme_support( 'editor-styles' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 38,
			'width'       => 160,
			'flex-width'  => true,
			'flex-height' => true,
		)
	);

	register_nav_menus(
		array(
			'primary'      => 'Menu główne',
			'footer_nav'   => 'Stopka - nawigacja',
			'footer_offer' => 'Stopka - oferta',
			'footer_legal' => 'Stopka - dokumenty',
		)
	);
}
add_action( 'after_setup_theme', 'ntc_setup' );

/**
 * Czy bieżąca strona zawiera któryś z podanych bloków.
 *
 * Ładowanie CSS-u i JS-u idzie za blokami, a nie za szablonem - dzięki temu
 * arkusz oferty schodzi wtedy, kiedy ktoś wstawi blok kategorii, nawet na
 * innej stronie niż podstrona oferty.
 *
 * @param array $blocks Nazwy bloków.
 * @return bool
 */
function ntc_page_uses( $blocks ) {
	$post = get_post();

	if ( ! $post ) {
		return false;
	}

	foreach ( $blocks as $block ) {
		if ( has_block( $block, $post ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Style i skrypty strony.
 */
function ntc_enqueue_assets() {
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	// Wersjonowanie po czasie modyfikacji pliku - przeglądarka klienta nie
	// zostanie ze starym CSS-em po poprawce, a nie trzeba ręcznie bumpować.
	$ver = function ( $rel ) use ( $dir ) {
		$path = $dir . $rel;

		return file_exists( $path ) ? (string) filemtime( $path ) : NTC_VERSION;
	};

	wp_enqueue_style( 'ntc-fonts', $uri . '/assets/css/fonts.css', array(), $ver( '/assets/css/fonts.css' ) );
	wp_enqueue_style( 'ntc-main', $uri . '/assets/css/main.css', array( 'ntc-fonts' ), $ver( '/assets/css/main.css' ) );

	// style.css niesie tylko nagłówek motywu, ale WordPress i część wtyczek
	// oczekują, że uchwyt 'ntc-style' istnieje.
	wp_register_style( 'ntc-style', get_stylesheet_uri(), array( 'ntc-main' ), $ver( '/style.css' ) );

	wp_enqueue_script( 'ntc-main', $uri . '/assets/js/main.js', array(), $ver( '/assets/js/main.js' ), true );

	$needs_offer = ntc_page_uses( array( 'ntc/page-hero', 'ntc/category', 'ntc/machines' ) )
		|| is_page_template( 'template-oferta.php' );

	if ( $needs_offer ) {
		wp_enqueue_style( 'ntc-oferta', $uri . '/assets/css/oferta.css', array( 'ntc-main' ), $ver( '/assets/css/oferta.css' ) );
		wp_enqueue_script( 'ntc-oferta', $uri . '/assets/js/oferta.js', array(), $ver( '/assets/js/oferta.js' ), true );

		wp_localize_script(
			'ntc-oferta',
			'ntcOferta',
			array(
				'countLabel' => ntc_raw( 'table.count' ),
				'noResults'  => ntc_raw( 'table.no_results' ),
				'pageLabel'  => ntc_raw( 'table.page' ),
				'ofLabel'    => ntc_raw( 'table.of' ),
			)
		);
	}

	// Blog nie jest składany z bloków, więc decyduje typ widoku, nie treść.
	if ( is_home() || is_singular( 'post' ) || is_archive() || is_search() ) {
		wp_enqueue_style( 'ntc-blog', $uri . '/assets/css/blog.css', array( 'ntc-main' ), $ver( '/assets/css/blog.css' ) );
	}

	if ( ntc_page_uses( array( 'ntc/checklist', 'ntc/quote-band', 'ntc/certs' ) ) ) {
		wp_enqueue_style( 'ntc-jakosc', $uri . '/assets/css/jakosc.css', array( 'ntc-main' ), $ver( '/assets/css/jakosc.css' ) );
	}

	$needs_contact = ntc_page_uses( array( 'ntc/lp-hero', 'ntc/contact-panel', 'ntc/map', 'ntc/quick-cta', 'ntc/product-form' ) )
		|| is_home() || is_singular( 'post' ) || is_archive()
		|| is_page_template( 'template-kontakt.php' );

	if ( $needs_contact ) {
		wp_enqueue_style( 'ntc-kontakt', $uri . '/assets/css/kontakt.css', array( 'ntc-main' ), $ver( '/assets/css/kontakt.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'ntc_enqueue_assets' );

/**
 * Skrypty i style edytora bloków.
 */
function ntc_enqueue_editor_assets() {
	$uri = get_template_directory_uri();
	$dir = get_template_directory();

	$ver = function ( $rel ) use ( $dir ) {
		$path = $dir . $rel;

		return file_exists( $path ) ? (string) filemtime( $path ) : NTC_VERSION;
	};

	wp_enqueue_script(
		'ntc-editor',
		$uri . '/assets/js/editor.js',
		array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
		$ver( '/assets/js/editor.js' ),
		true
	);

	// Definicje bloków lecą do edytora prosto z PHP - lista pól istnieje tylko
	// w inc/blocks.php i nie może się rozjechać z JS-em.
	$defs = array();

	foreach ( ntc_block_definitions() as $name => $def ) {
		$defs[ $name ] = array(
			'title'      => $def['title'],
			'icon'       => isset( $def['icon'] ) ? $def['icon'] : 'block-default',
			'attributes' => isset( $def['attributes'] ) ? $def['attributes'] : array(),
			'parent'     => isset( $def['parent'] ) ? $def['parent'] : null,
		);
	}

	wp_add_inline_script(
		'ntc-editor',
		'window.ntcBlocks = ' . wp_json_encode( $defs ) . ';'
		. 'window.ntcBlocksData = ' . wp_json_encode(
			array(
				'categories' => ntc_product_categories(),
				'icons'      => array(
					array( 'value' => 'flask',    'label' => 'Kolba (jakość)' ),
					array( 'value' => 'shield',   'label' => 'Tarcza (dostawcy)' ),
					array( 'value' => 'molecule', 'label' => 'Molekuła (substancje)' ),
					array( 'value' => 'bolt',     'label' => 'Błyskawica (szybkość)' ),
					array( 'value' => 'doc',      'label' => 'Dokument' ),
					array( 'value' => 'clock',    'label' => 'Zegar' ),
					array( 'value' => 'pin',      'label' => 'Pinezka' ),
					array( 'value' => 'phone',    'label' => 'Telefon' ),
					array( 'value' => 'mail',     'label' => 'Koperta' ),
				),
			)
		) . ';',
		'before'
	);

	wp_enqueue_style( 'ntc-editor-fonts', $uri . '/assets/css/fonts.css', array(), $ver( '/assets/css/fonts.css' ) );
	wp_enqueue_style( 'ntc-editor', $uri . '/assets/css/editor.css', array( 'ntc-editor-fonts' ), $ver( '/assets/css/editor.css' ) );
}
add_action( 'enqueue_block_editor_assets', 'ntc_enqueue_editor_assets' );

/**
 * Preload plików fontów użytych nad zgięciem.
 *
 * Bez tego nagłówek hero doczytuje się dopiero po CSS-ie i tytuł przeskakuje.
 * Preloadujemy dwie grubości w podzbiorze latin: 400 na tekst i 700 na
 * nagłówki. Reszta grubości i latin-ext dociągają się naturalnie, kiedy strona
 * na nie trafi - Barlow jest krojem statycznym, więc każda grubość to osobny
 * plik i preloadowanie wszystkich byłoby ściąganiem na zapas.
 */
function ntc_preload_fonts() {
	$uri = get_template_directory_uri() . '/assets/fonts/';

	foreach ( array( 'barlow-normal-400-latin.woff2', 'barlow-normal-700-latin.woff2' ) as $font ) {
		printf(
			'<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin />' . "\n",
			esc_url( $uri . $font )
		);
	}
}
add_action( 'wp_head', 'ntc_preload_fonts', 2 );

/**
 * Klasy na <body>.
 */
function ntc_body_class( $classes ) {
	if ( ntc_is_landing_page() ) {
		$classes[] = 'page-kontakt';
	}

	if ( is_front_page() ) {
		$classes[] = 'page-home';
	}

	return $classes;
}
add_filter( 'body_class', 'ntc_body_class' );

/**
 * Czy to strona typu landing, czyli kontakt: jasne hero i własny arkusz stylów.
 *
 * Decyduje obecność bloku hero kontaktu, a nie nazwa szablonu - dzięki temu
 * układ idzie za treścią, którą redaktor faktycznie złożył.
 *
 * Wpływa na dwie rzeczy: pasek nawigacji jest biały od pierwszej klatki zamiast
 * przezroczystego, a stopka schodzi do wersji skróconej. Nawigacja jest ta sama
 * co wszędzie - wcześniej strona pokazywała tu sam link powrotu.
 */
function ntc_is_landing_page() {
	return ntc_page_uses( array( 'ntc/lp-hero' ) ) || is_page_template( 'template-kontakt.php' );
}

/**
 * Logo w nawigacji.
 *
 * Domyślnie SVG z motywu; jeśli klient wgra własne logo przez Wygląd →
 * Dostosuj, wygrywa jego wersja.
 */
function ntc_logo_markup() {
	$home = ntc_url( home_url( '/' ) );

	if ( has_custom_logo() ) {
		$id  = get_theme_mod( 'custom_logo' );
		$src = wp_get_attachment_image_url( $id, 'full' );

		if ( $src ) {
			return sprintf(
				'<a href="%1$s" class="logo"><img src="%2$s" alt="%3$s" /></a>',
				esc_url( $home ),
				esc_url( $src ),
				esc_attr( get_bloginfo( 'name' ) )
			);
		}
	}

	return sprintf(
		'<a href="%1$s" class="logo"><img src="%2$s" alt="NTC Andar" width="160" height="38" /></a>',
		esc_url( $home ),
		esc_url( get_template_directory_uri() . '/assets/img/logo-ntc-andar.svg' )
	);
}

/**
 * Menu z ustawionej lokalizacji albo lista awaryjna.
 *
 * Dopóki nikt nie zbudował menu w Wygląd → Menu, pokazujemy pozycje z
 * projektu, żeby świeża instalacja miała działającą nawigację.
 *
 * @param string $location Lokalizacja menu.
 * @param array  $fallback Pozycje awaryjne: etykieta => adres.
 * @param string $class    Klasa listy.
 */
function ntc_menu( $location, $fallback, $class = 'footer-links', $depth = 1 ) {
	if ( has_nav_menu( $location ) ) {
		wp_nav_menu(
			array(
				'theme_location' => $location,
				'menu_class'     => $class,
				'container'      => false,
				'depth'          => $depth,
				'fallback_cb'    => false,
			)
		);

		return;
	}

	printf( '<ul class="%s">', esc_attr( $class ) );

	foreach ( $fallback as $label => $url ) {
		// Wartość może być adresem albo tablicą "adres + pozycje podrzędne" -
		// tak wygląda zapasowa Oferta z rozwijaną listą kategorii.
		$dzieci = is_array( $url ) ? $url['sub'] : array();
		$adres  = is_array( $url ) ? $url['url'] : $url;

		printf(
			'<li class="%s"><a href="%s">%s</a>',
			$dzieci ? 'menu-item menu-item-has-children' : 'menu-item',
			esc_url( $adres ),
			esc_html( $label )
		);

		if ( $dzieci ) {
			echo '<ul class="sub-menu">';

			foreach ( $dzieci as $pod_label => $pod_url ) {
				printf(
					'<li class="menu-item"><a href="%s">%s</a></li>',
					esc_url( $pod_url ),
					esc_html( $pod_label )
				);
			}

			echo '</ul>';
		}

		echo '</li>';
	}

	echo '</ul>';
}

/**
 * Tytuł strony 404 - reszta tytułów bierze się z tytułu strony w kokpicie.
 */
function ntc_document_title( $parts ) {
	if ( is_404() ) {
		$parts['title'] = ntc_raw( '404.title' );
	}

	return $parts;
}
add_filter( 'document_title_parts', 'ntc_document_title' );

/**
 * Emotki WordPressa - wyłączone.
 *
 * Motyw ich nie używa (ikony są liniowymi SVG), a wtyczka dokłada skrypt i
 * dodatkowe zapytanie DNS na każdej podstronie.
 */
function ntc_disable_emojis() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
}
add_action( 'init', 'ntc_disable_emojis' );
