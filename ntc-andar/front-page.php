<?php
/**
 * Strona startowa.
 *
 * Identyczna jak page.php - istnieje, żeby WordPress użył jej dla strony
 * ustawionej w Ustawienia → Czytanie i żeby dało się ją w przyszłości
 * rozdzielić bez ruszania pozostałych stron.
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
