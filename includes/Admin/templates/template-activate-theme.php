<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<div class="vlt-masonry-item">

		<?php
		/**
		 * Theme License Activation Form
		 * Hook for theme activation form
		 */
		do_action( 'vlt_toolkit_print_activation_form' );
?>

	</div>

	<?php
	/**
	 * Support reminder column: only while support ends soon or is over (ThemeActivation/Init.php)
	 */
	do_action( 'vlt_toolkit_print_support_reminder' );
	?>

	<div class="vlt-masonry-item">
		<div class="vlt-widget">

			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'No License Key?', 'toolkit' ); ?></mark>
				<span class="vlt-badge">vlthemes.me</span>
			</div>

			<div class="vlt-widget__content">
				<p>
					<?php
			echo wp_kses(
				__( 'You can <strong>skip the activation</strong> — the core features of the theme work without it. Activation unlocks one-click updates right here in the dashboard.', 'toolkit' ),
				[
					'strong' => [],
				],
			);
?>
				</p>

				<p class="mt-sm">
					<?php
printf(
	/* translators: 1: theme name, 2: link to vlthemes.me */
	esc_html__( 'Got %1$s without a license (for example, through a subscription)? Get your own license on %2$s — lifetime updates and 12 months of support included.', 'toolkit' ),
	'<strong>' . esc_html( $this->theme_name ) . '</strong>',
	'<a href="' . esc_url( $this->utm( $this->products_url . $this->theme_slug . '/', 'activate-no-license' ) ) . '" target="_blank" rel="noopener">vlthemes.me</a>',
);
?>
				</p>

				<div class="notice notice-info inline mt-sm">
					<p><?php esc_html_e( 'Support is provided only for licensed copies of the theme.', 'toolkit' ); ?></p>
				</div>

				<div class="vlt-btn-group mt-xs">
					<a href="<?php echo esc_url( $this->utm( $this->products_url . $this->theme_slug . '/', 'activate-get-license' ) ); ?>" target="_blank" rel="noopener" class="button button-primary mt-sm"><?php esc_html_e( 'Get a License', 'toolkit' ); ?></a>
					<a href="<?php echo esc_url( $this->utm( $this->products_url . '#all-access', 'activate-all-access' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary mt-sm"><?php esc_html_e( 'All-access Pass', 'toolkit' ); ?></a>
				</div>

				<p class="small mt-sm"><?php esc_html_e( 'Secure checkout via Gumroad · 14-day money-back guarantee', 'toolkit' ); ?></p>

			</div>

		</div>
		<!-- /.vlt-widget -->
	</div>

</div>