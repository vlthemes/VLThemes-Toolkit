<?php

/**
 * Dashboard Help Center Template
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

$help_links = [
	[
		'url'   => $this->utm( $this->docs_url . $this->theme_slug . '/', 'help-docs' ),
		'title' => esc_html__( 'Documentation', 'toolkit' ),
		'text'  => esc_html__( 'Installation, demo import, theme options and customization — step by step.', 'toolkit' ),
	],
	[
		'url'   => $this->utm( $this->knowledge_base_url, 'help-kb' ),
		'title' => esc_html__( 'Knowledge Base', 'toolkit' ),
		'text'  => esc_html__( 'Answers to common questions about licenses, updates, hosting and plugins.', 'toolkit' ),
	],
	[
		'url'   => $this->utm( $this->support_url, 'help-ticket' ),
		'title' => esc_html__( 'Support Ticket', 'toolkit' ),
		'text'  => esc_html__( 'Still stuck? Describe the issue, add a link and a screenshot — we\'ll help.', 'toolkit' ),
	],
];

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- Help & Support -->
	<div class="vlt-masonry-item vlt-masonry-item--wide">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'Help & Support', 'toolkit' ); ?></mark>
				<span class="vlt-badge true"><?php esc_html_e( 'Replies within 24h', 'toolkit' ); ?></span>
			</div>

			<div class="vlt-widget__content">
				<p><?php esc_html_e( 'Everything you need to set up and run the theme. Start with the docs — most questions are already answered there.', 'toolkit' ); ?></p>

				<ul class="vlt-help-links mt-sm">
					<?php foreach ( $help_links as $index => $link ) : ?>
					<li class="vlt-help-links__item">
						<a class="vlt-help-links__link" href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener">
							<span class="vlt-help-links__index"><?php echo esc_html( sprintf( '%02d', $index + 1 ) ); ?></span>
							<span class="vlt-help-links__body">
								<span class="vlt-help-links__title"><?php echo $link['title']; ?></span>
								<span class="vlt-help-links__text"><?php echo $link['text']; ?></span>
							</span>
							<svg class="vlt-help-links__arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
						</a>
					</li>
					<?php endforeach; ?>
				</ul>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
						printf(
							/* translators: 1: Support Policy link, 2: reply time */
							esc_html__( 'Please read the %1$s first: support covers bugs and questions about our products, not custom development. We reply within %2$s, Mon–Fri, 09:00–18:00 (UTC+3).', 'toolkit' ),
							'<a href="' . esc_url( $this->utm( $this->support_policy_url, 'help-policy' ) ) . '" target="_blank" rel="noopener">' . esc_html__( 'Support Policy', 'toolkit' ) . '</a>',
							'<strong>' . esc_html__( '24 hours', 'toolkit' ) . '</strong>',
						);
?>
					</p>
				</div>

				<p class="small mt-sm">
					<?php
printf(
	/* translators: 1: link to vlthemes.me products, 2: support email link */
	esc_html__( '12 months of support included with purchases on %1$s (via Gumroad) · %2$s', 'toolkit' ),
	'<a href="' . esc_url( $this->utm( $this->products_url, 'help-support' ) ) . '" target="_blank" rel="noopener">vlthemes.me</a>',
	'<a href="mailto:' . esc_attr( $this->support_email ) . '">' . esc_html( $this->support_email ) . '</a>',
);
?>
				</p>
			</div>
		</div>
	</div>

	<!-- More Themes -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'More Themes', 'toolkit' ); ?></mark>
				<span class="vlt-badge">vlthemes.me</span>
			</div>

			<div class="vlt-widget__content">
				<p>
					<?php
printf(
	/* translators: %s: link to vlthemes.me products */
	esc_html__( 'Like this theme? Get the next one straight from %s — WordPress themes, HTML templates and plugins.', 'toolkit' ),
	'<a href="' . esc_url( $this->utm( $this->products_url, 'help-more-themes' ) ) . '" target="_blank" rel="noopener">vlthemes.me</a>',
);
?>
				</p>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
printf(
	/* translators: %s: "All-access pass" in bold */
	esc_html__( '%s — all WordPress & HTML themes in one purchase, with lifetime updates and priority support for 12 months.', 'toolkit' ),
	'<strong>' . esc_html__( 'All-access pass', 'toolkit' ) . '</strong>',
);
?>
					</p>
				</div>

				<div class="vlt-btn-group mt-xs">
					<a href="<?php echo esc_url( $this->utm( $this->products_url, 'help-more-themes' ) ); ?>" target="_blank" rel="noopener" class="button button-primary mt-sm"><?php esc_html_e( 'Browse Themes', 'toolkit' ); ?></a>
					<a href="<?php echo esc_url( $this->utm( $this->products_url . '#all-access', 'help-all-access' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary mt-sm"><?php esc_html_e( 'All-access Pass', 'toolkit' ); ?></a>
				</div>

				<p class="small mt-sm"><?php esc_html_e( 'Secure checkout via Gumroad · 14-day money-back guarantee', 'toolkit' ); ?></p>
			</div>
		</div>
	</div>

</div>
