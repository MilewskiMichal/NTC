<?php
/**
 * Plugin Name: NTC Deploy
 * Description: Zdalna podmiana motywu NTC Andar przez REST API. Przyjmuje paczkę ZIP, robi kopię poprzedniej wersji i podmienia katalog. Narzędzie wdrożeniowe na czas budowy strony - przed publikacją do wyłączenia.
 * Version: 1.4.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: NTC Andar
 * License: GPL-2.0-or-later
 *
 * @package NTC_Deploy
 */

defined( 'ABSPATH' ) || exit;

define( 'NTC_DEPLOY_VERSION', '1.4.0' );

/**
 * Katalogi, które wolno podmieniać.
 *
 * Nazwa katalogu w paczce musi się zgadzać z kluczem. Nic spoza tej listy nie
 * zostanie zapisane, nawet jeśli ZIP przyjdzie z poprawnym kluczem - inaczej
 * endpoint byłby zapisem w dowolne miejsce na serwerze.
 */
function ntc_deploy_targets() {
	return array(
		'ntc-andar' => array(
			'label' => 'motyw NTC Andar',
			'path'  => get_theme_root() . '/ntc-andar',
			'must'  => 'style.css',
		),
		'ntc-deploy' => array(
			'label' => 'wtyczka NTC Deploy',
			'path'  => WP_PLUGIN_DIR . '/ntc-deploy',
			'must'  => 'ntc-deploy.php',
		),
	);
}

/* -------------------------------------------------------------- uprawnienia */

/**
 * Czy wdrożenia są w ogóle włączone.
 *
 * Klucz musi stać w wp-config.php. Bez niego trasy nie rejestrują się wcale,
 * więc wyłączenie tego narzędzia to skasowanie jednej linijki, a nie
 * pamiętanie o dezaktywacji wtyczki.
 */
function ntc_deploy_enabled() {
	return defined( 'NTC_DEPLOY_KEY' ) && is_string( NTC_DEPLOY_KEY ) && strlen( NTC_DEPLOY_KEY ) >= 20;
}

/**
 * Dwa warunki naraz: uprawnienia użytkownika i klucz wdrożeniowy.
 *
 * Samo hasło aplikacji nie wystarcza. Gdyby wyciekło, atakujący dostaje to, co
 * daje REST administratorowi, ale nie wykonanie własnego PHP - do tego trzeba
 * jeszcze klucza, którego przez API nie da się odczytać.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return true|WP_Error
 */
function ntc_deploy_permission( $request ) {
	if ( ! current_user_can( 'update_themes' ) ) {
		return new WP_Error(
			'ntc_deploy_forbidden',
			'Brak uprawnień do aktualizacji motywów.',
			array( 'status' => rest_authorization_required_code() )
		);
	}

	$given = (string) $request->get_header( 'x-ntc-deploy-key' );

	// hash_equals zamiast === , żeby czas porównania nie zdradzał, ile znaków
	// klucza się zgadza.
	if ( '' === $given || ! hash_equals( NTC_DEPLOY_KEY, $given ) ) {
		return new WP_Error(
			'ntc_deploy_bad_key',
			'Zły klucz wdrożeniowy albo brak nagłówka X-NTC-Deploy-Key.',
			array( 'status' => 403 )
		);
	}

	return true;
}

/* ------------------------------------------------------------------- trasy */

/**
 * Rejestracja tras REST.
 */
function ntc_deploy_routes() {
	if ( ! ntc_deploy_enabled() ) {
		return;
	}

	$args = array( 'permission_callback' => 'ntc_deploy_permission' );

	register_rest_route(
		'ntc-deploy/v1',
		'/status',
		array_merge( $args, array( 'methods' => 'GET', 'callback' => 'ntc_deploy_status' ) )
	);

	register_rest_route(
		'ntc-deploy/v1',
		'/push',
		array_merge(
			$args,
			array(
				'methods'  => 'POST',
				'callback' => 'ntc_deploy_push',
				'args'     => array(
					'target'  => array(
						'type'     => 'string',
						'required' => true,
					),
					'dry_run' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			)
		)
	);

	register_rest_route(
		'ntc-deploy/v1',
		'/run',
		array_merge(
			$args,
			array(
				'methods'  => 'POST',
				'callback' => 'ntc_deploy_run',
				'args'     => array(
					'task' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		)
	);

	register_rest_route(
		'ntc-deploy/v1',
		'/fetch-media',
		array_merge(
			$args,
			array(
				'methods'  => 'POST',
				'callback' => 'ntc_deploy_fetch_media',
				'args'     => array(
					'url'      => array(
						'type'     => 'string',
						'required' => true,
					),
					'filename' => array( 'type' => 'string' ),
					'alt'      => array( 'type' => 'string' ),
					'title'    => array( 'type' => 'string' ),
				),
			)
		)
	);

	register_rest_route(
		'ntc-deploy/v1',
		'/batch',
		array_merge(
			$args,
			array(
				'methods'  => 'POST',
				'callback' => 'ntc_deploy_batch',
				'args'     => array(
					'ops' => array(
						'type'     => 'array',
						'required' => true,
					),
				),
			)
		)
	);

	register_rest_route(
		'ntc-deploy/v1',
		'/rollback',
		array_merge(
			$args,
			array(
				'methods'  => 'POST',
				'callback' => 'ntc_deploy_rollback',
				'args'     => array(
					'target' => array(
						'type'     => 'string',
						'required' => true,
					),
				),
			)
		)
	);
}
add_action( 'rest_api_init', 'ntc_deploy_routes' );

/* ------------------------------------------------------------------ status */

/**
 * Co faktycznie leży na serwerze.
 *
 * Manifest z sumami kontrolnymi jest tu po to, żeby dało się porównać serwer z
 * repozytorium plik po pliku. Sam numer wersji z nagłówka motywu do niczego się
 * nie nadaje: wystarczy, że ktoś wgra starszą paczkę z tym samym numerem i
 * wszystko wygląda w porządku.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response
 */
function ntc_deploy_status( $request ) {
	$manifest = (bool) $request->get_param( 'manifest' );
	$out      = array(
		'plugin_version' => NTC_DEPLOY_VERSION,
		'wp'             => get_bloginfo( 'version' ),
		'php'            => PHP_VERSION,
		'active_theme'   => get_stylesheet(),
		'targets'        => array(),
	);

	foreach ( ntc_deploy_targets() as $slug => $target ) {
		$exists = is_dir( $target['path'] );
		$entry  = array(
			'label'  => $target['label'],
			'path'   => $target['path'],
			'exists' => $exists,
		);

		if ( $exists ) {
			$files = ntc_deploy_scan( $target['path'] );

			$entry['files']    = count( $files );
			$entry['bytes']    = array_sum( array_column( $files, 'bytes' ) );
			$entry['modified'] = gmdate( 'c', max( array_merge( array( 0 ), array_column( $files, 'mtime' ) ) ) );
			$entry['version']  = ntc_deploy_read_version( $target['path'] . '/' . $target['must'] );

			if ( $manifest ) {
				$entry['manifest'] = array_combine(
					array_column( $files, 'rel' ),
					array_column( $files, 'sha256' )
				);
			}
		}

		$entry['backups'] = array_map( 'basename', ntc_deploy_backups( $slug ) );

		$out['targets'][ $slug ] = $entry;
	}

	return rest_ensure_response( $out );
}

/**
 * Numer wersji z nagłówka motywu albo wtyczki.
 *
 * @param string $file Ścieżka do style.css albo głównego pliku wtyczki.
 * @return string
 */
function ntc_deploy_read_version( $file ) {
	if ( ! is_readable( $file ) ) {
		return '';
	}

	// Nagłówek siedzi na początku pliku, nie ma po co czytać całości.
	$head = (string) file_get_contents( $file, false, null, 0, 8192 ); // phpcs:ignore WordPress.WP.AlternativeFunctions

	return preg_match( '/^[ \t\/*#@]*Version:\s*(.+)$/mi', $head, $m ) ? trim( $m[1] ) : '';
}

/**
 * Lista plików katalogu razem z sumami kontrolnymi.
 *
 * @param string $dir Katalog.
 * @return array
 */
function ntc_deploy_scan( $dir ) {
	$out = array();

	if ( ! is_dir( $dir ) ) {
		return $out;
	}

	$walker = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS )
	);

	foreach ( $walker as $file ) {
		if ( ! $file->isFile() ) {
			continue;
		}

		$out[] = array(
			'rel'    => ltrim( str_replace( $dir, '', $file->getPathname() ), '/\\' ),
			'bytes'  => $file->getSize(),
			'mtime'  => $file->getMTime(),
			'sha256' => hash_file( 'sha256', $file->getPathname() ),
		);
	}

	return $out;
}

/* ---------------------------------------------------------------- wdrożenie */

/**
 * Podmiana katalogu paczką ZIP.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response|WP_Error
 */
function ntc_deploy_push( $request ) {
	$started = microtime( true );
	$slug    = (string) $request->get_param( 'target' );
	$targets = ntc_deploy_targets();

	if ( ! isset( $targets[ $slug ] ) ) {
		return new WP_Error(
			'ntc_deploy_unknown_target',
			sprintf( 'Nieznany cel "%s". Dozwolone: %s.', $slug, implode( ', ', array_keys( $targets ) ) ),
			array( 'status' => 400 )
		);
	}

	$target = $targets[ $slug ];
	$files  = $request->get_file_params();

	if ( empty( $files['zip']['tmp_name'] ) || ! is_uploaded_file( $files['zip']['tmp_name'] ) ) {
		return new WP_Error( 'ntc_deploy_no_file', 'Brak pliku w polu "zip".', array( 'status' => 400 ) );
	}

	if ( $files['zip']['size'] > 20 * MB_IN_BYTES ) {
		return new WP_Error( 'ntc_deploy_too_big', 'Paczka większa niż 20 MB.', array( 'status' => 413 ) );
	}

	$fs = ntc_deploy_filesystem();

	if ( is_wp_error( $fs ) ) {
		return $fs;
	}

	// Rozpakowanie do katalogu roboczego. Dopóki paczka nie przejdzie walidacji,
	// katalog docelowy pozostaje nietknięty.
	$work = trailingslashit( WP_CONTENT_DIR ) . 'upgrade/ntc-deploy-' . wp_generate_password( 8, false );

	if ( ! wp_mkdir_p( $work ) ) {
		return new WP_Error( 'ntc_deploy_no_workdir', 'Nie udało się utworzyć katalogu roboczego.', array( 'status' => 500 ) );
	}

	$unzipped = unzip_file( $files['zip']['tmp_name'], $work );

	if ( is_wp_error( $unzipped ) ) {
		$fs->delete( $work, true );

		return new WP_Error(
			'ntc_deploy_unzip_failed',
			'Nie udało się rozpakować paczki: ' . $unzipped->get_error_message(),
			array( 'status' => 400 )
		);
	}

	$source = ntc_deploy_validate( $work, $slug, $target );

	if ( is_wp_error( $source ) ) {
		$fs->delete( $work, true );

		return $source;
	}

	$incoming = ntc_deploy_scan( $source );
	$before   = ntc_deploy_scan( $target['path'] );
	$report   = array(
		'target'         => $slug,
		'label'          => $target['label'],
		'version_before' => ntc_deploy_read_version( $target['path'] . '/' . $target['must'] ),
		'version_after'  => ntc_deploy_read_version( $source . '/' . $target['must'] ),
		'files_before'   => count( $before ),
		'files_after'    => count( $incoming ),
		'changed'        => ntc_deploy_diff( $before, $incoming ),
	);

	if ( $request->get_param( 'dry_run' ) ) {
		$fs->delete( $work, true );

		$report['dry_run']  = true;
		$report['duration'] = round( microtime( true ) - $started, 2 );

		return rest_ensure_response( $report );
	}

	$backup = ntc_deploy_backup( $slug, $target['path'], $fs );

	if ( is_wp_error( $backup ) ) {
		$fs->delete( $work, true );

		return $backup;
	}

	$swapped = ntc_deploy_swap( $source, $target['path'], $fs );

	$fs->delete( $work, true );

	if ( is_wp_error( $swapped ) ) {
		return $swapped;
	}

	ntc_deploy_flush();

	$report['backup']   = $backup ? basename( $backup ) : null;
	$report['duration'] = round( microtime( true ) - $started, 2 );

	return rest_ensure_response( $report );
}

/**
 * Sprawdzenie, czy w paczce jest to, co deklaruje.
 *
 * @param string $work   Katalog roboczy po rozpakowaniu.
 * @param string $slug   Nazwa celu.
 * @param array  $target Definicja celu.
 * @return string|WP_Error Ścieżka do katalogu źródłowego.
 */
function ntc_deploy_validate( $work, $slug, $target ) {
	$entries = array_values( array_diff( scandir( $work ), array( '.', '..', '__MACOSX' ) ) );

	if ( 1 !== count( $entries ) || ! is_dir( $work . '/' . $entries[0] ) ) {
		return new WP_Error(
			'ntc_deploy_bad_layout',
			'Paczka musi zawierać dokładnie jeden katalog najwyższego poziomu.',
			array( 'status' => 400 )
		);
	}

	if ( $entries[0] !== $slug ) {
		return new WP_Error(
			'ntc_deploy_slug_mismatch',
			sprintf( 'Katalog w paczce to "%s", a cel to "%s".', $entries[0], $slug ),
			array( 'status' => 400 )
		);
	}

	$source = $work . '/' . $entries[0];

	if ( ! is_file( $source . '/' . $target['must'] ) ) {
		return new WP_Error(
			'ntc_deploy_missing_file',
			sprintf( 'W paczce brakuje pliku %s.', $target['must'] ),
			array( 'status' => 400 )
		);
	}

	if ( ! ntc_deploy_read_version( $source . '/' . $target['must'] ) ) {
		return new WP_Error(
			'ntc_deploy_no_version',
			sprintf( 'Plik %s nie ma nagłówka Version.', $target['must'] ),
			array( 'status' => 400 )
		);
	}

	return $source;
}

/**
 * Co się zmieniło między tym, co leży, a tym, co przyszło.
 *
 * @param array $before Skan katalogu docelowego.
 * @param array $after  Skan paczki.
 * @return array
 */
function ntc_deploy_diff( $before, $after ) {
	$old = array_combine( array_column( $before, 'rel' ), array_column( $before, 'sha256' ) );
	$new = array_combine( array_column( $after, 'rel' ), array_column( $after, 'sha256' ) );

	$modified = array();

	foreach ( $new as $rel => $hash ) {
		if ( isset( $old[ $rel ] ) && $old[ $rel ] !== $hash ) {
			$modified[] = $rel;
		}
	}

	return array(
		'added'    => array_values( array_diff( array_keys( $new ), array_keys( $old ) ) ),
		'removed'  => array_values( array_diff( array_keys( $old ), array_keys( $new ) ) ),
		'modified' => $modified,
	);
}

/**
 * Kopia katalogu przed podmianą.
 *
 * @param string       $slug Nazwa celu.
 * @param string       $path Katalog docelowy.
 * @param WP_Filesystem_Base $fs Uchwyt systemu plików.
 * @return string|WP_Error|null Ścieżka kopii, null gdy nie było czego kopiować.
 */
function ntc_deploy_backup( $slug, $path, $fs ) {
	if ( ! is_dir( $path ) ) {
		return null;
	}

	$backup = ntc_deploy_backup_root() . '/' . $slug . '-' . gmdate( 'Ymd-His' );

	if ( ! wp_mkdir_p( $backup ) ) {
		return new WP_Error( 'ntc_deploy_backup_failed', 'Nie udało się utworzyć katalogu kopii.', array( 'status' => 500 ) );
	}

	$copied = copy_dir( $path, $backup );

	if ( is_wp_error( $copied ) ) {
		$fs->delete( $backup, true );

		return new WP_Error(
			'ntc_deploy_backup_failed',
			'Nie udało się zrobić kopii: ' . $copied->get_error_message(),
			array( 'status' => 500 )
		);
	}

	// Trzymamy trzy ostatnie. Więcej nie jest do niczego potrzebne, a katalog
	// upgrade potrafi urosnąć niezauważenie.
	$all = ntc_deploy_backups( $slug );

	foreach ( array_slice( $all, 0, max( 0, count( $all ) - 3 ) ) as $old ) {
		$fs->delete( $old, true );
	}

	return $backup;
}

/**
 * Podmiana katalogu przez katalog tymczasowy.
 *
 * Kopiujemy najpierw obok, a dopiero potem przestawiamy nazwy. Gdyby kopiowanie
 * padło w połowie - na przykład skończyło się miejsce - stary katalog wciąż stoi
 * na swoim miejscu i strona działa.
 *
 * @param string $source Katalog źródłowy.
 * @param string $dest   Katalog docelowy.
 * @param WP_Filesystem_Base $fs Uchwyt systemu plików.
 * @return true|WP_Error
 */
function ntc_deploy_swap( $source, $dest, $fs ) {
	$staged = $dest . '-ntc-new';
	$retired = $dest . '-ntc-old';

	$fs->delete( $staged, true );
	$fs->delete( $retired, true );

	if ( ! wp_mkdir_p( $staged ) ) {
		return new WP_Error( 'ntc_deploy_stage_failed', 'Nie udało się przygotować katalogu tymczasowego.', array( 'status' => 500 ) );
	}

	$copied = copy_dir( $source, $staged );

	if ( is_wp_error( $copied ) ) {
		$fs->delete( $staged, true );

		return new WP_Error(
			'ntc_deploy_stage_failed',
			'Nie udało się skopiować paczki: ' . $copied->get_error_message(),
			array( 'status' => 500 )
		);
	}

	// Od tego miejsca do końca funkcji strona przez ułamek sekundy nie ma
	// katalogu pod docelową nazwą. Dwa move zamiast kopiowania po pliku sprawiają,
	// że okno jest tak krótkie, jak się da.
	if ( is_dir( $dest ) && ! $fs->move( $dest, $retired ) ) {
		$fs->delete( $staged, true );

		return new WP_Error( 'ntc_deploy_swap_failed', 'Nie udało się odsunąć starego katalogu.', array( 'status' => 500 ) );
	}

	if ( ! $fs->move( $staged, $dest ) ) {
		// Ratujemy stan sprzed próby.
		$fs->move( $retired, $dest );
		$fs->delete( $staged, true );

		return new WP_Error( 'ntc_deploy_swap_failed', 'Nie udało się wstawić nowego katalogu, przywrócono poprzedni.', array( 'status' => 500 ) );
	}

	$fs->delete( $retired, true );

	return true;
}

/**
 * Zadania konserwacyjne motywu, których nie da się odpalić przez zwykłe REST.
 *
 * Część rzeczy w motywie wisi na hakach kokpitu albo na przełączeniu motywu.
 * Wstawianie danych demonstracyjnych odpala się przy after_switch_theme, więc
 * podmiana plików bez przełączania go nie uruchamia, a produkty muszą dostać
 * meta _ntc_demo - bez niej przycisk "skasuj dane demonstracyjne" ich nie
 * znajdzie i wymyślone pozycje zostaną w katalogu na zawsze.
 *
 * Lista jest zamknięta. To nie jest trasa do wywoływania dowolnej funkcji.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response|WP_Error
 */
function ntc_deploy_run( $request ) {
	$tasks = array(
		'seed-demo'   => array(
			'fn'    => 'ntc_seed_demo_products',
			'label' => 'wstawienie produktów demonstracyjnych',
		),
		'remove-demo' => array(
			'fn'    => 'ntc_remove_demo_products',
			'label' => 'skasowanie produktów demonstracyjnych',
		),
		'count-demo'  => array(
			'fn'    => 'ntc_count_demo_products',
			'label' => 'zliczenie produktów demonstracyjnych',
		),
	);

	$task = (string) $request->get_param( 'task' );

	if ( ! isset( $tasks[ $task ] ) ) {
		return new WP_Error(
			'ntc_deploy_unknown_task',
			sprintf( 'Nieznane zadanie "%s". Dozwolone: %s.', $task, implode( ', ', array_keys( $tasks ) ) ),
			array( 'status' => 400 )
		);
	}

	if ( ! function_exists( $tasks[ $task ]['fn'] ) ) {
		return new WP_Error(
			'ntc_deploy_task_missing',
			sprintf( 'Motyw nie udostępnia funkcji %s. Czy na pewno jest włączony motyw NTC Andar?', $tasks[ $task ]['fn'] ),
			array( 'status' => 409 )
		);
	}

	$result = call_user_func( $tasks[ $task ]['fn'] );

	ntc_deploy_flush();

	return rest_ensure_response(
		array(
			'task'   => $task,
			'label'  => $tasks[ $task ]['label'],
			'result' => $result,
		)
	);
}

/* ------------------------------------------------------------------- wsad */

/**
 * Paczka operacji na treści wykonana w jednym żądaniu.
 *
 * Wgranie kompletu materiałów to kilkaset zapisów: katalog produktów,
 * podstrony, menu. Puszczone pojedynczo przez zwykłe REST API zamieniają się w
 * serię, którą ochrona antybotowa hostingu bierze za atak i zaczyna odsyłać
 * stronę weryfikacji zamiast JSON-a. Tutaj cała paczka jedzie jednym żądaniem
 * i wykonuje się po stronie serwera.
 *
 * Każda operacja to tablica z kluczem "op" i parametrami. Wynik wraca w tej
 * samej kolejności, więc widać, na czym paczka ewentualnie stanęła.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response|WP_Error
 */
function ntc_deploy_batch( $request ) {
	$ops = $request->get_param( 'ops' );

	if ( ! is_array( $ops ) ) {
		return new WP_Error( 'ntc_deploy_bad_batch', 'Pole ops musi być tablicą operacji.', array( 'status' => 400 ) );
	}

	$obslugiwane = array(
		'page'     => 'ntc_deploy_op_page',
		'product'  => 'ntc_deploy_op_product',
		'term'     => 'ntc_deploy_op_term',
		'trash'    => 'ntc_deploy_op_trash',
		'prune'    => 'ntc_deploy_op_prune',
		'menu'     => 'ntc_deploy_op_menu',
		'option'   => 'ntc_deploy_op_option',
		'user_meta' => 'ntc_deploy_op_user_meta',
	);

	$wyniki = array();

	foreach ( $ops as $i => $op ) {
		$nazwa = isset( $op['op'] ) ? (string) $op['op'] : '';

		if ( ! isset( $obslugiwane[ $nazwa ] ) ) {
			$wyniki[] = array(
				'i'     => $i,
				'op'    => $nazwa,
				'error' => 'nieznana operacja',
			);
			continue;
		}

		$wynik = call_user_func( $obslugiwane[ $nazwa ], $op );

		if ( is_wp_error( $wynik ) ) {
			$wynik = array( 'error' => $wynik->get_error_message() );
		}

		$wyniki[] = array_merge( array( 'i' => $i, 'op' => $nazwa ), (array) $wynik );
	}

	ntc_deploy_flush();

	return rest_ensure_response( array( 'count' => count( $wyniki ), 'results' => $wyniki ) );
}

/**
 * Wpis o podanym adresie, niezależnie od miejsca w hierarchii.
 *
 * Świadomie nie get_page_by_path(): tamta funkcja dopasowuje pełną ścieżkę, więc
 * podstrony oferty ("oferta/probiotyki") nie znalazłaby po samym "probiotyki" i
 * wsad zakładałby duplikaty zamiast aktualizować.
 *
 * @param string $slug Adres wpisu.
 * @param string $type Typ treści.
 * @return WP_Post|null
 */
function ntc_deploy_find_post( $slug, $type = 'page' ) {
	$found = get_posts(
		array(
			'name'                   => $slug,
			'post_type'              => $type,
			'post_status'            => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);

	return $found ? $found[0] : null;
}

/**
 * Strona: zakłada po adresie albo aktualizuje istniejącą.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_page( $op ) {
	$slug = isset( $op['slug'] ) ? sanitize_title( $op['slug'] ) : '';

	if ( ! $slug ) {
		return new WP_Error( 'ntc_deploy_no_slug', 'Operacja page wymaga pola slug.' );
	}

	$istniejaca = ntc_deploy_find_post( $slug );

	// wp_insert_post() oczekuje danych z ukośnikami i sam je zdejmuje. Bez
	// wp_slash() sekwencje < z atrybutów bloków traciłyby ukośnik i na
	// stronie zamiast <br/> pokazywałoby się "u003cbr/u003e".
	$dane = array(
		'post_type'    => 'page',
		'post_name'    => $slug,
		'post_status'  => isset( $op['status'] ) ? sanitize_key( $op['status'] ) : 'publish',
		'post_title'   => isset( $op['title'] ) ? wp_slash( wp_strip_all_tags( $op['title'] ) ) : '',
		'post_content' => isset( $op['content'] ) ? wp_slash( (string) $op['content'] ) : '',
	);

	if ( isset( $op['parent'] ) ) {
		$dane['post_parent'] = (int) $op['parent'];
	}

	if ( isset( $op['parent_slug'] ) ) {
		$rodzic              = ntc_deploy_find_post( sanitize_title( $op['parent_slug'] ) );
		$dane['post_parent'] = $rodzic ? $rodzic->ID : 0;
	}

	if ( isset( $op['menu_order'] ) ) {
		$dane['menu_order'] = (int) $op['menu_order'];
	}

	if ( $istniejaca ) {
		$dane['ID'] = $istniejaca->ID;

		// Bez tytułu nie nadpisujemy istniejącego - pusty tytuł byłby regresem.
		if ( '' === $dane['post_title'] ) {
			unset( $dane['post_title'] );
		}

		$id = wp_update_post( $dane, true );
	} else {
		$id = wp_insert_post( $dane, true );
	}

	if ( is_wp_error( $id ) ) {
		return $id;
	}

	if ( ! empty( $op['template'] ) ) {
		update_post_meta( $id, '_wp_page_template', sanitize_text_field( $op['template'] ) );
	}

	return array(
		'id'      => $id,
		'slug'    => $slug,
		'link'    => get_permalink( $id ),
		'created' => ! $istniejaca,
	);
}

/**
 * Pozycja katalogu produktów wraz z polami tabeli.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_product( $op ) {
	if ( ! defined( 'NTC_PRODUCT_CPT' ) ) {
		return new WP_Error( 'ntc_deploy_no_cpt', 'Motyw nie rejestruje typu produktów.' );
	}

	$slug = isset( $op['slug'] ) ? sanitize_title( $op['slug'] ) : '';

	if ( ! $slug ) {
		return new WP_Error( 'ntc_deploy_no_slug', 'Operacja product wymaga pola slug.' );
	}

	$istniejacy = ntc_deploy_find_post( $slug, NTC_PRODUCT_CPT );

	$dane = array(
		'post_type'   => NTC_PRODUCT_CPT,
		'post_name'   => $slug,
		'post_status' => 'publish',
		'post_title'  => wp_slash( isset( $op['title'] ) ? wp_strip_all_tags( $op['title'] ) : $slug ),
		'menu_order'  => isset( $op['order'] ) ? (int) $op['order'] : 0,
	);

	if ( $istniejacy ) {
		$dane['ID'] = $istniejacy->ID;
		$id         = wp_update_post( $dane, true );
	} else {
		$id = wp_insert_post( $dane, true );
	}

	if ( is_wp_error( $id ) ) {
		return $id;
	}

	if ( ! empty( $op['category'] ) ) {
		// Taksonomia jest hierarchiczna, więc wp_set_object_terms() musi dostać
		// identyfikator terminu. Slug potraktowałaby jak nazwę i założyła obok
		// drugą kategorię o tej samej nazwie.
		$term = get_term_by( 'slug', sanitize_title( $op['category'] ), 'ntc_product_cat' );

		if ( ! $term ) {
			return new WP_Error(
				'ntc_deploy_no_term',
				sprintf( 'Nie ma kategorii "%s" - załóż ją operacją term przed produktami.', $op['category'] )
			);
		}

		wp_set_object_terms( $id, array( (int) $term->term_id ), 'ntc_product_cat', false );
	}

	$pola = isset( $op['meta'] ) && is_array( $op['meta'] ) ? $op['meta'] : array();

	foreach ( $pola as $klucz => $wartosc ) {
		$klucz = sanitize_key( $klucz );

		if ( 0 !== strpos( $klucz, '_ntc_' ) ) {
			continue;
		}

		update_post_meta( $id, $klucz, sanitize_text_field( (string) $wartosc ) );
	}

	return array( 'id' => $id, 'slug' => $slug, 'created' => ! $istniejacy );
}

/**
 * Kategoria produktów wraz z kolejnością na liście.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_term( $op ) {
	$slug = isset( $op['slug'] ) ? sanitize_title( $op['slug'] ) : '';

	if ( ! $slug ) {
		return new WP_Error( 'ntc_deploy_no_slug', 'Operacja term wymaga pola slug.' );
	}

	$nazwa = isset( $op['name'] ) ? wp_strip_all_tags( $op['name'] ) : $slug;
	$term  = get_term_by( 'slug', $slug, 'ntc_product_cat' );

	if ( $term ) {
		wp_update_term( $term->term_id, 'ntc_product_cat', array( 'name' => $nazwa ) );
		$id = $term->term_id;
	} else {
		$nowy = wp_insert_term( $nazwa, 'ntc_product_cat', array( 'slug' => $slug ) );

		if ( is_wp_error( $nowy ) ) {
			return $nowy;
		}

		$id = $nowy['term_id'];
	}

	if ( isset( $op['order'] ) ) {
		update_term_meta( $id, '_ntc_order', (int) $op['order'] );
	}

	return array( 'id' => $id, 'slug' => $slug );
}

/**
 * Przenosi do kosza stronę albo produkt o podanym adresie.
 *
 * @param array $op Parametry operacji.
 * @return array
 */
function ntc_deploy_op_trash( $op ) {
	$slug = isset( $op['slug'] ) ? sanitize_title( $op['slug'] ) : '';
	$typ  = isset( $op['type'] ) ? sanitize_key( $op['type'] ) : 'page';
	$post = $slug ? ntc_deploy_find_post( $slug, $typ ) : null;

	if ( ! $post ) {
		return array( 'slug' => $slug, 'trashed' => false, 'note' => 'nie znaleziono' );
	}

	wp_trash_post( $post->ID );

	return array( 'slug' => $slug, 'trashed' => true, 'id' => $post->ID );
}

/**
 * Sprząta kategorię katalogu: zostawia podane pozycje, resztę do kosza.
 *
 * Wersja klienta jest listą zamkniętą, więc po każdej aktualizacji zostają w
 * bazie pozycje, których już nie ma w materiałach. Porównanie robimy po stronie
 * serwera - inaczej trzeba by najpierw pobrać całą kategorię, a to właśnie takie
 * zapytania listujące zapalają ochronę antybotową hostingu.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_prune( $op ) {
	if ( ! defined( 'NTC_PRODUCT_CPT' ) ) {
		return new WP_Error( 'ntc_deploy_no_cpt', 'Motyw nie rejestruje typu produktów.' );
	}

	$kategoria = isset( $op['category'] ) ? sanitize_title( $op['category'] ) : '';
	$zostaja   = isset( $op['keep'] ) && is_array( $op['keep'] ) ? array_map( 'sanitize_title', $op['keep'] ) : array();

	if ( ! $kategoria ) {
		return new WP_Error( 'ntc_deploy_no_category', 'Operacja prune wymaga pola category.' );
	}

	// Pusta lista "zostaje" wyczyściłaby całą kategorię - to prawie na pewno
	// pomyłka w danych, więc lepiej nic nie robić niż skasować katalog.
	if ( ! $zostaja ) {
		return new WP_Error( 'ntc_deploy_empty_keep', 'Pusta lista keep - odmawiam czyszczenia całej kategorii.' );
	}

	$wpisy = get_posts(
		array(
			'post_type'      => NTC_PRODUCT_CPT,
			'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => 'ntc_product_cat',
					'field'    => 'slug',
					'terms'    => $kategoria,
				),
			),
		)
	);

	$do_kosza = array();

	foreach ( $wpisy as $id ) {
		$post = get_post( $id );

		if ( $post && ! in_array( $post->post_name, $zostaja, true ) ) {
			wp_trash_post( $id );
			$do_kosza[] = $post->post_name;
		}
	}

	return array(
		'category' => $kategoria,
		'trashed'  => count( $do_kosza ),
		'names'    => array_slice( $do_kosza, 0, 20 ),
	);
}

/**
 * Menu w danej lokalizacji budowane od zera.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_menu( $op ) {
	$lokalizacja = isset( $op['location'] ) ? sanitize_key( $op['location'] ) : '';
	$pozycje     = isset( $op['items'] ) && is_array( $op['items'] ) ? $op['items'] : array();

	if ( ! $lokalizacja ) {
		return new WP_Error( 'ntc_deploy_no_location', 'Operacja menu wymaga pola location.' );
	}

	$przypisane = get_nav_menu_locations();
	$menu_id    = isset( $przypisane[ $lokalizacja ] ) ? (int) $przypisane[ $lokalizacja ] : 0;

	if ( ! $menu_id ) {
		return new WP_Error( 'ntc_deploy_no_menu', sprintf( 'Lokalizacja %s nie ma przypisanego menu.', $lokalizacja ) );
	}

	// Najpierw dokładamy nowe pozycje, potem kasujemy stare - menu ani przez
	// chwilę nie jest puste, gdyby ktoś w tym momencie wszedł na stronę.
	$stare = wp_get_nav_menu_items( $menu_id );
	$nowe  = array();

	// Pozycje podrzędne (np. rozwijana oferta) dostają identyfikator rodzica,
	// więc rodzic musi powstać wcześniej - stąd spłaszczenie listy na
	// wejściu zamiast zagnieżdżonej pętli.
	$plaskie = array();

	foreach ( $pozycje as $poz ) {
		$dzieci = isset( $poz['items'] ) && is_array( $poz['items'] ) ? $poz['items'] : array();
		unset( $poz['items'] );

		$klucz             = count( $plaskie );
		$poz['_rodzic']    = null;
		$plaskie[ $klucz ] = $poz;

		foreach ( $dzieci as $dziecko ) {
			$dziecko['_rodzic'] = $klucz;
			$plaskie[]          = $dziecko;
		}
	}

	$pozycje = $plaskie;

	foreach ( $pozycje as $i => $poz ) {
		$dane = array(
			'menu-item-title'     => wp_slash( isset( $poz['title'] ) ? wp_strip_all_tags( $poz['title'] ) : '' ),
			'menu-item-status'    => 'publish',
			'menu-item-position'  => $i + 1,
		);

		if ( isset( $poz['_rodzic'] ) && isset( $nowe[ $poz['_rodzic'] ] ) ) {
			$dane['menu-item-parent-id'] = $nowe[ $poz['_rodzic'] ];
		}

		$strona = ! empty( $poz['page'] ) ? ntc_deploy_find_post( sanitize_title( $poz['page'] ) ) : null;

		if ( $strona && empty( $poz['anchor'] ) ) {
			$dane['menu-item-type']      = 'post_type';
			$dane['menu-item-object']    = 'page';
			$dane['menu-item-object-id'] = $strona->ID;
		} else {
			$url = isset( $poz['url'] ) ? esc_url_raw( $poz['url'] ) : '';

			if ( $strona && ! empty( $poz['anchor'] ) ) {
				$url = get_permalink( $strona ) . '#' . sanitize_title( $poz['anchor'] );
			}

			if ( ! $url ) {
				continue;
			}

			$dane['menu-item-type'] = 'custom';
			$dane['menu-item-url']  = $url;
		}

		$id = wp_update_nav_menu_item( $menu_id, 0, $dane );

		if ( ! is_wp_error( $id ) ) {
			// Klucz musi odpowiadać pozycji na spłaszczonej liście, bo po nim
			// dzieci odnajdują identyfikator rodzica.
			$nowe[ $i ] = $id;
		}
	}

	foreach ( (array) $stare as $poz ) {
		wp_delete_post( $poz->ID, true );
	}

	return array( 'location' => $lokalizacja, 'menu' => $menu_id, 'items' => count( $nowe ) );
}

/**
 * Ustawienie motywu (Dostosuj) - tylko klucze z przedrostkiem ntc_.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_option( $op ) {
	$klucz = isset( $op['key'] ) ? sanitize_key( $op['key'] ) : '';

	if ( 0 !== strpos( $klucz, 'ntc_' ) ) {
		return new WP_Error( 'ntc_deploy_bad_option', 'Dozwolone są tylko ustawienia motywu (ntc_*).' );
	}

	set_theme_mod( $klucz, isset( $op['value'] ) ? $op['value'] : '' );

	return array( 'key' => $klucz );
}

/**
 * Pola profilu autora - tylko klucze z przedrostkiem ntc_.
 *
 * REST nie wystawia dowolnych meta użytkownika, a rejestrowanie ich w schemacie
 * po to, żeby raz je ustawić, byłoby dokładaniem publicznego API bez powodu.
 *
 * @param array $op Parametry operacji.
 * @return array|WP_Error
 */
function ntc_deploy_op_user_meta( $op ) {
	$user_id = isset( $op['user'] ) ? (int) $op['user'] : 0;
	$pola    = isset( $op['meta'] ) && is_array( $op['meta'] ) ? $op['meta'] : array();

	if ( ! $user_id || ! get_userdata( $user_id ) ) {
		return new WP_Error( 'ntc_deploy_no_user', 'Operacja user_meta wymaga istniejącego użytkownika.' );
	}

	$zapisane = array();

	foreach ( $pola as $klucz => $wartosc ) {
		$klucz = sanitize_key( $klucz );

		if ( 0 !== strpos( $klucz, 'ntc_' ) ) {
			continue;
		}

		update_user_meta( $user_id, $klucz, sanitize_text_field( (string) $wartosc ) );
		$zapisane[] = $klucz;
	}

	return array( 'user' => $user_id, 'meta' => $zapisane );
}

/**
 * Hosty, z których wolno pobierać pliki do biblioteki mediów.
 *
 * Trasa każe serwerowi pobrać plik spod podanego adresu, więc bez ograniczenia
 * byłaby narzędziem do odpytywania sieci wewnętrznej cudzymi rękami. Lista jest
 * zamknięta i obejmuje tylko to, czym faktycznie się posługujemy.
 */
function ntc_deploy_media_hosts() {
	return array(
		'drive.google.com',
		'drive.usercontent.google.com',
		'docs.google.com',
		'lh3.googleusercontent.com',
		// Dotychczasowy serwis klienta. Materiały, które trzeba przenieść na
		// nową stronę (godła instytucji, skany certyfikatów), leżą tam i tylko
		// tam - proxy agenta tej domeny nie przepuszcza.
		'ntcandar.com.pl',
		'www.ntcandar.com.pl',
	);
}

/**
 * Pobranie pliku do biblioteki mediów po stronie serwera.
 *
 * Agent budujący stronę siedzi za proxy, które nie przepuszcza Dysku Google.
 * Serwer WordPressa żadnego takiego ograniczenia nie ma, więc to on pobiera
 * plik. Bajty nie przechodzą przez pośrednika, a przy kilkudziesięciu
 * logotypach to różnica między jednym poleceniem a ręcznym przeklikiwaniem.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response|WP_Error
 */
function ntc_deploy_fetch_media( $request ) {
	$url  = (string) $request->get_param( 'url' );
	$host = wp_parse_url( $url, PHP_URL_HOST );

	if ( 'https' !== wp_parse_url( $url, PHP_URL_SCHEME ) ) {
		return new WP_Error( 'ntc_deploy_bad_scheme', 'Dozwolone są wyłącznie adresy https.', array( 'status' => 400 ) );
	}

	if ( ! in_array( $host, ntc_deploy_media_hosts(), true ) ) {
		return new WP_Error(
			'ntc_deploy_host_denied',
			sprintf( 'Host "%s" nie jest na liście dozwolonych.', $host ),
			array( 'status' => 400 )
		);
	}

	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';

	$tmp = download_url( $url, 60 );

	if ( is_wp_error( $tmp ) ) {
		return new WP_Error(
			'ntc_deploy_download_failed',
			'Nie udało się pobrać pliku: ' . $tmp->get_error_message(),
			array( 'status' => 502 )
		);
	}

	$name = sanitize_file_name( (string) $request->get_param( 'filename' ) );

	if ( ! $name ) {
		$name = 'plik-' . wp_generate_password( 6, false );
	}

	// Dysk podaje typ w nagłówku, nie w adresie, więc rozszerzenie ustalamy
	// z zawartości. Bez tego WordPress odrzuca plik jako niedozwolony typ.
	if ( ! pathinfo( $name, PATHINFO_EXTENSION ) ) {
		$type = wp_get_image_mime( $tmp );
		$ext  = $type ? ltrim( (string) strrchr( str_replace( 'jpeg', 'jpg', $type ), '/' ), '/' ) : '';

		if ( ! $ext ) {
			wp_delete_file( $tmp );

			return new WP_Error( 'ntc_deploy_not_image', 'Pobrany plik nie jest obrazem.', array( 'status' => 415 ) );
		}

		$name .= '.' . $ext;
	}

	$id = media_handle_sideload(
		array(
			'name'     => $name,
			'tmp_name' => $tmp,
		),
		0,
		(string) $request->get_param( 'title' )
	);

	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );

		return new WP_Error(
			'ntc_deploy_sideload_failed',
			'Nie udało się dodać pliku do mediów: ' . $id->get_error_message(),
			array( 'status' => 500 )
		);
	}

	$alt = (string) $request->get_param( 'alt' );

	if ( $alt ) {
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	}

	return rest_ensure_response(
		array(
			'id'       => $id,
			'url'      => wp_get_attachment_url( $id ),
			'filename' => $name,
		)
	);
}

/**
 * Przywrócenie ostatniej kopii.
 *
 * @param WP_REST_Request $request Żądanie.
 * @return WP_REST_Response|WP_Error
 */
function ntc_deploy_rollback( $request ) {
	$slug    = (string) $request->get_param( 'target' );
	$targets = ntc_deploy_targets();

	if ( ! isset( $targets[ $slug ] ) ) {
		return new WP_Error( 'ntc_deploy_unknown_target', sprintf( 'Nieznany cel "%s".', $slug ), array( 'status' => 400 ) );
	}

	$backups = ntc_deploy_backups( $slug );

	if ( ! $backups ) {
		return new WP_Error( 'ntc_deploy_no_backup', 'Nie ma żadnej kopii do przywrócenia.', array( 'status' => 404 ) );
	}

	$fs = ntc_deploy_filesystem();

	if ( is_wp_error( $fs ) ) {
		return $fs;
	}

	$latest   = end( $backups );
	$restored = ntc_deploy_swap( $latest, $targets[ $slug ]['path'], $fs );

	if ( is_wp_error( $restored ) ) {
		return $restored;
	}

	ntc_deploy_flush();

	return rest_ensure_response(
		array(
			'target'   => $slug,
			'restored' => basename( $latest ),
			'version'  => ntc_deploy_read_version( $targets[ $slug ]['path'] . '/' . $targets[ $slug ]['must'] ),
		)
	);
}

/* ------------------------------------------------------------- narzędziowe */

/**
 * Katalog na kopie.
 */
function ntc_deploy_backup_root() {
	$root = trailingslashit( WP_CONTENT_DIR ) . 'upgrade/ntc-backups';

	wp_mkdir_p( $root );

	return $root;
}

/**
 * Kopie danego celu, od najstarszej.
 *
 * @param string $slug Nazwa celu.
 * @return array
 */
function ntc_deploy_backups( $slug ) {
	$found = glob( ntc_deploy_backup_root() . '/' . $slug . '-*', GLOB_ONLYDIR );

	if ( ! $found ) {
		return array();
	}

	sort( $found );

	return $found;
}

/**
 * Uchwyt WP_Filesystem albo błąd z sensowną treścią.
 *
 * @return WP_Filesystem_Base|WP_Error
 */
function ntc_deploy_filesystem() {
	global $wp_filesystem;

	require_once ABSPATH . 'wp-admin/includes/file.php';

	if ( ! WP_Filesystem() ) {
		return new WP_Error(
			'ntc_deploy_no_filesystem',
			'WordPress nie ma bezpośredniego dostępu do plików. Sprawdź FS_METHOD i uprawnienia katalogu wp-content.',
			array( 'status' => 500 )
		);
	}

	return $wp_filesystem;
}

/**
 * Czyszczenie tego, co mogłoby podać starą wersję po podmianie.
 */
function ntc_deploy_flush() {
	wp_clean_themes_cache();
	wp_cache_flush();

	delete_site_transient( 'update_themes' );
	delete_site_transient( 'update_plugins' );

	// LiteSpeed trzyma gotowy HTML i bez tego strona pokazywałaby stary układ
	// jeszcze długo po wdrożeniu.
	do_action( 'litespeed_purge_all' );
}

/* ---------------------------------------------------------- widoczność w kokpicie */

/**
 * Ostrzeżenie w kokpicie, dopóki zdalne wdrożenia są włączone.
 *
 * Narzędzie na czas budowy strony łatwo zostawić włączone na produkcji, bo nic
 * nie widać. Ten pasek ma o nim przypominać.
 */
function ntc_deploy_notice() {
	if ( ! ntc_deploy_enabled() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>NTC Deploy:</strong> zdalna podmiana motywu jest włączona. '
		. 'Przed publikacją strony usuń stałą <code>NTC_DEPLOY_KEY</code> z <code>wp-config.php</code> '
		. 'i wyłącz tę wtyczkę.</p></div>';
}
add_action( 'admin_notices', 'ntc_deploy_notice' );
