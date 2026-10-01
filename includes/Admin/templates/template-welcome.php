<?php

/**
 * Dashboard Welcome Template
 *
 * @author: VLThemes
 *
 * @version: 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="vlt-widget-welcome">
	<div class="vlt-widget-welcome__image">
		<?php if ( $screenshot = $this->get_screenshot_url() ) : ?>
			<img src="<?php echo esc_url( $screenshot ); ?>" alt="<?php echo esc_attr( $this->theme_name ); ?>">
		<?php else : ?>
			<span class="vlt-widget-welcome__placeholder"><?php echo esc_html( $this->theme_name ); ?></span>
		<?php endif; ?>
	</div>

	<div class="vlt-widget-welcome__content">
		<span class="vlt-badge"><?php /* translators: %s: theme version */ printf( esc_html__( 'v%s', 'toolkit' ), esc_html( $this->theme_version ) ); ?></span>

		<h1><?php /* translators: %s: theme name */ printf( esc_html__( 'Getting started with %s', 'toolkit' ), esc_html( $this->theme_name ) ); ?></h1>

		<div class="notice notice-success inline mt-sm">
			<p><?php esc_html_e( 'The theme is installed and ready to go.', 'toolkit' ); ?></p>
		</div>

		<p class="mt-sm">
			<?php
			printf(
				/* translators: %s: theme name */
				esc_html__( 'Thanks for choosing %s. Activate your license, install the required plugins and import a demo — your site will be ready in a few minutes.', 'toolkit' ),
				esc_html( $this->theme_name ),
			);
?>
		</p>

		<p class="mt-sm">
			<?php
printf(
	/* translators: %s: link to vlthemes.me */
	esc_html__( 'Activate the theme with your license key or purchase code to get updates right here in the dashboard. Guides and answers to common questions live on %s.', 'toolkit' ),
	'<a href="' . esc_url( $this->utm( $this->site_url, 'welcome' ) ) . '" target="_blank" rel="noopener">vlthemes.me</a>',
);
?>
		</p>

		<div class="notice inline mt-sm">
			<p>
				<?php
printf(
	/* translators: 1: "Next theme?" in bold, 2: link to vlthemes.me products */
	esc_html__( '%1$s Get it directly on %2$s — lifetime updates and 12 months of support included.', 'toolkit' ),
	'<strong>' . esc_html__( 'Next theme?', 'toolkit' ) . '</strong>',
	'<a href="' . esc_url( $this->utm( $this->products_url, 'welcome-next-theme' ) ) . '" target="_blank" rel="noopener">vlthemes.me</a>',
);
?>
			</p>
		</div>

		<div class="vlt-btn-group mt-md">
			<a href="<?php echo esc_url( $this->utm( $this->docs_url . $this->theme_slug . '/', 'welcome' ) ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php esc_html_e( 'Read the docs', 'toolkit' ); ?></a>
			<a href="<?php echo esc_url( $this->utm( $this->products_url, 'welcome' ) ); ?>" target="_blank" rel="noopener" class="button button-secondary"><?php esc_html_e( 'Browse all themes', 'toolkit' ); ?></a>
		</div>
	</div>
</div>
