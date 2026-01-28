<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Equal Height Extension
 *
 * Handles equal height functionality for widgets using jQuery matchHeight plugin
 */
class EqualHeightModule extends Module_Base {

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
		return 'equal-height';
	}

	/**
	 * Register module scripts
	 */
	public function register_scripts() {
		// Enqueue jQuery matchHeight plugin
		wp_enqueue_script(
			'jquery-match-height',
			VLT_TOOLKIT_URL . 'assets/vendors/js/jquery.matchHeight.js',
			[ 'jquery' ],
			'0.7.2',
			true
		);

		wp_enqueue_script(
			'vlt-equal-height-module',
			plugin_dir_url( __FILE__ ) . 'js/EqualHeightModule.js',
			[ 'jquery', 'elementor-frontend', 'jquery-match-height' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register Equal Height controls
	 *
	 * @param Element_Base $element elementor element
	 */
	public function register_controls( Element_Base $element ) {

		$breakpoints           = $this->get_elementor_breakpoints();
		$default_reset_devices = $this->get_default_reset_devices();

		$element->start_controls_section(
			'vlt_section_equal_height',
			[
				'label' => esc_html__( 'Equal Height', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_equal_height_widgets',
			[
				'label'              => esc_html__( 'Equal Height', 'toolkit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'toolkit' ),
				'label_off'          => esc_html__( 'No', 'toolkit' ),
				'return_value'       => 'yes',
				'default'            => '',
				'prefix_class'       => 'has-equal-height-',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_equal_height_settings_popover',
			[
				'label'     => esc_html__( 'Settings', 'toolkit' ),
				'type'      => Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_equal_height_widgets' => 'yes' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_equal_height_widget_selector',
			[
				'label'              => esc_html__( 'Select Widgets', 'toolkit' ),
				'type'               => 'vlt-widget-list',
				'multiple'           => true,
				'label_block'        => true,
				'description'        => esc_html__( 'Select specific widgets to apply equal height.', 'toolkit' ),
				'render_type'        => 'ui',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_equal_height_reset_on_devices',
			[
				'label'              => esc_html__( 'Reset On Device', 'toolkit' ),
				'type'               => Controls_Manager::SELECT2,
				'multiple'           => true,
				'label_block'        => true,
				'default'            => $default_reset_devices,
				'options'            => $breakpoints,
				'render_type'        => 'none',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'vlt_equal_height_mode',
			[
				'label'              => esc_html__( 'Height Mode', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'default'            => 'separate',
				'options'            => [
					'separate' => esc_html__( 'Separately', 'toolkit' ),
					'combined' => esc_html__( 'Together', 'toolkit' ),
				],
				'description'        => esc_html__( 'Choose whether to apply equal height to each widget type separately or to all selected widgets together.', 'toolkit' ),
				'render_type'        => 'none',
				'frontend_available' => true,
			]
		);

		$element->end_popover();

		$element->end_controls_section();
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
