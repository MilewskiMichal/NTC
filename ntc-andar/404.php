<?php
/**
 * Strona 404.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="error-page">
	<div class="section-inner">
		<h1 class="section-title"><?php ntc_e( '404.title' ); ?></h1>
		<p><?php ntc_e( '404.text' ); ?></p>
		<a href="<?php echo esc_url( ntc_url( home_url( '/' ) ) ); ?>" class="btn-primary"><?php ntc_e( '404.home' ); ?></a>
	</div>
</section>

<?php
get_footer();
