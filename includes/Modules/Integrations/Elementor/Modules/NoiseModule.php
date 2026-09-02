<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Noise Background Extension
 *
 * Adds a noise/grain texture overlay block to Elementor containers
 */
class NoiseModule extends Module_Base {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'noise';
	}

	/**
	 * Register module scripts and styles
	 */
	public function register_scripts() {
		wp_enqueue_style(
			'vlt-noise-module',
			plugin_dir_url( __FILE__ ) . 'css/NoiseModule.css',
			[],
			VLT_TOOLKIT_VERSION
		);

		wp_enqueue_script(
			'vlt-noise-module',
			plugin_dir_url( __FILE__ ) . 'js/NoiseModule.js',
			[ 'jquery', 'elementor-frontend' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register Noise controls
	 *
	 * @param Element_Base $element Elementor element instance
	 */
	public function register_controls( Element_Base $element ) {

		$element->start_controls_section(
			'vlt_section_noise',
			[
				'label' => esc_html__( 'Noise Background', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$element->add_control(
			'vlt_noise_enable',
			[
				'label'              => esc_html__( 'Noise Background', 'toolkit' ),
				'type'               => Controls_Manager::SWITCHER,
				'return_value'       => 'yes',
				'default'            => '',
				'prefix_class'       => 'vlt-noise-',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_noise_opacity',
			[
				'label'     => esc_html__( 'Opacity', 'toolkit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.01,
					],
				],
				'default'   => [
					'size' => 0.05,
				],
				'selectors' => [
					'{{WRAPPER}}' => '--vlt-noise-opacity: {{SIZE}};',
				],
				'condition' => [
					'vlt_noise_enable' => 'yes',
				],
			]
		);

		$element->add_control(
			'vlt_noise_zindex',
			[
				'label'       => esc_html__( 'Z-Index', 'toolkit' ),
				'description' => esc_html__( 'Stacking order of the noise overlay relative to the container content', 'toolkit' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 1,
				'selectors'   => [
					'{{WRAPPER}}' => '--vlt-noise-zindex: {{VALUE}};',
				],
				'condition'   => [
					'vlt_noise_enable' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Register WordPress hooks
	 */
	protected function add_actions() {
		// Register controls for containers only
		add_action( 'elementor/element/container/section_background/after_section_end', [ $this, 'register_controls' ] );

		// Enqueue scripts on frontend and editor
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
	}
}
