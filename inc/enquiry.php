<?php
/**
 * Enquiry form: stored as an Enquiry post, emailed, and handed to the CRM hook.
 */

defined( 'ABSPATH' ) || exit;

function nrm_register_enquiries() {
	register_post_type(
		'nrm_enquiry',
		array(
			'labels'       => array(
				'name'          => 'Enquiries',
				'singular_name' => 'Enquiry',
				'menu_name'     => 'Enquiries',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => false,
			'menu_icon'    => 'dashicons-email-alt',
			'menu_position' => 6,
			'supports'     => array( 'title' ),
			'capabilities' => array( 'create_posts' => 'do_not_allow' ),
			'map_meta_cap' => true,
		)
	);
}
add_action( 'init', 'nrm_register_enquiries' );

function nrm_enquiry_types() {
	return array(
		'advertising' => 'Advertising',
		'partner'     => 'Partner (bring Now to a region)',
		'other'       => 'Something else',
	);
}

function nrm_enquiry_regions() {
	$out = array();
	foreach ( nrm_get_titles() as $t ) {
		$out[ $t['label'] ] = $t['label'] . ' (' . $t['region'] . ')';
	}
	$out['Several titles'] = 'Several titles';
	$out['A new region']   = 'A new region';
	return $out;
}

/**
 * Render the form.
 */
function nrm_enquiry_form() {
	$sent  = isset( $_GET['enquiry'] ) && 'sent' === $_GET['enquiry'];
	$error = isset( $_GET['enquiry'] ) && 'error' === $_GET['enquiry'];
	$type  = isset( $_GET['type'] ) && isset( nrm_enquiry_types()[ $_GET['type'] ] ) ? sanitize_key( $_GET['type'] ) : 'advertising';
	?>
	<form class="enquiry" id="enquiry" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate>
		<input type="hidden" name="action" value="nrm_enquiry">
		<?php wp_nonce_field( 'nrm_enquiry', 'nrm_nonce' ); ?>
		<input type="hidden" name="nrm_t" value="<?php echo esc_attr( time() ); ?>">
		<p class="enquiry__hp" aria-hidden="true"><label>Leave this field empty <input type="text" name="nrm_website" tabindex="-1" autocomplete="off"></label></p>

		<?php if ( $sent ) : ?>
			<p class="enquiry__notice enquiry__notice--ok" role="status">Thanks. Your enquiry is with the publisher and you'll hear back within two business days.</p>
		<?php elseif ( $error ) : ?>
			<p class="enquiry__notice enquiry__notice--error" role="alert">Something was missing. Please check your name, email and message and try again.</p>
		<?php endif; ?>

		<div class="enquiry__grid">
			<div class="field">
				<label for="enq-type">I'm enquiring about</label>
				<select id="enq-type" name="nrm_type">
					<?php foreach ( nrm_enquiry_types() as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $type, $k ); ?>><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label for="enq-region">Region of interest</label>
				<select id="enq-region" name="nrm_region">
					<?php foreach ( nrm_enquiry_regions() as $k => $l ) : ?>
						<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $l ); ?></option>
					<?php endforeach; ?>
				</select>
			</div>
			<div class="field">
				<label for="enq-name">Your name <span class="req">required</span></label>
				<input id="enq-name" name="nrm_name" type="text" autocomplete="name" required>
			</div>
			<div class="field">
				<label for="enq-business">Business or organisation</label>
				<input id="enq-business" name="nrm_business" type="text" autocomplete="organization">
			</div>
			<div class="field">
				<label for="enq-email">Email <span class="req">required</span></label>
				<input id="enq-email" name="nrm_email" type="email" autocomplete="email" required>
			</div>
			<div class="field">
				<label for="enq-phone">Phone</label>
				<input id="enq-phone" name="nrm_phone" type="tel" autocomplete="tel">
			</div>
			<div class="field field--full">
				<label for="enq-message">Message <span class="req">required</span></label>
				<textarea id="enq-message" name="nrm_message" rows="5" required></textarea>
			</div>
		</div>
		<button type="submit" class="btn btn--primary">Send enquiry</button>
	</form>
	<?php
}

/**
 * Handle submissions.
 */
function nrm_enquiry_handle() {
	$back = home_url( '/' );
	if ( ! isset( $_POST['nrm_nonce'] ) || ! wp_verify_nonce( $_POST['nrm_nonce'], 'nrm_enquiry' ) ) {
		wp_safe_redirect( $back . '?enquiry=error#contact' );
		exit;
	}
	// Honeypot and a two-second floor.
	if ( ! empty( $_POST['nrm_website'] ) || ( time() - (int) ( $_POST['nrm_t'] ?? 0 ) ) < 2 ) {
		wp_safe_redirect( $back . '?enquiry=sent#contact' );
		exit;
	}

	$types = nrm_enquiry_types();
	$data  = array(
		'type'     => isset( $types[ $_POST['nrm_type'] ?? '' ] ) ? sanitize_key( $_POST['nrm_type'] ) : 'other',
		'region'   => sanitize_text_field( wp_unslash( $_POST['nrm_region'] ?? '' ) ),
		'name'     => sanitize_text_field( wp_unslash( $_POST['nrm_name'] ?? '' ) ),
		'business' => sanitize_text_field( wp_unslash( $_POST['nrm_business'] ?? '' ) ),
		'email'    => sanitize_email( wp_unslash( $_POST['nrm_email'] ?? '' ) ),
		'phone'    => sanitize_text_field( wp_unslash( $_POST['nrm_phone'] ?? '' ) ),
		'message'  => sanitize_textarea_field( wp_unslash( $_POST['nrm_message'] ?? '' ) ),
		'source'   => 'advertising' === ( $_POST['nrm_type'] ?? '' ) ? 'NRM Advertising' : ( 'partner' === ( $_POST['nrm_type'] ?? '' ) ? 'NRM Partner' : 'NRM Website' ),
		'page'     => home_url( '/' ),
	);

	if ( ! $data['name'] || ! is_email( $data['email'] ) || ! $data['message'] ) {
		wp_safe_redirect( $back . '?enquiry=error&type=' . $data['type'] . '#contact' );
		exit;
	}

	$id = wp_insert_post(
		array(
			'post_type'   => 'nrm_enquiry',
			'post_status' => 'publish',
			'post_title'  => $types[ $data['type'] ] . ': ' . $data['name'] . ( $data['business'] ? ', ' . $data['business'] : '' ),
		)
	);
	if ( $id && ! is_wp_error( $id ) ) {
		foreach ( $data as $k => $v ) {
			update_post_meta( $id, '_nrm_' . $k, $v );
		}
		update_post_meta( $id, '_nrm_ip', sanitize_text_field( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	}

	$to      = nrm_option( 'enquiry_to' ) ?: get_option( 'admin_email' );
	$subject = $types[ $data['type'] ] . ' enquiry from ' . $data['name'] . ( $data['business'] ? ' (' . $data['business'] . ')' : '' );
	$headers = array(
		'Reply-To: ' . $data['name'] . ' <' . $data['email'] . '>',
		'Content-Type: text/html; charset=UTF-8',
	);
	$from_name = function () { return 'Now Regional Media'; };
	$from_addr = function ( $from ) {
		$site = nrm_option( 'contact_email' );
		return is_email( $site ) ? $site : $from;
	};
	add_filter( 'wp_mail_from_name', $from_name );
	add_filter( 'wp_mail_from', $from_addr );
	wp_mail( $to, $subject, nrm_enquiry_email_html( $data, $id, $types ), $headers );
	remove_filter( 'wp_mail_from_name', $from_name );
	remove_filter( 'wp_mail_from', $from_addr );

	/**
	 * Hand the lead to the CRM sync.
	 *
	 * @param array $data  type, region, name, business, email, phone, message, source, page.
	 * @param int   $id    Enquiry post ID.
	 */
	do_action( 'nrm_enquiry_submitted', $data, $id );

	wp_safe_redirect( $back . '?enquiry=sent#contact' );
	exit;
}
add_action( 'admin_post_nopriv_nrm_enquiry', 'nrm_enquiry_handle' );
add_action( 'admin_post_nrm_enquiry', 'nrm_enquiry_handle' );

/**
 * The notification email. Table layout and inline styles so it renders the same in every client.
 */
function nrm_enquiry_email_html( $data, $id, $types ) {
	$e         = 'esc_html';
	$type      = $types[ $data['type'] ] ?? 'Enquiry';
	$admin_url = $id && ! is_wp_error( $id ) ? admin_url( 'post.php?post=' . (int) $id . '&action=edit' ) : admin_url( 'edit.php?post_type=nrm_enquiry' );
	$reply     = 'mailto:' . rawurlencode( $data['email'] ) . '?subject=' . rawurlencode( 'Re: your enquiry to Now Regional Media' );
	$rows      = array(
		'Enquiry'  => $type,
		'Region'   => $data['region'],
		'Name'     => $data['name'],
		'Business' => $data['business'],
		'Email'    => $data['email'],
		'Phone'    => $data['phone'],
	);
	$font = "font-family: Karla, 'Helvetica Neue', Helvetica, Arial, sans-serif;";
	$head = "font-family: Gabarito, 'Trebuchet MS', Helvetica, Arial, sans-serif;";

	$h  = '<!DOCTYPE html><html lang="en-AU"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width"><title>' . $e( $type ) . ' enquiry</title></head>';
	$h .= '<body style="margin:0;padding:0;background:#F2F1EC;">';
	$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F2F1EC;"><tr><td align="center" style="padding:32px 16px;">';
	$h .= '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">';
	// Header.
	$h .= '<tr><td style="padding:0 0 18px 0;' . $font . '">';
	$h .= '<span style="display:inline-block;background:#13294B;color:#F2F1EC;border-radius:999px;padding:4px 12px 6px 12px;' . $head . 'font-weight:900;font-size:22px;letter-spacing:-0.04em;line-height:1;vertical-align:middle;">&#9679;&nbsp;Now</span>';
	$h .= '<span style="display:inline-block;vertical-align:middle;margin-left:8px;color:#13294B;font-weight:800;font-size:10px;letter-spacing:0.14em;text-transform:uppercase;line-height:1.1;">Regional<br>Media</span>';
	$h .= '</td></tr>';
	// Card.
	$h .= '<tr><td style="background:#FFFFFF;padding:28px 28px 8px 28px;' . $font . '">';
	$h .= '<p style="margin:0 0 6px 0;color:#56627A;font-weight:800;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">New enquiry</p>';
	$h .= '<h1 style="margin:0 0 18px 0;color:#13294B;' . $head . 'font-weight:900;font-size:28px;line-height:1.05;letter-spacing:-0.03em;">' . $e( $type ) . ' enquiry from ' . $e( $data['name'] ) . '</h1>';
	$h .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #D8D9D6;">';
	foreach ( $rows as $label => $value ) {
		if ( '' === $value ) {
			continue;
		}
		$cell = $e( $value );
		if ( 'Email' === $label ) {
			$cell = '<a href="mailto:' . esc_attr( $value ) . '" style="color:#13294B;">' . $cell . '</a>';
		} elseif ( 'Phone' === $label ) {
			$cell = '<a href="tel:' . esc_attr( preg_replace( '/\s+/', '', $value ) ) . '" style="color:#13294B;">' . $cell . '</a>';
		}
		$h .= '<tr><td style="padding:10px 12px 10px 0;border-bottom:1px solid #D8D9D6;color:#56627A;font-size:13px;font-weight:700;width:90px;vertical-align:top;">' . $e( $label ) . '</td>';
		$h .= '<td style="padding:10px 0;border-bottom:1px solid #D8D9D6;color:#13294B;font-size:16px;vertical-align:top;">' . $cell . '</td></tr>';
	}
	$h .= '</table>';
	$h .= '<p style="margin:20px 0 6px 0;color:#56627A;font-weight:800;font-size:12px;letter-spacing:0.12em;text-transform:uppercase;">Message</p>';
	$h .= '<p style="margin:0 0 24px 0;color:#1F2633;font-size:17px;line-height:1.55;">' . nl2br( $e( $data['message'] ) ) . '</p>';
	$h .= '<table role="presentation" cellpadding="0" cellspacing="0"><tr>';
	$h .= '<td style="padding:0 10px 20px 0;"><a href="' . esc_url( $reply ) . '" style="display:inline-block;background:#13294B;color:#F2F1EC;text-decoration:none;border-radius:999px;padding:13px 22px;font-weight:700;font-size:15px;' . $font . '">Reply to ' . $e( $data['name'] ) . '</a></td>';
	$h .= '<td style="padding:0 0 20px 0;"><a href="' . esc_url( $admin_url ) . '" style="display:inline-block;color:#13294B;text-decoration:none;border-radius:999px;padding:11px 20px;font-weight:700;font-size:15px;border:2px solid #13294B;' . $font . '">Open in admin</a></td>';
	$h .= '</tr></table>';
	$h .= '</td></tr>';
	// Footer.
	$h .= '<tr><td style="padding:16px 4px 0 4px;' . $font . 'color:#56627A;font-size:12px;line-height:1.5;">Lead source: ' . $e( $data['source'] ) . ( $id && ! is_wp_error( $id ) ? ' &middot; Enquiry #' . (int) $id : '' ) . '<br>Sent by nowregionalmedia.com.au. Replying to this email goes straight to the enquirer.</td></tr>';
	$h .= '</table></td></tr></table></body></html>';
	return $h;
}

/**
 * Show the enquiry in admin.
 */
add_action( 'add_meta_boxes', function () {
	add_meta_box( 'nrm_enquiry_view', 'Enquiry', function ( $post ) {
		$keys = array( 'type' => 'Type', 'region' => 'Region', 'name' => 'Name', 'business' => 'Business', 'email' => 'Email', 'phone' => 'Phone', 'source' => 'Lead source', 'ip' => 'IP' );
		echo '<table class="form-table">';
		foreach ( $keys as $k => $l ) {
			echo '<tr><th>' . esc_html( $l ) . '</th><td>' . esc_html( get_post_meta( $post->ID, '_nrm_' . $k, true ) ) . '</td></tr>';
		}
		echo '<tr><th>Message</th><td>' . nl2br( esc_html( get_post_meta( $post->ID, '_nrm_message', true ) ) ) . '</td></tr>';
		echo '</table>';
	}, 'nrm_enquiry', 'normal', 'high' );
} );

add_filter( 'manage_nrm_enquiry_posts_columns', function ( $cols ) {
	return array( 'cb' => $cols['cb'], 'title' => 'Enquiry', 'region' => 'Region', 'email' => 'Email', 'source' => 'Source', 'date' => 'Date' );
} );
add_action( 'manage_nrm_enquiry_posts_custom_column', function ( $col, $id ) {
	if ( in_array( $col, array( 'region', 'email', 'source' ), true ) ) {
		echo esc_html( get_post_meta( $id, '_nrm_' . $col, true ) );
	}
}, 10, 2 );
