<?php
/**
 * Template Name: NTC - Kontakt
 *
 * Zachowany dla zgodności ze stronami, które dostały ten szablon przed
 * przejściem na bloki. Renderuje bloki tak samo jak page.php - różnicę robi
 * tylko to, że arkusz kontaktu schodzi także wtedy, gdy ktoś usunie z treści
 * blok hero kontaktu.
 *
 * Nowych stron nie trzeba już nim oznaczać: wystarczy wstawić wzorzec
 * "NTC - Kontakt".
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
