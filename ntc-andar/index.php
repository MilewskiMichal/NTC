<?php
/**
 * Awaryjny szablon.
 *
 * Serwis składa się z trzech ręcznie zbudowanych stron, więc index.php nie
 * powinien się normalnie pokazać. Istnieje, bo WordPress wymaga go w każdym
 * motywie, i renderuje treść wpisu w typografii serwisu - gdyby ktoś dodał
 * stronę albo wpis bez przypisanego szablonu.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<section class="section" style="padding-top:160px">
	<div class="section-inner">
		<?php
		if ( have_posts() ) :
			while ( have_posts() ) :
				the_post();
				?>
				<article <?php post_class( 'ntc-entry' ); ?>>
					<h1 class="section-title"><?php the_title(); ?></h1>
					<div class="ntc-entry-content section-sub">
						<?php the_content(); ?>
					</div>
				</article>
				<?php
			endwhile;

			the_posts_pagination();
		else :
			?>
			<h1 class="section-title"><?php ntc_e( '404.title' ); ?></h1>
			<p class="section-sub"><?php ntc_e( '404.text' ); ?></p>
			<?php
		endif;
		?>
	</div>
</section>

<?php
get_footer();
