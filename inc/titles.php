<?php
/**
 * Titles: one post per Now title. Live titles sort first, then menu order.
 */

defined( 'ABSPATH' ) || exit;

function nrm_register_titles() {
	register_post_type(
		'nrm_title',
		array(
			'labels'       => array(
				'name'          => 'Titles',
				'singular_name' => 'Title',
				'add_new_item'  => 'Add title',
				'edit_item'     => 'Edit title',
				'menu_name'     => 'Titles',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-location-alt',
			'menu_position' => 5,
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
			'hierarchical' => false,
		)
	);
}
add_action( 'init', 'nrm_register_titles' );

function nrm_title_fields() {
	return array(
		'name'         => array( 'Region name in the lockup', 'e.g. Port, Hunter, Coffs. For Northern Rivers enter "Northern|Rivers" to stack two lines.' ),
		'region'       => array( 'Region (shown under the lockup)', 'e.g. Port Macquarie-Hastings' ),
		'badge'        => array( 'Badge colour (hex)', 'Title colour from the suite, e.g. #FFB020. Never navy or sea glass.' ),
		'ground'       => array( 'Board ground colour (hex)', 'Dark ground for this title\'s row on the homepage board, e.g. #0C2E3A. Signed off by NRM; never another title\'s ground or the parent navy.' ),
		'status'       => array( 'Status', 'live or upcoming' ),
		'launch'       => array( 'Launch month (upcoming only)', 'e.g. November 2026' ),
		'url'          => array( 'Site URL (live only)', 'https://portnow.com.au' ),
		'facts'        => array( 'Facts (live only, one per line)', '' ),
	);
}

function nrm_titles_meta_box() {
	add_meta_box( 'nrm_title_fields', 'Title details', 'nrm_titles_meta_box_html', 'nrm_title', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'nrm_titles_meta_box' );

function nrm_titles_meta_box_html( $post ) {
	wp_nonce_field( 'nrm_title_save', 'nrm_title_nonce' );
	echo '<table class="form-table">';
	foreach ( nrm_title_fields() as $key => $f ) {
		$val = get_post_meta( $post->ID, '_nrm_' . $key, true );
		echo '<tr><th><label for="nrm_' . esc_attr( $key ) . '">' . esc_html( $f[0] ) . '</label></th><td>';
		if ( 'status' === $key ) {
			echo '<select name="nrm_status" id="nrm_status">';
			foreach ( array( 'upcoming' => 'Upcoming', 'live' => 'Live' ) as $v => $l ) {
				echo '<option value="' . $v . '"' . selected( $val, $v, false ) . '>' . $l . '</option>';
			}
			echo '</select>';
		} elseif ( 'facts' === $key ) {
			echo '<textarea name="nrm_facts" id="nrm_facts" rows="3" class="large-text">' . esc_textarea( $val ) . '</textarea>';
		} else {
			echo '<input type="text" class="regular-text" name="nrm_' . esc_attr( $key ) . '" id="nrm_' . esc_attr( $key ) . '" value="' . esc_attr( $val ) . '">';
		}
		if ( $f[1] ) {
			echo '<p class="description">' . esc_html( $f[1] ) . '</p>';
		}
		echo '</td></tr>';
	}
	echo '</table>';
	echo '<p class="description">Card photo: set the Featured image (1200 x 720 minimum, shot in the region). Without one the card shows the badge colour panel. Order: Live titles always sort first, then by the Order field.</p>';
}

function nrm_titles_save( $post_id ) {
	if ( ! isset( $_POST['nrm_title_nonce'] ) || ! wp_verify_nonce( $_POST['nrm_title_nonce'], 'nrm_title_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( nrm_title_fields() as $key => $f ) {
		$raw = $_POST[ 'nrm_' . $key ] ?? '';
		if ( 'facts' === $key ) {
			$val = sanitize_textarea_field( $raw );
		} elseif ( 'url' === $key ) {
			$val = esc_url_raw( $raw );
		} elseif ( 'badge' === $key || 'ground' === $key ) {
			$val = sanitize_hex_color( $raw ) ?: '';
		} elseif ( 'status' === $key ) {
			$val = 'live' === $raw ? 'live' : 'upcoming';
		} else {
			$val = sanitize_text_field( $raw );
		}
		update_post_meta( $post_id, '_nrm_' . $key, $val );
	}
}
add_action( 'save_post_nrm_title', 'nrm_titles_save' );

/**
 * Admin list columns.
 */
add_filter( 'manage_nrm_title_posts_columns', function ( $cols ) {
	return array(
		'cb'     => $cols['cb'],
		'title'  => 'Title',
		'status' => 'Status',
		'region' => 'Region',
		'url'    => 'URL',
		'order'  => 'Order',
	);
} );
add_action( 'manage_nrm_title_posts_custom_column', function ( $col, $id ) {
	switch ( $col ) {
		case 'status':
			$s = get_post_meta( $id, '_nrm_status', true );
			echo 'live' === $s ? 'Live' : 'Upcoming, ' . esc_html( get_post_meta( $id, '_nrm_launch', true ) );
			break;
		case 'region':
			echo esc_html( get_post_meta( $id, '_nrm_region', true ) );
			break;
		case 'url':
			echo esc_html( get_post_meta( $id, '_nrm_url', true ) );
			break;
		case 'order':
			echo (int) get_post( $id )->menu_order;
			break;
	}
}, 10, 2 );

/**
 * Titles in display order, as plain arrays.
 */
function nrm_get_titles() {
	$posts = get_posts(
		array(
			'post_type'      => 'nrm_title',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'menu_order title',
			'order'          => 'ASC',
		)
	);
	$out = array();
	foreach ( $posts as $p ) {
		$facts = array_filter( array_map( 'trim', explode( "\n", (string) get_post_meta( $p->ID, '_nrm_facts', true ) ) ) );
		$out[] = array(
			'id'     => $p->ID,
			'label'  => $p->post_title,
			'name'   => get_post_meta( $p->ID, '_nrm_name', true ) ?: $p->post_title,
			'region' => get_post_meta( $p->ID, '_nrm_region', true ),
			'badge'  => get_post_meta( $p->ID, '_nrm_badge', true ) ?: '#F2F1EC',
			'ground' => get_post_meta( $p->ID, '_nrm_ground', true ) ?: '#1F2633',
			'live'   => 'live' === get_post_meta( $p->ID, '_nrm_status', true ),
			'launch' => get_post_meta( $p->ID, '_nrm_launch', true ),
			'url'    => get_post_meta( $p->ID, '_nrm_url', true ),
			'facts'  => $facts,
			'image'  => get_post_thumbnail_id( $p->ID ),
		);
	}
	usort( $out, function ( $a, $b ) {
		return ( $b['live'] <=> $a['live'] );
	} );
	return $out;
}

/**
 * Seed the four titles the first time the theme is activated.
 */
function nrm_seed_titles() {
	if ( get_option( 'nrm_titles_seeded' ) ) {
		return;
	}
	$existing = get_posts( array( 'post_type' => 'nrm_title', 'posts_per_page' => 1, 'post_status' => 'any' ) );
	if ( $existing ) {
		update_option( 'nrm_titles_seeded', 1 );
		return;
	}
	$seed = array(
		array( 'Port Now', 1, array( 'name' => 'Port', 'ground' => '#0C2E3A', 'region' => 'Port Macquarie-Hastings', 'badge' => '#FFB020', 'status' => 'live', 'launch' => '', 'url' => 'https://portnow.com.au', 'facts' => "5,000+ businesses in Port Pages\nAbout 9,000 social followers" ) ),
		array( 'Northern Rivers Now', 2, array( 'name' => 'Northern|Rivers', 'ground' => '#3A1A14', 'region' => 'Northern Rivers', 'badge' => '#7FC08B', 'status' => 'upcoming', 'launch' => 'November 2026', 'url' => 'https://northernriversnow.com.au', 'facts' => '' ) ),
		array( 'Hunter Now', 3, array( 'name' => 'Hunter', 'ground' => '#2F1533', 'region' => 'Hunter', 'badge' => '#F4A0B5', 'status' => 'upcoming', 'launch' => 'November 2026', 'url' => '', 'facts' => '' ) ),
		array( 'Coffs Now', 4, array( 'name' => 'Coffs', 'ground' => '#1C3318', 'region' => 'Coffs Coast', 'badge' => '#B9A8F0', 'status' => 'upcoming', 'launch' => 'December 2026', 'url' => '', 'facts' => '' ) ),
	);
	foreach ( $seed as $s ) {
		$id = wp_insert_post( array( 'post_type' => 'nrm_title', 'post_title' => $s[0], 'post_status' => 'publish', 'menu_order' => $s[1] ) );
		if ( $id && ! is_wp_error( $id ) ) {
			foreach ( $s[2] as $k => $v ) {
				update_post_meta( $id, '_nrm_' . $k, $v );
			}
		}
	}
	update_option( 'nrm_titles_seeded', 1 );
}
add_action( 'after_switch_theme', function () {
	nrm_register_titles();
	nrm_seed_titles();
	flush_rewrite_rules();
} );

/**
 * One-off: give existing titles their board grounds (October 2026 board brief).
 * Only fills empty fields, so later edits in the admin are never overwritten.
 */
add_action( 'admin_init', function () {
	if ( get_option( 'nrm_grounds_seeded' ) ) {
		return;
	}
	$grounds = array(
		'Port Now'            => '#0C2E3A',
		'Northern Rivers Now' => '#3A1A14',
		'Hunter Now'          => '#2F1533',
		'Coffs Now'           => '#1C3318',
	);
	foreach ( get_posts( array( 'post_type' => 'nrm_title', 'posts_per_page' => -1, 'post_status' => 'any' ) ) as $p ) {
		if ( isset( $grounds[ $p->post_title ] ) && ! get_post_meta( $p->ID, '_nrm_ground', true ) ) {
			update_post_meta( $p->ID, '_nrm_ground', $grounds[ $p->post_title ] );
		}
	}
	update_option( 'nrm_grounds_seeded', 1 );
} );
