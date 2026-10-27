<?php
/**
 * Theme updates from GitHub.
 *
 * Watches the main branch of github.com/frequencydigital-app/nrm-theme. When the Version in
 * its style.css is newer than the installed version, WordPress offers the update under
 * Dashboard > Updates like any other theme. Versions are only bumped when a build is ready.
 */

defined( 'ABSPATH' ) || exit;

define( 'NRM_UPDATE_REPO', 'frequencydigital-app/nrm-theme' );
define( 'NRM_UPDATE_BRANCH', 'main' );

function nrm_update_remote() {
	$cached = get_site_transient( 'nrm_theme_release' );
	if ( false !== $cached ) {
		return $cached;
	}
	$res = wp_remote_get(
		'https://raw.githubusercontent.com/' . NRM_UPDATE_REPO . '/' . NRM_UPDATE_BRANCH . '/style.css?t=' . time(),
		array( 'timeout' => 8, 'headers' => array( 'User-Agent' => 'nrm-theme/' . NRM_VERSION ) )
	);
	$release = array();
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		if ( preg_match( '/^\s*Version:\s*([0-9][0-9a-z.\-]*)/mi', wp_remote_retrieve_body( $res ), $m ) ) {
			$release = array(
				'version' => $m[1],
				'zip'     => 'https://github.com/' . NRM_UPDATE_REPO . '/archive/refs/heads/' . NRM_UPDATE_BRANCH . '.zip',
				'url'     => 'https://github.com/' . NRM_UPDATE_REPO,
			);
		}
	}
	set_site_transient( 'nrm_theme_release', $release, HOUR_IN_SECONDS );
	return $release;
}

function nrm_update_check( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}
	$slug    = get_template();
	$release = nrm_update_remote();
	if ( ! $release ) {
		return $transient;
	}
	$item = array(
		'theme'       => $slug,
		'new_version' => $release['version'],
		'url'         => $release['url'],
		'package'     => $release['zip'],
	);
	// Compare with the version WordPress just read from disk, not the constant loaded at
	// request start: straight after an upgrade the constant is still the old version.
	$installed = $transient->checked[ $slug ] ?? NRM_VERSION;
	if ( version_compare( $release['version'], $installed, '>' ) ) {
		$transient->response[ $slug ] = $item;
	} else {
		$transient->no_update[ $slug ] = $item;
	}
	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'nrm_update_check' );

/**
 * "Check again" on Dashboard > Updates (force-check=1), and installing an update, clear the cached GitHub answer,
 * so a release pushed in the last hour shows straight away.
 */
function nrm_update_force_check() {
	if ( isset( $_GET['force-check'] ) && current_user_can( 'update_themes' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		delete_site_transient( 'nrm_theme_release' );
	}
}
add_action( 'load-update-core.php', 'nrm_update_force_check' );
add_action( 'load-themes.php', 'nrm_update_force_check' );
add_action( 'upgrader_process_complete', static function () {
	delete_site_transient( 'nrm_theme_release' );
} );

/**
 * GitHub zips unpack to "nrm-theme-main/": rename to the theme folder.
 */
function nrm_update_source( $source, $remote_source, $upgrader, $hook_extra ) {
	if ( empty( $hook_extra['theme'] ) || get_template() !== $hook_extra['theme'] ) {
		return $source;
	}
	global $wp_filesystem;
	$target = trailingslashit( $remote_source ) . get_template() . '/';
	if ( untrailingslashit( $source ) === untrailingslashit( $target ) ) {
		return $source;
	}
	if ( $wp_filesystem->move( $source, $target, true ) ) {
		return $target;
	}
	return new WP_Error( 'nrm_rename_failed', 'Could not rename the update folder.' );
}
add_filter( 'upgrader_source_selection', 'nrm_update_source', 10, 4 );

/**
 * Clear the cached version whenever WordPress clears its own theme update cache
 * (Updates > Check again, after an upgrade, wp_clean_update_cache()), so a fresh
 * check always asks GitHub again.
 */
add_action( 'delete_site_transient_update_themes', function () { delete_site_transient( 'nrm_theme_release' ); } );
add_action( 'upgrader_process_complete', function () { delete_site_transient( 'nrm_theme_release' ); } );
// Priority 1 so this runs before WordPress's own wp_update_themes() on the same hook.
add_action( 'load-update-core.php', function () {
	if ( isset( $_GET['force-check'] ) ) {
		delete_site_transient( 'nrm_theme_release' );
	}
}, 1 );
