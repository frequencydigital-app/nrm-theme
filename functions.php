<?php
/**
 * Now Regional Media theme.
 */

defined( 'ABSPATH' ) || exit;

define( 'NRM_VERSION', '1.0.4' );
define( 'NRM_DIR', get_template_directory() );
define( 'NRM_URI', get_template_directory_uri() );

require NRM_DIR . '/inc/titles.php';
require NRM_DIR . '/inc/lockups.php';
require NRM_DIR . '/inc/enquiry.php';
require NRM_DIR . '/inc/customizer.php';
require NRM_DIR . '/inc/updater.php';

/**
 * Theme supports.
 */
function nrm_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	remove_theme_support( 'widgets' );
	add_image_size( 'nrm-card', 1200, 720, true );
	add_image_size( 'nrm-card-small', 600, 360, true );
}
add_action( 'after_setup_theme', 'nrm_setup' );

/**
 * Fonts, tokens, site styles and a small script.
 */
function nrm_assets() {
	$fonts = 'https://fonts.googleapis.com/css2?family=Gabarito:wght@700;900&family=Karla:wght@400;700;800&display=swap';
	wp_enqueue_style( 'nrm-fonts', $fonts, array(), null );
	wp_enqueue_style( 'nrm-tokens', NRM_URI . '/assets/nrm-tokens.css', array(), NRM_VERSION );
	wp_enqueue_style( 'nrm-site', NRM_URI . '/assets/site.css', array( 'nrm-tokens' ), NRM_VERSION );
	wp_enqueue_script( 'nrm-site', NRM_URI . '/assets/site.js', array(), NRM_VERSION, array( 'strategy' => 'defer' ) );
	wp_dequeue_style( 'wp-block-library' );
	wp_dequeue_style( 'classic-theme-styles' );
	wp_dequeue_style( 'global-styles' );
}
add_action( 'wp_enqueue_scripts', 'nrm_assets', 20 );

/**
 * Preconnect for Google Fonts.
 */
function nrm_preconnect( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' => 'anonymous' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'nrm_preconnect', 10, 2 );

/**
 * Head: meta description, Open Graph, favicon fallbacks, GA4.
 */
function nrm_head() {
	$desc = esc_attr( nrm_option( 'meta_description' ) );
	$og   = nrm_option( 'og_image' ) ?: NRM_URI . '/assets/icons/og-image.png';
	echo '<meta name="description" content="' . $desc . '">' . "\n";
	echo '<meta property="og:site_name" content="Now Regional Media">' . "\n";
	echo '<meta property="og:title" content="' . esc_attr( wp_get_document_title() ) . '">' . "\n";
	echo '<meta property="og:description" content="' . $desc . '">' . "\n";
	echo '<meta property="og:url" content="' . esc_url( home_url( '/' ) ) . '">' . "\n";
	echo '<meta property="og:type" content="website">' . "\n";
	if ( $og ) {
		echo '<meta property="og:image" content="' . esc_url( $og ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
	}
	if ( ! has_site_icon() ) {
		$i = NRM_URI . '/assets/icons/';
		foreach ( array( 16, 32, 48 ) as $s ) {
			echo '<link rel="icon" type="image/png" sizes="' . $s . 'x' . $s . '" href="' . esc_url( $i . 'icon-' . $s . '.png' ) . '">' . "\n";
		}
		echo '<link rel="apple-touch-icon" sizes="180x180" href="' . esc_url( $i . 'icon-180.png' ) . '">' . "\n";
		echo '<meta property="og:image:width" content="1200"><meta property="og:image:height" content="630">' . "\n";
	}
	$ga = trim( (string) nrm_option( 'ga4_id' ) );
	if ( $ga && ! is_user_logged_in() ) {
		$ga = esc_attr( $ga );
		echo '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $ga . '"></script>' . "\n";
		echo "<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','" . $ga . "');</script>\n";
	}
}
add_action( 'wp_head', 'nrm_head', 1 );

/**
 * Document title: fixed for the front page as per the brief.
 */
function nrm_document_title( $parts ) {
	if ( is_front_page() ) {
		$parts['title']   = 'Now Regional Media: local guides for coastal NSW';
		$parts['tagline'] = '';
		$parts['site']    = '';
	}
	return $parts;
}
add_filter( 'document_title_parts', 'nrm_document_title' );
add_filter( 'document_title_separator', function () { return ':'; } );

/**
 * Trim head clutter.
 */
remove_action( 'wp_head', 'wp_generator' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );

/**
 * Non-breaking "Now" after a title name in running text.
 */
function nrm_nbsp_now( $text ) {
	return preg_replace( '/(Port|Rivers|Hunter|Coffs|region) Now\b/', '$1&nbsp;Now', $text );
}

/**
 * Nav items shared by header and footer.
 */
function nrm_nav_items() {
	return array(
		'#titles'    => 'Titles',
		'#advertise' => 'Advertise',
		'#partner'   => 'Partner with us',
		'#about'     => 'About',
	);
}

/**
 * Anchor links need the home URL when not on the front page.
 */
function nrm_anchor( $hash ) {
	return is_front_page() ? $hash : home_url( '/' ) . $hash;
}
