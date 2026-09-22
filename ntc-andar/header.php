<?php
/**
 * Nagłówek dokumentu i pasek nawigacji.
 *
 * Pasek ma dwa warianty kolorystyczne. Domyślny jest przezroczysty i bieleje po
 * przewinięciu (klasę .scrolled dokłada assets/js/main.js). Strony z jasnym hero,
 * czyli kontakt, dostają wariant lity: biały od pierwszej klatki.
 *
 * Nawigacja jest w obu ta sama. Wcześniej kontakt pokazywał sam link powrotu,
 * przez co ktoś, kto wszedł tam prosto z wyszukiwarki, nie miał jak przejść do
 * oferty ani przełączyć języka.
 *
 * Pozycje menu pochodzą z Wygląd → Menu (lokalizacja "Menu główne"). Dopóki
 * nikt go nie zbudował, motyw pokazuje listę z projektu, żeby świeża
 * instalacja nie została bez nawigacji.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

// Pasek jest biały na każdej podstronie i od pierwszej klatki, nie dopiero po
// przewinięciu. Klasa zostaje w szablonie, bo część reguł nadal się do niej
// odwołuje, ale nie zależy już od rodzaju strony.
$ntc_solid = true;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="skip-link" href="#main"><?php ntc_e( 'nav.skip' ); ?></a>

<nav class="nav<?php echo $ntc_solid ? ' nav--solid' : ''; ?>">
	<div class="nav-inner">
		<?php echo ntc_logo_markup(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- markup budowany w funkcji z escapowaniem. ?>

		<?php
		// Prototyp na wąskim ekranie po prostu chował menu, więc na telefonie
		// nie było jak dojść do oferty ani kontaktu. Przycisk pojawia się
		// poniżej 600px, czyli dokładnie tam, gdzie menu znikało.
		?>
		<button class="nav-toggle" type="button"
			aria-controls="ntc-nav-links" aria-expanded="false"
			aria-label="<?php echo esc_attr( ntc_raw( 'nav.menu' ) ); ?>">
			<span class="nav-toggle-bar" aria-hidden="true"></span>
			<span class="nav-toggle-bar" aria-hidden="true"></span>
			<span class="nav-toggle-bar" aria-hidden="true"></span>
		</button>

		<div class="nav-menu" id="ntc-nav-links">
			<?php
			$ntc_oferta = ntc_page_url( 'oferta' );

			ntc_menu(
				'primary',
				array(
					ntc_raw( 'nav.about' )   => ntc_home_anchor( 'about' ),
					// "Jak działamy" ma teraz własną podstronę z pełnymi tekstami,
					// na stronie głównej został z nich tylko skrót.
					ntc_raw( 'nav.how' )     => ntc_page_url( 'jak-dzialamy' ),
					// Oferta rozwija pełny zakres - inaczej trzeba wejść na
					// podstronę, żeby w ogóle zobaczyć, co firma prowadzi.
					ntc_raw( 'nav.offer' )   => array(
						'url' => $ntc_oferta,
						'sub' => array(
							ntc_raw( 'footer.offer_api' )         => ntc_offer_url( 'substancje-czynne-api/' ),
							ntc_raw( 'footer.offer_probiotics' )  => ntc_offer_url( 'probiotyki/' ),
							ntc_raw( 'footer.offer_lactoferrin' ) => ntc_offer_url( 'laktoferyna/' ),
							ntc_raw( 'footer.offer_colostrum' )   => ntc_offer_url( 'colostrum/' ),
							ntc_raw( 'footer.offer_proteins' )    => ntc_offer_url( 'bialka-mleka/' ),
							ntc_raw( 'footer.offer_collagen' )    => ntc_offer_url( 'kolagen/' ),
							ntc_raw( 'footer.offer_machines' )    => ntc_offer_url( 'maszyny-i-uslugi/#cat-maszyny' ),
							ntc_raw( 'footer.offer_services' )    => ntc_offer_url( 'maszyny-i-uslugi/#cat-uslugi' ),
						),
					),
					ntc_raw( 'nav.quality' ) => ntc_home_anchor( 'quality' ),
				),
				'nav-links',
				2
			);
			?>

			<div class="nav-extra">
				<a href="<?php echo esc_url( ntc_page_url( 'kontakt' ) ); ?>" class="nav-cta"><?php ntc_e( 'nav.contact' ); ?></a>

				<?php
				// Przełącznik pokazuje języki inne niż bieżący. Z Polylangiem
				// pozycje i adresy przychodzą z wtyczki, bez niej z ?lang=.
				foreach ( ntc_language_links() as $ntc_link ) :
					if ( $ntc_link['current'] || ! $ntc_link['url'] ) {
						continue;
					}
					?>
					<a class="nav-lang" href="<?php echo esc_url( $ntc_link['url'] ); ?>"
						hreflang="<?php echo esc_attr( $ntc_link['slug'] ); ?>"
						title="<?php echo esc_attr( ntc_raw( 'nav.lang_label' ) ); ?>">
						<?php echo esc_html( strtoupper( $ntc_link['slug'] ) ); ?>
					</a>
				<?php endforeach; ?>
			</div>
		</div>

	</div>
</nav>

<main id="main">
