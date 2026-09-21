<?php
/**
 * Dostosuj - to, co jest wspólne dla całego serwisu i nie należy do żadnej
 * pojedynczej strony: dane teleadresowe, opis w stopce, tematy formularza.
 *
 * Treść sekcji nie jest tutaj, tylko w blokach na stronach. Tu trafia
 * wyłącznie to, co powtarza się w wielu miejscach i musi być zmieniane raz
 * (telefon widnieje w stopce, w sekcji kontaktowej i na pasku CTA).
 *
 * @package NTC_Andar
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pola danych firmowych: klucz theme_mod => etykieta i wartość domyślna.
 *
 * @return array<string,array<string,string>>
 */
function ntc_company_fields() {
	return array(
		'ntc_co_name'     => array( 'label' => 'Nazwa firmy',        'default' => 'NTC ANDAR Sp. z o.o.' ),
		'ntc_co_street'   => array( 'label' => 'Ulica i numer',      'default' => 'ul. Hlonda 2A/90' ),
		'ntc_co_city'     => array( 'label' => 'Kod i miasto',       'default' => '02-972 Warszawa' ),
		'ntc_co_phone'    => array( 'label' => 'Telefon',            'default' => '+48 22 331 67 89' ),
		'ntc_co_email'    => array( 'label' => 'E-mail',             'default' => 'biuro@ntcandar.com.pl' ),
		'ntc_co_nip'      => array( 'label' => 'NIP',                'default' => '951-250-31-11' ),
		'ntc_co_regon'    => array( 'label' => 'REGON',              'default' => '386321386' ),
		'ntc_co_krs'      => array( 'label' => 'KRS',                'default' => '0000846493' ),
	);
}

/**
 * Rejestracja opcji w Dostosuj.
 *
 * @param WP_Customize_Manager $wp_customize Manager.
 */
function ntc_customize_register( $wp_customize ) {

	/* ------------------------------------------------------ dane firmowe */

	$wp_customize->add_section(
		'ntc_company',
		array(
			'title'       => 'NTC - Dane firmowe',
			'priority'    => 30,
			'description' => 'Te dane pojawiają się w stopce, w sekcji kontaktowej i na pasku CTA. Zmiana tutaj działa wszędzie naraz.',
		)
	);

	foreach ( ntc_company_fields() as $key => $field ) {
		$wp_customize->add_setting(
			$key,
			array(
				'default'           => $field['default'],
				'sanitize_callback' => 'sanitize_text_field',
				'transport'         => 'refresh',
			)
		);

		$wp_customize->add_control(
			$key,
			array(
				'label'   => $field['label'],
				'section' => 'ntc_company',
				'type'    => 'text',
			)
		);
	}

	/* ------------------------------------------------------------ stopka */

	$wp_customize->add_section(
		'ntc_footer',
		array(
			'title'    => 'NTC - Stopka',
			'priority' => 31,
		)
	);

	$wp_customize->add_setting(
		'ntc_footer_desc',
		array(
			'default'           => 'Import i dystrybucja substancji aktywnych dla przemysłu farmaceutycznego, spożywczego, kosmetycznego i weterynaryjnego. Od 2000 roku.',
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);

	$wp_customize->add_control(
		'ntc_footer_desc',
		array(
			'label'       => 'Opis pod nazwą firmy',
			'section'     => 'ntc_footer',
			'type'        => 'textarea',
			'description' => 'Menu w stopce ustawia się w Wygląd → Menu (lokalizacje "Stopka - nawigacja" i "Stopka - oferta").',
		)
	);

	$wp_customize->add_setting(
		'ntc_footer_copy',
		array(
			'default'           => '© 2000-2026 NTC ANDAR Sp. z o.o. Wszelkie prawa zastrzeżone.',
			'sanitize_callback' => 'sanitize_text_field',
		)
	);

	$wp_customize->add_control(
		'ntc_footer_copy',
		array(
			'label'   => 'Nota o prawach autorskich',
			'section' => 'ntc_footer',
			'type'    => 'text',
		)
	);

	/* -------------------------------------------------------- formularze */

	$wp_customize->add_section(
		'ntc_forms',
		array(
			'title'    => 'NTC - Formularze',
			'priority' => 32,
		)
	);

	$wp_customize->add_setting(
		'ntc_contact_subjects',
		array(
			'default'           => ntc_raw( 'form.subjects_default' ),
			'sanitize_callback' => 'sanitize_textarea_field',
		)
	);

	$wp_customize->add_control(
		'ntc_contact_subjects',
		array(
			'label'       => 'Tematy zapytania',
			'description' => 'Jedna pozycja na wiersz. Trafiają do listy rozwijanej w formularzu na podstronie kontaktu.',
			'section'     => 'ntc_forms',
			'type'        => 'textarea',
		)
	);

	$wp_customize->add_setting(
		'ntc_form_demo',
		array(
			'default'           => true,
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);

	$wp_customize->add_control(
		'ntc_form_demo',
		array(
			'label'       => 'Pokaż adnotację, że formularz jest makietą',
			'description' => 'Zostaw włączone, dopóki formularz nie wysyła wiadomości. Po podpięciu wysyłki wyłącz - adnotacja zniknie z obu formularzy.',
			'section'     => 'ntc_forms',
			'type'        => 'checkbox',
		)
	);
}
add_action( 'customize_register', 'ntc_customize_register' );
