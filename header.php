<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip" href="#main">Skip to content</a>
<header class="site-header" id="top">
	<div class="wrap site-header__inner">
		<a class="site-header__brand" href="<?php echo esc_url( home_url( '/' ) ); ?>#top" aria-label="Now Regional Media home">
			<?php echo nrm_lockup( 'primary' ); ?>
		</a>
		<nav class="site-nav" aria-label="Main">
			<?php foreach ( nrm_nav_items() as $hash => $label ) : ?>
				<a class="site-nav__link" href="<?php echo esc_url( nrm_anchor( $hash ) ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
			<?php $kit = nrm_option( 'media_kit_url' ); ?>
			<?php if ( $kit ) : ?>
				<a class="btn btn--primary btn--nav" href="<?php echo esc_url( $kit ); ?>">Get the media kit</a>
			<?php else : ?>
				<a class="btn btn--primary btn--nav" href="<?php echo esc_url( nrm_anchor( '#contact' ) ); ?>" data-enquiry="advertising">Get the media kit</a>
			<?php endif; ?>
			<?php $login = nrm_option( 'client_login_url' ); ?>
			<?php if ( $login ) : ?>
				<a class="site-nav__link site-nav__login" href="<?php echo esc_url( $login ); ?>"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/></svg>Client login</a>
			<?php endif; ?>
		</nav>
	</div>
</header>
<main id="main">
