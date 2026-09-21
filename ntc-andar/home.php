<?php
/**
 * Blog - lista wpisów.
 *
 * WordPress bierze ten szablon dla strony ustawionej w Ustawienia → Czytanie
 * jako "Strona wpisów". Sam nagłówek i układ kafelków są w kodzie, bo lista
 * rośnie razem z liczbą wpisów i nie da się jej złożyć w edytorze.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ntc_blog_id  = (int) get_option( 'page_for_posts' );
$ntc_blog_sub = $ntc_blog_id ? get_post_meta( $ntc_blog_id, '_ntc_blog_sub', true ) : '';

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z ntc_sub_hero().
echo ntc_sub_hero(
	ntc_raw( 'blog.badge' ),
	ntc_blog_title(),
	$ntc_blog_sub ? $ntc_blog_sub : ntc_raw( 'blog.sub' )
);

ntc_the_post_list();

ntc_the_blog_cta( ntc_raw( 'blog.cta_head' ), ntc_raw( 'blog.cta_sub' ) );

get_footer();
