<?php
/**
 * Ikona strony (favicon) z logo NTC Andar.
 *
 * Bez niej Chrome pokazywał na karcie szarą planetę: strona nie miała ikony, a
 * adres /favicon.ico serwer zwraca jako 404. Ikona to uproszczone logo klienta
 * (logo NTC Andar uproszczone - kolor.pdf): "NTC" w obwódce, wektorowo, na
 * przezroczystym tle. Wyjątek to wersja na iPhone'a, która ma białe tło, bo
 * iOS zamienia przezroczystość na czarne.
 *
 * Pliki leżą w assets/favicon/, a źródłem jest favicon.svg. WordPress wypisuje
 * znaczniki ikony (wp_site_icon - na stronie, w kokpicie i przy logowaniu)
 * tylko wtedy, gdy ma ustawioną "Ikonę witryny", więc podsuwamy mu nasze pliki
 * filtrem. Ikona ustawiona w Dostosuj → Tożsamość witryny dalej wygrywa.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres pliku ikony z wersją po dacie zmiany pliku.
 *
 * Przeglądarki trzymają ikony bardzo długo. Nowy adres po podmianie pliku
 * wymusza pobranie nowej ikony.
 *
 * @param string $plik Nazwa pliku w assets/favicon/.
 * @return string
 */
function ntc_ikona_url( $plik ) {
	$sciezka = get_template_directory() . '/assets/favicon/' . $plik;
	$wersja  = file_exists( $sciezka ) ? (string) filemtime( $sciezka ) : '1';

	return add_query_arg( 'v', $wersja, get_template_directory_uri() . '/assets/favicon/' . $plik );
}

/**
 * Czy w kokpicie ustawiono własną ikonę witryny.
 *
 * Bez has_site_icon(): ono samo pyta filtr niżej, więc zawsze mówiłoby "tak".
 *
 * @return bool
 */
function ntc_ikona_z_kokpitu() {
	return (bool) get_option( 'site_icon' );
}

/**
 * Nasze pliki w miejsce brakującej ikony witryny.
 *
 * WordPress pyta o rozmiary 32, 192, 180 (iPhone), 270 i 512. Do tego
 * /favicon.ico, jeśli trafi do WordPressa, przekierowuje na rozmiar 32.
 *
 * @param string $url     Adres ikony z kokpitu albo pusty.
 * @param int    $rozmiar Rozmiar w pikselach.
 * @return string
 */
function ntc_ikona_filtr( $url, $rozmiar ) {
	if ( ntc_ikona_z_kokpitu() ) {
		return $url;
	}

	$rozmiar = (int) $rozmiar;

	if ( $rozmiar <= 48 ) {
		return ntc_ikona_url( 'favicon.ico' );
	}

	if ( 180 === $rozmiar ) {
		return ntc_ikona_url( 'apple-touch-icon.png' );
	}

	return ntc_ikona_url( $rozmiar <= 192 ? 'icon-192.png' : 'icon-512.png' );
}
add_filter( 'get_site_icon_url', 'ntc_ikona_filtr', 10, 2 );

/**
 * Wersja wektorowa obok plików PNG i ICO - ostra w każdym rozmiarze.
 *
 * Chrome wybiera ją przed plikiem .ico, bo ten ma w znaczniku rozmiar 32x32.
 *
 * @param string[] $tagi Znaczniki ikony od WordPressa.
 * @return string[]
 */
function ntc_ikona_svg( $tagi ) {
	if ( ! ntc_ikona_z_kokpitu() ) {
		$tagi[] = sprintf( '<link rel="icon" href="%s" type="image/svg+xml" />', esc_url( ntc_ikona_url( 'favicon.svg' ) ) );
	}

	return $tagi;
}
add_filter( 'site_icon_meta_tags', 'ntc_ikona_svg' );
