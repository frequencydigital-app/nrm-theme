<?php
/**
 * Site options (Appearance > Customise > Now Regional Media).
 */

defined( 'ABSPATH' ) || exit;

function nrm_option_defaults() {
	return array(
		'meta_description' => "Now Regional Media publishes the Now titles: what's on, where to go and who to call, region by region.",
		'contact_name'     => 'Mike Breen',
		'contact_role'     => 'Publisher',
		'contact_phone'    => '',
		'contact_email'    => 'hello@nowregionalmedia.com.au',
		'enquiry_to'       => '',
		'media_kit_url'    => '',
		'client_login_url' => 'https://adserver.nowregionalmedia.com.au/client-login/',
		'og_image'         => '',
		'ga4_id'           => '',
	);
}

function nrm_option( $key ) {
	$defaults = nrm_option_defaults();
	$value    = get_theme_mod( 'nrm_' . $key, $defaults[ $key ] ?? '' );
	return $value;
}

function nrm_customize( $wp_customize ) {
	$wp_customize->add_section(
		'nrm',
		array(
			'title'    => 'Now Regional Media',
			'priority' => 10,
		)
	);

	$fields = array(
		'contact_name'     => array( 'Contact name', 'text' ),
		'contact_role'     => array( 'Contact role', 'text' ),
		'contact_phone'    => array( 'Contact phone (blank = not shown)', 'text' ),
		'contact_email'    => array( 'Contact email (as displayed)', 'email' ),
		'enquiry_to'       => array( 'Send enquiry form to (blank = admin email)', 'email' ),
		'media_kit_url'    => array( 'Media kit URL (blank = buttons open the sales form)', 'url' ),
		'client_login_url' => array( 'Client login link in the header (blank = hidden)', 'url' ),
		'og_image'         => array( 'Open Graph image URL (1200 x 630)', 'url' ),
		'ga4_id'           => array( 'GA4 measurement ID (G-XXXXXXX)', 'text' ),
		'meta_description' => array( 'Meta description', 'textarea' ),
	);

	foreach ( $fields as $key => $f ) {
		$wp_customize->add_setting(
			'nrm_' . $key,
			array(
				'default'           => nrm_option_defaults()[ $key ],
				'sanitize_callback' => 'url' === $f[1] ? 'esc_url_raw' : ( 'email' === $f[1] ? 'sanitize_email' : 'sanitize_text_field' ),
			)
		);
		$wp_customize->add_control(
			'nrm_' . $key,
			array(
				'label'   => $f[0],
				'section' => 'nrm',
				'type'    => $f[1],
			)
		);
	}
}
add_action( 'customize_register', 'nrm_customize' );
