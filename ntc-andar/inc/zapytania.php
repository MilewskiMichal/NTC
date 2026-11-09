<?php
/**
 * Zapytania z formularzy: zapis w kokpicie i wysyłka e-mailem.
 *
 * Formularze na stronie (inc/forms.php) wysyłają dane skryptem do trasy REST
 * /ntc/v1/zapytanie. Każde zgłoszenie najpierw zapisuje się w kokpicie
 * (menu Zapytania), dopiero potem idzie mailem. Jeśli poczta zawiedzie,
 * zgłoszenie i tak czeka w kokpicie, a lista pokazuje, który mail nie doszedł.
 *
 * Bez nonce'a: strony siedzą w pamięci podręcznej LiteSpeed, a nonce z
 * zapamiętanej strony po dobie przestaje być ważny - formularz zacząłby
 * odrzucać prawdziwych klientów. Przed spamem chronią ukryte pole-pułapka,
 * podpisany znacznik z formularza, minimalny czas wypełniania i limit
 * zgłoszeń z jednego adresu IP.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

const NTC_ZAPYTANIE_CPT = 'ntc_zapytanie';

/** Pola zgłoszenia: klucz => etykieta w kokpicie i w mailu. */
function ntc_zapytanie_pola() {
	return array(
		'nazwisko' => 'Imię i nazwisko',
		'firma'    => 'Firma',
		'email'    => 'E-mail',
		'tel'      => 'Telefon',
		'temat'    => 'Temat',
		'jezyk'    => 'Język strony',
		'strona'   => 'Wysłano ze strony',
	);
}

/* ------------------------------------------------------------ typ treści */

/**
 * Typ "Zapytania" - tylko do odczytu, zgłoszeń nie dodaje się ręcznie.
 */
function ntc_zapytania_typ() {
	register_post_type(
		NTC_ZAPYTANIE_CPT,
		array(
			'labels'          => array(
				'name'               => 'Zapytania',
				'singular_name'      => 'Zapytanie',
				'menu_name'          => 'Zapytania',
				'all_items'          => 'Zapytania z formularzy',
				'edit_item'          => 'Zapytanie',
				'search_items'       => 'Szukaj w zapytaniach',
				'not_found'          => 'Nie ma jeszcze zapytań z formularzy.',
				'not_found_in_trash' => 'Kosz jest pusty.',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'menu_position'   => 4,
			'menu_icon'       => 'dashicons-email-alt',
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap'    => true,
			'show_in_rest'    => false,
		)
	);
}
add_action( 'init', 'ntc_zapytania_typ' );

/* ---------------------------------------------------- ochrona przed spamem */

/**
 * Podpisany znacznik czasu wstawiany do formularza.
 *
 * Dowodzi tylko tyle, że formularz pochodzi z tej strony - wiek strony w
 * pamięci podręcznej nie ma znaczenia, więc nie ma tu górnej granicy.
 *
 * @return string
 */
function ntc_zapytanie_token() {
	$czas = (string) time();

	return $czas . '.' . substr( hash_hmac( 'sha256', $czas, wp_salt( 'nonce' ) ), 0, 16 );
}

/**
 * Czy znacznik z formularza jest prawdziwy.
 *
 * @param string $token Znacznik.
 * @return bool
 */
function ntc_zapytanie_token_ok( $token ) {
	if ( ! preg_match( '/^(\d{9,11})\.([0-9a-f]{16})$/', (string) $token, $m ) ) {
		return false;
	}

	return hash_equals( substr( hash_hmac( 'sha256', $m[1], wp_salt( 'nonce' ) ), 0, 16 ), $m[2] );
}

/**
 * Ukryte pola formularza: pułapka na boty i znacznik.
 *
 * Pole-pułapka jest schowane poza ekranem i wyłączone z nawigacji
 * klawiaturą - człowiek go nie wypełni, bot wypełnia wszystko, co widzi.
 */
function ntc_form_ukryte() {
	?>
	<div class="form-hp" aria-hidden="true">
		<label>Strona www <input type="text" name="www" value="" tabindex="-1" autocomplete="off" /></label>
	</div>
	<input type="hidden" name="t" value="<?php echo esc_attr( ntc_zapytanie_token() ); ?>" />
	<p class="form-error" role="alert" data-ntc-error hidden></p>
	<?php
}

/* -------------------------------------------------------- odbiór zgłoszeń */

/**
 * Trasa przyjmująca zgłoszenia.
 */
function ntc_zapytania_trasa() {
	register_rest_route(
		'ntc/v1',
		'/zapytanie',
		array(
			'methods'             => 'POST',
			'callback'            => 'ntc_zapytanie_przyjmij',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'ntc_zapytania_trasa' );

/**
 * Odpowiedź z błędem dla skryptu formularza.
 *
 * @param string $kod    Kod błędu.
 * @param int    $status Status HTTP.
 * @return WP_REST_Response
 */
function ntc_zapytanie_blad( $kod, $status = 400 ) {
	return new WP_REST_Response( array( 'ok' => false, 'blad' => $kod ), $status );
}

/**
 * Przyjmuje zgłoszenie: sprawdza, zapisuje w kokpicie, wysyła mailem.
 *
 * @param WP_REST_Request $req Żądanie.
 * @return WP_REST_Response
 */
function ntc_zapytanie_przyjmij( $req ) {
	// Pułapka wypełniona - udajemy sukces, żeby bot nie próbował dalej.
	if ( '' !== trim( (string) $req->get_param( 'www' ) ) ) {
		return new WP_REST_Response( array( 'ok' => true ), 200 );
	}

	// Człowiek potrzebuje kilku sekund na wypełnienie formularza.
	if ( ! ntc_zapytanie_token_ok( $req->get_param( 't' ) ) || (int) $req->get_param( 'czas' ) < 3 ) {
		return ntc_zapytanie_blad( 'spam' );
	}

	$ip    = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	$limit = 'ntc_zapytania_' . md5( $ip );
	$ile   = (int) get_transient( $limit );

	if ( $ile >= 5 ) {
		return ntc_zapytanie_blad( 'limit', 429 );
	}

	set_transient( $limit, $ile + 1, 10 * MINUTE_IN_SECONDS );

	$tekst = function ( $klucz, $max = 200 ) use ( $req ) {
		return mb_substr( sanitize_text_field( (string) $req->get_param( $klucz ) ), 0, $max );
	};

	$wariant = 'full' === $req->get_param( 'wariant' ) ? 'full' : 'home';
	$dane    = array(
		'nazwisko'  => $tekst( 'nazwisko' ),
		'firma'     => $tekst( 'firma' ),
		'email'     => sanitize_email( (string) $req->get_param( 'email' ) ),
		'tel'       => $tekst( 'tel', 60 ),
		'temat'     => $tekst( 'temat' ),
		'jezyk'     => 'en' === $req->get_param( 'jezyk' ) ? 'angielski' : 'polski',
		'strona'    => esc_url_raw( mb_substr( (string) $req->get_param( 'strona' ), 0, 500 ) ),
		'wiadomosc' => mb_substr( sanitize_textarea_field( (string) $req->get_param( 'msg' ) ), 0, 5000 ),
	);

	// Te same pola wymagane co w przeglądarce - skrypt da się obejść.
	$wymagane = 'full' === $wariant ? array( 'nazwisko', 'firma', 'email', 'temat' ) : array( 'firma', 'email' );

	foreach ( $wymagane as $pole ) {
		if ( '' === $dane[ $pole ] ) {
			return ntc_zapytanie_blad( 'brak:' . $pole, 422 );
		}
	}

	if ( ! is_email( $dane['email'] ) || ! $req->get_param( 'consent' ) ) {
		return ntc_zapytanie_blad( 'dane', 422 );
	}

	$id = wp_insert_post(
		array(
			'post_type'    => NTC_ZAPYTANIE_CPT,
			'post_status'  => 'publish',
			'post_title'   => wp_strip_all_tags( $dane['firma'] . ( $dane['temat'] ? ' - ' . $dane['temat'] : '' ) ),
			'post_content' => $dane['wiadomosc'],
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		return ntc_zapytanie_blad( 'zapis', 500 );
	}

	foreach ( ntc_zapytanie_pola() as $klucz => $etykieta ) {
		update_post_meta( $id, '_ntc_z_' . $klucz, $dane[ $klucz ] );
	}

	update_post_meta( $id, '_ntc_z_mail', ntc_zapytanie_wyslij( $id, $dane ) ? 'wyslany' : 'blad' );

	// Zgłoszenie jest bezpieczne w kokpicie, więc nawet przy kłopocie z
	// pocztą odwiedzający dostaje potwierdzenie.
	return new WP_REST_Response( array( 'ok' => true ), 200 );
}

/* ----------------------------------------------------------------- poczta */

/**
 * Adresy, na które idą zgłoszenia - z Dostosuj, domyślnie e-mail firmy.
 *
 * @return string[]
 */
function ntc_zapytania_odbiorcy() {
	$co    = function_exists( 'ntc_company' ) ? ntc_company() : array();
	$domy  = isset( $co['email'] ) ? $co['email'] : get_option( 'admin_email' );
	$lista = preg_split( '/[\s,;]+/', (string) get_theme_mod( 'ntc_form_recipient', $domy ) );

	$lista = array_values( array_filter( array_map( 'sanitize_email', $lista ), 'is_email' ) );

	return $lista ? $lista : array( get_option( 'admin_email' ) );
}

/**
 * Mail z jednym zgłoszeniem.
 *
 * Odpowiedź z programu pocztowego trafia od razu do klienta (Reply-To).
 *
 * @param int   $id   Zgłoszenie w kokpicie.
 * @param array $dane Pola zgłoszenia.
 * @return bool Czy poczta przyjęła wiadomość.
 */
function ntc_zapytanie_wyslij( $id, $dane ) {
	$host  = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	$temat = sprintf( '[%s] Zapytanie: %s - %s', $host, $dane['temat'] ? $dane['temat'] : 'formularz', $dane['firma'] );

	$tresc = 'Nowe zapytanie z formularza na stronie ' . home_url( '/' ) . "\n\n";

	foreach ( ntc_zapytanie_pola() as $klucz => $etykieta ) {
		if ( '' !== $dane[ $klucz ] ) {
			$tresc .= $etykieta . ': ' . $dane[ $klucz ] . "\n";
		}
	}

	$tresc .= "\nWiadomość:\n" . ( '' !== $dane['wiadomosc'] ? $dane['wiadomosc'] : '(bez treści)' ) . "\n";
	$tresc .= "\n--\nOdpowiedz na tego maila, żeby napisać prosto do klienta.\n";
	$tresc .= 'Zapytanie jest też zapisane w kokpicie: ' . admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) . "\n";

	return wp_mail( ntc_zapytania_odbiorcy(), $temat, $tresc, array( 'Reply-To: ' . $dane['email'] ) );
}

/**
 * Nadawca maili ze strony - adres w domenie strony zamiast "WordPress".
 *
 * Podpis DKIM (niżej) jest wystawiany właśnie na tę domenę.
 *
 * @param string $adres Domyślny nadawca.
 * @return string
 */
function ntc_zapytania_nadawca( $adres ) {
	$wlasny = sanitize_email( (string) get_theme_mod( 'ntc_form_sender', '' ) );

	if ( $wlasny && is_email( $wlasny ) ) {
		return $wlasny;
	}

	$host = preg_replace( '/^www\./', '', (string) wp_parse_url( home_url(), PHP_URL_HOST ) );

	return 0 === strpos( $adres, 'wordpress@' ) && $host ? 'formularz@' . $host : $adres;
}
add_filter( 'wp_mail_from', 'ntc_zapytania_nadawca' );

/**
 * Nazwa nadawcy.
 *
 * @param string $nazwa Domyślna nazwa.
 * @return string
 */
function ntc_zapytania_nazwa_nadawcy( $nazwa ) {
	return 'WordPress' === $nazwa ? get_bloginfo( 'name' ) . ' - strona www' : $nazwa;
}
add_filter( 'wp_mail_from_name', 'ntc_zapytania_nazwa_nadawcy' );

/**
 * Podpis DKIM maili ze strony.
 *
 * Poczta domeny stoi w Microsoft 365, a maile ze strony wysyła serwer
 * hostingu. Bez podpisu domeny lądowałyby w spamie. Podpisuje sam WordPress
 * (PHPMailer), kluczem zapisanym w bazie (opcje ntc_dkim_selektor i
 * ntc_dkim_klucz) - nie trzeba do tego panelu hostingu.
 *
 * Podpis włącza się sam, dopiero gdy w DNS domeny pojawi się rekord
 * <selektor>._domainkey z kluczem publicznym. Wcześniej podpis byłby nie do
 * sprawdzenia i szkodziłby bardziej, niż jego brak.
 *
 * @param PHPMailer\PHPMailer\PHPMailer $mailer Wysyłający mail.
 */
function ntc_poczta_dkim( $mailer ) {
	$selektor = (string) get_option( 'ntc_dkim_selektor', '' );
	$klucz    = (string) get_option( 'ntc_dkim_klucz', '' );
	$domena   = strtolower( (string) substr( (string) strrchr( (string) $mailer->From, '@' ), 1 ) );

	if ( '' === $selektor || '' === $klucz || '' === $domena || ! ntc_poczta_dkim_w_dns( $selektor, $domena ) ) {
		return;
	}

	$mailer->DKIM_domain         = $domena;
	$mailer->DKIM_selector       = $selektor;
	$mailer->DKIM_private_string = $klucz;
	$mailer->DKIM_identity       = $mailer->From;
}
add_action( 'phpmailer_init', 'ntc_poczta_dkim' );

/**
 * Czy klucz publiczny DKIM jest już w DNS domeny.
 *
 * Wynik siedzi w pamięci: dodatni na dobę, ujemny na kwadrans - po dodaniu
 * rekordu podpis rusza najpóźniej po 15 minutach, bez ruszania strony.
 *
 * @param string $selektor Selektor DKIM.
 * @param string $domena   Domena nadawcy.
 * @return bool
 */
function ntc_poczta_dkim_w_dns( $selektor, $domena ) {
	$pamiec = 'ntc_dkim_dns_' . md5( $selektor . '|' . $domena );
	$stan   = get_transient( $pamiec );

	if ( false === $stan ) {
		$stan    = 'nie';
		$rekordy = function_exists( 'dns_get_record' ) ? @dns_get_record( $selektor . '._domainkey.' . $domena, DNS_TXT ) : array(); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- brak rekordu to zwykła sytuacja, nie błąd.

		foreach ( (array) $rekordy as $rekord ) {
			$tekst = isset( $rekord['entries'] ) ? implode( '', (array) $rekord['entries'] ) : ( isset( $rekord['txt'] ) ? $rekord['txt'] : '' );

			if ( false !== strpos( $tekst, 'v=DKIM1' ) && false !== strpos( $tekst, 'p=' ) ) {
				$stan = 'tak';
				break;
			}
		}

		set_transient( $pamiec, $stan, 'tak' === $stan ? DAY_IN_SECONDS : 15 * MINUTE_IN_SECONDS );
	}

	return 'tak' === $stan;
}

/* ----------------------------------------------------------------- kokpit */

/**
 * Kolumny listy zapytań.
 *
 * @param array $kolumny Kolumny.
 * @return array
 */
function ntc_zapytania_kolumny( $kolumny ) {
	return array(
		'cb'           => $kolumny['cb'],
		'title'        => 'Firma i temat',
		'ntc_osoba'    => 'Osoba',
		'ntc_email'    => 'E-mail',
		'ntc_tel'      => 'Telefon',
		'ntc_jezyk'    => 'Język',
		'ntc_mail'     => 'Mail do firmy',
		'date'         => 'Data',
	);
}
add_filter( 'manage_' . NTC_ZAPYTANIE_CPT . '_posts_columns', 'ntc_zapytania_kolumny' );

/**
 * Wartości kolumn.
 *
 * @param string $kolumna Kolumna.
 * @param int    $id      Zgłoszenie.
 */
function ntc_zapytania_kolumna( $kolumna, $id ) {
	$m = function ( $k ) use ( $id ) {
		return (string) get_post_meta( $id, '_ntc_z_' . $k, true );
	};

	switch ( $kolumna ) {
		case 'ntc_osoba':
			echo esc_html( $m( 'nazwisko' ) );
			break;
		case 'ntc_email':
			printf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $m( 'email' ) ) );
			break;
		case 'ntc_tel':
			echo esc_html( $m( 'tel' ) );
			break;
		case 'ntc_jezyk':
			echo esc_html( $m( 'jezyk' ) );
			break;
		case 'ntc_mail':
			echo 'wyslany' === $m( 'mail' )
				? '<span style="color:#008a20">wysłany</span>'
				: '<span style="color:#d63638" title="Poczta nie przyjęła wiadomości - zgłoszenie jest tylko tutaj">nie wysłany</span>';
			break;
	}
}
add_action( 'manage_' . NTC_ZAPYTANIE_CPT . '_posts_custom_column', 'ntc_zapytania_kolumna', 10, 2 );

/**
 * Szczegóły zgłoszenia na ekranie edycji (tylko do odczytu).
 */
function ntc_zapytania_metabox() {
	add_meta_box( 'ntc-zapytanie', 'Treść zgłoszenia', 'ntc_zapytania_metabox_html', NTC_ZAPYTANIE_CPT, 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'ntc_zapytania_metabox' );

/**
 * Zawartość ramki ze zgłoszeniem.
 *
 * @param WP_Post $post Zgłoszenie.
 */
function ntc_zapytania_metabox_html( $post ) {
	echo '<table class="widefat striped" style="max-width:820px"><tbody>';

	foreach ( ntc_zapytanie_pola() as $klucz => $etykieta ) {
		$wartosc = (string) get_post_meta( $post->ID, '_ntc_z_' . $klucz, true );

		if ( '' === $wartosc ) {
			continue;
		}

		if ( 'email' === $klucz ) {
			$wartosc = sprintf( '<a href="mailto:%1$s">%1$s</a>', esc_attr( $wartosc ) );
		} elseif ( 'strona' === $klucz ) {
			$wartosc = sprintf( '<a href="%1$s" target="_blank" rel="noopener">%1$s</a>', esc_url( $wartosc ) );
		} else {
			$wartosc = esc_html( $wartosc );
		}

		printf( '<tr><th style="width:180px">%s</th><td>%s</td></tr>', esc_html( $etykieta ), $wartosc ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- składane z esc_*.
	}

	printf(
		'<tr><th>Wiadomość</th><td>%s</td></tr><tr><th>Mail do firmy</th><td>%s</td></tr>',
		nl2br( esc_html( '' !== $post->post_content ? $post->post_content : '(bez treści)' ) ),
		'wyslany' === get_post_meta( $post->ID, '_ntc_z_mail', true ) ? 'wysłany' : 'nie wysłany - poczta nie przyjęła wiadomości'
	);

	echo '</tbody></table>';

	$email = (string) get_post_meta( $post->ID, '_ntc_z_email', true );

	if ( $email ) {
		printf(
			'<p><a class="button button-primary" href="mailto:%s?subject=%s">Odpowiedz klientowi</a></p>',
			esc_attr( $email ),
			rawurlencode( 'Re: ' . $post->post_title )
		);
	}
}
