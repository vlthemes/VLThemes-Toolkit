<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Stack;
use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Parallax Extension
 *
 * Adds parallax effects to Elementor elements using Rellax.js
 */
class ParallaxModule extends Module_Base {

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
		return 'parallax';
	}

	/**
	 * Register module scripts
	 */
	public function register_scripts() {
		// Enqueue Rellax
		wp_enqueue_script( 'rellax', VLT_TOOLKIT_URL . 'assets/vendors/js/rellax.js', [], VLT_TOOLKIT_VERSION, true );

		// Enqueue module script
		wp_enqueue_script(
			'vlt-parallax-module',
			plugin_dir_url( __FILE__ ) . 'js/ParallaxModule.js',
			[ 'jquery', 'elementor-frontend', 'rellax' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register Parallax controls
	 *
	 * @param Element_Base $element Elementor element instance
	 */
	public function register_controls( Element_Base $element ) {
		// Check if controls already registered to prevent duplicate registration
		$controls = $element->get_controls();
		if ( isset( $controls['vlt_section_parallax'] ) ) {
			return;
		}

		$element->start_controls_section(
			'vlt_section_parallax',
			[
				'label' => esc_html__( 'Parallax', 'toolkit' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_rellax_enable',
			[
				'label'        => esc_html__( 'Parallax', 'toolkit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => 'no',
				'prefix_class' => 'vlt-parallax-',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_rellax_speed',
			[
				'label'       => esc_html__( 'Speed', 'toolkit' ),
				'description' => esc_html__( 'Negative = up, Positive = down', 'toolkit' ),
				'type'        => Controls_Manager::SLIDER,
				'range'       => [
					'px' => [
						'min'  => -10,
						'max'  => 10,
						'step' => 1,
					],
				],
				'default' => [
					'size' => 2,
				],
				'frontend_available' => true,
				'condition' => [
					'vlt_rellax_enable' => [ 'yes' ],
				],
			]
		);

		$element->add_control(
			'vlt_advanced_options_popover',
			[
				'label'     => esc_html__( 'Advanced Options', 'toolkit' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_rellax_enable' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_rellax_percentage',
			[
				'label'       => esc_html__( 'Percentage', 'toolkit' ),
				'type'        => Controls_Manager::SLIDER,
				'description' => esc_html__( 'Element position in scroll (0 = start, 0.5 = center, 1 = end)', 'toolkit' ),
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_rellax_zindex',
			[
				'label'              => esc_html__( 'Z-Index', 'toolkit' ),
				'description'        => esc_html__( 'Z-index for parallax 3D effect', 'toolkit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => -100,
				'max'                => 100,
				'step'               => 1,
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_rellax_min',
			[
				'label'              => esc_html__( 'Min Offset', 'toolkit' ),
				'description'        => esc_html__( 'Minimum offset in pixels (limits movement)', 'toolkit' ),
				'type'               => Controls_Manager::NUMBER,
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_rellax_max',
			[
				'label'              => esc_html__( 'Max Offset', 'toolkit' ),
				'description'        => esc_html__( 'Maximum offset in pixels (limits movement)', 'toolkit' ),
				'type'               => Controls_Manager::NUMBER,
				'frontend_available' => true,
			]
		);

		$element->end_popover();

		$element->end_controls_section();
	}

	/**
	 * Render Parallax attributes
	 *
	 * @param Element_Base $element Elementor element instance
	 */
	public function render_attributes( Element_Base $element ) {
		$settings = $element->get_settings_for_display();

		if ( empty( $settings['vlt_rellax_enable'] ) || 'yes' !== $settings['vlt_rellax_enable'] ) {
			return;
		}

		// Add rellax class
		$element->add_render_attribute( '_wrapper', 'class', 'rellax' );

		// Speed - always add, even if default value
		$speed = isset( $settings['vlt_rellax_speed']['size'] ) ? $settings['vlt_rellax_speed']['size'] : -3;
		$element->add_render_attribute( '_wrapper', 'data-rellax-speed', $speed );

		// Percentage
		if ( ! empty( $settings['vlt_rellax_percentage']['size'] ) ) {
			$element->add_render_attribute( '_wrapper', 'data-rellax-percentage', $settings['vlt_rellax_percentage']['size'] );
		}

		// Z-index
		if ( isset( $settings['vlt_rellax_zindex'] ) && is_numeric( $settings['vlt_rellax_zindex'] ) ) {
			$element->add_render_attribute( '_wrapper', 'data-rellax-zindex', $settings['vlt_rellax_zindex'] );
		}

		// Min offset
		if ( isset( $settings['vlt_rellax_min'] ) && is_numeric( $settings['vlt_rellax_min'] ) ) {
			$element->add_render_attribute( '_wrapper', 'data-rellax-min', $settings['vlt_rellax_min'] );
		}

		// Max offset
		if ( isset( $settings['vlt_rellax_max'] ) && is_numeric( $settings['vlt_rellax_max'] ) ) {
			$element->add_render_attribute( '_wrapper', 'data-rellax-max', $settings['vlt_rellax_max'] );
		}
	}

	/**
	 * Register WordPress hooks
	 */
	protected function add_actions() {
		// Register controls for containers
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ] );

		// Register controls for common widgets
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_controls' ] );

		// Render for containers
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_attributes' ] );

		// Render for common widgets
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'render_attributes' ] );

		// Enqueue scripts on frontend and editor
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
	}
}
