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
		<img src="<?php echo esc_url( get_template_directory_uri() . '/screenshot.jpg' ); ?>" alt="<?php echo esc_attr( $this->theme_name ); ?>">
	</div>

	<div class="vlt-widget-welcome__content">
		<span class="vlt-badge"><?php printf( esc_html__( 'v%s', 'toolkit' ), $this->theme_version ); ?></span>

		<h1><?php printf( esc_html__( 'Getting started with %s', 'toolkit' ), $this->theme_name ); ?></h1>

		<div class="notice notice-info inline mt-sm">
			<p><?php esc_html_e( 'Your theme is successfully installed and ready to go!', 'toolkit' ); ?></p>
		</div>

		<p class="mt-sm">
			<?php esc_html_e( 'Thank you for selecting our premium themes. Your journey to a beautiful, high-performing website starts now.', 'toolkit' ); ?>
		</p>

		<p class="mt-sm">
			<?php esc_html_e( 'From sleek portfolios to powerful business hubs, our themes are crafted to impress and convert. You\'re not just building a site — you\'re launching a digital experience.', 'toolkit' ); ?>
		</p>

		<p class="mt-sm"><?php esc_html_e( 'Let\'s make it unforgettable.', 'toolkit' ); ?></p>
	</div>
</div>