<?php
/**
 * Standard page (privacy policy and the like).
 */
get_header();
?>
<article class="page wrap">
	<?php while ( have_posts() ) : the_post(); ?>
		<header class="section-head">
			<h1><?php the_title(); ?></h1>
		</header>
		<div class="prose">
			<?php the_content(); ?>
		</div>
	<?php endwhile; ?>
</article>
<?php get_footer(); ?>
