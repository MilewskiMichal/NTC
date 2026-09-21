<?php
/**
 * Template Name: NTC - Oferta
 *
 * Zachowany dla zgodności ze stronami, które dostały ten szablon przed
 * przejściem na bloki. Renderuje bloki tak samo jak page.php - różnicę robi
 * tylko to, że arkusz oferty schodzi także wtedy, gdy ktoś usunie z treści
 * wszystkie bloki kategorii.
 *
 * Nowych stron nie trzeba już nim oznaczać: wystarczy wstawić wzorzec
 * "NTC - Oferta".
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();
	the_content();
endwhile;

get_footer();
