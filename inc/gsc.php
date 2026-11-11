<?php
/**
 * Google Search Console impressions for the title cards (1.5.0).
 *
 * A Google service account (its email added as a Restricted user on each Search Console property) lets the theme read
 * each title's search impressions. The service account's JSON key is pasted once into Titles > Search stats and stored
 * in a non-autoloaded option; it is never printed anywhere. Each title names its property in the "GSC property" field
 * (e.g. sc-domain:portnow.com.au). The daily stats refresh in inc/stats.php then stores the last 28 days of
 * impressions on the title, and the {impressions} placeholder in Facts prints it, with the same rule as {listings}:
 * a stale or missing number drops the line from the card rather than showing something wrong.
 *
 * The 28-day window ends 3 days ago, because Search Console data runs about 2 to 3 days behind.
 */

defined( 'ABSPATH' ) || exit;

define( 'NRM_GSC_KEY_OPTION', 'nrm_gsc_key' );

/** The stored service account key as an array, or null when absent or unreadable. */
function nrm_gsc_key() {
	$raw = get_option( NRM_GSC_KEY_OPTION );
	if ( ! $raw ) {
		return null;
	}
	$key = json_decode( (string) $raw, true );
	return ( is_array( $key ) && ! empty( $key['client_email'] ) && ! empty( $key['private_key'] ) ) ? $key : null;
}

/** Base64url, as JWTs require. */
function nrm_gsc_b64( $data ) {
	return rtrim( strtr( base64_encode( $data ), '+/', '-_' ), '=' );
}

/**
 * An OAuth access token for the Search Console API (webmasters.readonly), via a signed JWT.
 * Cached for 50 minutes (tokens last 60). Returns the token string or a WP_Error.
 */
function nrm_gsc_token() {
	$cached = get_transient( 'nrm_gsc_token' );
	if ( $cached ) {
		return $cached;
	}
	$key = nrm_gsc_key();
	if ( ! $key ) {
		return new WP_Error( 'nrm_gsc_no_key', 'No service account key saved (Titles > Search stats).' );
	}
	$now    = time();
	$header = nrm_gsc_b64( wp_json_encode( array( 'alg' => 'RS256', 'typ' => 'JWT' ) ) );
	$claims = nrm_gsc_b64( wp_json_encode( array(
		'iss'   => $key['client_email'],
		'scope' => 'https://www.googleapis.com/auth/webmasters.readonly',
		'aud'   => 'https://oauth2.googleapis.com/token',
		'iat'   => $now,
		'exp'   => $now + 3600,
	) ) );
	$sig = '';
	if ( ! openssl_sign( $header . '.' . $claims, $sig, $key['private_key'], OPENSSL_ALGO_SHA256 ) ) {
		return new WP_Error( 'nrm_gsc_sign', 'Could not sign the request with the saved key (is the key complete?).' );
	}
	$res = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
		'timeout' => 15,
		'body'    => array(
			'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
			'assertion'  => $header . '.' . $claims . '.' . nrm_gsc_b64( $sig ),
		),
	) );
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( empty( $body['access_token'] ) ) {
		$why = $body['error_description'] ?? $body['error'] ?? ( 'HTTP ' . wp_remote_retrieve_response_code( $res ) );
		return new WP_Error( 'nrm_gsc_token', 'Google refused the key: ' . $why );
	}
	set_transient( 'nrm_gsc_token', $body['access_token'], 50 * MINUTE_IN_SECONDS );
	return $body['access_token'];
}

/**
 * Total impressions and clicks for one property over the last 28 counted days (ending 3 days ago).
 * Returns array( 'impressions' => int, 'clicks' => int ) or a WP_Error.
 */
function nrm_gsc_impressions( $property ) {
	$token = nrm_gsc_token();
	if ( is_wp_error( $token ) ) {
		return $token;
	}
	$end   = gmdate( 'Y-m-d', time() - 3 * DAY_IN_SECONDS );
	$start = gmdate( 'Y-m-d', time() - 30 * DAY_IN_SECONDS );
	$res   = wp_remote_post(
		'https://searchconsole.googleapis.com/webmasters/v3/sites/' . rawurlencode( $property ) . '/searchAnalytics/query',
		array(
			'timeout' => 15,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( array( 'startDate' => $start, 'endDate' => $end ) ),
		)
	);
	if ( is_wp_error( $res ) ) {
		return $res;
	}
	$code = (int) wp_remote_retrieve_response_code( $res );
	$body = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( 200 !== $code ) {
		$why = $body['error']['message'] ?? ( 'HTTP ' . $code );
		if ( 403 === $code ) {
			$why .= ' (is the service account added as a user on this property?)';
		}
		return new WP_Error( 'nrm_gsc_query', $why );
	}
	$row = $body['rows'][0] ?? array();
	return array(
		'impressions' => (int) round( $row['impressions'] ?? 0 ),
		'clicks'      => (int) round( $row['clicks'] ?? 0 ),
	);
}

/* ---------------------------------------------------------------- admin: Search stats page (key management) */

add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=nrm_title', 'Search stats', 'Search stats', 'manage_options', 'nrm-gsc', 'nrm_gsc_admin_page' );
} );

function nrm_gsc_admin_page() {
	$key = nrm_gsc_key();
	echo '<div class="wrap"><h1>Search stats</h1>';
	echo '<p>Impressions on the title cards come from Google Search Console, read by a service account. Create one in Google Cloud Console (enable the Search Console API), download its JSON key, and add its email as a Restricted user on each property. Then set each title\'s "GSC property" field (e.g. <code>sc-domain:portnow.com.au</code>) and use "Refresh numbers" on the Titles list to test.</p>';
	if ( $key ) {
		echo '<p><strong>Key saved.</strong> Service account: <code>' . esc_html( $key['client_email'] ) . '</code></p>';
	} else {
		echo '<p><strong>No key saved.</strong> Impressions are not fetched until one is.</p>';
	}
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
	wp_nonce_field( 'nrm_gsc_key_save', 'nrm_gsc_nonce' );
	echo '<input type="hidden" name="action" value="nrm_gsc_key_save">';
	echo '<p><label for="nrm_gsc_key_input">Paste the service account JSON key' . ( $key ? ' (replaces the saved one)' : '' ) . ':</label></p>';
	echo '<p><textarea name="nrm_gsc_key_input" id="nrm_gsc_key_input" rows="6" class="large-text code" autocomplete="off"></textarea></p>';
	if ( $key ) {
		echo '<p><label><input type="checkbox" name="nrm_gsc_key_remove" value="1"> Remove the saved key (stops all impression fetching; last good numbers stay until stale)</label></p>';
	}
	submit_button( 'Save' );
	echo '</form></div>';
}

add_action( 'admin_post_nrm_gsc_key_save', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'nrm_gsc_key_save', 'nrm_gsc_nonce' ) ) {
		wp_die( 'Not allowed.' );
	}
	$back = admin_url( 'edit.php?post_type=nrm_title&page=nrm-gsc' );
	if ( ! empty( $_POST['nrm_gsc_key_remove'] ) ) {
		delete_option( NRM_GSC_KEY_OPTION );
		delete_transient( 'nrm_gsc_token' );
		wp_safe_redirect( $back );
		exit;
	}
	$raw = trim( (string) wp_unslash( $_POST['nrm_gsc_key_input'] ?? '' ) );
	if ( $raw ) {
		$key = json_decode( $raw, true );
		if ( is_array( $key ) && ! empty( $key['client_email'] ) && ! empty( $key['private_key'] ) ) {
			update_option( NRM_GSC_KEY_OPTION, $raw, false );
			delete_transient( 'nrm_gsc_token' );
		} else {
			wp_safe_redirect( add_query_arg( 'nrm_gsc_bad_key', 1, $back ) );
			exit;
		}
	}
	wp_safe_redirect( $back );
	exit;
} );

add_action( 'admin_notices', function () {
	if ( isset( $_GET['nrm_gsc_bad_key'] ) && current_user_can( 'manage_options' ) ) {
		echo '<div class="notice notice-error"><p>That did not look like a service account JSON key (it needs client_email and private_key). Nothing was saved.</p></div>';
	}
} );
