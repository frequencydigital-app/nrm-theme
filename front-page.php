<?php
/**
 * The one page.
 */
get_header();
$titles = nrm_get_titles();
$kit    = nrm_option( 'media_kit_url' );
$live_count = count( array_filter( $titles, fn( $t ) => $t['live'] ) );
?>

<section class="hero wrap" aria-labelledby="hero-heading">
	<div class="hero__copy">
		<h1 id="hero-heading">Local guides for coastal NSW.</h1>
		<p class="intro">We publish the Now titles. Each one tells a region what's on, where to go and who to call.</p>
		<div class="btn-row">
			<a class="btn btn--primary btn--lg" href="#advertise">Advertise with us</a>
			<a class="btn btn--secondary btn--lg" href="#partner">Bring Now to your region</a>
		</div>
	</div>
	<div class="board" aria-label="The Now titles and their status">
		<?php foreach ( $titles as $t ) : ?>
			<?php
			$linked = $t['live'] && $t['url'];
			$tag    = $linked ? 'a' : 'div';
			$style  = '--nrm-ground:' . $t['ground'] . ';--nrm-badge:' . $t['badge'] . ';--nrm-ink:' . $t['ground'];
			$stack  = str_contains( (string) $t['name'], '|' );
			?>
			<<?php echo $tag; ?> class="board__row"<?php echo $linked ? ' href="' . esc_url( $t['url'] ) . '"' : ''; ?> style="<?php echo esc_attr( $style ); ?>">
				<span class="board__lockup<?php echo $stack ? ' board__lockup--stacked' : ''; ?>" role="img" aria-label="<?php echo esc_attr( $t['label'] ); ?>">
					<?php if ( $stack ) : list( $top, $bottom ) = array_map( 'trim', explode( '|', $t['name'], 2 ) ); ?>
						<span class="board__name" aria-hidden="true"><span class="board__line-1"><?php echo esc_html( $top ); ?></span><span><?php echo esc_html( $bottom ); ?></span></span>
					<?php else : ?>
						<span class="board__name" aria-hidden="true"><?php echo esc_html( $t['name'] ); ?></span>
					<?php endif; ?>
					<span class="board__pill" aria-hidden="true">Now</span>
				</span>
				<?php if ( $t['live'] ) : ?>
					<span class="board__status"><span class="board__dot" aria-hidden="true"></span>Live</span>
				<?php else : ?>
					<span class="board__status"><?php echo esc_html( $t['launch'] ); ?></span>
				<?php endif; ?>
			</<?php echo $tag; ?>>
		<?php endforeach; ?>
	</div>
</section>

<section class="titles" id="titles" aria-labelledby="titles-heading">
	<div class="wrap titles__inner">
		<div class="section-head">
			<span class="label">The titles</span>
			<h2 id="titles-heading">One name, region by region.</h2>
		</div>
		<?php
		// Outer grid of pairs, each pair a two-card grid: 4 across, then 2 by 2, then 1. Never 3 plus 1.
		$pairs = array_chunk( $titles, 2 );
		?>
		<div class="cards">
			<?php foreach ( $pairs as $pair ) : ?>
				<div class="cards__pair">
					<?php foreach ( $pair as $t ) : ?>
						<article class="card">
							<?php if ( $t['image'] ) : ?>
								<?php echo wp_get_attachment_image( $t['image'], 'nrm-card', false, array( 'class' => 'card__photo', 'sizes' => '(min-width: 1200px) 280px, (min-width: 800px) 45vw, 100vw', 'loading' => 'lazy' ) ); ?>
							<?php else : ?>
								<div class="card__panel" style="--panel:<?php echo esc_attr( $t['badge'] ); ?>" aria-hidden="true"></div>
							<?php endif; ?>
							<div class="card__body">
								<div class="card__lockup"><?php echo nrm_title_lockup( $t ); ?></div>
								<div class="card__meta">
									<span class="card__region"><?php echo esc_html( $t['region'] ); ?></span>
									<?php echo nrm_title_status( $t ); ?>
								</div>
								<?php if ( $t['live'] && $t['facts'] ) : ?>
									<ul class="card__facts">
										<?php foreach ( $t['facts'] as $fact ) : ?>
											<li><?php echo esc_html( $fact ); ?></li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
								<?php if ( $t['live'] && $t['url'] ) : ?>
									<a class="card__link" href="<?php echo esc_url( $t['url'] ); ?>"><?php echo esc_html( wp_parse_url( $t['url'], PHP_URL_HOST ) ); ?></a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
</section>

<section class="advertise wrap" id="advertise" aria-labelledby="advertise-heading">
	<div class="advertise__copy">
		<span class="label">Advertise</span>
		<h2 id="advertise-heading">Reach locals in one region or along the coast.</h2>
		<p>Book one title or several. The media kit has formats, audiences and rates.</p>
		<div class="btn-row">
			<?php if ( $kit ) : ?>
				<a class="btn btn--primary" href="<?php echo esc_url( $kit ); ?>">Get the media kit</a>
			<?php else : ?>
				<a class="btn btn--primary" href="#contact" data-enquiry="advertising">Get the media kit</a>
			<?php endif; ?>
			<a class="btn btn--outline" href="#contact" data-enquiry="advertising">Talk to sales</a>
		</div>
	</div>
	<div class="formats">
		<div class="format"><h3>Directory listings</h3><p>Claim and upgrade a business listing in each title's directory.</p></div>
		<div class="format"><h3>Events</h3><p>Feature an event in the calendar and on social.</p></div>
		<div class="format"><h3>Display</h3><p>Ad units across the site and newsletter.</p></div>
		<div class="format"><h3>Social</h3><p>Sponsored posts on each title's channels.</p></div>
	</div>
</section>

<section class="partner" id="partner" aria-labelledby="partner-heading">
	<div class="wrap partner__inner">
		<div class="partner__copy">
			<span class="label label--on-dark">Partner with us</span>
			<h2 id="partner-heading">Bring Now to your region.</h2>
			<p>Every title runs on the same name, design and platform. The region name and the badge colour are yours. We're talking to partners in regional and metro areas.</p>
		</div>
		<div class="partner__demo">
			<span class="lk lk--demo" role="img" aria-label="Your region Now" style="--lk-ink:var(--nrm-salt)"><span class="lk__name" aria-hidden="true">Your region</span><?php echo nrm_pill( 'var(--nrm-salt)', 'var(--nrm-navy)' ); ?></span>
			<a class="btn btn--secondary btn--lg" href="#contact" data-enquiry="partner">Start a conversation</a>
		</div>
	</div>
</section>

<section class="about wrap" id="about" aria-labelledby="about-heading">
	<div class="about__copy">
		<span class="label">About</span>
		<h2 id="about-heading">The publisher of Port&nbsp;Now and the Now titles.</h2>
		<p>Now Regional Media runs local guides for regional coastal NSW: events, places and a directory of local businesses for each region. Advertising, partner and investor enquiries all come to the publisher.</p>
	</div>
	<div class="contact-card">
		<span class="label">Contact</span>
		<span class="contact-card__name"><?php echo esc_html( nrm_option( 'contact_name' ) ); ?></span>
		<span class="contact-card__role"><?php echo esc_html( nrm_option( 'contact_role' ) ); ?></span>
		<div class="contact-card__links">
			<?php $phone = trim( (string) nrm_option( 'contact_phone' ) ); ?>
			<?php if ( $phone ) : ?>
				<?php $tel = '+61' . ltrim( preg_replace( '/\D+/', '', $phone ), '0' ); ?>
				<a href="tel:<?php echo esc_attr( $tel ); ?>"><?php echo esc_html( $phone ); ?></a>
			<?php endif; ?>
			<a href="mailto:<?php echo esc_attr( nrm_option( 'contact_email' ) ); ?>"><?php echo esc_html( nrm_option( 'contact_email' ) ); ?></a>
		</div>
	</div>
</section>

<section class="contact wrap" id="contact" aria-labelledby="contact-heading">
	<div class="section-head">
		<span class="label">Enquiry</span>
		<h2 id="contact-heading">Tell us what you have in mind.</h2>
		<p>Advertising, a new region, or something else. The publisher replies to every enquiry.</p>
	</div>
	<?php nrm_enquiry_form(); ?>
</section>

<?php get_footer(); ?>
