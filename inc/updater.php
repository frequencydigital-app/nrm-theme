<?php
/**
 * Theme updates from GitHub releases.
 *
 * Watches github.com/frequencydigital-app/nrm-theme. When a release tag is newer than the
 * installed version, WordPress offers it under Dashboard > Updates like any other theme.
 */

defined( 'ABSPATH' ) || exit;

define( 'NRM_UPDATE_REPO', 'frequencydigital-app/nrm-theme' );

function nrm_update_remote() {
	$cached = get_site_transient( 'nrm_theme_release' );
	if ( false !== $cached ) {
		return $cached;
	}
	$res = wp_remote_get(
		'https://api.github.com/repos/' . NRM_UPDATE_REPO . '/releases/latest',
		array(
			'timeout' => 8,
			'headers' => array( 'Accept' => 'application/vnd.github+json', 'User-Agent' => 'nrm-theme/' . NRM_VERSION ),
		)
	);
	$release = array();
	if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
		$body = json_decode( wp_remote_retrieve_body( $res ), true );
		if ( ! empty( $body['tag_name'] ) ) {
			$release = array(
				'version' => ltrim( $body['tag_name'], 'v' ),
				'zip'     => $body['zipball_url'],
				'url'     => $body['html_url'],
				'notes'   => $body['body'] ?? '',
			);
			foreach ( (array) ( $body['assets'] ?? array() ) as $asset ) {
				if ( 'nrm.zip' === ( $asset['name'] ?? '' ) ) {
					$release['zip'] = $asset['browser_download_url'];
				}
			}
		}
	}
	set_site_transient( 'nrm_theme_release', $release, 6 * HOUR_IN_SECONDS );
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
	if ( version_compare( $release['version'], NRM_VERSION, '>' ) ) {
		$transient->response[ $slug ] = $item;
	} else {
		$transient->no_update[ $slug ] = $item;
	}
	return $transient;
}
add_filter( 'pre_set_site_transient_update_themes', 'nrm_update_check' );

/**
 * GitHub zipballs unpack to "owner-repo-hash/": rename to the theme folder.
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
 * Clear the cached release after an update or when the user forces a check.
 */
add_action( 'upgrader_process_complete', function () { delete_site_transient( 'nrm_theme_release' ); } );
add_action( 'load-update-core.php', function () {
	if ( isset( $_GET['force-check'] ) ) {
		delete_site_transient( 'nrm_theme_release' );
	}
} );
