<?php
/**
 * Formularze kontaktowe.
 *
 * Dwa warianty tego samego formularza: skrócony na ciemnym tle sekcji na
 * stronie głównej i pełny na jasnej karcie podstrony kontaktu. Oba są na
 * razie makietą - walidują pola i pokazują potwierdzenie, ale nic nie
 * wysyłają. Obsługa siedzi w assets/js/main.js, w jednym oznaczonym miejscu.
 *
 * Wyciągnięte z szablonów do osobnego pliku, bo po przejściu na bloki ten sam
 * formularz renderują dwa różne bloki.
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Wypisuje formularz kontaktowy.
 *
 * @param string $variant 'home' (skrócony) albo 'full' (pełny).
 */
function ntc_the_contact_form( $variant = 'home', $args = array() ) {
	if ( 'full' === $variant ) {
		ntc_contact_form_full( $args );
	} else {
		ntc_contact_form_home();
	}
}

/** Wariant skrócony - sekcja kontaktowa strony głównej. */
function ntc_contact_form_home() {
	?>
	<form class="contact-form" data-ntc-form novalidate>
		<label class="screen-reader-text" for="ntc-home-firma"><?php ntc_e( 'form.company_req' ); ?></label>
		<input class="form-input" id="ntc-home-firma" name="firma" required
			placeholder="<?php echo esc_attr( ntc_raw( 'form.company_req' ) ); ?>" />

		<label class="screen-reader-text" for="ntc-home-email"><?php ntc_e( 'form.email_req' ); ?></label>
		<input class="form-input" id="ntc-home-email" name="email" type="email" required
			placeholder="<?php echo esc_attr( ntc_raw( 'form.email_req' ) ); ?>" />

		<label class="screen-reader-text" for="ntc-home-tel"><?php ntc_e( 'form.phone' ); ?></label>
		<input class="form-input" id="ntc-home-tel" name="tel" type="tel"
			placeholder="<?php echo esc_attr( ntc_raw( 'form.phone' ) ); ?>" />

		<label class="screen-reader-text" for="ntc-home-msg"><?php ntc_e( 'form.message' ); ?></label>
		<textarea class="form-input" id="ntc-home-msg" name="msg"
			placeholder="<?php echo esc_attr( ntc_raw( 'form.message' ) ); ?>"></textarea>

		<label class="form-consent">
			<input type="checkbox" name="consent" required />
			<span><?php ntc_e( 'form.consent' ); ?></span>
		</label>

		<button type="submit" class="form-submit"><?php ntc_e( 'form.submit_ask' ); ?></button>
		<?php ntc_form_demo_notice(); ?>
	</form>

	<div class="form-sent" data-ntc-sent hidden>
		<div class="form-sent-check" aria-hidden="true">&check;</div>
		<div class="form-sent-title"><?php ntc_e( 'form.sent_title' ); ?></div>
		<div class="form-sent-text"><?php ntc_e( 'form.sent_text' ); ?></div>
	</div>
	<?php
}

/**
 * Wariant pełny - podstrona kontaktu i formularze pod kategoriami oferty.
 *
 * @param array $args prefix: przedrostek id pól (na stronie może stać więcej
 *                    niż jeden formularz), subject: temat wybrany z góry.
 */
function ntc_contact_form_full( $args = array() ) {
	$prefix  = isset( $args['prefix'] ) && $args['prefix'] ? sanitize_html_class( $args['prefix'] ) : 'ntc';
	$subject = isset( $args['subject'] ) ? (string) $args['subject'] : '';
	?>
	<form class="contact-form" data-ntc-form data-ntc-hide="[data-ntc-form-head]" novalidate>

		<div class="form-row">
			<div class="form-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-nazwisko"><?php ntc_e( 'form.name_req' ); ?></label>
				<input class="form-input" id="<?php echo esc_attr( $prefix ); ?>-nazwisko" name="nazwisko" required
					placeholder="<?php echo esc_attr( ntc_raw( 'form.name_ph' ) ); ?>" />
			</div>
			<div class="form-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-firma"><?php ntc_e( 'form.company_req' ); ?></label>
				<input class="form-input" id="<?php echo esc_attr( $prefix ); ?>-firma" name="firma" required
					placeholder="<?php echo esc_attr( ntc_raw( 'form.company_ph' ) ); ?>" />
			</div>
		</div>

		<div class="form-row">
			<div class="form-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-email"><?php ntc_e( 'form.email_req' ); ?></label>
				<input class="form-input" id="<?php echo esc_attr( $prefix ); ?>-email" name="email" type="email" required
					placeholder="<?php echo esc_attr( ntc_raw( 'form.email_ph' ) ); ?>" />
			</div>
			<div class="form-field">
				<label for="<?php echo esc_attr( $prefix ); ?>-tel"><?php ntc_e( 'form.phone' ); ?></label>
				<input class="form-input" id="<?php echo esc_attr( $prefix ); ?>-tel" name="tel" type="tel"
					placeholder="<?php echo esc_attr( ntc_raw( 'form.phone_ph' ) ); ?>" />
			</div>
		</div>

		<div class="form-field">
			<label for="<?php echo esc_attr( $prefix ); ?>-temat"><?php ntc_e( 'form.subject_req' ); ?></label>
			<?php
			$tematy = ntc_contact_subjects();

			// Formularz pod kategorią oferty przychodzi z tematem ustawionym z
			// góry. Gdyby ktoś zmienił listę tematów w Dostosuj i temat kategorii
			// z niej wypadł, dopisujemy go - inaczej pole wyszłoby puste, a jest
			// wymagane.
			if ( $subject && ! in_array( $subject, $tematy, true ) ) {
				array_unshift( $tematy, $subject );
			}
			?>
			<select class="form-input" id="<?php echo esc_attr( $prefix ); ?>-temat" name="temat" required>
				<option value=""><?php ntc_e( 'form.subject_choose' ); ?></option>
				<?php foreach ( $tematy as $temat ) : ?>
					<option<?php selected( $subject, $temat ); ?>><?php echo esc_html( $temat ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="form-field">
			<?php // Wymagane są tylko cztery pola: nazwisko, firma, e-mail i temat. ?>
			<label for="<?php echo esc_attr( $prefix ); ?>-msg"><?php ntc_e( 'form.message' ); ?></label>
			<textarea class="form-input" id="<?php echo esc_attr( $prefix ); ?>-msg" name="msg"
				placeholder="<?php echo esc_attr( ntc_raw( 'form.message_ph' ) ); ?>"></textarea>
		</div>

		<label class="form-consent">
			<input type="checkbox" name="consent" required />
			<span><?php ntc_e( 'form.consent_req' ); ?></span>
		</label>

		<button type="submit" class="form-submit"><?php ntc_e( 'form.submit_send' ); ?></button>
		<?php ntc_form_demo_notice(); ?>
	</form>

	<div class="sent-card" data-ntc-sent hidden>
		<div class="sent-check" aria-hidden="true">&check;</div>
		<div class="sent-title"><?php ntc_e( 'form.sent_title_long' ); ?></div>
		<div class="sent-text"><?php ntc_e( 'form.sent_text_long' ); ?></div>
	</div>
	<?php
}

/**
 * Adnotacja, że formularz jest makietą.
 *
 * Znika sama, kiedy w Dostosuj wyłączysz przełącznik "formularz jest makietą" -
 * po podpięciu prawdziwej wysyłki nie trzeba grzebać w szablonach.
 */
function ntc_form_demo_notice() {
	if ( ! get_theme_mod( 'ntc_form_demo', true ) ) {
		return;
	}

	printf( '<p class="form-demo-notice">%s</p>', esc_html( ntc_raw( 'form.demo_notice' ) ) );
}

/**
 * Tematy w rozwijanej liście - z Dostosuj, jedna pozycja na wiersz.
 *
 * @return array<int,string>
 */
function ntc_contact_subjects() {
	$raw = (string) get_theme_mod( 'ntc_contact_subjects', ntc_raw( 'form.subjects_default' ) );

	return array_values( array_filter( array_map( 'trim', explode( "\n", $raw ) ) ) );
}
