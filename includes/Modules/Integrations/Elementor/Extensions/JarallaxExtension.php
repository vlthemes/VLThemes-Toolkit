<?php

namespace VLT\Helper\Modules\Integrations\Elementor\Extensions;

use VLT\Helper\Modules\Integrations\Elementor\BaseExtension;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jarallax Extension
 *
 * Handles Jarallax parallax background effects
 */
class JarallaxExtension extends BaseExtension {

	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'jarallax';

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
		// Register controls for containers only (background section)
		add_action( 'elementor/element/container/section_background/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Render for containers
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_attributes' ] );
	}

	/**
	 * Register Jarallax parallax controls
	 *
	 * @param object $element Elementor element.
	 * @param array  $args    Element arguments.
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_jarallax',
			[
				'label' => esc_html__( 'VLT Jarallax Background', 'vlt-helper' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			]
		);

		$element->add_control(
			'vlt_jarallax_enabled',
			[
				'label'        => esc_html__( 'Enable', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'jarallax',
				'prefix_class' => '',
			]
		);

		$element->add_control(
			'vlt_jarallax_settings_popover',
			[
				'label'     => esc_html__( 'Jarallax Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_jarallax_speed',
			[
				'label'      => esc_html__( 'Speed', 'vlt-helper' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => -1,
						'max'  => 2,
						'step' => 0.1,
					],
				],
				'default' => [
					'size' => 0.9,
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_type',
			[
				'label'   => esc_html__( 'Type', 'vlt-helper' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''               => esc_html__( 'Scroll', 'vlt-helper' ),
					'scale'          => esc_html__( 'Scale', 'vlt-helper' ),
					'opacity'        => esc_html__( 'Opacity', 'vlt-helper' ),
					'scroll-opacity' => esc_html__( 'Scroll + Opacity', 'vlt-helper' ),
					'scale-opacity'  => esc_html__( 'Scale + Opacity', 'vlt-helper' ),
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_img_size',
			[
				'label'   => esc_html__( 'Image Size', 'vlt-helper' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''        => esc_html__( 'Default', 'vlt-helper' ),
					'auto'    => esc_html__( 'Auto', 'vlt-helper' ),
					'cover'   => esc_html__( 'Cover', 'vlt-helper' ),
					'contain' => esc_html__( 'Contain', 'vlt-helper' ),
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_img_position',
			[
				'label'   => esc_html__( 'Image Position', 'vlt-helper' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''              => esc_html__( 'Default', 'vlt-helper' ),
					'center center' => esc_html__( 'Center Center', 'vlt-helper' ),
					'center left'   => esc_html__( 'Center Left', 'vlt-helper' ),
					'center right'  => esc_html__( 'Center Right', 'vlt-helper' ),
					'top center'    => esc_html__( 'Top Center', 'vlt-helper' ),
					'top left'      => esc_html__( 'Top Left', 'vlt-helper' ),
					'top right'     => esc_html__( 'Top Right', 'vlt-helper' ),
					'bottom center' => esc_html__( 'Bottom Center', 'vlt-helper' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'vlt-helper' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'vlt-helper' ),
					'custom'        => esc_html__( 'Custom', 'vlt-helper' ),
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_img_position_custom',
			[
				'label'       => esc_html__( 'Custom Position', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '50% 50%',
				'condition'   => [
					'vlt_jarallax_img_position' => 'custom',
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_video_url',
			[
				'label'       => esc_html__( 'Video URL', 'vlt-helper' ),
				'description' => esc_html__( 'YouTube, Vimeo or local video. Use "mp4:" prefix for self-hosted.', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://www.youtube.com/watch?v=...',
			]
		);

		$element->end_popover();

		$element->end_controls_section();

		// Allow themes to add custom Jarallax controls
		do_action( 'vlt_helper_elementor_jarallax_controls', $element, $args );
	}

	/**
	 * Render Jarallax attributes
	 *
	 * @param object $widget Elementor widget instance.
	 */
	public function render_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_jarallax_enabled'] ) || $settings['vlt_jarallax_enabled'] !== 'jarallax' ) {
			return;
		}

		// Add jarallax class and speed
		if ( ! empty( $settings['vlt_jarallax_speed']['size'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-jarallax', '' );
			$widget->add_render_attribute( '_wrapper', 'data-speed', $settings['vlt_jarallax_speed']['size'] );
		}

		// Add video URL
		if ( ! empty( $settings['vlt_jarallax_video_url'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-jarallax-video', $settings['vlt_jarallax_video_url'] );
		}

		// Add type
		if ( ! empty( $settings['vlt_jarallax_type'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-type', $settings['vlt_jarallax_type'] );
		}

		// Add image size
		if ( ! empty( $settings['vlt_jarallax_img_size'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-img-size', $settings['vlt_jarallax_img_size'] );
		}

		// Add image position
		$position = $settings['vlt_jarallax_img_position'] ?? '';
		if ( $position === 'custom' ) {
			$position = $settings['vlt_jarallax_img_position_custom'] ?? '';
		}

		if ( ! empty( $position ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-img-position', $position );
		}
	}
}
