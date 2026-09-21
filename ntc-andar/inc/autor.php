<?php
/**
 * Profil autora wpisów.
 *
 * Blog bez podpisanego autora to dla wyszukiwarki tekst znikąd. Tutaj jest
 * komplet: rozszerzony profil w kokpicie (stanowisko, profile zawodowe),
 * podpis pod wpisem, strona autora i dane strukturalne - Person przy autorze,
 * Organization przy wydawcy, BlogPosting przy wpisie.
 *
 * Pola profilu są zwykłymi polami użytkownika, więc redakcja uzupełnia je w
 * Użytkownicy → Profil, bez wtyczek i bez naszej pomocy.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dodatkowe pola profilu autora.
 *
 * @return array<string,array{label:string,desc:string}>
 */
function ntc_autor_pola() {
	return array(
		'ntc_stanowisko' => array(
			'label' => 'Stanowisko',
			'desc'  => 'Np. „Specjalista ds. jakości”. Pokazuje się pod wpisem i trafia do danych strukturalnych.',
		),
		'ntc_typ_autora' => array(
			'label' => 'Typ autora',
			'desc'  => 'Wpisz „zespol”, jeśli pod wpisami podpisuje się redakcja, a nie konkretna osoba. '
				. 'Puste pole znaczy osobę - wtedy dane strukturalne opisują autora jako Person, a nie Organization. '
				. 'Wyszukiwarki wyżej cenią wpisy podpisane człowiekiem z nazwiskiem i doświadczeniem.',
		),
		'ntc_foto'       => array(
			'label' => 'Zdjęcie autora',
			'desc'  => 'Adres pliku z biblioteki mediów. Puste pole oznacza awatar spod adresu e-mail '
				. '(Gravatar), a przy jego braku - zastępczy znak z inicjałami.',
		),
		'ntc_linkedin'   => array(
			'label' => 'Profil LinkedIn',
			'desc'  => 'Pełny adres. Wyszukiwarki łączą po nim autora z jego profilem zawodowym.',
		),
		'ntc_orcid'      => array(
			'label' => 'ORCID albo inny profil naukowy',
			'desc'  => 'Opcjonalnie. Pełny adres profilu.',
		),
	);
}

/**
 * Pola w formularzu profilu użytkownika.
 *
 * @param WP_User $user Edytowany użytkownik.
 */
function ntc_autor_pola_formularz( $user ) {
	?>
	<h2>Profil autora na stronie</h2>
	<p class="description">
		Te dane pokazują się pod wpisami na blogu i w danych strukturalnych dla wyszukiwarek.
		Zdjęcie autora bierze się z awatara przypisanego do adresu e-mail.
	</p>
	<table class="form-table" role="presentation">
		<?php foreach ( ntc_autor_pola() as $klucz => $pole ) : ?>
			<tr>
				<th><label for="<?php echo esc_attr( $klucz ); ?>"><?php echo esc_html( $pole['label'] ); ?></label></th>
				<td>
					<input type="text" name="<?php echo esc_attr( $klucz ); ?>" id="<?php echo esc_attr( $klucz ); ?>"
						value="<?php echo esc_attr( get_user_meta( $user->ID, $klucz, true ) ); ?>"
						class="regular-text" />
					<p class="description"><?php echo esc_html( $pole['desc'] ); ?></p>
				</td>
			</tr>
		<?php endforeach; ?>
	</table>
	<?php
}
add_action( 'show_user_profile', 'ntc_autor_pola_formularz' );
add_action( 'edit_user_profile', 'ntc_autor_pola_formularz' );

/**
 * Zapis pól profilu.
 *
 * @param int $user_id Identyfikator użytkownika.
 */
function ntc_autor_pola_zapis( $user_id ) {
	if ( ! current_user_can( 'edit_user', $user_id ) ) {
		return;
	}

	foreach ( array_keys( ntc_autor_pola() ) as $klucz ) {
		if ( ! isset( $_POST[ $klucz ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- weryfikuje rdzeń przed tym hakiem.
			continue;
		}

		$wartosc = sanitize_text_field( wp_unslash( $_POST[ $klucz ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( in_array( $klucz, array( 'ntc_linkedin', 'ntc_orcid', 'ntc_foto' ), true ) ) {
			$wartosc = esc_url_raw( $wartosc );
		}

		update_user_meta( $user_id, $klucz, $wartosc );
	}
}
add_action( 'personal_options_update', 'ntc_autor_pola_zapis' );
add_action( 'edit_user_profile_update', 'ntc_autor_pola_zapis' );

/**
 * Komplet danych autora wpisu.
 *
 * @param int $post_id Wpis; domyślnie bieżący.
 * @return array<string,mixed>
 */
function ntc_autor( $post_id = 0 ) {
	$autor_id = (int) get_post_field( 'post_author', $post_id ? $post_id : get_the_ID() );

	if ( ! $autor_id ) {
		return array();
	}

	$profile = array_filter(
		array(
			get_user_meta( $autor_id, 'ntc_linkedin', true ),
			get_user_meta( $autor_id, 'ntc_orcid', true ),
			get_the_author_meta( 'url', $autor_id ),
		)
	);

	return array(
		'id'         => $autor_id,
		'zespol'     => 'zespol' === get_user_meta( $autor_id, 'ntc_typ_autora', true ),
		'imie'       => get_the_author_meta( 'display_name', $autor_id ),
		'stanowisko' => (string) get_user_meta( $autor_id, 'ntc_stanowisko', true ),
		'bio'        => (string) get_the_author_meta( 'description', $autor_id ),
		'foto'       => ntc_autor_foto( $autor_id, 160 ),
		'inicjaly'   => ntc_autor_inicjaly( get_the_author_meta( 'display_name', $autor_id ) ),
		'url'        => get_author_posts_url( $autor_id ),
		'profile'    => array_values( array_unique( $profile ) ),
	);
}

/**
 * Zdjęcie autora: własne z profilu albo awatar spod adresu e-mail.
 *
 * Gravatar przy adresie firmowym oddaje zwykle szarą sylwetkę, więc puste pole
 * profilu i brak awatara traktujemy tak samo - lepszy znak z inicjałami niż
 * cudza grafika zastępcza.
 *
 * @param int $autor_id Identyfikator autora.
 * @param int $rozmiar  Rozmiar w pikselach.
 * @return string Pusty ciąg, gdy nie ma czego pokazać.
 */
function ntc_autor_foto( $autor_id, $rozmiar = 160 ) {
	$wlasne = (string) get_user_meta( $autor_id, 'ntc_foto', true );

	if ( $wlasne ) {
		return $wlasne;
	}

	$avatar = (string) get_avatar_url( $autor_id, array( 'size' => $rozmiar, 'default' => '404' ) );

	// default=404 sprawia, że Gravatar zwraca błąd zamiast sylwetki. Sprawdzamy
	// więc, czy obrazek w ogóle istnieje - jednym zapytaniem na dobę.
	$klucz = 'ntc_avatar_' . $autor_id;
	$stan  = get_transient( $klucz );

	if ( false === $stan ) {
		$odp  = wp_remote_head( $avatar, array( 'timeout' => 4 ) );
		$stan = ( ! is_wp_error( $odp ) && 200 === wp_remote_retrieve_response_code( $odp ) ) ? 'jest' : 'brak';

		set_transient( $klucz, $stan, DAY_IN_SECONDS );
	}

	return 'jest' === $stan ? $avatar : '';
}

/**
 * Inicjały do znaku zastępczego.
 *
 * @param string $nazwa Nazwa wyświetlana autora.
 * @return string
 */
function ntc_autor_inicjaly( $nazwa ) {
	$slowa = preg_split( '/\s+/', trim( (string) $nazwa ) );
	$out   = '';

	foreach ( array_slice( $slowa, 0, 2 ) as $slowo ) {
		$out .= mb_strtoupper( mb_substr( $slowo, 0, 1 ) );
	}

	return $out;
}

/**
 * Zdjęcie autora albo znak z inicjałami.
 *
 * @param array  $autor Dane z ntc_autor().
 * @param string $klasa Klasa CSS elementu.
 * @param int    $bok   Rozmiar w pikselach.
 */
function ntc_the_autor_foto( $autor, $klasa, $bok ) {
	if ( ! empty( $autor['foto'] ) ) {
		printf(
			'<img class="%s" src="%s" alt="" width="%d" height="%d" loading="lazy" />',
			esc_attr( $klasa ),
			esc_url( $autor['foto'] ),
			(int) $bok,
			(int) $bok
		);

		return;
	}

	printf(
		'<span class="%s %s--inicjaly" aria-hidden="true">%s</span>',
		esc_attr( $klasa ),
		esc_attr( $klasa ),
		esc_html( $autor['inicjaly'] )
	);
}

/**
 * Podpis autora pod wpisem.
 *
 * @param int $post_id Wpis; domyślnie bieżący.
 */
function ntc_the_autor_box( $post_id = 0 ) {
	$autor = ntc_autor( $post_id );

	if ( ! $autor ) {
		return;
	}
	?>
	<aside class="autor-box">
		<?php ntc_the_autor_foto( $autor, 'autor-box-foto', 80 ); ?>
		<div class="autor-box-tresc">
			<div class="autor-box-etykieta"><?php ntc_e( 'autor.label' ); ?></div>
			<a class="autor-box-imie" href="<?php echo esc_url( $autor['url'] ); ?>">
				<?php echo esc_html( $autor['imie'] ); ?>
			</a>
			<?php if ( $autor['stanowisko'] ) : ?>
				<div class="autor-box-rola"><?php echo esc_html( $autor['stanowisko'] ); ?></div>
			<?php endif; ?>
			<?php if ( $autor['bio'] ) : ?>
				<p class="autor-box-bio"><?php echo esc_html( $autor['bio'] ); ?></p>
			<?php endif; ?>
			<?php if ( $autor['profile'] ) : ?>
				<div class="autor-box-linki">
					<?php foreach ( $autor['profile'] as $link ) : ?>
						<a href="<?php echo esc_url( $link ); ?>" rel="noopener me" target="_blank">
							<?php echo esc_html( ntc_autor_nazwa_profilu( $link ) ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</div>
	</aside>
	<?php
}

/**
 * Tekst do danych strukturalnych.
 *
 * WordPress przepuszcza tytuły i zajawki przez wptexturize, więc myślnik wraca
 * jako &#8211;. W HTML-u to bez znaczenia, ale JSON-LD czyta maszyna i encja
 * zostaje w niej dosłownie - stąd odwrotna zamiana.
 *
 * @param string $tekst Tekst ze znacznikami i encjami.
 * @return string
 */
function ntc_schema_tekst( $tekst ) {
	return trim( html_entity_decode( wp_strip_all_tags( (string) $tekst ), ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Stały identyfikator autora w grafie danych strukturalnych.
 *
 * Ten sam @id przy wpisie i na stronie profilu mówi wyszukiwarce, że autor
 * artykułu i podmiot profilu to jeden byt, a nie dwa o zbieżnej nazwie.
 *
 * @param int $autor_id Identyfikator autora.
 * @return string
 */
function ntc_autor_schema_id( $autor_id ) {
	return get_author_posts_url( $autor_id ) . '#autor';
}

/**
 * Nazwa serwisu z adresu profilu - żeby odnośnik nie był gołym URL-em.
 *
 * @param string $url Adres profilu.
 * @return string
 */
function ntc_autor_nazwa_profilu( $url ) {
	$host = (string) wp_parse_url( $url, PHP_URL_HOST );
	$host = preg_replace( '/^www\./', '', $host );

	$znane = array(
		'linkedin.com' => 'LinkedIn',
		'orcid.org'    => 'ORCID',
		'x.com'        => 'X',
		'twitter.com'  => 'X',
	);

	return isset( $znane[ $host ] ) ? $znane[ $host ] : $host;
}

/**
 * Dane strukturalne wpisu razem z autorem i wydawcą.
 *
 * Google wymaga przy artykule i autora, i wydawcy z logotypem. Bez tego wpis
 * jest dla wyszukiwarki tekstem bez pochodzenia, a autor - samym imieniem.
 */
function ntc_wpis_schema() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$autor = ntc_autor();

	if ( ! $autor ) {
		return;
	}

	$co = ntc_company();

	// Redakcja to Organization, konkretna osoba - Person. Podpisywanie zespołu
	// jako osoby byłoby w danych strukturalnych zwyczajną nieprawdą.
	$osoba = array(
		'@type' => $autor['zespol'] ? 'Organization' : 'Person',
		'@id'   => ntc_autor_schema_id( $autor['id'] ),
		'name'  => ntc_schema_tekst( $autor['imie'] ),
		'url'   => $autor['url'],
	);

	// jobTitle opisuje człowieka, nie firmę - przy redakcji byłby błędem.
	if ( $autor['stanowisko'] && ! $autor['zespol'] ) {
		$osoba['jobTitle'] = $autor['stanowisko'];
	}

	if ( $autor['bio'] ) {
		$osoba['description'] = ntc_schema_tekst( $autor['bio'] );
	}

	if ( $autor['profile'] ) {
		$osoba['sameAs'] = $autor['profile'];
	}

	if ( $autor['foto'] ) {
		$osoba['image'] = $autor['foto'];
	}

	if ( ! $autor['zespol'] ) {
		$osoba['worksFor'] = array(
			'@type' => 'Organization',
			'name'  => $co['name'],
			'url'   => home_url( '/' ),
		);
	}

	$wydawca = array(
		'@type' => 'Organization',
		'name'  => $co['name'],
		'url'   => home_url( '/' ),
		'logo'  => array(
			'@type' => 'ImageObject',
			'url'   => ntc_img( 'logo-ntc-andar' ),
		),
	);

	$dane = array(
		'@context'         => 'https://schema.org',
		'@type'            => 'BlogPosting',
		'mainEntityOfPage' => array(
			'@type' => 'WebPage',
			'@id'   => get_permalink(),
		),
		'headline'         => ntc_schema_tekst( get_the_title() ),
		'datePublished'    => get_the_date( 'c' ),
		'dateModified'     => get_the_modified_date( 'c' ),
		'author'           => $osoba,
		'publisher'        => $wydawca,
		'inLanguage'       => get_bloginfo( 'language' ),
	);

	$zajawka = ntc_schema_tekst( get_the_excerpt() );

	if ( $zajawka ) {
		$dane['description'] = $zajawka;
	}

	if ( has_post_thumbnail() ) {
		$dane['image'] = get_the_post_thumbnail_url( null, 'large' );
	}

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $dane, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'ntc_wpis_schema' );

/**
 * Dane strukturalne strony autora.
 */
function ntc_autor_schema() {
	if ( ! is_author() ) {
		return;
	}

	$autor_id = (int) get_queried_object_id();
	$profile  = array_filter(
		array(
			get_user_meta( $autor_id, 'ntc_linkedin', true ),
			get_user_meta( $autor_id, 'ntc_orcid', true ),
		)
	);

	$zespol = 'zespol' === get_user_meta( $autor_id, 'ntc_typ_autora', true );
	$firma  = array(
		'@type' => 'Organization',
		'name'  => ntc_company()['name'],
		'url'   => home_url( '/' ),
	);

	$podmiot = array_filter(
		array(
			'@type'       => $zespol ? 'Organization' : 'Person',
			'@id'         => ntc_autor_schema_id( $autor_id ),
			'name'        => ntc_schema_tekst( get_the_author_meta( 'display_name', $autor_id ) ),
			'jobTitle'    => $zespol ? '' : get_user_meta( $autor_id, 'ntc_stanowisko', true ),
			'description' => ntc_schema_tekst( get_the_author_meta( 'description', $autor_id ) ),
			'image'       => ntc_autor_foto( $autor_id, 240 ),
			'url'         => get_author_posts_url( $autor_id ),
			'sameAs'      => array_values( $profile ),
		)
	);

	// Osoba pracuje dla firmy, redakcja jest jej częścią - worksFor opisuje
	// zatrudnienie i przy Organization byłoby użyte wbrew słownikowi schema.org.
	$podmiot[ $zespol ? 'parentOrganization' : 'worksFor' ] = $firma;

	$dane = array(
		'@context'   => 'https://schema.org',
		'@type'      => 'ProfilePage',
		'mainEntity' => $podmiot,
	);

	printf(
		'<script type="application/ld+json">%s</script>' . "\n",
		wp_json_encode( $dane, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
	);
}
add_action( 'wp_head', 'ntc_autor_schema' );

/**
 * Odnośnik rel="author" w nagłówku dokumentu.
 *
 * Uzupełnia dane strukturalne o powiązanie na poziomie samego HTML-u.
 */
function ntc_autor_link() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$autor = ntc_autor();

	if ( $autor ) {
		printf( '<link rel="author" href="%s" />' . "\n", esc_url( $autor['url'] ) );
	}
}
add_action( 'wp_head', 'ntc_autor_link' );
