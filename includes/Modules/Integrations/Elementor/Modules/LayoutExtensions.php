<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

use VLT\Toolkit\Modules\Integrations\Elementor\BaseExtension;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layout Extensions
 *
 * Handles Sticky Column, Stretch, Padding to Container, and Equal Height
 */
class LayoutExtensions extends BaseExtension {
	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'layout_extensions';

	/**
	 * Register extension scripts
	 */
	public function register_scripts() {
		wp_enqueue_script(
			'vlt-layout-extension',
			plugin_dir_url( __FILE__ ) . 'js/LayoutExtensions.js',
			[],
			VLT_TOOLKIT_VERSION,
			true,
		);
	}

	/**
	 * Register Sticky & Stretch controls
	 *
	 * Adds sticky column, stretch, and padding controls for containers
	 * Functionality is provided by Sticky and Stretch modules
	 *
	 * @param object $element elementor element instance
	 * @param array  $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$breakpoints           = $this->get_elementor_breakpoints();
		$default_reset_devices = $this->get_default_reset_devices();

		$element->start_controls_section(
			'vlt_section_layout_extensions',
			[
				'label' => esc_html__( 'VLT Layout Extensions', 'toolkit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			],
		);

		// Sticky Column
		$element->add_control(
			'vlt_sticky_column',
			[
				'label'        => esc_html__( 'Sticky Column', 'toolkit' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'has-sticky-column',
				'prefix_class' => '',
			],
		);

		// Stretch
		$element->add_control(
			'vlt_stretch_enabled',
			[
				'label'        => esc_html__( 'Stretch', 'toolkit' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'separator'    => 'before',
			],
		);

		$element->add_control(
			'vlt_stretch_settings_popover',
			[
				'label'     => esc_html__( 'Stretch Settings', 'toolkit' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_stretch_enabled' => 'yes' ],
			],
		);

		$element->start_popover();

		$element->add_control(
			'vlt_stretch_side',
			[
				'label'   => esc_html__( 'Side', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'to-left',
				'options' => [
					'to-left'      => esc_html__( 'Left', 'toolkit' ),
					'to-right'     => esc_html__( 'Right', 'toolkit' ),
					'to-container' => esc_html__( 'Container', 'toolkit' ),
				],
				'prefix_class' => 'has-stretch-block-',
				'condition'    => [ 'vlt_stretch_enabled' => 'yes' ],
			],
		);

		$element->add_control(
			'vlt_stretch_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_stretch_enabled' => 'yes' ],
			],
		);

		$element->end_popover();

		// Padding to Container
		$element->add_control(
			'vlt_padding_to_container',
			[
				'label'        => esc_html__( 'Padding to Container', 'toolkit' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'separator'    => 'before',
			],
		);

		$element->add_control(
			'vlt_padding_settings_popover',
			[
				'label'     => esc_html__( 'Padding Settings', 'toolkit' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_padding_to_container' => 'yes' ],
			],
		);

		$element->start_popover();

		$element->add_control(
			'vlt_padding_to_container_side',
			[
				'label'   => esc_html__( 'Side', 'toolkit' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'default' => 'to-left',
				'options' => [
					'to-left'  => esc_html__( 'Left', 'toolkit' ),
					'to-right' => esc_html__( 'Right', 'toolkit' ),
				],
				'prefix_class' => 'has-padding-block-',
				'condition'    => [ 'vlt_padding_to_container' => 'yes' ],
			],
		);

		$element->add_control(
			'vlt_padding_to_container_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_padding_to_container' => 'yes' ],
			],
		);

		$element->end_popover();

		// Equal Height
		$element->add_control(
			'vlt_equal_height_widgets',
			[
				'label'        => esc_html__( 'Equal Height', 'toolkit' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => 'has-equal-height-block-',
				'separator'    => 'before',
			],
		);

		$element->add_control(
			'vlt_equal_height_popover',
			[
				'label'     => esc_html__( 'Equal Height Settings', 'toolkit' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_equal_height_widgets' => 'yes' ],
			],
		);

		$element->start_popover();

		$element->add_control(
			'vlt_equal_height_widgets_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_equal_height_widgets' => 'yes' ],
			],
		);

		$element->end_popover();

		$element->end_controls_section();
	}

	/**
	 * Render Sticky & Stretch attributes
	 *
	 * @param object $widget elementor widget instance
	 */
	public function render_attributes( Element_Base $widget ) {
		$settings = $widget->get_settings_for_display();

		// Stretch reset on devices
		if ( isset( $settings['vlt_stretch_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-on-devices',
				wp_json_encode( $settings['vlt_stretch_reset_on_devices'] ),
			);
		}

		// Padding to container reset on devices
		if ( isset( $settings['vlt_padding_to_container_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-padding-to-container-on-devices',
				wp_json_encode( $settings['vlt_padding_to_container_reset_on_devices'] ),
			);
		}

		// Equal height reset on devices
		if ( isset( $settings['vlt_equal_height_widgets_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-equal-height-on-devices',
				wp_json_encode( $settings['vlt_equal_height_widgets_reset_on_devices'] ),
			);
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
}
