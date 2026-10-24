</main>
<footer class="site-footer">
	<div class="wrap site-footer__inner">
		<div class="site-footer__row">
			<?php echo nrm_lockup( 'reversed' ); ?>
			<nav class="footer-nav" aria-label="Footer">
				<?php foreach ( nrm_nav_items() as $hash => $label ) : ?>
					<a href="<?php echo esc_url( nrm_anchor( $hash ) ); ?>"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
				<?php foreach ( nrm_get_titles() as $t ) : ?>
					<?php if ( $t['live'] && $t['url'] ) : ?>
						<a href="<?php echo esc_url( $t['url'] ); ?>"><?php echo esc_html( wp_parse_url( $t['url'], PHP_URL_HOST ) ); ?></a>
					<?php endif; ?>
				<?php endforeach; ?>
				<?php $privacy = get_option( 'wp_page_for_privacy_policy' ); ?>
				<?php if ( $privacy && 'publish' === get_post_status( $privacy ) ) : ?>
					<a href="<?php echo esc_url( get_permalink( $privacy ) ); ?>">Privacy</a>
				<?php endif; ?>
			</nav>
		</div>
		<p class="site-footer__legal">&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> Now Regional Media &middot; nowregionalmedia.com.au</p>
	</div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
