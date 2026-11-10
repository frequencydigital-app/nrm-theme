<?php
/**
 * Live numbers on the title cards (1.3.0).
 *
 * Each live title's site publishes GET /wp-json/now/v1/stats (Now theme 0.43.30+): published listings and events on
 * now or coming up, the same two numbers that site's own home page shows. Once a week, and whenever a title is saved
 * or "Refresh numbers" is clicked, this fetches them and keeps the last good values on the title.
 *
 * The Facts field takes placeholders, so the wording stays editable:
 *   {listings} businesses listed   ->  5,119 businesses listed
 *   {events} events coming up      ->  26 events coming up
 *   {impressions} Google impressions in the last 28 days -> 38,412 ... (from Search Console, see inc/gsc.php)
 * A line whose placeholder has no value, or a value older than NRM_STATS_MAX_AGE, is left off the card, so a broken
 * or stale number never shows. Lines without placeholders print as typed.
 */

defined( 'ABSPATH' ) || exit;

define( 'NRM_STATS_MAX_AGE', 21 * DAY_IN_SECONDS ); // Three missed weekly refreshes.

/** Where a title's numbers come from: the Stats URL field, else the site URL plus the Now route. */
function nrm_stats_endpoint( $id ) {
	$custom = trim( (string) get_post_meta( $id, '_nrm_stats_url', true ) );
	if ( $custom ) {
		return $custom;
	}
	$url = trim( (string) get_post_meta( $id, '_nrm_url', true ) );
	return $url ? trailingslashit( $url ) . 'wp-json/now/v1/stats' : '';
}

/**
 * Fetch one title's numbers. On success the values are stored with the time; on failure the last good values stay
 * and the error is recorded. Returns true when a stored value changed.
 */
function nrm_stats_refresh_title( $id ) {
	$endpoint = nrm_stats_endpoint( $id );
	if ( ! $endpoint || 'live' !== get_post_meta( $id, '_nrm_status', true ) ) {
		return false;
	}
	$old = get_post_meta( $id, '_nrm_stats', true );
	$old = is_array( $old ) ? $old : array();
	$new = $old;

	$res  = wp_remote_get( $endpoint, array( 'timeout' => 8, 'headers' => array( 'User-Agent' => 'nrm-theme/' . NRM_VERSION, 'Accept' => 'application/json' ) ) );
	$code = is_wp_error( $res ) ? 0 : (int) wp_remote_retrieve_response_code( $res );
	$body = 200 === $code ? json_decode( wp_remote_retrieve_body( $res ), true ) : null;

	if ( is_array( $body ) && isset( $body['listings'] ) && is_numeric( $body['listings'] ) ) {
		$new['listings'] = (int) $body['listings'];
		$new['events']   = isset( $body['events_upcoming'] ) && is_numeric( $body['events_upcoming'] ) ? (int) $body['events_upcoming'] : null;
		$new['fetched']  = time();
		$new['error']    = '';
	} else {
		$new['error']    = is_wp_error( $res ) ? $res->get_error_message() : ( $code ? 'HTTP ' . $code : 'No response' );
		$new['error_at'] = time();
	}

	// Search impressions from Google Search Console (inc/gsc.php), when a property and key are set.
	$prop = trim( (string) get_post_meta( $id, '_nrm_gsc', true ) );
	if ( $prop && function_exists( 'nrm_gsc_impressions' ) && nrm_gsc_key() ) {
		$g = nrm_gsc_impressions( $prop );
		if ( is_wp_error( $g ) ) {
			$new['gsc_error']    = $g->get_error_message();
			$new['gsc_error_at'] = time();
		} else {
			$new['impressions'] = $g['impressions'];
			$new['clicks']      = $g['clicks'];
			$new['gsc_fetched'] = time();
			$new['gsc_error']   = '';
		}
	}
	update_post_meta( $id, '_nrm_stats', $new );

	return ( $old['listings'] ?? null ) !== ( $new['listings'] ?? null )
		|| ( $old['events'] ?? null ) !== ( $new['events'] ?? null )
		|| ( $old['impressions'] ?? null ) !== ( $new['impressions'] ?? null );
}

/** Refresh every live title; purge the SiteGround page cache once if anything on the cards changed. */
function nrm_stats_refresh_all() {
	$changed = false;
	foreach ( get_posts( array( 'post_type' => 'nrm_title', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids' ) ) as $id ) {
		$changed = nrm_stats_refresh_title( $id ) || $changed;
	}
	if ( $changed ) {
		nrm_stats_purge();
	}
}
add_action( 'nrm_stats_refresh', 'nrm_stats_refresh_all' );

/** SiteGround Speed Optimizer's purge, when it is installed. Nothing else is touched. */
function nrm_stats_purge() {
	if ( function_exists( 'sg_cachepress_purge_cache' ) ) {
		sg_cachepress_purge_cache();
	}
}

/** Weekly schedule (core's 'weekly' recurrence). */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'nrm_stats_refresh' ) ) {
		wp_schedule_event( time() + 60, 'weekly', 'nrm_stats_refresh' );
	}
} );
add_action( 'switch_theme', function () {
	wp_clear_scheduled_hook( 'nrm_stats_refresh' );
} );

/** Saving a title fetches its numbers straight away, so new placeholders fill in without waiting a week. */
add_action( 'save_post_nrm_title', function ( $id ) {
	if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	if ( nrm_stats_refresh_title( $id ) ) {
		nrm_stats_purge();
	}
}, 20 );

/**
 * One fact line with its placeholders filled. Returns '' when a placeholder has no current value.
 */
function nrm_stats_fact( $line, $stats ) {
	if ( false === strpos( $line, '{' ) ) {
		return $line;
	}
	$fresh  = is_array( $stats ) && ! empty( $stats['fetched'] ) && ( time() - (int) $stats['fetched'] ) <= NRM_STATS_MAX_AGE;
	$gfresh = is_array( $stats ) && ! empty( $stats['gsc_fetched'] ) && ( time() - (int) $stats['gsc_fetched'] ) <= NRM_STATS_MAX_AGE;
	$map    = array(
		'{listings}'    => $fresh && isset( $stats['listings'] ) && $stats['listings'] > 0 ? number_format_i18n( (int) $stats['listings'] ) : null,
		'{events}'      => $fresh && isset( $stats['events'] ) && $stats['events'] > 0 ? number_format_i18n( (int) $stats['events'] ) : null,
		'{impressions}' => $gfresh && isset( $stats['impressions'] ) && $stats['impressions'] > 0 ? number_format_i18n( (int) $stats['impressions'] ) : null,
	);
	foreach ( $map as $tag => $val ) {
		if ( false !== strpos( $line, $tag ) ) {
			if ( null === $val ) {
				return '';
			}
			$line = str_replace( $tag, $val, $line );
		}
	}
	// A count of one reads "1 event", not "1 events".
	return preg_replace( array( '/^1 events\b/', '/^1 businesses\b/', '/^1 listings\b/' ), array( '1 event', '1 business', '1 listing' ), $line );
}

/* ---------------------------------------------------------------- admin: column and Refresh numbers */

add_filter( 'manage_nrm_title_posts_columns', function ( $cols ) {
	$cols['numbers'] = 'Live numbers';
	return $cols;
}, 20 );

add_action( 'manage_nrm_title_posts_custom_column', function ( $col, $id ) {
	if ( 'numbers' !== $col ) {
		return;
	}
	if ( 'live' !== get_post_meta( $id, '_nrm_status', true ) ) {
		echo '&ndash;';
		return;
	}
	$s = get_post_meta( $id, '_nrm_stats', true );
	if ( ! is_array( $s ) || empty( $s['fetched'] ) ) {
		echo 'Not fetched yet';
	} else {
		echo esc_html( number_format_i18n( (int) $s['listings'] ) . ' listings' );
		echo null === ( $s['events'] ?? null ) ? '' : esc_html( ', ' . number_format_i18n( (int) $s['events'] ) . ' events' );
		echo '<br><span class="description">' . esc_html( 'Fetched ' . wp_date( 'j M Y, g:ia', (int) $s['fetched'] ) ) . '</span>';
	}
	if ( is_array( $s ) && ! empty( $s['gsc_fetched'] ) ) {
		echo '<br>' . esc_html( number_format_i18n( (int) ( $s['impressions'] ?? 0 ) ) . ' impressions, 28 days' );
		echo '<br><span class="description">' . esc_html( 'GSC fetched ' . wp_date( 'j M Y, g:ia', (int) $s['gsc_fetched'] ) ) . '</span>';
	}
	if ( is_array( $s ) && ! empty( $s['error'] ) ) {
		echo '<br><span style="color:#b32d2e">' . esc_html( 'Last try failed: ' . $s['error'] ) . '</span>';
	}
	if ( is_array( $s ) && ! empty( $s['gsc_error'] ) ) {
		echo '<br><span style="color:#b32d2e">' . esc_html( 'GSC failed: ' . $s['gsc_error'] ) . '</span>';
	}
}, 10, 2 );

add_filter( 'post_row_actions', function ( $actions, $post ) {
	if ( 'nrm_title' === $post->post_type && 'live' === get_post_meta( $post->ID, '_nrm_status', true ) && current_user_can( 'manage_options' ) ) {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=nrm_stats_refresh&title=' . $post->ID ), 'nrm_stats_refresh_' . $post->ID );
		$actions['nrm_stats'] = '<a href="' . esc_url( $url ) . '">Refresh numbers</a>';
	}
	return $actions;
}, 10, 2 );

add_action( 'admin_post_nrm_stats_refresh', function () {
	$id = isset( $_GET['title'] ) ? absint( $_GET['title'] ) : 0;
	if ( ! $id || ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'nrm_stats_refresh_' . $id ) ) {
		wp_die( 'Not allowed.' );
	}
	if ( nrm_stats_refresh_title( $id ) ) {
		nrm_stats_purge();
	}
	wp_safe_redirect( admin_url( 'edit.php?post_type=nrm_title' ) );
	exit;
} );
