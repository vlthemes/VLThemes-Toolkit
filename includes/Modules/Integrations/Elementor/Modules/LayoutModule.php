<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Layout Module Module
 *
 * Handles Sticky Column, Stretch, and Padding to Container
 */
class LayoutModule extends Module_Base {

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
		return 'layout';
	}

	/**
	 * Register module scripts
	 */
	public function register_scripts() {
		wp_enqueue_script(
			'vlt-layout-module',
			plugin_dir_url( __FILE__ ) . 'js/LayoutModule.js',
			[ 'jquery', 'elementor-frontend' ],
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
	 * @param Element_Base $element elementor element instance
	 */
	public function register_controls( Element_Base $element ) {
		$breakpoints           = $this->get_elementor_breakpoints();
		$default_reset_devices = $this->get_default_reset_devices();

		$element->start_controls_section(
			'vlt_section_layout_module',
			[
				'label' => esc_html__( 'Layout Module', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			],
		);

		// Stretch
		$element->add_control(
			'vlt_stretch_enabled',
			[
				'label'        => esc_html__( 'Stretch', 'toolkit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'frontend_available' => true,
				'separator'    => 'before',
			],
		);

		$element->add_control(
			'vlt_stretch_settings_popover',
			[
				'label'     => esc_html__( 'Settings', 'toolkit' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_stretch_enabled' => 'yes' ],
			],
		);

		$element->start_popover();

		$element->add_control(
			'vlt_stretch_side',
			[
				'label'              => esc_html__( 'Side', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'to-left',
				'options'            => [
					'to-left'      => esc_html__( 'Left', 'toolkit' ),
					'to-right'     => esc_html__( 'Right', 'toolkit' ),
					'to-container' => esc_html__( 'Container', 'toolkit' ),
				],
				'prefix_class'       => 'has-stretch-block-',
				'render_type'        => 'none',
				'frontend_available' => true,
			],
		);

		$element->add_control(
			'vlt_stretch_reset_on_devices',
			[
				'label'              => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'               => Controls_Manager::SELECT2,
				'multiple'           => true,
				'label_block'        => true,
				'default'            => $default_reset_devices,
				'options'            => $breakpoints,
				'render_type'        => 'none',
				'frontend_available' => true,
			],
		);

		$element->end_popover();

		// Padding to Container
		$element->add_control(
			'vlt_padding_to_container',
			[
				'label'        => esc_html__( 'Padding to Container', 'toolkit' ),
				'type'         => Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'frontend_available' => true,
				'separator'    => 'before',
			],
		);

		$element->add_control(
			'vlt_padding_settings_popover',
			[
				'label'     => esc_html__( 'Settings', 'toolkit' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_padding_to_container' => 'yes' ],
			],
		);

		$element->start_popover();

		$element->add_control(
			'vlt_padding_to_container_side',
			[
				'label'              => esc_html__( 'Side', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'to-left',
				'options'            => [
					'to-left'  => esc_html__( 'Left', 'toolkit' ),
					'to-right' => esc_html__( 'Right', 'toolkit' ),
				],
				'prefix_class'       => 'has-padding-block-',
				'render_type'        => 'none',
				'frontend_available' => true,
			],
		);

		$element->add_control(
			'vlt_padding_to_container_reset_on_devices',
			[
				'label'              => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'               => Controls_Manager::SELECT2,
				'multiple'           => true,
				'label_block'        => true,
				'default'            => $default_reset_devices,
				'options'            => $breakpoints,
				'render_type'        => 'none',
				'frontend_available' => true,
			],
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

	/**
	 * Get Elementor breakpoints
	 *
	 * @return array available breakpoints
	 */
	protected function get_elementor_breakpoints() {
		$breakpoints_manager = \Elementor\Plugin::$instance->breakpoints;
		$breakpoints         = $breakpoints_manager->get_active_breakpoints();

		$options = [
			'desktop' => esc_html__( 'Desktop', 'toolkit' ),
		];

		foreach ( $breakpoints as $breakpoint_key => $breakpoint ) {
			$options[ $breakpoint_key ] = $breakpoint->get_label();
		}

		return $options;
	}

	/**
	 * Get default reset devices (mobile and mobile_extra if exists)
	 *
	 * @return array
	 */
	protected function get_default_reset_devices() {
		$breakpoints = $this->get_elementor_breakpoints();

		$default_reset_devices = [ 'mobile' ];

		if ( isset( $breakpoints['mobile_extra'] ) ) {
			$default_reset_devices[] = 'mobile_extra';
		}

		return $default_reset_devices;
	}
}
