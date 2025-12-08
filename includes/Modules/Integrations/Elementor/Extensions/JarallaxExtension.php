<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Extensions;

use VLT\Toolkit\Modules\Integrations\Elementor\BaseExtension;

if ( !defined( 'ABSPATH' ) ) {
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
	 * Register extension scripts
	 */
	public function register_scripts() {
		wp_enqueue_style( 'jarallax' );
		wp_enqueue_script(
			'vlt-jarallax-extension',
			plugin_dir_url( __FILE__ ) . 'js/JarallaxExtension.js',
			[ 'jarallax', 'jarallax-video' ],
			VLT_TOOLKIT_VERSION,
			true,
		);
	}

	/**
	 * Register Jarallax parallax controls
	 *
	 * @param object $element elementor element
	 * @param array  $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_jarallax',
			[
				'label' => esc_html__( 'VLT Jarallax Background', 'toolkit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
			],
		);

		$element->add_control(
			'vlt_jarallax_enabled',
			[
				'label'        => esc_html__( 'Enable', 'toolkit' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'jarallax',
				'prefix_class' => '',
			],
		);

		$element->add_control(
			'vlt_jarallax_image',
			[
				'label'     => esc_html__( 'Background Image', 'toolkit' ),
				'type'      => \Elementor\Controls_Manager::MEDIA,
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->add_group_control(
			\Elementor\Group_Control_Image_Size::get_type(),
			[
				'name'      => 'vlt_jarallax_image',
				'default'   => 'full',
				'condition' => [
					'vlt_jarallax_enabled' => 'jarallax',
					'vlt_jarallax_image[url]!' => ''
				],
			]
		);

		$element->add_control(
			'vlt_jarallax_speed',
			[
				'label'      => esc_html__( 'Speed', 'toolkit' ),
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
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_type',
			[
				'label'   => esc_html__( 'Type', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''               => esc_html__( 'Scroll', 'toolkit' ),
					'scale'          => esc_html__( 'Scale', 'toolkit' ),
					'opacity'        => esc_html__( 'Opacity', 'toolkit' ),
					'scroll-opacity' => esc_html__( 'Scroll + Opacity', 'toolkit' ),
					'scale-opacity'  => esc_html__( 'Scale + Opacity', 'toolkit' ),
				],
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_img_size',
			[
				'label'   => esc_html__( 'Image Size', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''        => esc_html__( 'Default', 'toolkit' ),
					'auto'    => esc_html__( 'Auto', 'toolkit' ),
					'cover'   => esc_html__( 'Cover', 'toolkit' ),
					'contain' => esc_html__( 'Contain', 'toolkit' ),
				],
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_img_position',
			[
				'label'   => esc_html__( 'Image Position', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => [
					''              => esc_html__( 'Default', 'toolkit' ),
					'center center' => esc_html__( 'Center Center', 'toolkit' ),
					'center left'   => esc_html__( 'Center Left', 'toolkit' ),
					'center right'  => esc_html__( 'Center Right', 'toolkit' ),
					'top center'    => esc_html__( 'Top Center', 'toolkit' ),
					'top left'      => esc_html__( 'Top Left', 'toolkit' ),
					'top right'     => esc_html__( 'Top Right', 'toolkit' ),
					'bottom center' => esc_html__( 'Bottom Center', 'toolkit' ),
					'bottom left'   => esc_html__( 'Bottom Left', 'toolkit' ),
					'bottom right'  => esc_html__( 'Bottom Right', 'toolkit' ),
					'custom'        => esc_html__( 'Custom', 'toolkit' ),
				],
				'condition' => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_img_position_custom',
			[
				'label'       => esc_html__( 'Custom Position', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => '50% 50%',
				'condition'   => [
					'vlt_jarallax_enabled'      => 'jarallax',
					'vlt_jarallax_img_position' => 'custom',
				],
			],
		);

		$element->add_control(
			'vlt_jarallax_video_url',
			[
				'label'       => esc_html__( 'Video URL', 'toolkit' ),
				'description' => esc_html__( 'YouTube, Vimeo or local video. Use "mp4:" prefix for self-hosted.', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'condition'   => [ 'vlt_jarallax_enabled' => 'jarallax' ],
			],
		);

		$element->end_controls_section();

		// Allow themes to add custom Jarallax controls
		do_action( 'vlt_toolkit_elementor_jarallax_controls', $element, $args );
	}

	/**
	 * Render Jarallax attributes
	 *
	 * @param object $widget elementor widget instance
	 */
	public function render_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_jarallax_enabled'] ) || 'jarallax' !== $settings['vlt_jarallax_enabled'] ) {
			return;
		}

		// Add jarallax class and speed
		if ( !empty( $settings['vlt_jarallax_speed']['size'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-jarallax', '' );
			$widget->add_render_attribute( '_wrapper', 'data-speed', $settings['vlt_jarallax_speed']['size'] );
		}

		// Build inline styles array
		$styles = [];

		// Add background image as CSS using Elementor's Group_Control_Image_Size helper
		if ( !empty( $settings['vlt_jarallax_image']['id'] ) ) {
			$image_url = \Elementor\Group_Control_Image_Size::get_attachment_image_src(
				$settings['vlt_jarallax_image']['id'],
				'vlt_jarallax_image',
				$settings
			);

			if ( $image_url ) {
				$styles[] = 'background-image: url(' . esc_url( $image_url ) . ');';
			}
		} elseif ( !empty( $settings['vlt_jarallax_image']['url'] ) ) {
			// Fallback to direct URL if no ID is available
			$styles[] = 'background-image: url(' . esc_url( $settings['vlt_jarallax_image']['url'] ) . ');';
		}

		// Add background size
		$img_size = $settings['vlt_jarallax_img_size'] ?? 'cover';
		if ( !empty( $img_size ) ) {
			$styles[] = 'background-size: ' . esc_attr( $img_size ) . ';';
		}

		// Add background position
		$position = $settings['vlt_jarallax_img_position'] ?? 'center center';
		if ( 'custom' === $position ) {
			$position = $settings['vlt_jarallax_img_position_custom'] ?? 'center center';
		}
		if ( !empty( $position ) ) {
			$styles[] = 'background-position: ' . esc_attr( $position ) . ';';
		}

		// Apply inline styles
		if ( !empty( $styles ) ) {
			$widget->add_render_attribute( '_wrapper', 'style', implode( ' ', $styles ) );
		}

		// Add video URL
		if ( !empty( $settings['vlt_jarallax_video_url'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-jarallax-video', $settings['vlt_jarallax_video_url'] );
		}

		// Add type
		if ( !empty( $settings['vlt_jarallax_type'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-type', $settings['vlt_jarallax_type'] );
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
		// Register controls for containers only (background section)
		add_action( 'elementor/element/container/section_background/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Render for containers
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_attributes' ] );
	}
}
