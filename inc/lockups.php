<?php
/**
 * Live HTML lockups so they render in the web fonts.
 */

defined( 'ABSPATH' ) || exit;

/**
 * The "Now" pill.
 *
 * @param string $bg   Pill background.
 * @param string $ink  Pill text and dot colour.
 */
function nrm_pill( $bg, $ink ) {
	return '<span class="pill" style="--pill-bg:' . esc_attr( $bg ) . ';--pill-ink:' . esc_attr( $ink ) . '"><span class="pill__dot" aria-hidden="true"></span>Now</span>';
}

/**
 * Now Regional Media lockup.
 *
 * @param string $variant 'primary' (navy pill, navy wordmark) or 'reversed' (sea glass pill, salt wordmark).
 * @param string $tag     Wrapping element.
 */
function nrm_lockup( $variant = 'primary', $extra_class = '' ) {
	$reversed = 'reversed' === $variant;
	$bg  = $reversed ? 'var(--nrm-sea-glass)' : 'var(--nrm-navy)';
	$ink = $reversed ? 'var(--nrm-navy)' : 'var(--nrm-salt)';
	$dot = $reversed ? 'var(--nrm-navy)' : 'var(--nrm-sea-glass)';
	$html  = '<span class="nrm-lockup ' . esc_attr( $extra_class ) . ( $reversed ? ' nrm-lockup--reversed' : '' ) . '" role="img" aria-label="Now Regional Media">';
	$html .= '<span class="pill" style="--pill-bg:' . $bg . ';--pill-ink:' . $ink . ';--pill-dot:' . $dot . '" aria-hidden="true"><span class="pill__dot"></span>Now</span>';
	$html .= '<span class="nrm-lockup__word" aria-hidden="true">Regional<br>Media</span>';
	$html .= '</span>';
	return $html;
}

/**
 * A title lockup: "[Region] Now" with the title's badge colour.
 *
 * @param array  $t     Title array from nrm_get_titles().
 * @param string $ink   Region name colour (CSS value).
 */
function nrm_title_lockup( $t, $ink = 'var(--now-badge-text)' ) {
	$name    = (string) $t['name'];
	$stacked = str_contains( $name, '|' );
	$label   = esc_attr( $t['label'] );
	$html    = '<span class="lk' . ( $stacked ? ' lk--stacked' : '' ) . '" role="img" aria-label="' . $label . '" style="--lk-ink:' . esc_attr( $ink ) . '">';
	if ( $stacked ) {
		list( $top, $bottom ) = array_map( 'trim', explode( '|', $name, 2 ) );
		$html .= '<span class="lk__name" aria-hidden="true"><span class="lk__top">' . esc_html( $top ) . '</span><span class="lk__bottom">' . esc_html( $bottom ) . '</span></span>';
	} else {
		$html .= '<span class="lk__name" aria-hidden="true">' . esc_html( $name ) . '</span>';
	}
	$html .= '<span class="pill" style="--pill-bg:' . esc_attr( $t['badge'] ) . ';--pill-ink:var(--now-badge-text)" aria-hidden="true"><span class="pill__dot"></span>Now</span>';
	$html .= '</span>';
	return $html;
}

/**
 * Status line for a title.
 */
function nrm_title_status( $t, $on_dark = false ) {
	if ( $t['live'] ) {
		return '<span class="status status--live' . ( $on_dark ? ' status--on-dark' : '' ) . '"><span class="status__dot" aria-hidden="true"></span>Live</span>';
	}
	$prefix = $on_dark ? '' : 'Launching ';
	return '<span class="status status--upcoming' . ( $on_dark ? ' status--on-dark' : '' ) . '">' . esc_html( $prefix . $t['launch'] ) . '</span>';
}
