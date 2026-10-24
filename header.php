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
		</nav>
	</div>
</header>
<main id="main">
