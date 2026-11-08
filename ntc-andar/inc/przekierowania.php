<?php
/**
 * Przekierowania 301 ze starej strony www.ntcandar.com.pl.
 *
 * Poprzednia strona (Avada + Polylang) miała inne adresy: oferta leżała w
 * głównym katalogu (/probiotyki/), a wersja angielska pod /en/ z własnymi
 * nazwami stron. Linki do niej żyją dalej w Google, w stopkach maili i u
 * partnerów, więc każdy stary adres prowadzi na swój nowy odpowiednik
 * zamiast na błąd 404.
 *
 * Lista jest zamknięta - powstała z mapy strony starej witryny
 * (wp-sitemap.xml) w dniu przeniesienia, 8.11.2026.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Stary adres (ścieżka bez ukośnika na końcu) => nowy adres względem strony.
 *
 * @return array<string,string>
 */
function ntc_stare_adresy() {
	return array(
		'/en'                              => '/?lang=en',
		'/en/en-ntc-andar'                 => '/?lang=en',
		'/en/en-ntc-andar-2'               => '/?lang=en',
		'/en/quality-policy'               => '/jakosc/?lang=en',
		'/en/offer'                        => '/oferta/?lang=en',
		'/en/api'                          => '/oferta/substancje-czynne-api/?lang=en',
		'/en/probiotics'                   => '/oferta/probiotyki/?lang=en',
		'/en/lactoferrin'                  => '/oferta/laktoferyna/?lang=en',
		'/en/contact_us'                   => '/kontakt/?lang=en',
		'/en/production_equipment_offer'   => '/oferta/maszyny-i-uslugi/?lang=en',
		'/en/author/m-roszkowski'          => '/?lang=en',
		'/przykladowa-strona'              => '/',
		'/substancje-aktywne-api'          => '/oferta/substancje-czynne-api/',
		'/maszyny'                         => '/oferta/maszyny-i-uslugi/',
		'/probiotyki'                      => '/oferta/probiotyki/',
		'/laktoferyna'                     => '/oferta/laktoferyna/',
		'/ekstrakty-roslinne'              => '/oferta/',
		'/author/m-roszkowski'             => '/',
	);
}

/**
 * Przekierowanie, zanim WordPress zacznie zgadywać.
 *
 * Priorytet 0 na template_redirect: bez tego WordPress sam dopasowywał
 * podobne adresy i np. /ekstrakty-roslinne/ prowadziło na plik zdjęcia
 * o tej nazwie, a /en/offer/ na przypadkową treść.
 */
function ntc_przekieruj_stare_adresy() {
	if ( is_admin() || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}

	$sciezka = wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- porównujemy z zamkniętą listą.
	$sciezka = strtolower( untrailingslashit( (string) $sciezka ) );

	// Strona w podkatalogu - ścieżki z mapy są liczone od jej korzenia.
	$baza = untrailingslashit( (string) wp_parse_url( home_url(), PHP_URL_PATH ) );

	if ( $baza && 0 === strpos( $sciezka, $baza ) ) {
		$sciezka = substr( $sciezka, strlen( $baza ) );
	}

	$mapa = ntc_stare_adresy();

	if ( isset( $mapa[ $sciezka ] ) ) {
		wp_safe_redirect( home_url( $mapa[ $sciezka ] ), 301, 'NTC Andar' );
		exit;
	}
}
add_action( 'template_redirect', 'ntc_przekieruj_stare_adresy', 0 );
