<?php
/**
 * Not found.
 */
get_header();
?>
<article class="page wrap">
	<header class="section-head">
		<span class="label">404</span>
		<h1>That page isn't here.</h1>
	</header>
	<div class="prose">
		<p>Try the <a href="<?php echo esc_url( home_url( '/' ) ); ?>">home page</a>, or jump to <a href="<?php echo esc_url( home_url( '/#titles' ) ); ?>">the titles</a>.</p>
	</div>
</article>
<?php get_footer(); ?>
