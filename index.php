<?php
/**
 * Fallback for anything that is not the front page or a page.
 */
get_header();
?>
<article class="inner wrap">
	<header class="section-head">
		<h1><?php echo have_posts() ? esc_html( get_the_archive_title() ) : 'Nothing here'; ?></h1>
	</header>
	<div class="prose">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
			<h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
			<?php the_excerpt(); ?>
		<?php endwhile; else : ?>
			<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Back to the home page</a>.</p>
		<?php endif; ?>
	</div>
</article>
<?php get_footer(); ?>
