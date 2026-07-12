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
		// Enqueue gsap
		wp_enqueue_script( 'gsap', VLT_TOOLKIT_URL . 'assets/vendors/js/gsap.js', [], VLT_TOOLKIT_VERSION, true );
		wp_enqueue_script( 'scrolltrigger', VLT_TOOLKIT_URL . 'assets/vendors/js/gsap-scrolltrigger.js', [], VLT_TOOLKIT_VERSION, true );

		// Enqueue module script
		wp_enqueue_script(
			'vlt-parallax-module',
			plugin_dir_url( __FILE__ ) . 'js/ParallaxModule.js',
			[ 'jquery', 'elementor-frontend', 'gsap', 'scrolltrigger' ],
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

		$element->start_controls_section(
			'vlt_section_parallax',
			[
				'label' => esc_html__( 'Parallax', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_parallax_enable',
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
			'vlt_parallax_speed',
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
					'vlt_parallax_enable' => [ 'yes' ],
				],
			]
		);

		$element->add_control(
			'vlt_advanced_options_popover',
			[
				'label'     => esc_html__( 'Settings', 'toolkit' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_parallax_enable' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_parallax_percentage',
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
				'default' => [
					'size' => 0.5,
				],
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_parallax_zindex',
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
			'vlt_parallax_min',
			[
				'label'              => esc_html__( 'Min Offset', 'toolkit' ),
				'description'        => esc_html__( 'Minimum offset in pixels (limits movement)', 'toolkit' ),
				'type'               => Controls_Manager::NUMBER,
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_parallax_max',
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
	 * Register WordPress hooks
	 */
	protected function add_actions() {
		// Register controls for containers
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ] );

		// Register controls for common widgets
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_controls' ] );

		// Enqueue scripts on frontend and editor
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
	}
}
