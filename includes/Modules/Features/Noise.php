<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;
use VLT\Framework\Modules\Core\Customizer;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Noise Module
 *
 * Adds a sitewide noise/grain texture overlay, toggled from the Customizer.
 */
class Noise extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'noise';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 *
	 * The theme's Customizer framework (VLT\Framework) is loaded on
	 * 'after_setup_theme', which runs after plugins are loaded — so its
	 * class can't be checked yet here. Each callback below checks
	 * class_exists() itself once WordPress has actually reached that hook.
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_filter( 'body_class', [ $this, 'add_body_class' ] );
		add_action( 'wp_footer', [ $this, 'render_overlay' ] );
		add_action( 'vlt_fw_customizer_register', [ $this, 'customize_register' ] );
	}

	/**
	 * Register Customizer section and fields
	 */
	public function customize_register() {
		Customizer::add_section(
			'vlt_toolkit_noise',
			[
				'title'    => esc_html__( 'Noise Background', 'toolkit' ),
				'priority' => 160,
				'icon'     => 'dashicons-images-alt2',
			]
		);

		Customizer::add_field(
			[
				'type'     => 'select',
				'settings' => 'vlt_toolkit_noise_enabled',
				'section'  => 'vlt_toolkit_noise',
				'label'    => esc_html__( 'Enable', 'toolkit' ),
				'priority' => 10,
				'choices'  => [
					'no'  => esc_html__( 'Disabled', 'toolkit' ),
					'yes' => esc_html__( 'Enabled', 'toolkit' ),
				],
				'default'  => 'no',
			]
		);

		Customizer::add_field(
			[
				'type'            => 'number',
				'settings'        => 'vlt_toolkit_noise_opacity',
				'section'         => 'vlt_toolkit_noise',
				'label'           => esc_html__( 'Opacity', 'toolkit' ),
				'priority'        => 20,
				'default'         => 0.05,
				'input_attrs'     => [
					'min'  => 0,
					'max'  => 1,
					'step' => 0.01,
				],
				'active_callback' => [
					[
						'setting'  => 'vlt_toolkit_noise_enabled',
						'operator' => '==',
						'value'    => 'yes',
					],
				],
			]
		);

		Customizer::add_field(
			[
				'type'            => 'number',
				'settings'        => 'vlt_toolkit_noise_zindex',
				'section'         => 'vlt_toolkit_noise',
				'label'           => esc_html__( 'Z-Index', 'toolkit' ),
				'description'     => esc_html__( 'Stacking order of the noise overlay relative to the rest of the page.', 'toolkit' ),
				'priority'        => 30,
				'default'         => 9999,
				'active_callback' => [
					[
						'setting'  => 'vlt_toolkit_noise_enabled',
						'operator' => '==',
						'value'    => 'yes',
					],
				],
			]
		);
	}

	/**
	 * Whether the sitewide noise overlay is enabled
	 *
	 * @return bool
	 */
	public function is_enabled() {
		if ( !class_exists( Customizer::class ) ) {
			return false;
		}

		return 'yes' === Customizer::get_option( 'vlt_toolkit_noise_enabled', 'no' );
	}

	/**
	 * Get the overlay opacity
	 *
	 * @return float
	 */
	public function get_opacity() {
		return floatval( Customizer::get_option( 'vlt_toolkit_noise_opacity', 0.05 ) );
	}

	/**
	 * Get the overlay z-index
	 *
	 * @return int
	 */
	public function get_zindex() {
		return (int) Customizer::get_option( 'vlt_toolkit_noise_zindex', 9999 );
	}

	/**
	 * Add a body class when the sitewide overlay is enabled
	 *
	 * @param array $classes body classes
	 *
	 * @return array
	 */
	public function add_body_class( $classes ) {
		if ( $this->is_enabled() ) {
			$classes[] = 'vlt-noise-yes';
		}

		return $classes;
	}

	/**
	 * Enqueue the noise overlay assets
	 */
	public function enqueue_assets() {
		if ( !$this->is_enabled() ) {
			return;
		}

		wp_enqueue_style(
			'vlt-noise-module',
			VLT_TOOLKIT_URL . 'assets/css/feature-noise.css',
			[],
			VLT_TOOLKIT_VERSION
		);

		wp_add_inline_style(
			'vlt-noise-module',
			sprintf(
				'body.vlt-noise-yes { position: relative; --vlt-noise-opacity: %s; --vlt-noise-zindex: %d; }',
				esc_attr( $this->get_opacity() ),
				$this->get_zindex()
			)
		);
	}

	/**
	 * Print the noise overlay markup at the end of the body
	 */
	public function render_overlay() {
		if ( !$this->is_enabled() ) {
			return;
		}

		echo '<div class="vlt-noise-overlay" style="position:fixed;"></div>';
	}
}
