<?php
/**
 * Typ treści "Produkty" - katalog substancji na podstronie oferty.
 *
 * Tabele w ofercie były wcześniej tablicą w inc/data.php, więc dopisanie
 * substancji wymagało edycji pliku. Teraz to zwykłe wpisy: Produkty → Dodaj
 * nowy, cztery pola, kategoria z listy. Tabela na stronie układa się sama.
 *
 * Świadomie nie jest to blok - dwadzieścia wierszy po pięć pól klika się
 * w edytorze fatalnie, a lista wpisów daje sortowanie, wyszukiwarkę i import.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_PRODUCT_CPT = 'ntc_product';
const NTC_PRODUCT_TAX = 'ntc_product_cat';

/**
 * Pola produktu, które mają wersję angielską.
 *
 * "name" to nazwa wyświetlana w tabeli - tytuł wpisu zostaje polski.
 *
 * @return string[]
 */
function ntc_product_fields_en() {
	return array( 'name', 'group', 'group_full', 'form', 'use', 'origin', 'cas', 'docs', 'dev' );
}

/** Pola dodatkowe produktu: klucz meta => etykieta w kokpicie. */
function ntc_product_fields() {
	$pola = array(
		'_ntc_cas'    => array(
			'label' => 'Nr CAS',
			'hint'  => 'Np. 1115-70-4. Zostaw myślnik, jeśli substancja go nie ma.',
		),
		'_ntc_form'   => array(
			'label' => 'Forma',
			'hint'  => 'Np. proszek, proszek liofilizowany.',
		),
		'_ntc_docs'       => array(
			'label' => 'Dokumentacja',
			'hint'  => 'Skróty po przecinku - każdy wyświetli się jako osobna plakietka. Np. CEP, DMF, WC.',
		),
		'_ntc_origin'     => array(
			'label' => 'Pochodzenie',
			'hint'  => 'Kod kraju lub kilka po ukośniku. Np. CN/EU.',
		),
		'_ntc_group'      => array(
			'label' => 'Grupa terapeutyczna',
			'hint'  => 'Krótko, np. Anti-viral. To widać w tabeli.',
		),
		'_ntc_group_full' => array(
			'label' => 'Grupa terapeutyczna - pełny opis',
			'hint'  => 'Dłuższy opis pokazywany po najechaniu na grupę w tabeli.',
		),
		'_ntc_maker'      => array(
			'label' => 'Wytwórca',
			'hint'  => 'Nazwa wytwórni, np. Supriya Lifesciences.',
		),
		'_ntc_use'        => array(
			'label' => 'Zastosowanie',
			'hint'  => 'Np. ludzkie, weterynaryjne. Puste, jeśli do ustalenia.',
		),
		'_ntc_dev'        => array(
			'label' => 'Status',
			'hint'  => 'Wypełnij tylko dla substancji w opracowaniu - wtedy przy nazwie pojawi się plakietka.',
		),
		'_ntc_collection' => array(
			'label' => 'Kolekcja',
			'hint'  => 'Numer szczepu w kolekcji, np. CNCM I-6030 albo MTCC 5260. Dotyczy probiotyków.',
		),
		'_ntc_postbiotic' => array(
			'label' => 'Dostępny jako postbiotyk',
			'hint'  => 'Wpisz "tak", jeśli szczep jest dostępny również w wersji postbiotycznej. '
				. 'Puste pole znaczy, że nie jest.',
		),
	);

	// Angielskie odpowiedniki pól opisowych - w formularzu pod polskimi,
	// z tą samą podpowiedzią. Puste zostawia w wersji EN wartość polską.
	$nazwy = array(
		'name' => array( 'label' => 'Nazwa w tabeli', 'hint' => 'Np. Lactoferrin 95%. Puste - tytuł wpisu.' ),
	);

	foreach ( ntc_product_fields_en() as $pole ) {
		$zrodlo = isset( $pola[ '_ntc_' . $pole ] ) ? $pola[ '_ntc_' . $pole ] : $nazwy[ $pole ];

		$pola[ '_ntc_' . $pole . '_en' ] = array(
			'label' => $zrodlo['label'] . ' (EN)',
			'hint'  => 'Wersja angielska. ' . $zrodlo['hint'],
		);
	}

	return $pola;
}

/**
 * Rejestracja typu treści i taksonomii.
 */
function ntc_register_product_cpt() {
	register_post_type(
		NTC_PRODUCT_CPT,
		array(
			'labels'             => array(
				'name'               => 'Produkty',
				'singular_name'      => 'Produkt',
				'add_new'            => 'Dodaj nowy',
				'add_new_item'       => 'Dodaj produkt',
				'edit_item'          => 'Edytuj produkt',
				'new_item'           => 'Nowy produkt',
				'view_item'          => 'Zobacz produkt',
				'search_items'       => 'Szukaj produktów',
				'not_found'          => 'Nie ma jeszcze żadnych produktów',
				'not_found_in_trash' => 'Kosz jest pusty',
				'menu_name'          => 'Produkty',
			),
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-clipboard',
			'menu_position'      => 21,
			// custom-fields jest tu warunkiem wystawienia pól produktu w REST.
			// Bez tego register_post_meta( ..., show_in_rest ) nic nie daje:
			// klucz "meta" nie pojawia się nawet w schemacie, a import katalogu
			// przez API wchodzi z samymi tytułami i pustymi kolumnami.
			// Klucze zaczynają się od podkreślenia, więc panel "Pola własne"
			// i tak ich nie pokazuje - redaktor dalej widzi tylko metaboks.
			'supports'           => array( 'title', 'page-attributes', 'custom-fields' ),
			'has_archive'        => false,
			'rewrite'            => false,
			'hierarchical'       => false,
		)
	);

	register_taxonomy(
		NTC_PRODUCT_TAX,
		NTC_PRODUCT_CPT,
		array(
			'labels'            => array(
				'name'          => 'Kategorie oferty',
				'singular_name' => 'Kategoria',
				'add_new_item'  => 'Dodaj kategorię',
				'edit_item'     => 'Edytuj kategorię',
				'menu_name'     => 'Kategorie',
			),
			'public'            => false,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_rest'      => true,
			'hierarchical'      => true,
			'rewrite'           => false,
		)
	);

	// Kolejność kategorii. Wcześniej decydowała kolejność zakładania, więc
	// dołożenie kategorii w środek oferty wymagało przekładania terminów w
	// bazie. Teraz wystarczy zmienić liczbę.
	register_term_meta(
		NTC_PRODUCT_TAX,
		'_ntc_order',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => function () {
				return current_user_can( 'manage_categories' );
			},
		)
	);

	// Pola produktu w REST - bez tego blok kategorii nie pokaże podglądu tabeli
	// w edytorze.
	foreach ( array_keys( ntc_product_fields() ) as $key ) {
		register_post_meta(
			NTC_PRODUCT_CPT,
			$key,
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'ntc_register_product_cpt' );

/**
 * Trzy startowe kategorie, zakładane przy pierwszym włączeniu motywu.
 *
 * Slugi są te same, których używały bloki kategorii w projekcie, więc świeża
 * instalacja od razu się spina.
 */
function ntc_seed_product_terms() {
	$seed = array(
		'api'         => 'Substancje aktywne (API)',
		'probiotyki'  => 'Probiotyki',
		'laktoferyna' => 'Laktoferyna',
	);

	foreach ( $seed as $slug => $name ) {
		if ( ! term_exists( $slug, NTC_PRODUCT_TAX ) ) {
			wp_insert_term( $name, NTC_PRODUCT_TAX, array( 'slug' => $slug ) );
		}
	}
}
add_action( 'after_switch_theme', 'ntc_seed_product_terms' );

/**
 * Metabox z polami produktu.
 */
function ntc_product_metabox() {
	add_meta_box(
		'ntc-product-details',
		'Dane substancji',
		'ntc_product_metabox_render',
		NTC_PRODUCT_CPT,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'ntc_product_metabox' );

/**
 * Zawartość metaboksa.
 *
 * @param WP_Post $post Edytowany wpis.
 */
function ntc_product_metabox_render( $post ) {
	wp_nonce_field( 'ntc_product_save', 'ntc_product_nonce' );

	echo '<style>.ntc-field{margin:0 0 16px}.ntc-field label{display:block;font-weight:600;margin-bottom:4px}'
		. '.ntc-field input{width:100%;max-width:420px}.ntc-field p{margin:4px 0 0;color:#666;font-size:12px}</style>';

	foreach ( ntc_product_fields() as $key => $field ) {
		$value = get_post_meta( $post->ID, $key, true );
		$id    = 'ntc-' . ltrim( $key, '_' );

		printf(
			'<div class="ntc-field"><label for="%1$s">%2$s</label>'
			. '<input type="text" id="%1$s" name="%3$s" value="%4$s" />'
			. '<p>%5$s</p></div>',
			esc_attr( $id ),
			esc_html( $field['label'] ),
			esc_attr( $key ),
			esc_attr( $value ),
			esc_html( $field['hint'] )
		);
	}
}

/**
 * Zapis pól produktu.
 *
 * @param int $post_id Identyfikator wpisu.
 */
function ntc_product_save( $post_id ) {
	if ( ! isset( $_POST['ntc_product_nonce'] )
		|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['ntc_product_nonce'] ) ), 'ntc_product_save' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	foreach ( array_keys( ntc_product_fields() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}

		$value = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );

		if ( '' === $value ) {
			delete_post_meta( $post_id, $key );
		} else {
			update_post_meta( $post_id, $key, $value );
		}
	}
}
add_action( 'save_post_' . NTC_PRODUCT_CPT, 'ntc_product_save' );

/**
 * Kolumny na liście produktów - żeby dało się ogarnąć katalog bez wchodzenia
 * w każdy wpis.
 */
function ntc_product_columns( $columns ) {
	$out = array();

	foreach ( $columns as $key => $label ) {
		$out[ $key ] = $label;

		if ( 'title' === $key ) {
			$out['ntc_cas']    = 'Nr CAS';
			$out['ntc_form']   = 'Forma';
			$out['ntc_docs']   = 'Dokumentacja';
			$out['ntc_origin'] = 'Pochodzenie';
		}
	}

	return $out;
}
add_filter( 'manage_' . NTC_PRODUCT_CPT . '_posts_columns', 'ntc_product_columns' );

/**
 * Wartości w kolumnach.
 */
function ntc_product_column_value( $column, $post_id ) {
	$map = array(
		'ntc_cas'    => '_ntc_cas',
		'ntc_form'   => '_ntc_form',
		'ntc_docs'   => '_ntc_docs',
		'ntc_origin' => '_ntc_origin',
	);

	if ( isset( $map[ $column ] ) ) {
		echo esc_html( get_post_meta( $post_id, $map[ $column ], true ) );
	}
}
add_action( 'manage_' . NTC_PRODUCT_CPT . '_posts_custom_column', 'ntc_product_column_value', 10, 2 );

/**
 * Produkty danej kategorii, w kolejności ustawionej atrybutem "Kolejność".
 *
 * @param string $slug Slug kategorii.
 * @return array<int,array<string,string>> Wiersze gotowe do tabeli.
 */
function ntc_get_products( $slug ) {
	if ( ! $slug ) {
		return array();
	}

	$query = new WP_Query(
		array(
			'post_type'              => NTC_PRODUCT_CPT,
			// Cały katalog kategorii idzie do przeglądarki, a stronicowanie
			// robi JS - dzięki temu wyszukiwarka nad tabelą przeszukuje
			// wszystko, a nie tylko bieżącą stronę. Przy 230 substancjach to
			// ok. 40 kB HTML-u, więc gra jest warta świeczki.
			'posts_per_page'         => -1,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'title'      => 'ASC',
			),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'tax_query'              => array(
				array(
					'taxonomy' => NTC_PRODUCT_TAX,
					'field'    => 'slug',
					'terms'    => $slug,
				),
			),
		)
	);

	$rows = array();

	foreach ( $query->posts as $post ) {
		$rows[] = array(
			'name'       => get_the_title( $post ),
			'cas'        => (string) get_post_meta( $post->ID, '_ntc_cas', true ),
			'form'       => (string) get_post_meta( $post->ID, '_ntc_form', true ),
			'docs'       => (string) get_post_meta( $post->ID, '_ntc_docs', true ),
			'origin'     => (string) get_post_meta( $post->ID, '_ntc_origin', true ),
			'group'      => (string) get_post_meta( $post->ID, '_ntc_group', true ),
			'group_full' => (string) get_post_meta( $post->ID, '_ntc_group_full', true ),
			'maker'      => (string) get_post_meta( $post->ID, '_ntc_maker', true ),
			'use'        => (string) get_post_meta( $post->ID, '_ntc_use', true ),
			'dev'        => (string) get_post_meta( $post->ID, '_ntc_dev', true ),
			'collection' => (string) get_post_meta( $post->ID, '_ntc_collection', true ),
			'postbiotic' => (string) get_post_meta( $post->ID, '_ntc_postbiotic', true ),
		);

		// W wersji angielskiej pola opisowe biorą się z odpowiedników "_en",
		// jeśli redakcja je wypełniła. Puste pole zostawia wartość polską -
		// nazwy łacińskie, numery CAS czy nazwy wytwórców są i tak te same.
		if ( function_exists( 'ntc_is_en' ) && ntc_is_en() ) {
			$ostatni = count( $rows ) - 1;

			foreach ( ntc_product_fields_en() as $pole ) {
				$en = (string) get_post_meta( $post->ID, '_ntc_' . $pole . '_en', true );

				if ( '' !== $en ) {
					$rows[ $ostatni ][ $pole ] = $en;
				}
			}
		}
	}

	return $rows;
}

/**
 * Lista kategorii do wyboru w bloku - slug => nazwa.
 *
 * @return array<string,string>
 */
function ntc_product_categories() {
	// Zakładki nad ofertą mają iść tak samo jak sekcje pod nimi, a te stoją w
	// kolejności ustalonej z klientem, nie alfabetycznej. Prowadzi ją pole
	// _ntc_order przy kategorii; bez niego decyduje kolejność zakładania.
	$terms = get_terms(
		array(
			'taxonomy'   => NTC_PRODUCT_TAX,
			'hide_empty' => false,
			'orderby'    => 'term_id',
			'order'      => 'ASC',
		)
	);

	if ( is_wp_error( $terms ) ) {
		return array();
	}

	usort(
		$terms,
		function ( $a, $b ) {
			$oa = (int) get_term_meta( $a->term_id, '_ntc_order', true );
			$ob = (int) get_term_meta( $b->term_id, '_ntc_order', true );

			// Kategorie bez ustawionej kolejności lądują na końcu, w kolejności
			// zakładania - tak jak działało to przed wprowadzeniem pola.
			$oa = $oa ? $oa : PHP_INT_MAX;
			$ob = $ob ? $ob : PHP_INT_MAX;

			return $oa === $ob ? $a->term_id - $b->term_id : $oa - $ob;
		}
	);

	$out = array();

	foreach ( $terms as $term ) {
		$out[ $term->slug ] = $term->name;
	}

	return $out;
}
