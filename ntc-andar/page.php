<?php
/**
 * Zwykła strona - rama dla bloków.
 *
 * Po przejściu na bloki szablon nie zna już żadnej sekcji z projektu. Jego
 * cała robota to wypisać nagłówek, treść złożoną w edytorze i stopkę. Sekcje
 * to bloki NTC (inc/blocks.php), a ich układ ustawia redaktor.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	// Bloki NTC renderują pełnoekranowe sekcje z własnym marginesem, więc
	// treść nie jest tu w żaden kontener opakowana. Zwykły tekst z edytora
	// dostaje ramy z .ntc-entry-content w main.css.
	the_content();

endwhile;

get_footer();
