<?php
/**
 * Angielskie wersje stron i wpisów.
 *
 * Serwis działa bez Polylanga: język siedzi w adresie (?lang=en), a teksty
 * interfejsu w słownikach inc/strings-*.php. Tutaj dochodzi treść. Każda
 * strona i każdy wpis może mieć bliźniaczą wersję angielską - osobny wpis
 * typu "Wersja angielska", edytowany w zwykłym edytorze bloków. W trybie EN
 * motyw podstawia jego tytuł, treść i zajawkę w miejsce polskich.
 *
 * Wersje angielskie są osobnym typem, a nie ukrytymi stronami, żeby nigdy nie
 * pojawiły się na liście wpisów bloga, w wyszukiwarce ani w mapie strony -
 * adres każdej treści pozostaje jeden, zmienia się tylko parametr języka.
 *
 * Poza treścią plik obsługuje: etykiety menu, angielskie pola produktów w
 * tabelach, nazwy kategorii bloga, daty (przez locale) i doklejanie ?lang=en
 * do odnośników wewnętrznych w treści.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_EN_CPT = 'ntc_en';

/**
 * Typ wpisu na wersje angielskie i pola, które je wiążą.
 */
function ntc_en_register() {
	register_post_type(
		NTC_EN_CPT,
		array(
			'labels'       => array(
				'name'          => 'Wersje angielskie',
				'singular_name' => 'Wersja angielska',
				'edit_item'     => 'Edytuj wersję angielską',
				'add_new_item'  => 'Dodaj wersję angielską',
				'search_items'  => 'Szukaj w wersjach angielskich',
				'not_found'     => 'Brak wersji angielskich',
				'menu_name'     => 'Wersje EN',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => true,
			'show_in_rest' => true,
			'rest_base'    => 'ntc_en',
			'menu_icon'    => 'dashicons-translation',
			'hierarchical' => false,
			'supports'     => array( 'title', 'editor', 'excerpt', 'custom-fields', 'revisions' ),
		)
	);

	$uprawnienie = function () {
		return current_user_can( 'edit_posts' );
	};

	foreach ( array( 'page', 'post' ) as $typ ) {
		register_post_meta(
			$typ,
			'_ntc_en_id',
			array(
				'type'          => 'integer',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => $uprawnienie,
			)
		);
	}

	register_post_meta(
		NTC_EN_CPT,
		'_ntc_zrodlo',
		array(
			'type'          => 'integer',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $uprawnienie,
		)
	);

	register_post_meta(
		'nav_menu_item',
		'_ntc_title_en',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $uprawnienie,
		)
	);

	register_post_meta(
		'attachment',
		'_ntc_alt_en',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $uprawnienie,
		)
	);

	register_term_meta(
		'category',
		'_ntc_name_en',
		array(
			'type'          => 'string',
			'single'        => true,
			'show_in_rest'  => true,
			'auth_callback' => $uprawnienie,
		)
	);
}
add_action( 'init', 'ntc_en_register' );

/**
 * Wersja angielska wpisu, jeśli istnieje i jesteśmy w trybie EN.
 *
 * @param int|WP_Post|null $post Wpis; domyślnie bieżący.
 * @return WP_Post|null
 */
function ntc_en_twin( $post = null ) {
	if ( ! ntc_is_en() || is_admin() ) {
		return null;
	}

	$post = get_post( $post );

	if ( ! $post || NTC_EN_CPT === $post->post_type ) {
		return null;
	}

	static $cache = array();

	if ( ! array_key_exists( $post->ID, $cache ) ) {
		$id   = (int) get_post_meta( $post->ID, '_ntc_en_id', true );
		$twin = $id ? get_post( $id ) : null;

		$cache[ $post->ID ] = ( $twin && NTC_EN_CPT === $twin->post_type && 'trash' !== $twin->post_status ) ? $twin : null;
	}

	return $cache[ $post->ID ];
}

/**
 * Treść wpisu w bieżącym języku - dla kodu, który parsuje bloki sam.
 *
 * @param WP_Post $post Wpis.
 * @return string
 */
function ntc_post_content( $post ) {
	$twin = ntc_en_twin( $post );

	return $twin ? $twin->post_content : $post->post_content;
}

/**
 * Treść: podmiana przed parsowaniem bloków (do_blocks ma priorytet 9).
 *
 * @param string $content Treść.
 * @return string
 */
function ntc_en_content( $content ) {
	$twin = ntc_en_twin();

	return $twin ? $twin->post_content : $content;
}
add_filter( 'the_content', 'ntc_en_content', 1 );

/**
 * Tytuł wpisu - obejmuje nagłówki, okruszki, nawigację między wpisami.
 *
 * @param string $title Tytuł.
 * @param int    $id    Identyfikator wpisu.
 * @return string
 */
function ntc_en_title( $title, $id = 0 ) {
	if ( ! $id ) {
		return $title;
	}

	$twin = ntc_en_twin( $id );

	return ( $twin && '' !== $twin->post_title ) ? $twin->post_title : $title;
}
add_filter( 'the_title', 'ntc_en_title', 10, 2 );

/**
 * Tytuł w znaczniku title.
 *
 * @param string  $title Tytuł.
 * @param WP_Post $post  Wpis.
 * @return string
 */
function ntc_en_single_title( $title, $post ) {
	$twin = ntc_en_twin( $post );

	return ( $twin && '' !== $twin->post_title ) ? $twin->post_title : $title;
}
add_filter( 'single_post_title', 'ntc_en_single_title', 10, 2 );

/**
 * Zajawka - lista wpisów na blogu i opis w danych strukturalnych.
 *
 * @param string  $excerpt Zajawka.
 * @param WP_Post $post    Wpis.
 * @return string
 */
function ntc_en_excerpt( $excerpt, $post = null ) {
	$twin = ntc_en_twin( $post );

	if ( ! $twin ) {
		return $excerpt;
	}

	return '' !== $twin->post_excerpt
		? $twin->post_excerpt
		: wp_trim_words( wp_strip_all_tags( do_blocks( $twin->post_content ) ), 30 );
}
add_filter( 'get_the_excerpt', 'ntc_en_excerpt', 10, 2 );

/**
 * Opis serwisu w tytule strony głównej.
 *
 * @param array $parts Części tytułu.
 * @return array
 */
function ntc_en_document_title( $parts ) {
	if ( ntc_is_en() && isset( $parts['tagline'] ) ) {
		$parts['tagline'] = ntc_raw( 'site.tagline' );
	}

	return $parts;
}
add_filter( 'document_title_parts', 'ntc_en_document_title' );

/**
 * Język WordPressa na froncie w trybie EN - daty wpisów i teksty rdzenia
 * wychodzą wtedy po angielsku.
 *
 * Sam filtr "locale" nie wystarcza: nazwy miesięcy WordPress ładuje, zanim
 * wczyta się motyw. switch_to_locale() przeładowuje je na żądanie.
 */
function ntc_en_switch_locale() {
	if ( ntc_is_en() && ! is_admin() && ! wp_doing_ajax() ) {
		switch_to_locale( 'en_US' );
	}
}
add_action( 'after_setup_theme', 'ntc_en_switch_locale', 99 );

/**
 * Adresy wpisów, stron i kategorii w trybie EN niosą ?lang=en.
 *
 * Dzięki temu każdy odnośnik zbudowany przez get_permalink() - lista wpisów,
 * okruszki, nawigacja między wpisami, adres kanoniczny - zostaje w wersji
 * angielskiej, bez przepuszczania każdego z osobna przez ntc_url().
 *
 * @param string $url Adres.
 * @return string
 */
function ntc_en_permalink( $url ) {
	if ( ! ntc_is_en() || is_admin() || false !== strpos( $url, 'lang=' ) ) {
		return $url;
	}

	return add_query_arg( 'lang', 'en', $url );
}

foreach ( array( 'post_link', 'page_link', 'post_type_link', 'term_link', 'author_link', 'post_type_archive_link' ) as $ntc_filtr ) {
	add_filter( $ntc_filtr, 'ntc_en_permalink', 20 );
}
unset( $ntc_filtr );

/* ----------------------------------------------------------------- menu */

/**
 * Angielskie etykiety pozycji menu.
 *
 * @param array $items Pozycje menu.
 * @return array
 */
function ntc_en_menu_items( $items ) {
	if ( ! ntc_is_en() ) {
		return $items;
	}

	foreach ( $items as $item ) {
		$en = (string) get_post_meta( $item->ID, '_ntc_title_en', true );

		if ( '' !== $en ) {
			$item->title = $en;
		}
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'ntc_en_menu_items' );

/**
 * Pole "Etykieta EN" w edytorze menu.
 *
 * @param int $item_id Pozycja menu.
 */
function ntc_en_menu_field( $item_id ) {
	$value = (string) get_post_meta( $item_id, '_ntc_title_en', true );
	?>
	<p class="description description-wide">
		<label for="ntc-title-en-<?php echo esc_attr( $item_id ); ?>">
			Etykieta w wersji angielskiej<br />
			<input type="text" id="ntc-title-en-<?php echo esc_attr( $item_id ); ?>" class="widefat"
				name="ntc_title_en[<?php echo esc_attr( $item_id ); ?>]" value="<?php echo esc_attr( $value ); ?>" />
		</label>
	</p>
	<?php
}
add_action( 'wp_nav_menu_item_custom_fields', 'ntc_en_menu_field' );

/**
 * Zapis pola "Etykieta EN".
 *
 * @param int $menu_id Menu.
 * @param int $item_id Pozycja.
 */
function ntc_en_menu_save( $menu_id, $item_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce sprawdza rdzeń przed tym hakiem.
	if ( ! isset( $_POST['ntc_title_en'][ $item_id ] ) ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	update_post_meta( $item_id, '_ntc_title_en', sanitize_text_field( wp_unslash( $_POST['ntc_title_en'][ $item_id ] ) ) );
}
add_action( 'wp_update_nav_menu_item', 'ntc_en_menu_save', 10, 2 );

/* ------------------------------------------------------ kategorie bloga */

/**
 * Angielskie nazwy kategorii wpisów.
 *
 * @param WP_Term[] $terms Kategorie.
 * @return WP_Term[]
 */
function ntc_en_categories( $terms ) {
	if ( ! ntc_is_en() || is_admin() ) {
		return $terms;
	}

	// Kopie, bo obiekty terminów siedzą w cache i zmiana nazwy na oryginale
	// przeciekłaby do polskiej wersji w tym samym żądaniu.
	return array_map(
		function ( $term ) {
			$en = (string) get_term_meta( $term->term_id, '_ntc_name_en', true );

			if ( '' !== $en ) {
				$term       = clone $term;
				$term->name = $en;
			}

			return $term;
		},
		$terms
	);
}
add_filter( 'get_the_categories', 'ntc_en_categories' );

/**
 * Angielska nazwa kategorii w nagłówku i tytule archiwum.
 *
 * @param string $name Nazwa kategorii.
 * @return string
 */
function ntc_en_cat_title( $name ) {
	if ( ! ntc_is_en() || ! is_category() ) {
		return $name;
	}

	$en = (string) get_term_meta( get_queried_object_id(), '_ntc_name_en', true );

	return '' !== $en ? $en : $name;
}
add_filter( 'single_cat_title', 'ntc_en_cat_title' );

/* ------------------------------------------------ opisy zdjęć (alt) */

/**
 * Tekst alternatywny zdjęć wyróżniających i miniatur w wersji angielskiej.
 *
 * Zdjęcia w treści stron mają opis w samym bloku, więc tłumaczy go wersja EN
 * strony. Tu chodzi o obrazki wstawiane przez szablon (wpisy, kafelki bloga).
 *
 * @param array   $attr       Atrybuty znacznika img.
 * @param WP_Post $attachment Załącznik.
 * @return array
 */
function ntc_en_image_alt( $attr, $attachment ) {
	if ( ntc_is_en() && ! is_admin() ) {
		$en = (string) get_post_meta( $attachment->ID, '_ntc_alt_en', true );

		if ( '' !== $en ) {
			$attr['alt'] = $en;
		}
	}

	return $attr;
}
add_filter( 'wp_get_attachment_image_attributes', 'ntc_en_image_alt', 10, 2 );

/**
 * Pole "Tekst alternatywny (EN)" w oknie biblioteki mediów.
 *
 * @param array   $fields Pola formularza.
 * @param WP_Post $post   Załącznik.
 * @return array
 */
function ntc_en_alt_field( $fields, $post ) {
	if ( ! wp_attachment_is_image( $post ) ) {
		return $fields;
	}

	$fields['ntc_alt_en'] = array(
		'label' => 'Tekst alternatywny (EN)',
		'input' => 'text',
		'value' => (string) get_post_meta( $post->ID, '_ntc_alt_en', true ),
		'helps' => 'Opis zdjęcia w angielskiej wersji strony. Puste - zostaje polski.',
	);

	return $fields;
}
add_filter( 'attachment_fields_to_edit', 'ntc_en_alt_field', 10, 2 );

/**
 * Zapis pola "Tekst alternatywny (EN)".
 *
 * @param array $post       Dane załącznika.
 * @param array $attachment Wartości z formularza.
 * @return array
 */
function ntc_en_alt_save( $post, $attachment ) {
	if ( isset( $attachment['ntc_alt_en'] ) ) {
		update_post_meta( $post['ID'], '_ntc_alt_en', sanitize_text_field( $attachment['ntc_alt_en'] ) );
	}

	return $post;
}
add_filter( 'attachment_fields_to_save', 'ntc_en_alt_save', 10, 2 );

/* ------------------------------------------------- odnośniki w treści */

/**
 * Doklejanie ?lang=en do odnośników wewnętrznych w treści bloków.
 *
 * Adresy w blokach są wpisane na sztywno, po polsku. Bez tego klik w
 * "Learn more" na angielskiej stronie przenosił na polską wersję podstrony.
 * Pliki z biblioteki mediów (PDF-y certyfikatów, zdjęcia) zostają bez zmian.
 *
 * @param string $html Wynik renderowania bloku.
 * @return string
 */
function ntc_en_links( $html ) {
	if ( ! ntc_is_en() || false === strpos( $html, 'href=' ) ) {
		return $html;
	}

	$dom = preg_quote( untrailingslashit( home_url() ), '#' );

	return preg_replace_callback(
		'#href=(["\'])(' . $dom . '(?!/wp-content/)[^"\'\#]*)(\#[^"\']*)?\1#',
		function ( $m ) {
			$url = $m[2];

			if ( false !== strpos( $url, 'lang=' ) ) {
				return $m[0];
			}

			return 'href=' . $m[1] . esc_url( add_query_arg( 'lang', 'en', $url ) ) . ( isset( $m[3] ) ? $m[3] : '' ) . $m[1];
		},
		$html
	);
}
add_filter( 'render_block', 'ntc_en_links', 30 );

/* ------------------------------------------------------------- kokpit */

/**
 * Odnośnik do wersji angielskiej w edytorze strony i wpisu.
 */
function ntc_en_metabox() {
	foreach ( array( 'page', 'post' ) as $typ ) {
		add_meta_box( 'ntc-en', 'Wersja angielska', 'ntc_en_metabox_html', $typ, 'side' );
	}

	add_meta_box( 'ntc-en-zrodlo', 'Wersja polska', 'ntc_en_zrodlo_html', NTC_EN_CPT, 'side' );
}
add_action( 'add_meta_boxes', 'ntc_en_metabox' );

/**
 * Pole boczne na stronie polskiej.
 *
 * @param WP_Post $post Wpis.
 */
function ntc_en_metabox_html( $post ) {
	$id = (int) get_post_meta( $post->ID, '_ntc_en_id', true );

	if ( $id && get_post( $id ) ) {
		printf(
			'<p><a href="%s">Edytuj wersję angielską</a></p><p><a href="%s" target="_blank">Podgląd po angielsku</a></p>',
			esc_url( get_edit_post_link( $id ) ),
			esc_url( add_query_arg( 'lang', 'en', get_permalink( $post ) ) )
		);
		return;
	}

	echo '<p>Ta treść nie ma jeszcze wersji angielskiej - w trybie EN pokazuje się po polsku.</p>';
}

/**
 * Pole boczne na wersji angielskiej.
 *
 * @param WP_Post $post Wersja angielska.
 */
function ntc_en_zrodlo_html( $post ) {
	$id = (int) get_post_meta( $post->ID, '_ntc_zrodlo', true );

	if ( $id && get_post( $id ) ) {
		printf(
			'<p>Tłumaczenie strony: <a href="%s">%s</a></p><p><a href="%s" target="_blank">Podgląd po angielsku</a></p>',
			esc_url( get_edit_post_link( $id ) ),
			esc_html( get_post( $id )->post_title ),
			esc_url( add_query_arg( 'lang', 'en', get_permalink( $id ) ) )
		);
	}
}

/**
 * Kolumna "Tłumaczenie strony" na liście wersji angielskich.
 *
 * @param array $cols Kolumny.
 * @return array
 */
function ntc_en_columns( $cols ) {
	$cols['ntc_zrodlo'] = 'Tłumaczenie strony';

	return $cols;
}
add_filter( 'manage_' . NTC_EN_CPT . '_posts_columns', 'ntc_en_columns' );

/**
 * Zawartość kolumny.
 *
 * @param string $col     Kolumna.
 * @param int    $post_id Wersja angielska.
 */
function ntc_en_column( $col, $post_id ) {
	if ( 'ntc_zrodlo' !== $col ) {
		return;
	}

	$id = (int) get_post_meta( $post_id, '_ntc_zrodlo', true );

	if ( $id && get_post( $id ) ) {
		printf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $id ) ), esc_html( get_post( $id )->post_title ) );
	}
}
add_action( 'manage_' . NTC_EN_CPT . '_posts_custom_column', 'ntc_en_column', 10, 2 );
