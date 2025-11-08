<?php

namespace VLT\Helper\Modules\Integrations;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor Integration Module
 *
 * Handles Elementor widgets registration and integration
 */
class Elementor extends BaseModule {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'elementor';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Assets URL
	 *
	 * @var string
	 */
	private $assets_url;

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register() {
		return defined( 'ELEMENTOR_VERSION' );
	}

	/**
	 * Initialize module
	 */
	protected function init() {
		$this->assets_url  = VLT_HELPER_URL . 'assets/';

		// Enqueue all assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ], 1 );
	}

	/**
	 * Register module
	 */
	public function register() {
		add_action( 'elementor/init', [ $this, 'init_elementor' ] );
	}

	/**
	 * Enqueue all elementor assets
	 */
	public function enqueue_assets() {
		// ===================================
		// SCRIPTS
		// ===================================
		wp_enqueue_script(
			'vlt-extension-elementor',
			$this->assets_url . 'extensions/elementor/elementor-bundle.js',
			[
				'vlt-aos',
				'vlt-gsap',
				'vlt-scrolltrigger',
				'vlt-jarallax',
				'vlt-jarallax-video'
			],
			VLT_HELPER_VERSION,
			true
		);

		// ===================================
		// STYLES
		// ===================================
		wp_enqueue_style( 'vlt-aos' );
		wp_enqueue_style( 'vlt-jarallax' );
		wp_enqueue_style( 'vlt-jarallax-video' );
	}

	/**
	 * Enqueue editor styles
	 */
	public function editor_styles() {
		// Enqueue main editor CSS
		wp_enqueue_style(
			'vlt-elementor-editor',
			$this->assets_url . 'extensions/elementor/elementor-editor.css',
			[],
			VLT_HELPER_VERSION
		);

		// Add inline CSS for badge customization
		$this->add_badge_styles();
	}

	/**
	 * Add badge styles to editor
	 */
	private function add_badge_styles() {
		$theme = wp_get_theme();
		$theme_name = $theme->get( 'Name' );

		$badge_config = apply_filters( 'vlt_helper_elementor_badge', [
			'text' => $theme_name,
		] );

		if ( empty( $badge_config['text'] ) ) {
			return;
		}

		$custom_css = sprintf(
			'#elementor-panel-elements-wrapper .elementor-element .icon i[class*="-badge"]::after,
			#elementor-panel-elements-wrapper .elementor-element .icon .vlt-badge::after {
				content: "%s";
			}',
			esc_attr( $badge_config['text'] )
		);

		wp_add_inline_style( 'vlt-elementor-editor', $custom_css );
	}

	/**
	 * Initialize Elementor integration
	 */
	public function init_elementor() {

		// Register widgets - support both old and new Elementor versions
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );

		// Register other hooks
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
		add_action( 'elementor/elements/categories_registered', [ $this, 'register_categories' ] );
		add_action( 'elementor/theme/register_locations', [ $this, 'register_locations' ] );
		add_filter( 'elementor/icons_manager/additional_tabs', [ $this, 'add_icon_tabs' ] );

		// Hide promo widgets
		add_filter( 'elementor/editor/localize_settings', [ $this, 'hide_promo_widgets' ], 20 );

		// Register extension controls for containers and widgets
		$this->register_element_extensions();
	}

	/**
	 * Include widget files
	 *
	 * Widget files should be loaded from theme using the action hook.
	 * Theme manages all widget file paths and loading.
	 */
	private function include_widget_files() {
		// Fire action to allow theme to load widget files from theme directory
		do_action( 'vlt_helper_elementor_register_widgets' );
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets( $widgets_manager = null ) {
		$this->include_widget_files();

		// Get widget manager
		if ( ! $widgets_manager ) {
			$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
		}

		// Get widget classes
		$widgets = $this->get_widget_classes();

		// Register each widget
		foreach ( $widgets as $widget_class ) {
			if ( class_exists( $widget_class ) ) {
				$widgets_manager->register( new $widget_class() );
			}
		}

		do_action( 'vlt_helper_elementor_widgets_registered' );
	}

	/**
	 * Get widget classes
	 *
	 * Returns empty array by default. Use 'vlt_helper_elementor_widget_classes' filter
	 * in theme to register widget classes.
	 *
	 * @return array
	 */
	private function get_widget_classes() {
		/**
		 * Filter Elementor widget classes
		 *
		 * Allows themes to specify which widget classes to register.
		 *
		 * @param array $widget_classes Array of widget class names.
		 */
		return apply_filters( 'vlt_helper_elementor_widget_classes', [] );
	}

	/**
	 * Register Elementor categories
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			'vlthemes-elements',
			[
				'title' => esc_html__( 'VLThemes Elements', 'vlt-helper' ),
				'icon'  => 'fa fa-plug',
			]
		);

		$elements_manager->add_category(
			'vlthemes-showcase',
			[
				'title' => esc_html__( 'VLThemes Showcase', 'vlt-helper' ),
				'icon'  => 'fa fa-image',
			]
		);

		$elements_manager->add_category(
			'vlthemes-woo',
			[
				'title' => esc_html__( 'VLThemes WooCommerce', 'vlt-helper' ),
				'icon'  => 'fa fa-shopping-cart',
			]
		);
	}

	/**
	 * Register Elementor theme locations
	 *
	 * @param object $elementor_theme_manager Elementor theme manager.
	 */
	public function register_locations( $elementor_theme_manager ) {
		$elementor_theme_manager->register_location( 'header' );
		$elementor_theme_manager->register_location( 'footer' );
		$elementor_theme_manager->register_location( '404' );
	}

	/**
	 * Hide Elementor Pro promo widgets
	 *
	 * Removes promotion widgets when Elementor Pro is not installed
	 *
	 * @param array $settings Elementor settings.
	 * @return array Modified settings.
	 */
	public function hide_promo_widgets( $settings ) {
		if ( ! class_exists( 'ElementorPro\Plugin' ) && ! empty( $settings['promotionWidgets'] ) ) {
			$settings['promotionWidgets'] = [];
		}
		return $settings;
	}

	/**
	 * Add custom icon tabs
	 *
	 * @param array $settings Icon settings.
	 * @return array
	 */
	public function add_icon_tabs( $settings ) {
		$icon_sets = $this->get_icon_sets();

		foreach ( $icon_sets as $key => $icon_set ) {
			// Check if icon set files exist before adding
			$css_path = str_replace( VLT_HELPER_URL, VLT_HELPER_PATH, $icon_set['url'] );
			if ( file_exists( $css_path ) ) {
				$settings[ $key ] = $icon_set;
			}
		}

		return apply_filters( 'vlt_helper_elementor_icon_tabs', $settings );
	}

	/**
	 * Get icon sets configuration
	 *
	 * @return array
	 */
	private function get_icon_sets() {
		return [
			// Socicons
			'socicons' => [
				'name'          => 'socicons',
				'label'         => esc_html__( 'Socicons', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/socicons/socicons.css',
				'enqueue'       => false, // CSS loaded globally in SocialIcons module
				'prefix'        => 'socicon-',
				'displayPrefix' => false,
				'labelIcon'     => 'socicon-twitter',
				'fetchJson'     => $this->assets_url . 'fonts/socicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// ET-Line Icons
			'etline' => [
				'name'          => 'etline',
				'label'         => esc_html__( 'ET-Line', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/etline/etl.css',
				'enqueue'       => [ $this->assets_url . 'fonts/etline/etl.css' ],
				'prefix'        => 'etl-',
				'displayPrefix' => false,
				'labelIcon'     => 'etl-desktop',
				'fetchJson'     => $this->assets_url . 'fonts/etline/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// Icomoon
			'icomoon' => [
				'name'          => 'icomoon',
				'label'         => esc_html__( 'Icomoon', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/icomoon/icnm.css',
				'enqueue'       => [ $this->assets_url . 'fonts/icomoon/icnm.css' ],
				'prefix'        => 'icnm-',
				'displayPrefix' => false,
				'labelIcon'     => 'icnm-barcode',
				'fetchJson'     => $this->assets_url . 'fonts/icomoon/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// Iconsmind
			'iconsmind' => [
				'name'          => 'iconsmind',
				'label'         => esc_html__( 'Iconsmind', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/iconsmind/iconsmind.css',
				'enqueue'       => [ $this->assets_url . 'fonts/iconsmind/iconsmind.css' ],
				'prefix'        => 'icnmd-',
				'displayPrefix' => false,
				'labelIcon'     => 'icnmd-ATM',
				'fetchJson'     => $this->assets_url . 'fonts/iconsmind/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// Linearicons
			'linearicons' => [
				'name'          => 'linearicons',
				'label'         => esc_html__( 'Linearicons', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/linearicons/lnr.css',
				'enqueue'       => [ $this->assets_url . 'fonts/linearicons/lnr.css' ],
				'prefix'        => 'lnr-',
				'displayPrefix' => false,
				'labelIcon'     => 'lnr-book',
				'fetchJson'     => $this->assets_url . 'fonts/linearicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// Elusive Icons
			'elusiveicons' => [
				'name'          => 'elusiveicons',
				'label'         => esc_html__( 'Elusive Icons', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/elusiveicons/el.css',
				'enqueue'       => [ $this->assets_url . 'fonts/elusiveicons/el.css' ],
				'prefix'        => 'el-',
				'displayPrefix' => false,
				'labelIcon'     => 'el-address-book',
				'fetchJson'     => $this->assets_url . 'fonts/elusiveicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
			// Icofont
			'icofont' => [
				'name'          => 'icofont',
				'label'         => esc_html__( 'Icofont', 'vlt-helper' ),
				'url'           => $this->assets_url . 'fonts/icofont/icofont.css',
				'enqueue'       => [ $this->assets_url . 'fonts/icofont/icofont.css' ],
				'prefix'        => 'icofont-',
				'displayPrefix' => false,
				'labelIcon'     => 'icofont-cop',
				'fetchJson'     => $this->assets_url . 'fonts/icofont/elementor.json',
				'native'        => false,
				'ver'           => VLT_HELPER_VERSION,
			],
		];
	}

	/**
	 * Register element extensions
	 *
	 * Adds custom controls and attributes to Elementor elements
	 */
	private function register_element_extensions() {
		// Register controls for containers
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_sticky_stretch_controls' ], 10, 2 );
		add_action( 'elementor/element/container/section_background/after_section_end', [ $this, 'register_jarallax_controls' ], 10, 2 );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_aos_controls' ], 10, 2 );
		add_action( 'elementor/element/container/section_layout/after_section_end', [ $this, 'register_element_parallax_controls' ], 10, 2 );

		// Register controls for common widgets
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_element_parallax_controls' ], 10, 2 );
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_sticky_stretch_controls' ], 10, 2 );
		add_action( 'elementor/element/common/_section_style/after_section_end', [ $this, 'register_aos_controls' ], 10, 2 );

		// Render for containers
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_sticky_stretch_attributes' ] );
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_jarallax_attributes' ] );
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_aos_attributes' ] );
		add_action( 'elementor/frontend/container/before_render', [ $this, 'render_element_parallax_attributes' ] );

		// Render for common widgets
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'render_sticky_stretch_attributes' ] );
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'render_aos_attributes' ] );
		add_action( 'elementor/frontend/widget/before_render', [ $this, 'render_element_parallax_attributes' ] );
	}

	/**
	 * Get Elementor breakpoints
	 *
	 * @return array Available breakpoints.
	 */
	private function get_elementor_breakpoints() {
		$breakpoints_manager = \Elementor\Plugin::$instance->breakpoints;
		$breakpoints = $breakpoints_manager->get_active_breakpoints();

		$options = [];
		foreach ( $breakpoints as $breakpoint_key => $breakpoint ) {
			$options[ $breakpoint_key ] = $breakpoint->get_label();
		}

		return $options;
	}

	/**
	 * Register Sticky & Stretch controls
	 *
	 * Adds sticky column, stretch, and padding controls for containers
	 * Functionality is provided by Sticky and Stretch modules
	 *
	 * @param object $element Elementor element instance.
	 * @param array  $args    Element arguments.
	 */
	public function register_sticky_stretch_controls( $element, $args ) {
		// Get available breakpoints from Elementor
		$breakpoints = $this->get_elementor_breakpoints();

		// Get default reset devices (mobile and mobile_extra if exists)
		$default_reset_devices = [ 'mobile' ];
		if ( isset( $breakpoints['mobile_extra'] ) ) {
			$default_reset_devices[] = 'mobile_extra';
		}

		$element->start_controls_section(
			'vlt_section_sticky_stretch',
			[
				'label' => esc_html__( 'VLT Sticky & Stretch', 'vlt-helper' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		// Sticky Column
		$element->add_control(
			'vlt_sticky_column',
			[
				'label'        => esc_html__( 'Sticky Column', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'has-sticky-column',
				'prefix_class' => '',
				'separator'    => 'before',
			]
		);

		$element->add_control(
			'vlt_sticky_settings_popover',
			[
				'label'     => esc_html__( 'Sticky Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_sticky_column' => 'has-sticky-column' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_sticky_column_reset_offset',
			[
				'label'        => esc_html__( 'Reset Offset', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'has-sticky-column-reset-offset',
				'prefix_class' => '',
				'condition'    => [ 'vlt_sticky_column' => 'has-sticky-column' ],
			]
		);

		$element->end_popover();

		// Stretch
		$element->add_control(
			'vlt_stretch_enabled',
			[
				'label'        => esc_html__( 'Stretch', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'separator'    => 'before',
			]
		);

		$element->add_control(
			'vlt_stretch_settings_popover',
			[
				'label'     => esc_html__( 'Stretch Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_stretch_enabled' => 'yes' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_stretch_side',
			[
				'label'        => esc_html__( 'Side', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SELECT,
				'default'      => 'to-left',
				'options'      => [
					'to-left'      => esc_html__( 'Left', 'vlt-helper' ),
					'to-right'     => esc_html__( 'Right', 'vlt-helper' ),
					'to-container' => esc_html__( 'Container', 'vlt-helper' ),
				],
				'prefix_class' => 'has-stretch-block-',
				'condition'    => [ 'vlt_stretch_enabled' => 'yes' ],
			]
		);

		$element->add_control(
			'vlt_stretch_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_stretch_enabled' => 'yes' ],
			]
		);

		$element->end_popover();

		// Padding to Container
		$element->add_control(
			'vlt_padding_to_container',
			[
				'label'        => esc_html__( 'Padding to Container', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => '',
				'separator'    => 'before',
			]
		);

		$element->add_control(
			'vlt_padding_settings_popover',
			[
				'label'     => esc_html__( 'Padding Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_padding_to_container' => 'yes' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_padding_to_container_side',
			[
				'label'        => esc_html__( 'Side', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SELECT,
				'default'      => 'to-left',
				'options'      => [
					'to-left'  => esc_html__( 'Left', 'vlt-helper' ),
					'to-right' => esc_html__( 'Right', 'vlt-helper' ),
				],
				'prefix_class' => 'has-padding-block-',
				'condition'    => [ 'vlt_padding_to_container' => 'yes' ],
			]
		);

		$element->add_control(
			'vlt_padding_to_container_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_padding_to_container' => 'yes' ],
			]
		);

		$element->end_popover();

		// Equal Height
		$element->add_control(
			'vlt_equal_height_widgets',
			[
				'label'        => esc_html__( 'Equal Height', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'prefix_class' => 'has-equal-height-block-',
				'separator'    => 'before',
			]
		);

		$element->add_control(
			'vlt_equal_height_popover',
			[
				'label'     => esc_html__( 'Equal Height Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_equal_height_widgets' => 'yes' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_equal_height_widgets_reset_on_devices',
			[
				'label'       => esc_html__( 'Reset On Device', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SELECT2,
				'multiple'    => true,
				'label_block' => true,
				'default'     => $default_reset_devices,
				'options'     => $breakpoints,
				'condition'   => [ 'vlt_equal_height_widgets' => 'yes' ],
			]
		);

		$element->end_popover();

		$element->end_controls_section();
	}

	/**
	 * Render Sticky & Stretch attributes
	 *
	 * @param object $widget Elementor widget instance.
	 */
	public function render_sticky_stretch_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		// Stretch reset on devices
		if ( isset( $settings['vlt_stretch_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-on-devices',
				wp_json_encode( $settings['vlt_stretch_reset_on_devices'] )
			);
		}

		// Padding to container reset on devices
		if ( isset( $settings['vlt_padding_to_container_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-padding-to-container-on-devices',
				wp_json_encode( $settings['vlt_padding_to_container_reset_on_devices'] )
			);
		}

		// Equal height reset on devices
		if ( isset( $settings['vlt_equal_height_widgets_reset_on_devices'] ) ) {
			$widget->add_render_attribute(
				'_wrapper',
				'data-reset-equal-height-on-devices',
				wp_json_encode( $settings['vlt_equal_height_widgets_reset_on_devices'] )
			);
		}

	}

	/**
	 * Register Jarallax parallax controls
	 *
	 * @param object $element Elementor element.
	 * @param array  $args    Element arguments.
	 */
	public function register_jarallax_controls( $element, $args ) {
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
	public function render_jarallax_attributes( $widget ) {
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

	/**
	 * Register AOS animation controls
	 *
	 * @param object $element Elementor element.
	 * @param array  $args    Element arguments.
	 */
	public function register_aos_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_aos_animation',
			[
				'label' => esc_html__( 'VLT Entrance Animation', 'vlt-helper' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_aos_animation',
			[
				'label'   => esc_html__( 'Entrance Animation', 'vlt-helper' ),
				'type'    => \Elementor\Controls_Manager::SELECT,
				'options' => $this->get_aos_animations(),
				'default' => 'none',
			]
		);

		$element->add_control(
			'vlt_aos_settings_popover',
			[
				'label'     => esc_html__( 'Animation Settings', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [ 'vlt_aos_animation!' => 'none' ],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_aos_duration',
			[
				'label'       => esc_html__( 'Duration (seconds)', 'vlt-helper' ),
				'description' => esc_html__( 'Animation duration in seconds', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 0.8,
				],
			]
		);

		$element->add_control(
			'vlt_aos_delay',
			[
				'label'       => esc_html__( 'Delay (seconds)', 'vlt-helper' ),
				'description' => esc_html__( 'Delay before animation starts in seconds', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => 0,
						'max'  => 3,
						'step' => 0.1,
					],
				],
				'default' => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$element->add_control(
			'vlt_aos_offset',
			[
				'label'       => esc_html__( 'Offset (px)', 'vlt-helper' ),
				'description' => esc_html__( 'Distance from bottom of viewport to start', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => -500,
				'max'         => 500,
				'step'        => 10,
			]
		);

		$element->add_control(
			'vlt_aos_once',
			[
				'label'       => esc_html__( 'Animate Once', 'vlt-helper' ),
				'description' => esc_html__( 'Animate only once while scrolling down', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SWITCHER,
				'default'     => 'yes',
			]
		);

		$element->end_popover();

		$element->end_controls_section();

		// Allow themes to add custom AOS controls
		do_action( 'vlt_helper_elementor_aos_controls', $element, $args );
	}

	/**
	 * Render AOS attributes
	 *
	 * @param object $widget Elementor widget instance.
	 */
	public function render_aos_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_aos_animation'] ) || $settings['vlt_aos_animation'] === 'none' ) {
			return;
		}

		// Add animation
		$widget->add_render_attribute( '_wrapper', 'data-aos', $settings['vlt_aos_animation'] );

		// Add duration (convert seconds to milliseconds)
		if ( ! empty( $settings['vlt_aos_duration']['size'] ) ) {
			$duration_ms = $settings['vlt_aos_duration']['size'] * 1000;
			$widget->add_render_attribute( '_wrapper', 'data-aos-duration', $duration_ms );
		}

		// Add delay (convert seconds to milliseconds)
		if ( ! empty( $settings['vlt_aos_delay']['size'] ) ) {
			$delay_ms = $settings['vlt_aos_delay']['size'] * 1000;
			$widget->add_render_attribute( '_wrapper', 'data-aos-delay', $delay_ms );
		}

		// Add offset
		if ( isset( $settings['vlt_aos_offset'] ) && $settings['vlt_aos_offset'] !== '' ) {
			$widget->add_render_attribute( '_wrapper', 'data-aos-offset', $settings['vlt_aos_offset'] );
		}

		// Add once
		if ( ! empty( $settings['vlt_aos_once'] ) ) {
			$once_value = $settings['vlt_aos_once'] === 'yes' ? 'true' : 'false';
			$widget->add_render_attribute( '_wrapper', 'data-aos-once', $once_value );
		}
	}

	/**
	 * Get AOS animations list
	 *
	 * @return array Array of animations.
	 */
	private function get_aos_animations() {
		// Check if AOS module is loaded
		if ( ! class_exists( 'VLT\Helper\Modules\Features\AOS' ) ) {
			return [ 'none' => esc_html__( 'None', 'vlt-framework' ) ];
		}

		return \VLT\Helper\Modules\Features\AOS::get_animations();
	}

	/**
	 * Register Element Parallax controls
	 *
	 * Adds parallax controls to Elementor containers and widgets
	 * Controls are defined here, but functionality is in ElementParallax module
	 *
	 * @param object $element Elementor element instance.
	 * @param array  $args    Element arguments.
	 */
	public function register_element_parallax_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_element_parallax',
			[
				'label' => esc_html__( 'VLT Element Parallax', 'vlt-helper' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_parallax_enabled',
			[
				'label'        => esc_html__( 'Enable Parallax', 'vlt-helper' ),
				'type'         => \Elementor\Controls_Manager::SWITCHER,
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		// Horizontal Scroll Popover
		$element->add_control(
			'vlt_parallax_horizontal_popover',
			[
				'label'     => esc_html__( 'Horizontal Scroll', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_parallax_enabled' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_parallax_x',
			[
				'label'       => esc_html__( 'Parallax X (px)', 'vlt-helper' ),
				'description' => esc_html__( 'Distance to move horizontally during scroll', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => -500,
						'max'  => 500,
						'step' => 5,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$element->end_popover();

		// Vertical Scroll Popover
		$element->add_control(
			'vlt_parallax_vertical_popover',
			[
				'label'     => esc_html__( 'Vertical Scroll', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_parallax_enabled' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_parallax_y',
			[
				'label'       => esc_html__( 'Parallax Y (px)', 'vlt-helper' ),
				'description' => esc_html__( 'Distance to move vertically during scroll', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::SLIDER,
				'size_units'  => [ 'px' ],
				'range'       => [
					'px' => [
						'min'  => -500,
						'max'  => 500,
						'step' => 5,
					],
				],
				'default'     => [
					'unit' => 'px',
					'size' => 0,
				],
			]
		);

		$element->end_popover();

		// Transparency Popover
		$element->add_control(
			'vlt_parallax_opacity_popover',
			[
				'label'     => esc_html__( 'Transparency', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_parallax_enabled' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_parallax_opacity_start',
			[
				'label'       => esc_html__( 'Opacity Start', 'vlt-helper' ),
				'description' => esc_html__( 'Starting opacity value (0-1)', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
			]
		);

		$element->add_control(
			'vlt_parallax_opacity_end',
			[
				'label'       => esc_html__( 'Opacity End', 'vlt-helper' ),
				'description' => esc_html__( 'Ending opacity value (0-1)', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0,
				'max'         => 1,
				'step'        => 0.1,
			]
		);

		$element->end_popover();

		// Scale Popover
		$element->add_control(
			'vlt_parallax_scale_popover',
			[
				'label'     => esc_html__( 'Scale', 'vlt-helper' ),
				'type'      => \Elementor\Controls_Manager::POPOVER_TOGGLE,
				'condition' => [
					'vlt_parallax_enabled' => 'yes',
				],
			]
		);

		$element->start_popover();

		$element->add_control(
			'vlt_parallax_scale_start',
			[
				'label'       => esc_html__( 'Scale Start', 'vlt-helper' ),
				'description' => esc_html__( 'Starting scale value (0.1-5)', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0.1,
				'max'         => 5,
				'step'        => 0.1,
			]
		);

		$element->add_control(
			'vlt_parallax_scale_end',
			[
				'label'       => esc_html__( 'Scale End', 'vlt-helper' ),
				'description' => esc_html__( 'Ending scale value (0.1-5)', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::NUMBER,
				'min'         => 0.1,
				'max'         => 5,
				'step'        => 0.1,
			]
		);

		$element->end_popover();

		// Parent Selector
		$element->add_control(
			'vlt_parallax_parent',
			[
				'label'       => esc_html__( 'Parent Selector', 'vlt-helper' ),
				'description' => esc_html__( 'CSS selector of parent element to use as trigger (e.g., .parent-class)', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::TEXT,
				'label_block' => true,
				'separator'   => 'before',
				'condition'   => [
					'vlt_parallax_enabled' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Render Element Parallax attributes
	 *
	 * @param object $widget Elementor widget instance.
	 */
	public function render_element_parallax_attributes( $widget ) {
		$settings = $widget->get_settings_for_display();

		if ( empty( $settings['vlt_parallax_enabled'] ) || $settings['vlt_parallax_enabled'] !== 'yes' ) {
			return;
		}

		// Add parallax class
		$widget->add_render_attribute( '_wrapper', 'class', 'vlt-element-parallax' );

		// Parent selector
		if ( ! empty( $settings['vlt_parallax_parent'] ) ) {
			$widget->add_render_attribute( '_wrapper', 'data-element-parallax-parent', $settings['vlt_parallax_parent'] );
		}

		// Parallax X and Y
		$y = isset( $settings['vlt_parallax_y']['size'] ) && is_numeric( $settings['vlt_parallax_y']['size'] ) ? $settings['vlt_parallax_y']['size'] : 0;
		$x = isset( $settings['vlt_parallax_x']['size'] ) && is_numeric( $settings['vlt_parallax_x']['size'] ) ? $settings['vlt_parallax_x']['size'] : 0;

		if ( $y !== 0 || $x !== 0 ) {
			$widget->add_render_attribute( '_wrapper', 'data-element-parallax', "{$y} {$x}" );
		}

		// Opacity
		$opacity_start = $settings['vlt_parallax_opacity_start'] ?? null;
		$opacity_end   = $settings['vlt_parallax_opacity_end'] ?? null;

		if ( is_numeric( $opacity_start ) || is_numeric( $opacity_end ) ) {
			$opacity_val = '';
			if ( is_numeric( $opacity_start ) && is_numeric( $opacity_end ) ) {
				$opacity_val = "{$opacity_start} {$opacity_end}";
			} elseif ( is_numeric( $opacity_start ) ) {
				$opacity_val = (string) $opacity_start;
			} elseif ( is_numeric( $opacity_end ) ) {
				$opacity_val = (string) $opacity_end;
			}

			if ( $opacity_val !== '' ) {
				$widget->add_render_attribute( '_wrapper', 'data-element-opacity', $opacity_val );
			}
		}

		// Scale
		$scale_start = $settings['vlt_parallax_scale_start'] ?? null;
		$scale_end   = $settings['vlt_parallax_scale_end'] ?? null;

		if ( is_numeric( $scale_start ) || is_numeric( $scale_end ) ) {
			$scale_val = '';
			if ( is_numeric( $scale_start ) && is_numeric( $scale_end ) ) {
				$scale_val = "{$scale_start} {$scale_end}";
			} elseif ( is_numeric( $scale_start ) ) {
				$scale_val = (string) $scale_start;
			} elseif ( is_numeric( $scale_end ) ) {
				$scale_val = (string) $scale_end;
			}

			if ( $scale_val !== '' ) {
				$widget->add_render_attribute( '_wrapper', 'data-element-scale', $scale_val );
			}
		}
	}

	/**
	 * Static helper methods for Elementor widgets
	 */

	/**
	 * Get post names by post type
	 *
	 * @param string $post_type Post type.
	 * @return array Posts list.
	 */
	public static function get_post_name( $post_type = 'post' ) {
		$options = [];

		$all_post = [
			'posts_per_page' => -1,
			'post_type'      => $post_type,
		];

		$post_terms = get_posts( $all_post );

		if ( ! empty( $post_terms ) && ! is_wp_error( $post_terms ) ) {
			foreach ( $post_terms as $term ) {
				$options[ $term->ID ] = $term->post_title;
			}
		}

		return $options;
	}

	/**
	 * Get post types
	 *
	 * @param array $args Arguments.
	 * @return array Post types list.
	 */
	public static function get_post_types( $args = [] ) {
		$post_type_args = [
			'show_in_nav_menus' => true,
		];

		if ( ! empty( $args['post_type'] ) ) {
			$post_type_args['name'] = $args['post_type'];
		}

		$_post_types = get_post_types( $post_type_args, 'objects' );

		$post_types = [];
		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $object->label;
		}

		return $post_types;
	}

	/**
	 * Get all sidebars
	 *
	 * @return array Sidebars list.
	 */
	public static function get_all_sidebars() {
		global $wp_registered_sidebars;

		$options = [];

		if ( ! $wp_registered_sidebars ) {
			$options[''] = esc_html__( 'No sidebars were found', 'vlt-helper' );
		} else {
			$options[''] = esc_html__( 'Choose Sidebar', 'vlt-helper' );

			foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
				$options[ $sidebar_id ] = $sidebar['name'];
			}
		}

		return $options;
	}

	/**
	 * Get all types of posts
	 *
	 * @return array Posts list.
	 */
	public static function get_all_types_post() {
		$posts = get_posts( [
			'post_type'      => 'any',
			'post_style'     => 'all_types',
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
		] );

		if ( ! empty( $posts ) ) {
			return wp_list_pluck( $posts, 'post_title', 'ID' );
		}

		return [];
	}

	/**
	 * Get post type categories
	 *
	 * @param string $type Type of value to return (term_id, slug, etc).
	 * @return array Categories list.
	 */
	public static function get_post_type_categories( $type = 'term_id' ) {
		$options = [];

		$terms = get_terms( [
			'taxonomy'   => 'category',
			'hide_empty' => true,
		] );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->{$type} ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get taxonomies
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array Taxonomies list.
	 */
	public static function get_taxonomies( $taxonomy = 'category' ) {
		$options = [];

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		] );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get available menus
	 *
	 * @return array Menus list.
	 */
	public static function get_available_menus() {
		$options = [];
		$menus   = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$options[ $menu->slug ] = $menu->name;
		}

		return $options;
	}

	/**
	 * Get Elementor templates
	 *
	 * @param string|null $type Template type.
	 * @return array Templates list.
	 */
	public static function get_elementor_templates( $type = null ) {
		$args = [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
		];

		if ( $type ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => $type,
				],
			];
		}

		$page_templates = get_posts( $args );

		$options[0] = esc_html__( 'Select a Template', 'vlt-helper' );

		if ( ! empty( $page_templates ) && ! is_wp_error( $page_templates ) ) {
			foreach ( $page_templates as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		} else {
			$options[0] = esc_html__( 'Create a Template First', 'vlt-helper' );
		}

		return $options;
	}

	/**
	 * Render Elementor template
	 *
	 * @param int $template_id Template ID to render.
	 * @return string Rendered template HTML.
	 */
	public static function render_template( $template_id ) {
		if ( ! $template_id || ! class_exists( '\Elementor\Frontend' ) ) {
			return '';
		}

		// Only render published templates
		if ( 'publish' !== get_post_status( $template_id ) ) {
			return '';
		}

		// Get rendered template content
		$content = \Elementor\Plugin::$instance->frontend->get_builder_content_for_display( $template_id, false );

		// Force enqueue Elementor styles for proper rendering
		\Elementor\Plugin::$instance->frontend->enqueue_styles();

		return $content;
	}

}
