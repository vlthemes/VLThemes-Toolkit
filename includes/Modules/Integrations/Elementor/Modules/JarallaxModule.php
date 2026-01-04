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
class JarallaxModule extends Module_Base {

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
		return 'jarallax';
	}

	/**
	 * Register extension scripts
	 */
	public function register_scripts() {
		wp_enqueue_script( 'jarallax', VLT_TOOLKIT_URL . 'assets/vendors/js/jarallax.js', [], VLT_TOOLKIT_VERSION, true );
		wp_enqueue_script( 'jarallax-video', VLT_TOOLKIT_URL . 'assets/vendors/js/jarallax-video.js', [], VLT_TOOLKIT_VERSION, true );
		wp_enqueue_style( 'jarallax', VLT_TOOLKIT_URL . 'assets/vendors/css/jarallax.css', [], VLT_TOOLKIT_VERSION );

		wp_enqueue_script(
			'vlt-jarallax-module',
			plugin_dir_url( __FILE__ ) . 'js/JarallaxModule.js',
			[ 'jarallax', 'jarallax-video', 'elementor-frontend', 'jquery' ],
			VLT_TOOLKIT_VERSION,
			true,
		);

		wp_enqueue_style( 'vlt-jarallax-module',
			plugin_dir_url( __FILE__ ) . 'css/JarallaxModule.css',
			[],
			VLT_TOOLKIT_VERSION
		);


	}

	/**
	 * Register Parallax controls
	 *
	 * @param Element_Base $element Elementor element instance
	 */
	public function register_controls( Element_Base $element ) {

		$element->add_control(
			'vlt_jarallax_enable',
			[
				'label' => esc_html__( 'Jarallax', 'toolkit' ),
				'type' => Controls_Manager::SWITCHER,
				'label_off' => esc_html__( 'Off', 'toolkit' ),
				'label_on' => esc_html__( 'On', 'toolkit' ),
				'render_type' => 'ui',
				'frontend_available' => true,
				'separator' => 'before',
				'conditions' => [
					'relation' => 'or',
					'terms' => [
						[
							'terms' => [
								[
									'name' => 'background_background',
									'value' => 'classic',
								],
								[
									'name' => 'background_image[url]',
									'operator' => '!==',
									'value' => '',
								],
							],
						],
						[
							'terms' => [
								[
									'name' => 'background_background',
									'value' => 'gradient',
								],
								[
									'name' => 'background_color',
									'operator' => '!==',
									'value' => '',
								],
								[
									'name' => 'background_color_b',
									'operator' => '!==',
									'value' => '',
								],
							],
						],
					],
				],
			],
		);

		$element->add_control(
			'vlt_jarallax_speed',
			[
				'label'      => esc_html__( 'Speed', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px' ],
				'range'      => [
					'px' => [
						'min'  => -1,
						'max'  => 1,
						'step' => 0.1,
					],
				],
				'frontend_available' => true,
				'default' => [
					'size' => 0.9,
				],
				'condition' => [ 'vlt_jarallax_enable' => 'yes' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_type',
			[
				'label'   => esc_html__( 'Type', 'toolkit' ),
				'type'    => Controls_Manager::SELECT,
				'options' => [
					''               => esc_html__( 'Scroll', 'toolkit' ),
					'scale'          => esc_html__( 'Scale', 'toolkit' ),
					'opacity'        => esc_html__( 'Opacity', 'toolkit' ),
					'scroll-opacity' => esc_html__( 'Scroll + Opacity', 'toolkit' ),
					'scale-opacity'  => esc_html__( 'Scale + Opacity', 'toolkit' ),
				],
				'frontend_available' => true,
				'condition' => [ 'vlt_jarallax_enable' => 'yes' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_video_url',
			[
				'label'       => esc_html__( 'Video URL', 'toolkit' ),
				'description' => esc_html__( 'YouTube, Vimeo or local video. Use "mp4:" prefix for self-hosted.', 'toolkit' ),
				'type'        => Controls_Manager::TEXT,
				'frontend_available' => true,
				'placeholder' => 'https://www.youtube.com/watch?v=...',
				'condition'   => [ 'vlt_jarallax_enable' => 'yes' ],
			],
		);

		$element->add_control(
			'vlt_jarallax_update',
			[
				'label' => __( 'Apply Button', 'toolkit' ),
				'show_label' => false,
				'type'  => Controls_Manager::RAW_HTML,
				'raw' => '<div class="elementor-update-preview" style="margin: 0 0 8px 0"><div class="elementor-update-preview-title">' . __( 'Update changes to the page', 'toolkit' ) . '</div><div class="elementor-update-preview-button-wrapper"><button class="elementor-update-preview-button elementor-button elementor-button-success" style="background-image: linear-gradient(90deg, #e2498a 0%, #562dd4 100%);">' . __( 'Apply', 'toolkit' ) . '</button></div></div>',
				'condition' => [
					'vlt_jarallax_enable' => 'yes',
				],
			]
		);

	}

	/**
	 * Render Jarallax attributes
	 *
	 * @param object $widget elementor widget instance
	 */
	public function render_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_jarallax_enable'] ) || 'yes' !== $settings['vlt_jarallax_enable'] ) {
			return;
		}

		// Add jarallax class and speed
		if ( !empty( $settings['vlt_jarallax_speed']['size'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-jarallax', '' );
			$widget->add_render_attribute( '_wrapper', 'data-speed', $settings['vlt_jarallax_speed']['size'] );
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
	 * Register WordPress hooks
	 */
	protected function add_actions() {
		// Register controls for containers
		add_action( 'elementor/element/container/section_background/before_section_end', [ $this, 'register_controls' ] );

		// Render for containers
		// add_action( 'elementor/frontend/container/before_render', [ $this, 'render_attributes' ] );

		// Enqueue scripts on frontend and editor
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
	}
}
