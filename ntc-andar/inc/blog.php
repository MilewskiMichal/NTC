<?php
/**
 * Blog - lista wpisów i elementy wspólne szablonów.
 *
 * Blog jest jedyną częścią serwisu, której nie składa się z bloków: liczba
 * wpisów rośnie sama, więc układ musi być w szablonie, a nie w treści strony.
 * Wygląd trzyma się reszty: ten sam ciemny nagłówek, ta sama typografia,
 * kafelki jak w sekcji oferty.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adres strony z listą wpisów.
 *
 * @return string
 */
function ntc_blog_url() {
	$id = (int) get_option( 'page_for_posts' );

	return ntc_url( $id ? get_permalink( $id ) : home_url( '/' ) );
}

/**
 * Nazwa strony bloga - z kokpitu, żeby dało się ją zmienić bez ruszania kodu.
 *
 * @return string
 */
function ntc_blog_title() {
	$id = (int) get_option( 'page_for_posts' );

	return $id ? get_the_title( $id ) : ntc_raw( 'blog.title' );
}

/**
 * Kafelek wpisu na liście.
 *
 * Bez zdjęcia wyróżniającego kafelek dostaje gradient marki zamiast pustego
 * prostokąta - wpisy branżowe rzadko mają grafikę, a siatka nie może się przez
 * to sypać.
 */
function ntc_the_post_card() {
	$kategorie = get_the_category();
	?>
	<article <?php post_class( 'post-card' ); ?>>
		<a class="post-card-media<?php echo has_post_thumbnail() ? '' : ' post-card-media--pusta'; ?>"
			href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
			<?php if ( has_post_thumbnail() ) : ?>
				<?php the_post_thumbnail( 'large', array( 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<span class="post-card-znak">NTC</span>
			<?php endif; ?>
		</a>
		<div class="post-card-body">
			<div class="post-card-meta">
				<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
				<?php if ( $kategorie ) : ?>
					<span class="post-card-kat"><?php echo esc_html( $kategorie[0]->name ); ?></span>
				<?php endif; ?>
			</div>
			<h2 class="post-card-title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>
			<p class="post-card-excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<a class="post-card-link" href="<?php the_permalink(); ?>">
				<?php ntc_e( 'blog.read_more' ); ?> <span aria-hidden="true">&rarr;</span>
			</a>
		</div>
	</article>
	<?php
}

/**
 * Lista wpisów razem z paginacją albo komunikat, gdy nic nie ma.
 *
 * Tego samego układu używa strona bloga i archiwa kategorii, więc siedzi w
 * jednym miejscu.
 */
function ntc_the_post_list() {
	?>
	<section class="section post-list">
		<div class="section-inner">
			<?php if ( have_posts() ) : ?>
				<div class="post-grid">
					<?php
					while ( have_posts() ) :
						the_post();
						ntc_the_post_card();
					endwhile;
					?>
				</div>

				<?php
				the_posts_pagination(
					array(
						'mid_size'  => 1,
						'prev_text' => ntc_raw( 'table.prev' ),
						'next_text' => ntc_raw( 'table.next' ),
					)
				);
				?>
			<?php else : ?>
				<div class="post-pusto">
					<p class="post-pusto-title"><?php ntc_e( 'blog.empty_title' ); ?></p>
					<p class="post-pusto-text"><?php ntc_e( 'blog.empty_text' ); ?></p>
					<a class="btn-primary" href="<?php echo esc_url( ntc_page_url( 'kontakt' ) ); ?>">
						<?php ntc_e( 'blog.empty_cta' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>
	<?php
}

/**
 * Pasek z telefonem i e-mailem pod treścią - ten sam, co na podstronach oferty.
 *
 * @param string $head Nagłówek paska.
 * @param string $sub  Zdanie pod nagłówkiem.
 */
function ntc_the_blog_cta( $head, $sub ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z renderera bloku.
	echo ntc_render_quick_cta(
		array(
			'head' => $head,
			'sub'  => $sub,
		)
	);
}
