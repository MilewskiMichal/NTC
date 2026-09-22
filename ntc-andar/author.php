<?php
/**
 * Strona autora.
 *
 * Wpisy podpisane nazwiskiem prowadzą do profilu - wyszukiwarki liczą tę
 * ścieżkę przy ocenie wiarygodności tekstu, a czytelnik dostaje kontekst,
 * kto stoi za artykułem. Lista wpisów jest ta sama, co na blogu i w archiwach.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

get_header();

$ntc_autor_id = (int) get_queried_object_id();
$ntc_autor    = array(
	'imie'       => ntc_autor_tekst( $ntc_autor_id, 'imie' ),
	'stanowisko' => ntc_autor_tekst( $ntc_autor_id, 'stanowisko' ),
	'bio'        => ntc_autor_tekst( $ntc_autor_id, 'bio' ),
	'foto'       => ntc_autor_foto( $ntc_autor_id, 240 ),
	'inicjaly'   => ntc_autor_inicjaly( ntc_autor_tekst( $ntc_autor_id, 'imie' ) ),
	'profile'    => array_values(
		array_filter(
			array(
				get_user_meta( $ntc_autor_id, 'ntc_linkedin', true ),
				get_user_meta( $ntc_autor_id, 'ntc_orcid', true ),
				get_the_author_meta( 'url', $ntc_autor_id ),
			)
		)
	),
);

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- HTML z ntc_sub_hero().
echo ntc_sub_hero(
	ntc_raw( 'autor.badge' ),
	$ntc_autor['imie'],
	$ntc_autor['stanowisko']
);
?>

<section class="section autor-strona">
	<div class="section-inner">
		<div class="autor-profil">
			<?php ntc_the_autor_foto( $ntc_autor, 'autor-profil-foto', 120 ); ?>
			<div>
				<?php if ( $ntc_autor['bio'] ) : ?>
					<p class="autor-profil-bio"><?php echo esc_html( $ntc_autor['bio'] ); ?></p>
				<?php endif; ?>
				<?php if ( $ntc_autor['profile'] ) : ?>
					<div class="autor-box-linki">
						<?php foreach ( $ntc_autor['profile'] as $ntc_link ) : ?>
							<a href="<?php echo esc_url( $ntc_link ); ?>" rel="noopener me" target="_blank">
								<?php echo esc_html( ntc_autor_nazwa_profilu( $ntc_link ) ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
</section>

<?php
ntc_the_post_list();

ntc_the_blog_cta( ntc_raw( 'blog.cta_head' ), ntc_raw( 'blog.cta_sub' ) );

get_footer();
