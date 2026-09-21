<?php
/**
 * Pomocnicze funkcje motywu: adresy podstron, obrazki, ikony SVG.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres podstrony zbudowanej na jednym z szablonów motywu.
 *
 * Szukamy najpierw strony, która ma przypisany szablon (template-oferta.php),
 * potem strony o tym slugu. Jeśli nie ma żadnej - wracamy na stronę główną
 * zamiast wyprodukować martwy link.
 *
 * @param string $which 'oferta' albo 'kontakt'.
 * @return string
 */
function ntc_page_url( $which ) {
	static $cache = array();

	if ( isset( $cache[ $which ] ) ) {
		return ntc_url( $cache[ $which ] );
	}

	$pages = get_pages(
		array(
			'meta_key'   => '_wp_page_template',
			'meta_value' => "template-{$which}.php",
			'number'     => 1,
		)
	);

	if ( ! empty( $pages ) ) {
		$cache[ $which ] = get_permalink( $pages[0] );

		return ntc_url( $cache[ $which ] );
	}

	$by_slug = get_page_by_path( $which );

	$cache[ $which ] = $by_slug ? get_permalink( $by_slug ) : home_url( '/' );

	return ntc_url( $cache[ $which ] );
}

/**
 * Kotwica na stronie głównej - działa też z podstron.
 *
 * @param string $anchor Identyfikator sekcji, bez #.
 * @return string
 */
function ntc_home_anchor( $anchor ) {
	return ntc_url( home_url( '/' ) ) . '#' . $anchor;
}

/**
 * Adres obrazka z assets/img.
 *
 * Zdjęcia dostarczone przez klienta trafiają do assets/img/<slug>.jpg i mają
 * pierwszeństwo. Dopóki ich nie ma, motyw pokazuje firmowy placeholder SVG z
 * assets/img/placeholder/ - strona wygląda kompletnie od pierwszego wgrania,
 * a podmiana zdjęcia to skopiowanie pliku, bez ruszania kodu.
 *
 * Rozszerzenia sprawdzamy w kolejności: jpg, jpeg, png, webp, avif.
 *
 * @param string $slug Nazwa obrazka bez rozszerzenia.
 * @return string
 */
function ntc_img( $slug ) {
	static $cache = array();

	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}

	$dir = get_template_directory() . '/assets/img/';
	$uri = get_template_directory_uri() . '/assets/img/';

	foreach ( array( 'jpg', 'jpeg', 'png', 'webp', 'avif' ) as $ext ) {
		if ( file_exists( $dir . $slug . '.' . $ext ) ) {
			$cache[ $slug ] = $uri . $slug . '.' . $ext;

			return $cache[ $slug ];
		}
	}

	$cache[ $slug ] = $uri . 'placeholder/' . $slug . '.svg';

	return $cache[ $slug ];
}

/**
 * Ikona SVG z zestawu motywu.
 *
 * Ikony sekcji "Co nas wyróżnia" - liniowe, dziedziczą kolor po rodzicu
 * (currentColor), zgodnie z decyzją z czatu, żeby zastąpić emotki.
 *
 * @param string $name Nazwa ikony.
 * @return string Znacznik SVG.
 */
function ntc_icon( $name ) {
	$paths = array(
		// Kolba laboratoryjna - farmaceutyczna jakość.
		'flask'  => '<path d="M9 3h6"/><path d="M10 3v6.5L4.3 18.4A1.8 1.8 0 0 0 5.8 21h12.4a1.8 1.8 0 0 0 1.5-2.6L14 9.5V3"/><path d="M7.8 15h8.4"/>',
		// Tarcza z fajką - kwalifikowani dostawcy.
		'shield' => '<path d="M12 3l7 3.2v4.8c0 5-3 8.5-7 10-4-1.5-7-5-7-10V6.2L12 3z"/><path d="M9 12.2l2.2 2.2L15.5 10"/>',
		// Molekuła - substancje rzadkie.
		'molecule' => '<circle cx="6" cy="17.5" r="1.8"/><circle cx="18" cy="17.5" r="1.8"/><circle cx="12" cy="5.5" r="1.8"/><path d="M7.4 16L10.8 7M16.6 16L13.2 7M7.8 17.5h8.4"/>',
		// Błyskawica - elastyczność.
		'bolt'   => '<path d="M13 3 5 14h6l-1 7 9-12h-6l1-6z"/>',
		// Ikony kafelków kontaktu.
		'pin'    => '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11z"/><circle cx="12" cy="10" r="2.6"/>',
		'phone'  => '<path d="M6.3 3.5h3l1.5 3.8-2 1.4a11.5 11.5 0 0 0 5.5 5.5l1.4-2 3.8 1.5v3a1.8 1.8 0 0 1-2 1.8A15.5 15.5 0 0 1 4.5 5.5a1.8 1.8 0 0 1 1.8-2z"/>',
		'mail'   => '<rect x="3" y="5.5" width="18" height="13" rx="2.2"/><path d="M3.6 6.8 12 13l8.4-6.2"/>',
		'clock'  => '<circle cx="12" cy="12" r="8.6"/><path d="M12 7.2V12l3.2 2"/>',
		'doc'    => '<path d="M6.5 3h7l4.5 4.5V21H6.5z"/><path d="M13.2 3v5h4.6"/><path d="M9.4 12.6h5.2M9.4 16.2h5.2"/>',
	);

	if ( ! isset( $paths[ $name ] ) ) {
		return '';
	}

	return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">'
		. $paths[ $name ]
		. '</svg>';
}

/** Echo dla ntc_icon(). */
function ntc_the_icon( $name ) {
	echo ntc_icon( $name ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- statyczny SVG z motywu.
}

/**
 * Krzywa bloba w hero - ten sam path dla obrysów, wypełnienia i maski zdjęcia.
 *
 * Trzymany w jednym miejscu, bo w prototypie ta sama ścieżka była wklejona
 * sześć razy i rozjechanie jednej kopii psuło kadrowanie zdjęcia.
 */
const NTC_BLOB_PATH = 'M 213 10 C 320 -5 430 65 415 185 C 402 295 310 415 190 410 C 65 405 -20 305 15 180 C 45 60 105 25 213 10 Z';

/**
 * Dane teleadresowe - jedno źródło prawdy dla bloków i stopki.
 *
 * Wartości pochodzą z Wygląd → Dostosuj → NTC - Dane firmowe. Domyślne
 * odpowiadają temu, co było w projekcie, więc świeża instalacja wygląda
 * poprawnie zanim ktokolwiek cokolwiek ustawi.
 *
 * @return array<string,string>
 */
function ntc_company() {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$values = array();

	foreach ( ntc_company_fields() as $key => $field ) {
		// 'ntc_co_street' => 'street'
		$short            = substr( $key, 7 );
		$values[ $short ] = (string) get_theme_mod( $key, $field['default'] );
	}

	// Numer w atrybucie href musi być bez spacji i nawiasów, inaczej część
	// telefonów nie potraktuje go jako numeru.
	$values['phone_href'] = 'tel:' . preg_replace( '/[^\d+]/', '', $values['phone'] );

	$cache = $values;

	return $cache;
}

/* ------------------------------------------------------- menu okruszkowe */

/**
 * Ścieżka do bieżącej strony, od strony głównej w dół.
 *
 * Buduje się z hierarchii stron w kokpicie, więc podstrony oferty dostają
 * okruszki same z siebie, a przeniesienie strony pod inną nadrzędną od razu
 * zmienia ścieżkę. Poza podstronami (czyli tam, gdzie strona nie ma rodzica)
 * okruszki nie mają czego pokazać i funkcja zwraca pustą tablicę.
 *
 * @return array<int,array{label:string,url:string}> Ostatni element to bieżąca strona, bez adresu.
 */
function ntc_breadcrumbs() {
	if ( ! is_page() ) {
		return array();
	}

	$strona = get_queried_object();

	if ( ! $strona || ! $strona->post_parent ) {
		return array();
	}

	$przodkowie = array();
	$rodzic     = (int) $strona->post_parent;

	// get_post_ancestors() daje identyfikatory od najbliższego rodzica w górę.
	while ( $rodzic ) {
		$przodek = get_post( $rodzic );

		if ( ! $przodek ) {
			break;
		}

		array_unshift(
			$przodkowie,
			array(
				'label' => get_the_title( $przodek ),
				'url'   => ntc_url( get_permalink( $przodek ) ),
			)
		);

		$rodzic = (int) $przodek->post_parent;
	}

	$sciezka = array_merge(
		array(
			array(
				'label' => ntc_raw( 'breadcrumbs.home' ),
				'url'   => ntc_url( home_url( '/' ) ),
			),
		),
		$przodkowie,
		array(
			array(
				'label' => get_the_title( $strona ),
				'url'   => '',
			),
		)
	);

	return $sciezka;
}

/**
 * Okruszki jako HTML - lista odnośników z ostatnim elementem bez adresu.
 *
 * @return string Pusty ciąg, gdy nie ma czego pokazać.
 */
function ntc_breadcrumbs_html() {
	$sciezka = ntc_breadcrumbs();

	if ( count( $sciezka ) < 2 ) {
		return '';
	}

	$ostatni = count( $sciezka ) - 1;

	ob_start();
	?>
	<nav class="okruszki" aria-label="<?php echo esc_attr( ntc_raw( 'breadcrumbs.label' ) ); ?>">
		<ol class="okruszki-lista">
			<?php foreach ( $sciezka as $i => $krok ) : ?>
				<li class="okruszki-krok">
					<?php if ( $i === $ostatni ) : ?>
						<span aria-current="page"><?php echo esc_html( $krok['label'] ); ?></span>
					<?php else : ?>
						<a href="<?php echo esc_url( $krok['url'] ); ?>"><?php echo esc_html( $krok['label'] ); ?></a>
						<span class="okruszki-sep" aria-hidden="true">&rsaquo;</span>
					<?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ol>
	</nav>
	<?php
	return ob_get_clean();
}

/**
 * Okruszki dla wyszukiwarek.
 *
 * Google pokazuje ścieżkę w wynikach wyszukiwania zamiast gołego adresu, ale
 * tylko wtedy, gdy dostanie ją w danych strukturalnych. Sam HTML mu nie
 * wystarcza, stąd dodatkowy blok w nagłówku dokumentu.
 */
function ntc_breadcrumbs_schema() {
	$sciezka = ntc_breadcrumbs();

	if ( count( $sciezka ) < 2 ) {
		return;
	}

	$pozycje = array();

	foreach ( $sciezka as $i => $krok ) {
		$pozycja = array(
			'@type'    => 'ListItem',
			'position' => $i + 1,
			'name'     => $krok['label'],
		);

		if ( $krok['url'] ) {
			$pozycja['item'] = $krok['url'];
		}

		$pozycje[] = $pozycja;
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode(
			array(
				'@context'        => 'https://schema.org',
				'@type'           => 'BreadcrumbList',
				'itemListElement' => $pozycje,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		)
	);
}
add_action( 'wp_head', 'ntc_breadcrumbs_schema' );
