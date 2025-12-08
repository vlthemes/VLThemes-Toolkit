<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Extensions;

use VLT\Toolkit\Modules\Integrations\Elementor\BaseExtension;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AOS Animation Extension
 *
 * Handles AOS (Animate On Scroll) animations
 */
class AosExtension extends BaseExtension {
	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'aos';

	/**
	 * Register extension scripts
	 */
	public function register_scripts() {
		wp_enqueue_style( 'aos' );

		// Enqueue animation-based CSS (instead of transition-based)
		wp_enqueue_style(
			'aos-animations',
			VLT_TOOLKIT_URL . 'assets/vendors/css/aos-animations.css',
			[ 'aos' ],
			VLT_TOOLKIT_VERSION
		);

		wp_enqueue_script(
			'vlt-aos-extension',
			plugin_dir_url( __FILE__ ) . 'js/AosExtension.js',
			[ 'aos' ],
			VLT_TOOLKIT_VERSION,
			true,
		);
	}

	/**
	 * Register AOS animation controls
	 *
	 * @param object $element elementor element
	 * @param array  $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_aos_animation',
			[
				'label' => esc_html__( 'VLT Entrance Animation', 'toolkit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			],
		);

		$element->add_control(
			'vlt_aos_animation',
			[
				'label'   => esc_html__( 'Entrance Animation', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_aos_animations(),
				'default' => 'none',
			],
		);

		$element->add_control(
			'vlt_aos_duration',
			[
				'label'       => esc_html__( 'Duration (seconds)', 'toolkit' ),
				'description' => esc_html__( 'Animation duration in seconds', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
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
				'condition' => [ 'vlt_aos_animation!' => 'none' ],
			],
		);

		$element->add_control(
			'vlt_aos_delay',
			[
				'label'       => esc_html__( 'Delay (seconds)', 'toolkit' ),
				'description' => esc_html__( 'Delay before animation starts in seconds', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
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
			],
		);

		$element->add_control(
			'vlt_aos_offset',
			[
				'label'       => esc_html__( 'Offset (px)', 'toolkit' ),
				'description' => esc_html__( 'Distance from bottom of viewport to start', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => -500,
				'max'         => 500,
				'step'        => 10,
				'condition'   => [ 'vlt_aos_animation!' => 'none' ],
			],
		);

		$element->end_controls_section();

		// Allow themes to add custom AOS controls
		do_action( 'vlt_toolkit_elementor_aos_controls', $element, $args );
	}

	/**
	 * Render AOS attributes
	 *
	 * @param object $widget elementor widget instance
	 */
	public function render_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_aos_animation'] ) || 'none' === $settings['vlt_aos_animation'] ) {
			return;
		}

		// Add animation
		$widget->add_render_attribute( '_wrapper', 'data-aos', $settings['vlt_aos_animation'] );

		// Add duration (convert seconds to milliseconds)
		if ( !empty( $settings['vlt_aos_duration']['size'] ) ) {
			$duration_ms = $settings['vlt_aos_duration']['size'] * 1000;
			$widget->add_render_attribute( '_wrapper', 'data-aos-duration', $duration_ms );
		}

		// Add delay (convert seconds to milliseconds)
		if ( !empty( $settings['vlt_aos_delay']['size'] ) ) {
			$delay_ms = $settings['vlt_aos_delay']['size'] * 1000;
			$widget->add_render_attribute( '_wrapper', 'data-aos-delay', $delay_ms );
		}

		// Add offset
		if ( isset( $settings['vlt_aos_offset'] ) && '' !== $settings['vlt_aos_offset'] ) {
			$widget->add_render_attribute( '_wrapper', 'data-aos-offset', $settings['vlt_aos_offset'] );
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
	protected function register_hooks() {
		// Register controls for containers
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Register controls for common widgets
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Render for containers
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_attributes' ] );

		// Render for common widgets
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'render_attributes' ] );
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
