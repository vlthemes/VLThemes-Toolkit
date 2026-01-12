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
 * AOS Animation Extension
 *
 * Handles AOS (Animate On Scroll) animations
 */
class AosModule extends Module_Base {

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
		return 'aos';
	}

	/**
	 * Register module scripts
	 */
	public function register_scripts() {

		wp_enqueue_style(
			'aos-animations',
			VLT_TOOLKIT_URL . 'assets/vendors/css/aos-animations.css',
			[],
			VLT_TOOLKIT_VERSION
		);

		wp_enqueue_script( 'aos', VLT_TOOLKIT_URL . 'assets/vendors/js/aos.js', [], VLT_TOOLKIT_VERSION, true );

		wp_enqueue_script(
			'vlt-aos-extension',
			plugin_dir_url( __FILE__ ) . 'js/AosModule.js',
			[ 'jquery', 'elementor-frontend', 'aos' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register AOS animation controls
	 *
	 * @param Element_Base $element elementor element
	 */
	public function register_controls( Element_Base $element ) {
		$element->start_controls_section(
			'vlt_section_aos_animation',
			[
				'label' => esc_html__( 'Entrance Animation', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_responsive_control(
			'vlt_aos_animation',
			[
				'label'              => esc_html__( 'Entrance Animation', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'options'            => $this->get_aos_animations(),
				'default'            => 'none',
				'frontend_available' => true
			]
		);

		$element->add_control(
			'vlt_aos_duration',
			[
				'label'              => esc_html__( 'Duration (seconds)', 'toolkit' ),
				'description'        => esc_html__( 'Animation duration in seconds', 'toolkit' ),
				'type'               => Controls_Manager::NUMBER,
				'min'                => 0,
				'max'                => 3,
				'step'               => 0.05,
				'default'            => 1,
				'frontend_available' => true,
				'condition'          => [ 'vlt_aos_animation!' => 'none' ],
			]
		);

		$element->add_control(
			'vlt_aos_delay',
			[
				'label'       => esc_html__( 'Delay (seconds)', 'toolkit' ),
				'description' => esc_html__( 'Delay before animation starts in seconds', 'toolkit' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 3,
				'step'        => 0.05,
				'default'     => 0,
				'render_type' => 'none',
				'frontend_available' => true,
				'condition'   => [ 'vlt_aos_animation!' => 'none' ],
			]
		);

		$element->add_control(
			'vlt_aos_easing',
			[
				'label'       => esc_html__( 'Easing', 'toolkit' ),
				'description' => esc_html__( 'Animation easing function', 'toolkit' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'frontend_available' => true,
				'options'     => [
					''                  => esc_html__( 'Default', 'toolkit' ),
					'linear'            => esc_html__( 'Linear', 'toolkit' ),
					'ease'              => esc_html__( 'Ease (default)', 'toolkit' ),
					'ease-in'           => esc_html__( 'Ease In', 'toolkit' ),
					'ease-out'          => esc_html__( 'Ease Out', 'toolkit' ),
					'ease-in-out'       => esc_html__( 'Ease In Out', 'toolkit' ),
					'ease-in-back'      => esc_html__( 'Ease In Back', 'toolkit' ),
					'ease-out-back'     => esc_html__( 'Ease Out Back', 'toolkit' ),
					'ease-in-out-back'  => esc_html__( 'Ease In Out Back', 'toolkit' ),
					'ease-in-sine'      => esc_html__( 'Ease In Sine', 'toolkit' ),
					'ease-out-sine'     => esc_html__( 'Ease Out Sine', 'toolkit' ),
					'ease-in-out-sine'  => esc_html__( 'Ease In Out Sine', 'toolkit' ),
					'ease-in-quad'      => esc_html__( 'Ease In Quad', 'toolkit' ),
					'ease-out-quad'     => esc_html__( 'Ease Out Quad', 'toolkit' ),
					'ease-in-out-quad'  => esc_html__( 'Ease In Out Quad', 'toolkit' ),
					'ease-in-cubic'     => esc_html__( 'Ease In Cubic', 'toolkit' ),
					'ease-out-cubic'    => esc_html__( 'Ease Out Cubic', 'toolkit' ),
					'ease-in-out-cubic' => esc_html__( 'Ease In Out Cubic', 'toolkit' ),
					'ease-in-quart'     => esc_html__( 'Ease In Quart', 'toolkit' ),
					'ease-out-quart'    => esc_html__( 'Ease Out Quart', 'toolkit' ),
					'ease-in-out-quart' => esc_html__( 'Ease In Out Quart', 'toolkit' ),
				],
				'condition' => [
					'vlt_aos_animation!' => 'none',
				],
			]
		);

		$element->add_control(
			'vlt_aos_offset',
			[
				'label'       => esc_html__( 'Offset (px)', 'toolkit' ),
				'description' => esc_html__( 'Distance from bottom of viewport to start', 'toolkit' ),
				'type'        => Controls_Manager::NUMBER,
				'min'         => -500,
				'max'         => 500,
				'step'        => 10,
				'frontend_available' => true,
				'condition'   => [ 'vlt_aos_animation!' => 'none' ],
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Render AOS attributes
	 *
	 * @param object Element_Base $element Elementor element instance
	 */
	public function render_attributes( Element_Base $element ) {
		// JavaScript will handle all data attribute rendering dynamically based on breakpoints
		// No need to render attributes in PHP
	}

	/**
	 * Initialize extension
	 */
	protected function init() {
		// Extension initialization
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

	/**
	 * Get AOS animations list
	 *
	 * @return array array of animations
	 */
	private function get_aos_animations() {
		// Check if AOS module is loaded
		if ( !class_exists( 'VLT\Toolkit\Modules\Features\AOS' ) ) {
			return [ 'none' => esc_html__( 'None', 'toolkit' ) ];
		}

		return \VLT\Toolkit\Modules\Features\AOS::get_animations();
	}
}
