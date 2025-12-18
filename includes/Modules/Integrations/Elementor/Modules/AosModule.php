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
				'label' => esc_html__( 'VLT Entrance Animation', 'toolkit' ),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_aos_animation',
			[
				'label'              => esc_html__( 'Entrance Animation', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'options'            => $this->get_aos_animations(),
				'default'            => 'none',
			]
		);

		$element->add_control(
			'vlt_aos_duration',
			[
				'label'              => esc_html__( 'Duration (seconds)', 'toolkit' ),
				'description'        => esc_html__( 'Animation duration in seconds', 'toolkit' ),
				'type'               => Controls_Manager::SLIDER,
				'size_units'         => [ 'px' ],
				'range'              => [
					'px' => [
						'min'  => 0.05,
						'max'  => 3,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 1,
				],
				'condition'          => [ 'vlt_aos_animation!' => 'none' ],
			]
		);

		$element->add_control(
			'vlt_aos_delay',
			[
				'label'       => esc_html__( 'Delay (seconds)', 'toolkit' ),
				'description' => esc_html__( 'Delay before animation starts in seconds', 'toolkit' ),
				'type'        => Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 3,
						'step' => 0.05,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 0,
				],
				'condition' => [ 'vlt_aos_animation!' => 'none' ],
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
		$settings = $element->get_settings_for_display();

		if ( empty( $settings['vlt_aos_animation'] ) || 'none' === $settings['vlt_aos_animation'] ) {
			return;
		}

		// Add animation
		$element->add_render_attribute( '_wrapper', 'data-aos', $settings['vlt_aos_animation'] );

		// Add duration (convert seconds to milliseconds)
		if ( !empty( $settings['vlt_aos_duration']['size'] ) ) {
			$duration_ms = $settings['vlt_aos_duration']['size'] * 1000;
			$element->add_render_attribute( '_wrapper', 'data-aos-duration', $duration_ms );
		}

		// Add delay (convert seconds to milliseconds)
		if ( !empty( $settings['vlt_aos_delay']['size'] ) ) {
			$delay_ms = $settings['vlt_aos_delay']['size'] * 1000;
			$element->add_render_attribute( '_wrapper', 'data-aos-delay', $delay_ms );
		}

		// Add offset
		if ( isset( $settings['vlt_aos_offset'] ) && '' !== $settings['vlt_aos_offset'] ) {
			$element->add_render_attribute( '_wrapper', 'data-aos-offset', $settings['vlt_aos_offset'] );
		}

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
