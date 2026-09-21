<?php
/**
 * Archiwa wpisów: kategoria, tag, autor, data.
 *
 * Ten sam układ co lista bloga - różni się tylko nagłówkiem, który mówi, co
 * właściwie oglądamy.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z ntc_sub_hero().
echo ntc_sub_hero(
	ntc_blog_title(),
	wp_strip_all_tags( get_the_archive_title() ),
	wp_strip_all_tags( get_the_archive_description() )
);

ntc_the_post_list();

ntc_the_blog_cta( ntc_raw( 'blog.cta_head' ), ntc_raw( 'blog.cta_sub' ) );

get_footer();
