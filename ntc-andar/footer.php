<?php
/**
 * Stopka.
 *
 * Ta sama na każdej stronie. Kontakt miał wcześniej wąską wersję, żeby nie
 * powtarzać danych teleadresowych z karty powyżej, ale Klient chce jednej
 * stopki w całym serwisie - nawigacja i oferta w stopce są potrzebne także
 * tam.
 *
 * Menu bierze się z Wygląd → Menu, dane firmowe z Dostosuj → NTC - Dane
 * firmowe. W kodzie nie ma już żadnego adresu ani numeru telefonu.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

$ntc_co   = ntc_company();
$ntc_copy = (string) get_theme_mod( 'ntc_footer_copy', ntc_raw( 'footer.copy' ) );
?>
</main>

<footer class="footer">
	<div class="footer-inner">
		<div class="footer-grid">

			<div>
				<div class="footer-logo-txt"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></div>
				<p class="footer-desc">
					<?php echo esc_html( ntc_pll( (string) get_theme_mod( 'ntc_footer_desc', ntc_raw( 'footer.desc' ) ) ) ); ?>
				</p>
			</div>

			<div>
				<div class="footer-heading"><?php ntc_e( 'footer.nav_heading' ); ?></div>
				<?php
				ntc_menu(
					'footer_nav',
					array(
						ntc_raw( 'nav.about' )   => ntc_home_anchor( 'about' ),
						ntc_raw( 'nav.how' )     => ntc_page_url( 'jak-dzialamy' ),
						// Oferta przed Jakością, tak jak w menu górnym.
						ntc_raw( 'nav.offer' )   => ntc_page_url( 'oferta' ),
						ntc_raw( 'nav.quality' ) => ntc_home_anchor( 'quality' ),
						ntc_raw( 'blog.title' )  => ntc_blog_url(),
						ntc_raw( 'nav.contact' ) => ntc_page_url( 'kontakt' ),
					)
				);
				?>
			</div>

			<div>
				<div class="footer-heading"><?php ntc_e( 'footer.offer_heading' ); ?></div>
				<?php
				ntc_menu(
					'footer_offer',
					array(
						// Osiem pozycji odwzorowuje stan faktyczny oferty. "Maszyny"
						// i "Usługi" prowadzą do jednej podstrony, w dwa różne miejsca.
						ntc_raw( 'footer.offer_api' )         => ntc_page_url( 'oferta' ) . 'substancje-czynne-api/',
						ntc_raw( 'footer.offer_probiotics' )  => ntc_page_url( 'oferta' ) . 'probiotyki/',
						ntc_raw( 'footer.offer_lactoferrin' ) => ntc_page_url( 'oferta' ) . 'laktoferyna/',
						ntc_raw( 'footer.offer_colostrum' )   => ntc_page_url( 'oferta' ) . 'colostrum/',
						ntc_raw( 'footer.offer_proteins' )    => ntc_page_url( 'oferta' ) . 'bialka-mleka/',
						ntc_raw( 'footer.offer_collagen' )    => ntc_page_url( 'oferta' ) . 'kolagen/',
						ntc_raw( 'footer.offer_machines' )    => ntc_page_url( 'oferta' ) . 'maszyny-i-uslugi/#cat-maszyny',
						ntc_raw( 'footer.offer_services' )    => ntc_page_url( 'oferta' ) . 'maszyny-i-uslugi/#cat-uslugi',
					)
				);
				?>
			</div>

			<div>
				<div class="footer-heading"><?php ntc_e( 'footer.contact_heading' ); ?></div>
				<div class="footer-contact-item">
					<?php echo esc_html( $ntc_co['name'] ); ?><br />
					<?php echo esc_html( $ntc_co['street'] ); ?><br />
					<?php echo esc_html( $ntc_co['city'] ); ?>
				</div>
				<div class="footer-contact-item">
					<a href="<?php echo esc_url( $ntc_co['phone_href'] ); ?>"><?php echo esc_html( $ntc_co['phone'] ); ?></a>
				</div>
				<div class="footer-contact-item">
					<a href="mailto:<?php echo esc_attr( $ntc_co['email'] ); ?>"><?php echo esc_html( $ntc_co['email'] ); ?></a>
				</div>
				<div class="footer-contact-item footer-contact-item--fine">
					<?php // Komplet danych rejestrowych, tak jak w stopce starego serwisu. ?>
					NIP: <?php echo esc_html( $ntc_co['nip'] ); ?><br />
					REGON: <?php echo esc_html( $ntc_co['regon'] ); ?><br />
					KRS: <?php echo esc_html( $ntc_co['krs'] ); ?>
				</div>
			</div>

		</div>

		<div class="footer-bottom">
			<div class="footer-copy"><?php echo esc_html( ntc_pll( $ntc_copy ) ); ?></div>
			<?php
			// Lokalizacja "Stopka - dokumenty" jest pusta dopóki nie powstaną
			// strony polityki prywatności i OWS - wtedy zamiast martwych
			// odnośników nie ma nic.
			if ( has_nav_menu( 'footer_legal' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'footer_legal',
						'menu_class'     => 'footer-legal',
						'container'      => false,
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
