<?php
/**
 * Kokpit dla redakcji: jedno miejsce na wszystkie treści i prostsze menu.
 *
 * Każda polska strona i każdy wpis mają osobną angielską bliźniaczkę
 * (inc/tlumaczenia.php). Dotąd redaktor szukał wersji polskiej w "Stronach",
 * angielskiej w "Wersjach EN" i sam musiał pamiętać, która jest która. Ekran
 * "Treści strony" stawia obie wersje w jednym wierszu, w kolejności z menu
 * serwisu, a edytor i pasek na stronie mówią wprost, którą wersję się
 * właśnie poprawia.
 *
 * Menu kokpitu pokazuje domyślnie tylko to, czego redakcja używa. Pełne menu
 * wraca jednym kliknięciem, a wybór zapamiętuje się dla każdego użytkownika
 * osobno. Nic nie jest blokowane - schowane pozycje działają pod swoimi
 * adresami jak wcześniej.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_KOKPIT = 'ntc-tresci';

/* ---------------------------------------------------------- wersje językowe */

/**
 * Angielska wersja wpisu - także w kokpicie, gdzie ntc_en_twin() milczy.
 *
 * @param int $post_id Polska strona albo wpis.
 * @return WP_Post|null
 */
function ntc_kokpit_en( $post_id ) {
	$id   = (int) get_post_meta( $post_id, '_ntc_en_id', true );
	$twin = $id ? get_post( $id ) : null;

	return ( $twin && NTC_EN_CPT === $twin->post_type && 'trash' !== $twin->post_status ) ? $twin : null;
}

/**
 * Polski oryginał wersji angielskiej.
 *
 * @param int $twin_id Wersja angielska.
 * @return WP_Post|null
 */
function ntc_kokpit_pl( $twin_id ) {
	$id   = (int) get_post_meta( $twin_id, '_ntc_zrodlo', true );
	$post = $id ? get_post( $id ) : null;

	return ( $post && 'trash' !== $post->post_status ) ? $post : null;
}

/**
 * Adres akcji zakładającej wersję angielską.
 *
 * @param int $post_id Polska strona albo wpis.
 * @return string
 */
function ntc_en_utworz_url( $post_id ) {
	// Surowy adres, nie wp_nonce_url(): ten zwraca "&amp;", a adres trafia
	// też do skryptu edytora i do przekierowań, gdzie "&amp;" psuje nonce.
	return add_query_arg(
		'_wpnonce',
		wp_create_nonce( 'ntc_en_utworz_' . (int) $post_id ),
		admin_url( 'admin-post.php?action=ntc_en_utworz&post=' . (int) $post_id )
	);
}

/**
 * Zakłada wersję angielską jako kopię polskiej i otwiera ją w edytorze.
 *
 * Kopia, a nie pusta strona: redaktor podmienia teksty w gotowym układzie
 * sekcji, zamiast składać stronę od nowa. Do czasu tłumaczenia wersja
 * angielska pokazuje polski tekst - dokładnie tak samo jak bez niej.
 */
function ntc_en_utworz() {
	$id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;

	check_admin_referer( 'ntc_en_utworz_' . $id );

	$post = get_post( $id );

	if ( ! $post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || ! current_user_can( 'edit_post', $id ) ) {
		wp_die( 'Nie ma takiej strony albo brak uprawnień do jej edycji.' );
	}

	$twin = ntc_kokpit_en( $id );

	if ( $twin ) {
		$twin_id = $twin->ID;
	} else {
		$twin_id = wp_insert_post(
			wp_slash(
				array(
					'post_type'    => NTC_EN_CPT,
					// Status bez znaczenia dla strony (liczy się samo istnienie
					// wersji), ale "opublikowana" daje w edytorze zwykły przycisk
					// zapisu zamiast mylącego "Opublikuj".
					'post_status'  => 'publish',
					'post_title'   => $post->post_title,
					'post_content' => $post->post_content,
					'post_excerpt' => $post->post_excerpt,
				)
			),
			true
		);

		if ( is_wp_error( $twin_id ) ) {
			wp_die( esc_html( $twin_id->get_error_message() ) );
		}

		update_post_meta( $twin_id, '_ntc_zrodlo', $id );
		update_post_meta( $id, '_ntc_en_id', $twin_id );
	}

	wp_safe_redirect( get_edit_post_link( $twin_id, 'raw' ) );
	exit;
}
add_action( 'admin_post_ntc_en_utworz', 'ntc_en_utworz' );

/* ------------------------------------------------------------------- menu */

/** Czy użytkownik wybrał pełne menu kokpitu. */
function ntc_kokpit_pelne_menu( $user_id = 0 ) {
	return '1' === (string) get_user_meta( $user_id ? $user_id : get_current_user_id(), 'ntc_pelne_menu', true );
}

/** Czy uproszczone menu dotyczy bieżącego użytkownika. */
function ntc_kokpit_uproszczone() {
	return current_user_can( 'edit_pages' ) && ! ntc_kokpit_pelne_menu();
}

/** Adres przełącznika menu - względny, bo tak buduje go menu kokpitu. */
function ntc_kokpit_przelacznik() {
	return 'admin-post.php?action=ntc_menu&_wpnonce=' . wp_create_nonce( 'ntc_menu' );
}

/**
 * Pozycja "Treści strony" i przełącznik menu.
 *
 * Priorytet 9: typ "Wersje angielskie" podpina się pod tę pozycję jako
 * podmenu, a WordPress dokłada podmenu typów treści z priorytetem 10.
 */
function ntc_kokpit_menu() {
	add_menu_page( 'Treści strony', 'Treści strony', 'edit_pages', NTC_KOKPIT, 'ntc_kokpit_ekran', 'dashicons-welcome-write-blog', 3 );
	add_submenu_page( NTC_KOKPIT, 'Treści strony', 'Strony i wpisy', 'edit_pages', NTC_KOKPIT, 'ntc_kokpit_ekran' );
	add_submenu_page( NTC_KOKPIT, 'Dodaj stronę', 'Dodaj stronę', 'edit_pages', 'post-new.php?post_type=page' );

	$pelne = ntc_kokpit_pelne_menu();

	add_menu_page(
		$pelne ? 'Uprość menu' : 'Pokaż pełne menu',
		$pelne ? 'Uprość menu' : 'Pokaż pełne menu',
		'edit_pages',
		ntc_kokpit_przelacznik(),
		'',
		$pelne ? 'dashicons-editor-contract' : 'dashicons-editor-expand',
		999
	);
}
add_action( 'admin_menu', 'ntc_kokpit_menu', 9 );

/**
 * Chowa pozycje menu, których redakcja nie używa.
 *
 * Lista tego, co zostaje, a nie tego, co znika: wtyczki dokładają własne
 * pozycje i lista do schowania nigdy nie byłaby kompletna.
 */
function ntc_kokpit_uprosc_menu() {
	global $menu, $submenu;

	if ( ! ntc_kokpit_uproszczone() ) {
		return;
	}

	$zostaw = array(
		NTC_KOKPIT,
		'edit.php',
		'upload.php',
		'edit.php?post_type=' . NTC_PRODUCT_CPT,
		'edit.php?post_type=ntc_zapytanie',
		'themes.php',
		'profile.php',
	);

	foreach ( (array) $menu as $pozycja => $element ) {
		$slug = isset( $element[2] ) ? (string) $element[2] : '';

		if ( ! in_array( $slug, $zostaw, true ) && 0 !== strpos( $slug, 'admin-post.php?action=ntc_menu' ) ) {
			unset( $menu[ $pozycja ] );
		}
	}

	// W "Wyglądzie" zostają dane firmy (Dostosuj) i menu serwisu. Wygląd
	// prowadzi wtedy prosto do pierwszej z nich, a nie do listy motywów.
	if ( isset( $submenu['themes.php'] ) ) {
		foreach ( $submenu['themes.php'] as $i => $element ) {
			if ( 0 !== strpos( (string) $element[2], 'customize.php' ) && 'nav-menus.php' !== $element[2] ) {
				unset( $submenu['themes.php'][ $i ] );
			}
		}
	}
}
add_action( 'admin_menu', 'ntc_kokpit_uprosc_menu', 999 );

/**
 * Przełącza menu między pełnym a uproszczonym i wraca tam, skąd kliknięto.
 */
function ntc_kokpit_przelacz_menu() {
	check_admin_referer( 'ntc_menu' );

	$uid = get_current_user_id();

	if ( ntc_kokpit_pelne_menu( $uid ) ) {
		delete_user_meta( $uid, 'ntc_pelne_menu' );
	} else {
		update_user_meta( $uid, 'ntc_pelne_menu', '1' );
	}

	$wroc = wp_get_referer();

	wp_safe_redirect( $wroc ? $wroc : admin_url( 'admin.php?page=' . NTC_KOKPIT ) );
	exit;
}
add_action( 'admin_post_ntc_menu', 'ntc_kokpit_przelacz_menu' );

/**
 * Podświetlenie "Treści strony" przy edycji stron, gdy "Strony" są schowane.
 *
 * @param string $parent_file Pozycja menu do podświetlenia.
 * @return string
 */
function ntc_kokpit_podswietlenie( $parent_file ) {
	global $pagenow, $typenow;

	if ( 'page' === $typenow && in_array( $pagenow, array( 'post.php', 'post-new.php', 'edit.php' ), true ) && ntc_kokpit_uproszczone() ) {
		return NTC_KOKPIT;
	}

	return $parent_file;
}
add_filter( 'parent_file', 'ntc_kokpit_podswietlenie' );

/**
 * Po zalogowaniu prosto do "Treści strony", jeśli nikt nie prosił o inną.
 *
 * @param string           $redirect_to Docelowy adres.
 * @param string           $requested   Adres podany w żądaniu.
 * @param WP_User|WP_Error $user        Użytkownik.
 * @return string
 */
function ntc_kokpit_po_zalogowaniu( $redirect_to, $requested, $user ) {
	if ( ! $user instanceof WP_User || ! $user->has_cap( 'edit_pages' ) || ntc_kokpit_pelne_menu( $user->ID ) ) {
		return $redirect_to;
	}

	// Pusty adres albo sam kokpit to domyślne zachowanie WordPressa. Konkretna
	// strona (np. link do edycji z maila) wygrywa.
	if ( '' === (string) $requested || untrailingslashit( $requested ) === untrailingslashit( admin_url() ) ) {
		return admin_url( 'admin.php?page=' . NTC_KOKPIT );
	}

	return $redirect_to;
}
add_filter( 'login_redirect', 'ntc_kokpit_po_zalogowaniu', 10, 3 );

/* ------------------------------------------------------------ ekran treści */

/**
 * Strony w kolejności serwisu: najpierw strona główna, potem działy w
 * kolejności z menu, na końcu reszta. Podstrony idą zawsze za swoim działem.
 *
 * @return array<int,array{0:WP_Post,1:int}> Strona i poziom zagłębienia.
 */
function ntc_kokpit_strony() {
	$strony = get_pages(
		array(
			'post_status' => 'publish,draft,pending,private,future',
			'sort_column' => 'menu_order,post_title',
		)
	);

	if ( ! $strony ) {
		return array();
	}

	$wg_id   = array();
	$dzieci  = array();
	$korzen  = array();

	foreach ( $strony as $strona ) {
		$wg_id[ $strona->ID ]                   = $strona;
		$dzieci[ (int) $strona->post_parent ][] = $strona->ID;
	}

	// Dział strony to jej najwyższy przodek - po nim ustawiamy kolejność.
	$dzial = function ( $id ) use ( $wg_id ) {
		while ( isset( $wg_id[ $id ] ) && $wg_id[ $id ]->post_parent && isset( $wg_id[ $wg_id[ $id ]->post_parent ] ) ) {
			$id = (int) $wg_id[ $id ]->post_parent;
		}

		return $id;
	};

	$dodaj = function ( $id ) use ( &$korzen, $wg_id ) {
		if ( isset( $wg_id[ $id ] ) && ! in_array( $id, $korzen, true ) ) {
			$korzen[] = $id;
		}
	};

	$dodaj( $dzial( (int) get_option( 'page_on_front' ) ) );

	$lokacje = get_nav_menu_locations();

	foreach ( array( 'primary', 'footer_nav', 'footer_offer', 'footer_legal' ) as $lokacja ) {
		$pozycje = empty( $lokacje[ $lokacja ] ) ? array() : wp_get_nav_menu_items( $lokacje[ $lokacja ] );

		foreach ( (array) $pozycje as $pozycja ) {
			if ( 'post_type' === $pozycja->type && 'page' === $pozycja->object ) {
				$dodaj( $dzial( (int) $pozycja->object_id ) );
			}
		}
	}

	foreach ( $strony as $strona ) {
		$dodaj( $dzial( $strona->ID ) );
	}

	$wynik = array();

	$zejdz = function ( $id, $poziom ) use ( &$zejdz, &$wynik, $wg_id, $dzieci ) {
		$wynik[] = array( $wg_id[ $id ], $poziom );

		foreach ( isset( $dzieci[ $id ] ) ? $dzieci[ $id ] : array() as $dziecko ) {
			$zejdz( $dziecko, $poziom + 1 );
		}
	};

	foreach ( $korzen as $id ) {
		$zejdz( $id, 0 );
	}

	return $wynik;
}

/**
 * Data zmiany w krótkiej postaci.
 *
 * @param WP_Post $post Wpis.
 * @return string
 */
function ntc_kokpit_data( $post ) {
	return mysql2date( 'j.m.Y, H:i', $post->post_modified );
}

/**
 * Plakietki przy tytule: rola strony i status inny niż opublikowany.
 *
 * @param WP_Post $post Strona albo wpis.
 * @return string HTML.
 */
function ntc_kokpit_plakietki( $post ) {
	$plakietki = array();

	if ( (int) get_option( 'page_on_front' ) === $post->ID ) {
		$plakietki[] = 'strona główna';
	}

	if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
		$plakietki[] = 'lista wpisów bloga';
	}

	if ( (int) get_option( 'wp_page_for_privacy_policy' ) === $post->ID ) {
		$plakietki[] = 'polityka prywatności';
	}

	$statusy = array(
		'draft'   => 'szkic',
		'pending' => 'czeka na akceptację',
		'private' => 'prywatna',
		'future'  => 'zaplanowana',
	);

	if ( isset( $statusy[ $post->post_status ] ) ) {
		$plakietki[] = $statusy[ $post->post_status ];
	}

	$html = '';

	foreach ( $plakietki as $plakietka ) {
		$html .= ' <span class="ntc-kokpit-plakietka">' . esc_html( $plakietka ) . '</span>';
	}

	return $html;
}

/**
 * Jeden wiersz tabeli: tytuł, obie wersje językowe, data zmiany.
 *
 * @param WP_Post $post   Polska strona albo wpis.
 * @param int     $poziom Zagłębienie podstrony.
 */
function ntc_kokpit_wiersz( $post, $poziom = 0 ) {
	$twin     = ntc_kokpit_en( $post->ID );
	$link     = get_permalink( $post );
	$widoczna = in_array( $post->post_status, array( 'publish', 'private' ), true );
	$tytul    = '' !== $post->post_title ? $post->post_title : '(bez tytułu)';

	// Strona bloga to sama lista wpisów - jej nagłówek siedzi w motywie, a
	// treści z edytora WordPress na niej nie pokazuje. Edycja prowadziłaby
	// donikąd, więc zostaje tylko podgląd.
	if ( (int) get_option( 'page_for_posts' ) === $post->ID ) {
		?>
		<tr>
			<td class="ntc-kokpit-tytul">
				<strong><?php echo esc_html( $tytul ); ?></strong>
				<?php echo ntc_kokpit_plakietki( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- składane z esc_html. ?>
			</td>
			<td class="ntc-kokpit-przyciski">
				<a class="button button-small" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Zobacz</a>
			</td>
			<td>
				<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'lang', 'en', $link ) ); ?>" target="_blank" rel="noopener">Zobacz</a>
				<p class="ntc-kokpit-opis">Lista układa się sama z wpisów poniżej, w obu językach.</p>
			</td>
			<td class="ntc-kokpit-daty"></td>
		</tr>
		<?php
		return;
	}
	?>
	<tr>
		<td class="ntc-kokpit-tytul" style="padding-left: <?php echo (int) ( 10 + 22 * $poziom ); ?>px">
			<?php echo $poziom ? '<span class="ntc-kokpit-wciecie" aria-hidden="true">&#8627;</span>' : ''; ?>
			<a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><strong><?php echo esc_html( $tytul ); ?></strong></a>
			<?php echo ntc_kokpit_plakietki( $post ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- składane z esc_html. ?>
		</td>
		<td class="ntc-kokpit-przyciski">
			<a class="button button-primary button-small" href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>">Edytuj</a>
			<?php if ( $widoczna ) : ?>
				<a class="button button-small" href="<?php echo esc_url( $link ); ?>" target="_blank" rel="noopener">Zobacz</a>
			<?php endif; ?>
		</td>
		<td>
			<?php if ( $twin ) : ?>
				<a class="button button-primary button-small" href="<?php echo esc_url( get_edit_post_link( $twin->ID ) ); ?>">Edytuj</a>
				<?php if ( $widoczna ) : ?>
					<a class="button button-small" href="<?php echo esc_url( add_query_arg( 'lang', 'en', $link ) ); ?>" target="_blank" rel="noopener">Zobacz</a>
				<?php endif; ?>
				<?php
				// Polska wersja poprawiana po angielskiej to najczęstsze źródło
				// rozjazdów między językami - podpowiadamy, gdzie zajrzeć.
				if ( strtotime( $post->post_modified_gmt ) > strtotime( $twin->post_modified_gmt ) + 60 ) :
					?>
					<p class="ntc-kokpit-uwaga">Polska zmieniona później niż angielska - sprawdź, czy angielska nie wymaga tej samej poprawki.</p>
				<?php endif; ?>
			<?php else : ?>
				<a class="button button-small" href="<?php echo esc_url( ntc_en_utworz_url( $post->ID ) ); ?>">Utwórz wersję angielską</a>
				<p class="ntc-kokpit-opis">Na razie po angielsku widać tekst polski.</p>
			<?php endif; ?>
		</td>
		<td class="ntc-kokpit-daty">
			PL: <?php echo esc_html( ntc_kokpit_data( $post ) ); ?>
			<?php if ( $twin ) : ?>
				<br />EN: <?php echo esc_html( ntc_kokpit_data( $twin ) ); ?>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

/** Nagłówek tabeli - ten sam dla stron i wpisów. */
function ntc_kokpit_naglowek( $pierwsza ) {
	?>
	<thead>
		<tr>
			<th scope="col" class="ntc-kokpit-kol-tytul"><?php echo esc_html( $pierwsza ); ?></th>
			<th scope="col">Wersja polska</th>
			<th scope="col">Wersja angielska</th>
			<th scope="col" class="ntc-kokpit-kol-daty">Ostatnia zmiana</th>
		</tr>
	</thead>
	<?php
}

/**
 * Ekran "Treści strony".
 */
function ntc_kokpit_ekran() {
	$strony = ntc_kokpit_strony();
	$wpisy  = get_posts(
		array(
			'post_type'   => 'post',
			'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
			'numberposts' => 100,
			'orderby'     => 'date',
			'order'       => 'DESC',
		)
	);
	$sekcja = function ( $id ) {
		return admin_url( 'customize.php?autofocus[section]=' . $id );
	};
	?>
	<div class="wrap ntc-kokpit">
		<style>
			.ntc-kokpit-wstep { max-width: 760px; font-size: 14px; }
			.ntc-kokpit h2 { margin-top: 28px; }
			.ntc-kokpit-tabela td { vertical-align: middle; }
			.ntc-kokpit-tabela .button { margin: 2px 4px 2px 0; }
			.ntc-kokpit-kol-tytul { width: 34%; }
			.ntc-kokpit-przyciski { white-space: nowrap; }
			.ntc-kokpit-kol-daty { width: 150px; }
			.ntc-kokpit-daty { color: #646970; font-size: 12px; }
			.ntc-kokpit-wciecie { color: #8c8f94; margin-right: 4px; }
			.ntc-kokpit-plakietka { display: inline-block; margin-left: 6px; padding: 1px 8px; border-radius: 10px; background: #f0f0f1; color: #50575e; font-size: 11px; }
			.ntc-kokpit-uwaga { margin: 4px 0 0; color: #996800; font-size: 12px; }
			.ntc-kokpit-opis { margin: 4px 0 0; color: #646970; font-size: 12px; }
			.ntc-kokpit-skroty { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; max-width: 1100px; }
			.ntc-kokpit-skroty li { margin: 0; padding: 12px 14px; background: #fff; border: 1px solid #dcdcde; border-radius: 4px; }
			.ntc-kokpit-skroty a { font-weight: 600; text-decoration: none; }
			.ntc-kokpit-skroty span { display: block; margin-top: 4px; color: #646970; }
			.ntc-kokpit-stopka { margin-top: 28px; color: #646970; }
		</style>

		<h1 class="wp-heading-inline">Treści strony</h1>
		<a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=page' ) ); ?>" class="page-title-action">Dodaj stronę</a>
		<a href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>" class="page-title-action">Dodaj wpis na blog</a>
		<hr class="wp-header-end" />

		<p class="ntc-kokpit-wstep">
			Każda strona i każdy wpis mają dwie wersje: polską i angielską. Edytuje się je osobno, tym samym
			edytorem - poprawka w jednej nie zmienia drugiej, więc po zmianie treści zajrzyj też do drugiej wersji.
		</p>

		<h2>Strony</h2>
		<table class="widefat striped ntc-kokpit-tabela">
			<?php ntc_kokpit_naglowek( 'Strona' ); ?>
			<tbody>
				<?php
				foreach ( $strony as $wiersz ) {
					ntc_kokpit_wiersz( $wiersz[0], $wiersz[1] );
				}
				?>
			</tbody>
		</table>

		<h2>Wpisy na blogu</h2>
		<?php if ( $wpisy ) : ?>
			<table class="widefat striped ntc-kokpit-tabela">
				<?php ntc_kokpit_naglowek( 'Wpis' ); ?>
				<tbody>
					<?php
					foreach ( $wpisy as $wpis ) {
						ntc_kokpit_wiersz( $wpis );
					}
					?>
				</tbody>
			</table>
		<?php else : ?>
			<p>Nie ma jeszcze wpisów.</p>
		<?php endif; ?>

		<h2>Inne miejsca</h2>
		<ul class="ntc-kokpit-skroty">
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=ntc_zapytanie' ) ); ?>">Zapytania z formularzy</a>
				<span>Każde zgłoszenie ze strony, także gdy mail nie doszedł.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . NTC_PRODUCT_CPT ) ); ?>">Produkty</a>
				<span>Substancje w tabelach oferty, z polami po polsku i po angielsku.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( $sekcja( 'ntc_company' ) ); ?>">Dane firmy</a>
				<span>Adres, telefony, e-mail, NIP - zmiana działa na całej stronie naraz.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( $sekcja( 'ntc_footer' ) ); ?>">Stopka</a>
				<span>Opis firmy i nota na dole każdej strony.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( $sekcja( 'ntc_forms' ) ); ?>">Formularze</a>
				<span>Lista tematów zapytania w formularzach.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>">Menu serwisu</a>
				<span>Pozycje w pasku i w stopce, razem z etykietami angielskimi.</span>
			</li>
			<li>
				<a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>">Biblioteka mediów</a>
				<span>Zdjęcia i pliki PDF (certyfikaty, katalog, OWS).</span>
			</li>
		</ul>

		<p class="ntc-kokpit-stopka">
			<?php if ( ntc_kokpit_pelne_menu() ) : ?>
				Menu kokpitu po lewej jest pełne.
				<a href="<?php echo esc_url( admin_url( ntc_kokpit_przelacznik() ) ); ?>">Uprość menu</a>
			<?php else : ?>
				Menu kokpitu po lewej pokazuje tylko najczęściej używane pozycje.
				<a href="<?php echo esc_url( admin_url( ntc_kokpit_przelacznik() ) ); ?>">Pokaż pełne menu</a>
			<?php endif; ?>
		</p>
	</div>
	<?php
}

/* ---------------------------------------------------------------- edytor */

/**
 * Pasek nad treścią w edytorze: którą wersję językową się poprawia i jak
 * przejść do drugiej.
 */
function ntc_kokpit_jezyk_w_edytorze() {
	$post = get_post();

	if ( ! $post ) {
		return;
	}

	$dane = null;

	if ( in_array( $post->post_type, array( 'page', 'post' ), true ) ) {
		$co   = 'page' === $post->post_type ? 'strony' : 'wpisu';
		$twin = ntc_kokpit_en( $post->ID );

		if ( 'auto-draft' === $post->post_status ) {
			$dane = array(
				'tekst' => 'To wersja polska. Angielską utworzysz po pierwszym zapisaniu - z tego paska albo z ekranu Treści strony.',
				'akcje' => array(),
			);
		} elseif ( $twin ) {
			$dane = array(
				'tekst' => 'Edytujesz polską wersję ' . $co . '. Angielska to osobna kopia - zmiany tutaj jej nie zmieniają.',
				'akcje' => array(
					array(
						'label' => 'Przejdź do wersji angielskiej',
						'url'   => get_edit_post_link( $twin->ID, 'raw' ),
					),
				),
			);
		} else {
			$dane = array(
				'tekst' => 'Ta treść nie ma jeszcze wersji angielskiej - po angielsku pokazuje się tekst polski. Zapisz zmiany przed utworzeniem kopii.',
				'akcje' => array(
					array(
						'label' => 'Utwórz wersję angielską',
						'url'   => ntc_en_utworz_url( $post->ID ),
					),
				),
			);
		}
	} elseif ( NTC_EN_CPT === $post->post_type ) {
		$pl   = ntc_kokpit_pl( $post->ID );
		$dane = array(
			'tekst' => 'Edytujesz angielską wersję' . ( $pl ? ' „' . $pl->post_title . '”' : '' ) . '. Zmiany tutaj nie ruszają wersji polskiej.',
			'typ'   => 'warning',
			'akcje' => array(),
		);

		if ( $pl ) {
			$dane['akcje'][] = array(
				'label' => 'Wróć do wersji polskiej',
				'url'   => get_edit_post_link( $pl->ID, 'raw' ),
			);

			if ( in_array( $pl->post_status, array( 'publish', 'private' ), true ) ) {
				$dane['akcje'][] = array(
					'label' => 'Zobacz po angielsku',
					'url'   => add_query_arg( 'lang', 'en', get_permalink( $pl ) ),
				);
			}
		}
	}

	if ( ! $dane ) {
		return;
	}

	$sciezka = '/assets/js/jezyk.js';

	wp_enqueue_script(
		'ntc-jezyk',
		get_template_directory_uri() . $sciezka,
		array( 'wp-data', 'wp-notices', 'wp-dom-ready' ),
		(string) filemtime( get_template_directory() . $sciezka ),
		true
	);

	wp_add_inline_script( 'ntc-jezyk', 'window.ntcJezyk = ' . wp_json_encode( $dane ) . ';', 'before' );
}
add_action( 'enqueue_block_editor_assets', 'ntc_kokpit_jezyk_w_edytorze' );

/* ------------------------------------------------------- listy i pasek */

/**
 * Akcja "wersja angielska" pod tytułem na listach stron i wpisów.
 *
 * @param array   $akcje Akcje wiersza.
 * @param WP_Post $post  Wpis.
 * @return array
 */
function ntc_kokpit_akcje_wiersza( $akcje, $post ) {
	if ( ! current_user_can( 'edit_post', $post->ID ) || 'trash' === $post->post_status ) {
		return $akcje;
	}

	$twin = ntc_kokpit_en( $post->ID );

	$akcje['ntc_en'] = $twin
		? '<a href="' . esc_url( get_edit_post_link( $twin->ID ) ) . '">Edytuj wersję angielską</a>'
		: '<a href="' . esc_url( ntc_en_utworz_url( $post->ID ) ) . '">Utwórz wersję angielską</a>';

	return $akcje;
}
add_filter( 'page_row_actions', 'ntc_kokpit_akcje_wiersza', 10, 2 );
add_filter( 'post_row_actions', 'ntc_kokpit_akcje_wiersza', 10, 2 );

/**
 * "Edytuj" na górnym pasku strony otwiera wersję w języku, który się ogląda.
 *
 * Wcześniej z angielskiej strony trafiało się do edycji polskiej i poprawka
 * lądowała nie w tej wersji, o którą chodziło.
 *
 * @param WP_Admin_Bar $pasek Pasek administracyjny.
 */
function ntc_kokpit_pasek( $pasek ) {
	if ( is_admin() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'page', 'post' ), true ) || ! current_user_can( 'edit_post', $post->ID ) ) {
		return;
	}

	$twin = ntc_kokpit_en( $post->ID );

	if ( ntc_is_en() ) {
		$pasek->add_node(
			array(
				'id'    => 'edit',
				'title' => $twin ? 'Edytuj wersję angielską' : 'Utwórz wersję angielską',
				'href'  => $twin ? get_edit_post_link( $twin->ID ) : ntc_en_utworz_url( $post->ID ),
			)
		);
		$pasek->add_node(
			array(
				'id'     => 'ntc-edytuj-pl',
				'parent' => 'edit',
				'title'  => 'Edytuj wersję polską',
				'href'   => get_edit_post_link( $post->ID ),
			)
		);

		return;
	}

	$pasek->add_node(
		array(
			'id'    => 'edit',
			'title' => 'Edytuj wersję polską',
			'href'  => get_edit_post_link( $post->ID ),
		)
	);

	$pasek->add_node(
		array(
			'id'     => 'ntc-edytuj-en',
			'parent' => 'edit',
			'title'  => $twin ? 'Edytuj wersję angielską' : 'Utwórz wersję angielską',
			'href'   => $twin ? get_edit_post_link( $twin->ID ) : ntc_en_utworz_url( $post->ID ),
		)
	);
}
add_action( 'admin_bar_menu', 'ntc_kokpit_pasek', 81 );
