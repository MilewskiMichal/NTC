<?php
/**
 * Pojedynczy wpis bloga.
 *
 * Treść leci przez the_content(), więc redaktor pisze wpis zwykłymi blokami
 * WordPressa. Ramy i typografię daje .ntc-entry-content, tak samo jak przy
 * dokumentach prawnych.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$ntc_kategorie = get_the_category();

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z ntc_sub_hero().
	echo ntc_sub_hero(
		$ntc_kategorie ? $ntc_kategorie[0]->name : ntc_raw( 'blog.badge' ),
		get_the_title(),
		''
	);
	?>

	<article <?php post_class( 'section post-single' ); ?>>
		<div class="section-inner">

			<?php $ntc_autor = ntc_autor(); ?>
			<div class="post-single-meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<?php if ( $ntc_autor ) : ?>
					<span class="post-single-sep" aria-hidden="true">&middot;</span>
					<a rel="author" href="<?php echo esc_url( $ntc_autor['url'] ); ?>">
						<?php echo esc_html( $ntc_autor['imie'] ); ?>
					</a>
				<?php endif; ?>
				<span class="post-single-sep" aria-hidden="true">&middot;</span>
				<a href="<?php echo esc_url( ntc_blog_url() ); ?>"><?php echo esc_html( ntc_blog_title() ); ?></a>
			</div>

			<?php if ( has_post_thumbnail() ) : ?>
				<figure class="post-single-foto">
					<?php the_post_thumbnail( 'large' ); ?>
				</figure>
			<?php endif; ?>

			<div class="ntc-entry-content post-single-tresc">
				<?php the_content(); ?>
			</div>

			<?php ntc_the_autor_box(); ?>

			<?php
			$ntc_poprzedni = get_previous_post();
			$ntc_nastepny  = get_next_post();
			?>

			<?php if ( $ntc_poprzedni || $ntc_nastepny ) : ?>
				<nav class="post-nawigacja" aria-label="<?php echo esc_attr( ntc_raw( 'blog.nav_label' ) ); ?>">
					<?php if ( $ntc_poprzedni ) : ?>
						<a class="post-nawigacja-link" href="<?php echo esc_url( ntc_url( get_permalink( $ntc_poprzedni ) ) ); ?>">
							<span class="post-nawigacja-kier"><?php ntc_e( 'blog.prev' ); ?></span>
							<span class="post-nawigacja-tytul"><?php echo esc_html( get_the_title( $ntc_poprzedni ) ); ?></span>
						</a>
					<?php endif; ?>
					<?php if ( $ntc_nastepny ) : ?>
						<a class="post-nawigacja-link post-nawigacja-link--nast" href="<?php echo esc_url( ntc_url( get_permalink( $ntc_nastepny ) ) ); ?>">
							<span class="post-nawigacja-kier"><?php ntc_e( 'blog.next' ); ?></span>
							<span class="post-nawigacja-tytul"><?php echo esc_html( get_the_title( $ntc_nastepny ) ); ?></span>
						</a>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

			<a class="post-powrot" href="<?php echo esc_url( ntc_blog_url() ); ?>">
				<span aria-hidden="true">&larr;</span> <?php ntc_e( 'blog.back' ); ?>
			</a>

		</div>
	</article>

	<?php
	ntc_the_blog_cta( ntc_raw( 'blog.cta_head' ), ntc_raw( 'blog.cta_sub' ) );

endwhile;

get_footer();
