<?php
/**
 * Produkty demonstracyjne z makiety.
 *
 * Dwadzieścia pozycji, które były w prototypie z Claude Design. Wstawiane po to,
 * żeby tabele w ofercie nie były puste przed dostarczeniem katalogu przez
 * klienta.
 *
 * UWAGA: to są dane wymyślone na potrzeby projektu graficznego. Numery CAS są
 * prawdziwe (to publiczne identyfikatory substancji), ale przypisanie ich do
 * oferty NTC Andar, kraje pochodzenia i komplety dokumentacji - już nie. Nie
 * nadają się do publikacji.
 *
 * Dlatego każdy taki wpis dostaje meta _ntc_demo, po którym:
 *  - lista produktów oznacza go plakietką "demo",
 *  - w kokpicie wisi ostrzeżenie, dopóki choć jeden został,
 *  - da się je skasować hurtem jednym przyciskiem (Produkty → Dane demonstracyjne).
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_DEMO_META = '_ntc_demo';

/**
 * Katalog demonstracyjny: kategoria => lista pozycji.
 *
 * Kolejność w tablicy jest kolejnością w tabeli (trafia do menu_order).
 *
 * @return array<string,array<int,array<string,string>>>
 */
function ntc_demo_products() {
	return array(
		'api'         => array(
			array( 'name' => 'Metformina HCl',         'cas' => '1115-70-4',   'form' => 'proszek',           'docs' => 'CEP, GMP, WC',   'origin' => 'CN/EU' ),
			array( 'name' => 'Amoksycylina trójwodna', 'cas' => '61336-70-7',  'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'CN' ),
			array( 'name' => 'Ibuprofen',              'cas' => '15687-27-1',  'form' => 'proszek',           'docs' => 'CEP, GMP, WC',   'origin' => 'CN/EU' ),
			array( 'name' => 'Paracetamol',            'cas' => '103-90-2',    'form' => 'proszek/granulat',  'docs' => 'CEP, GMP, WC',   'origin' => 'CN/EU' ),
			array( 'name' => 'Omeprazol',              'cas' => '73590-58-6',  'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'CN/IN' ),
			array( 'name' => 'Atorwastatyna wapniowa', 'cas' => '344423-98-9', 'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'CN/IN' ),
			array( 'name' => 'Kwas acetylosalicylowy', 'cas' => '50-78-2',     'form' => 'proszek',           'docs' => 'CEP, GMP',       'origin' => 'EU/CN' ),
			array( 'name' => 'Diklofenak sodu',        'cas' => '15307-79-6',  'form' => 'proszek',           'docs' => 'CEP, GMP, WC',   'origin' => 'CN' ),
			array( 'name' => 'Amlodypina bezylanu',    'cas' => '111470-99-6', 'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'CN/IN' ),
			array( 'name' => 'Ciprofloksacyna HCl',    'cas' => '86393-32-0',  'form' => 'proszek',           'docs' => 'CEP, GMP, WC',   'origin' => 'CN/IN' ),
			array( 'name' => 'Ramipril',               'cas' => '87333-19-5',  'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'IN/EU' ),
			array( 'name' => 'Simwastatyna',           'cas' => '79902-63-9',  'form' => 'proszek',           'docs' => 'ASMF, CEP, GMP', 'origin' => 'CN' ),
		),
		'probiotyki'  => array(
			array( 'name' => 'Lactobacillus acidophilus LA-5®',    'cas' => '-', 'form' => 'proszek liofilizowany', 'docs' => 'CoA, spec. tech.', 'origin' => 'DK' ),
			array( 'name' => 'Bifidobacterium lactis BB-12®',      'cas' => '-', 'form' => 'proszek liofilizowany', 'docs' => 'CoA, spec. tech.', 'origin' => 'DK' ),
			array( 'name' => 'Lactobacillus rhamnosus GG',         'cas' => '-', 'form' => 'proszek liofilizowany', 'docs' => 'CoA, spec. tech.', 'origin' => 'US/EU' ),
			array( 'name' => 'Lactobacillus plantarum LP-01',      'cas' => '-', 'form' => 'proszek liofilizowany', 'docs' => 'CoA, spec. tech.', 'origin' => 'EU' ),
			array( 'name' => 'Saccharomyces boulardii CNCM I-745', 'cas' => '-', 'form' => 'proszek liofilizowany', 'docs' => 'CoA, spec. tech.', 'origin' => 'EU' ),
		),
		'laktoferyna' => array(
			array( 'name' => 'Laktoferyna bydlęca (BLF) >95%', 'cas' => '1462-54-4', 'form' => 'proszek',         'docs' => 'CoA, spec. tech.', 'origin' => 'NL/NZ' ),
			array( 'name' => 'Laktoferyna bydlęca (BLF) >98%', 'cas' => '1462-54-4', 'form' => 'proszek premium', 'docs' => 'CoA, spec. tech.', 'origin' => 'NZ' ),
			array( 'name' => 'Apolaktoferyna',                 'cas' => '-',         'form' => 'proszek',         'docs' => 'CoA, spec. tech.', 'origin' => 'EU' ),
		),
	);
}

/**
 * Wstawia produkty demonstracyjne.
 *
 * Idempotentne: pozycja o tej samej nazwie w tej samej kategorii nie zostanie
 * zdublowana, więc ponowne kliknięcie nic nie psuje.
 *
 * @return int Liczba faktycznie dodanych wpisów.
 */
function ntc_seed_demo_products() {
	$added = 0;
	$order = 0;

	// Kategorie z właściwymi nazwami zakłada ntc_seed_product_terms(), wisząca
	// na after_switch_theme. Przy wdrożeniu przez podmianę plików ten hak nie
	// leci, a wcześniej wstawialiśmy tu termin o nazwie równej slugowi - przez
	// co zakładki nad ofertą pokazywały "api" zamiast "Substancje aktywne
	// (API)". Wołamy więc źródło nazw, zamiast trzymać drugą, gorszą listę.
	ntc_seed_product_terms();

	foreach ( ntc_demo_products() as $slug => $items ) {
		$term = get_term_by( 'slug', $slug, NTC_PRODUCT_TAX );

		if ( ! $term ) {
			continue;
		}

		foreach ( $items as $item ) {
			++$order;

			$existing = get_posts(
				array(
					'post_type'      => NTC_PRODUCT_CPT,
					'title'          => $item['name'],
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'fields'         => 'ids',
				)
			);

			if ( $existing ) {
				continue;
			}

			$post_id = wp_insert_post(
				array(
					'post_type'   => NTC_PRODUCT_CPT,
					'post_title'  => $item['name'],
					'post_status' => 'publish',
					'menu_order'  => $order,
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				continue;
			}

			update_post_meta( $post_id, '_ntc_cas', $item['cas'] );
			update_post_meta( $post_id, '_ntc_form', $item['form'] );
			update_post_meta( $post_id, '_ntc_docs', $item['docs'] );
			update_post_meta( $post_id, '_ntc_origin', $item['origin'] );
			update_post_meta( $post_id, NTC_DEMO_META, '1' );

			wp_set_object_terms( $post_id, $term->term_id, NTC_PRODUCT_TAX );

			++$added;
		}
	}

	return $added;
}

/**
 * Kasuje wszystkie produkty oznaczone jako demonstracyjne.
 *
 * Rusza wyłącznie wpisy z meta _ntc_demo, więc pozycje dopisane ręcznie przez
 * klienta zostają nietknięte, nawet jeśli mają tę samą nazwę.
 *
 * @return int Liczba skasowanych wpisów.
 */
function ntc_remove_demo_products() {
	$ids = get_posts(
		array(
			'post_type'      => NTC_PRODUCT_CPT,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'meta_key'       => NTC_DEMO_META,
			'meta_value'     => '1',
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( $id, true );
	}

	return count( $ids );
}

/**
 * Ile produktów demonstracyjnych siedzi jeszcze w bazie.
 *
 * @return int
 */
function ntc_count_demo_products() {
	$ids = get_posts(
		array(
			'post_type'      => NTC_PRODUCT_CPT,
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
			'meta_key'       => NTC_DEMO_META,
			'meta_value'     => '1',
		)
	);

	return count( $ids );
}

/**
 * Przy pierwszym włączeniu motywu zakładamy kategorie i wstawiamy demo.
 *
 * Odpala się tylko raz, przy przełączeniu motywu - późniejsze wejścia w kokpit
 * niczego nie dosypują.
 */
function ntc_seed_on_activation() {
	ntc_seed_demo_products();
}
add_action( 'after_switch_theme', 'ntc_seed_on_activation', 20 );

/* ==========================================================================
 * Ekran w kokpicie
 * ========================================================================== */

/**
 * Podstrona "Dane demonstracyjne" w menu Produkty.
 */
function ntc_demo_admin_page() {
	add_submenu_page(
		'edit.php?post_type=' . NTC_PRODUCT_CPT,
		'Dane demonstracyjne',
		'Dane demonstracyjne',
		'manage_options',
		'ntc-demo-content',
		'ntc_demo_admin_render'
	);
}
add_action( 'admin_menu', 'ntc_demo_admin_page' );

/**
 * Obsługa przycisków na tej podstronie.
 */
function ntc_demo_admin_handle() {
	if ( ! isset( $_POST['ntc_demo_action'] ) || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	check_admin_referer( 'ntc_demo_content' );

	$action = sanitize_key( wp_unslash( $_POST['ntc_demo_action'] ) );

	if ( 'seed' === $action ) {
		$count = ntc_seed_demo_products();

		add_settings_error(
			'ntc_demo',
			'ntc_demo_seeded',
			sprintf( 'Dodano %d pozycji demonstracyjnych.', $count ),
			'success'
		);
	} elseif ( 'remove' === $action ) {
		$count = ntc_remove_demo_products();

		add_settings_error(
			'ntc_demo',
			'ntc_demo_removed',
			sprintf( 'Usunięto %d pozycji demonstracyjnych. Pozycje dopisane ręcznie zostały nietknięte.', $count ),
			'success'
		);
	}
}
add_action( 'admin_init', 'ntc_demo_admin_handle' );

/**
 * Zawartość podstrony.
 */
function ntc_demo_admin_render() {
	$count = ntc_count_demo_products();
	?>
	<div class="wrap">
		<h1>Dane demonstracyjne</h1>

		<?php settings_errors( 'ntc_demo' ); ?>

		<p style="max-width:46em">
			Katalog z makiety: 20 pozycji w trzech kategoriach. Służy do tego, żeby
			tabele w ofercie miały co pokazać, zanim klient dostarczy prawdziwą listę.
		</p>

		<div class="notice notice-warning inline" style="max-width:46em">
			<p>
				<strong>To nie jest oferta NTC Andar.</strong> Nazwy substancji i numery
				CAS są prawdziwe, ale przypisanie ich do oferty, kraje pochodzenia i
				komplety dokumentacji zostały wymyślone na potrzeby projektu graficznego.
				Przed uruchomieniem strony trzeba je zastąpić danymi od klienta.
			</p>
		</div>

		<p>
			W bazie jest teraz <strong><?php echo (int) $count; ?></strong> pozycji
			oznaczonych jako demonstracyjne.
		</p>

		<form method="post">
			<?php wp_nonce_field( 'ntc_demo_content' ); ?>

			<p>
				<button type="submit" name="ntc_demo_action" value="seed" class="button button-secondary">
					Wstaw pozycje demonstracyjne
				</button>
				<span class="description" style="margin-left:8px">
					Nie zdubluje tego, co już jest.
				</span>
			</p>

			<p>
				<button type="submit" name="ntc_demo_action" value="remove" class="button button-secondary"
					onclick="return confirm('Usunąć wszystkie pozycje demonstracyjne? Produkty dopisane ręcznie zostaną nietknięte.');">
					Usuń wszystkie pozycje demonstracyjne
				</button>
				<span class="description" style="margin-left:8px">
					Kliknij, kiedy wpiszesz prawdziwy katalog.
				</span>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Ostrzeżenie w kokpicie, dopóki demo siedzi w bazie.
 *
 * Pokazujemy je tylko na ekranach związanych z produktami, żeby nie zaśmiecać
 * całego panelu.
 */
function ntc_demo_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$screen = get_current_screen();

	if ( ! $screen || false === strpos( (string) $screen->id, NTC_PRODUCT_CPT ) ) {
		return;
	}

	if ( 'ntc-demo-content' === ( $_GET['page'] ?? '' ) ) {
		return;
	}

	$url   = admin_url( 'edit.php?post_type=' . NTC_PRODUCT_CPT . '&page=ntc-demo-content' );
	$count = ntc_count_demo_products();

	// Pusty katalog - podpowiadamy, skąd wziąć pozycje na próbę. Przydaje się,
	// gdy motyw był włączony przed dodaniem danych demonstracyjnych, więc
	// wstawienie przy aktywacji już się nie odpali.
	if ( ! $count ) {
		$all = (int) wp_count_posts( NTC_PRODUCT_CPT )->publish;

		if ( 0 === $all ) {
			printf(
				'<div class="notice notice-info"><p><strong>Katalog produktów jest pusty</strong>, więc tabele w ofercie nic nie pokażą. '
				. 'Możesz wstawić 20 pozycji z makiety, żeby zobaczyć jak to wygląda. '
				. '<a href="%s">Dane demonstracyjne</a></p></div>',
				esc_url( $url )
			);
		}

		return;
	}

	printf(
		'<div class="notice notice-warning"><p><strong>Katalog zawiera %d pozycji demonstracyjnych z makiety.</strong> '
		. 'Nie są to prawdziwe dane oferty - przed uruchomieniem strony zastąp je listą od klienta. '
		. '<a href="%s">Zarządzaj danymi demonstracyjnymi</a></p></div>',
		(int) $count,
		esc_url( $url )
	);
}
add_action( 'admin_notices', 'ntc_demo_admin_notice' );

/**
 * Plakietka "demo" przy nazwie na liście produktów.
 *
 * display_post_states to mechanizm, którym WordPress oznacza np. stronę
 * startową - plakietka trafia obok tytułu i nie rusza samego tytułu wpisu.
 */
function ntc_demo_post_state( $states, $post ) {
	if ( NTC_PRODUCT_CPT === $post->post_type && get_post_meta( $post->ID, NTC_DEMO_META, true ) ) {
		$states['ntc_demo'] = 'dane demonstracyjne';
	}

	return $states;
}
add_filter( 'display_post_states', 'ntc_demo_post_state', 10, 2 );
