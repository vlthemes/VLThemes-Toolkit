<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Load base files only
require_once __DIR__ . '/Elementor/Helpers.php';

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
	 * Module instances
	 *
	 * @var array
	 */
	private $modules = [];

	/**
	 * Icon Sets manager
	 *
	 * @var IconSets
	 */
	private $icon_sets;

	/**
	 * Enable dev debug styles
	 *
	 * @var bool
	 */
	private $enable_dev_debug = false;

	/**
	 * Register module
	 */
	public function register() {
		add_action( 'elementor/init', [ $this, 'init_elementor' ] );
	}

	/**
	 * Enqueue editor styles
	 */
	public function editor_styles() {
		// Enqueue main editor CSS
		wp_enqueue_style(
			'vlt-editor-styles',
			$this->assets_url . 'css/editor-styles.css',
			[],
			VLT_TOOLKIT_VERSION,
		);

		// Enqueue dev debug styles
		if ( $this->enable_dev_debug ) {
			wp_enqueue_style(
				'vlt-dev-debug',
				VLT_TOOLKIT_URL . 'assets/css/dev-debug.css',
				[],
				VLT_TOOLKIT_VERSION,
			);
		}

		// Add inline CSS for badge customization
		$this->add_badge_styles();
	}

	/**
	 * Enqueue frontend styles
	 */
	public function frontend_styles() {
		// Enqueue dev debug styles
		if ( $this->enable_dev_debug ) {
			wp_enqueue_style(
				'vlt-dev-debug',
				VLT_TOOLKIT_URL . 'assets/css/dev-debug.css',
				[],
				VLT_TOOLKIT_VERSION,
			);
		}
	}

	/**
	 * Register frontend scripts
	 */
	public function register_frontend_scripts() {
		$plugin_assets_dir = VLT_TOOLKIT_URL . 'assets/';

		// Register Sharer
		wp_register_script( 'sharer', $plugin_assets_dir . 'vendors/js/sharer.js', [], VLT_TOOLKIT_VERSION, true );

		// Register Socicons
		wp_register_style( 'socicons', $plugin_assets_dir . 'fonts/socicons/socicons.css', [], VLT_TOOLKIT_VERSION );

		// Register scripts needed for frontend
		do_action( 'vlt_toolkit_elementor_register_frontend_scripts' );
	}

	/**
	 * Enqueue frontend scripts
	 */
	public function enqueue_frontend_scripts() {

		// Enqueue scripts needed for frontend
		do_action( 'vlt_toolkit_elementor_enqueue_frontend_scripts' );
	}

	/**
	 * Initialize Elementor integration
	 */
	public function init_elementor() {
		// Register widgets - support both old and new Elementor versions
		add_action( 'elementor/widgets/register', [ $this, 'register_widgets' ] );
		add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_widgets' ] );

		// Register custom controls
		add_action( 'elementor/controls/register', [ $this, 'register_controls' ] );

		// Register other hooks
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ] );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'frontend_styles' ] );

		add_action( 'elementor/frontend/after_register_scripts', [ $this, 'register_frontend_scripts' ] );
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'enqueue_frontend_scripts' ] );

		add_action( 'elementor/elements/categories_registered', [ $this, 'register_categories' ] );
		add_action( 'elementor/theme/register_locations', [ $this, 'register_locations' ] );
		add_filter( 'elementor/icons_manager/additional_tabs', [ $this, 'add_icon_tabs' ] );

		// Hide promo widgets
		add_filter( 'elementor/editor/localize_settings', [ $this, 'hide_promo_widgets' ], 20 );

		// Add sticky position option
		add_action( 'elementor/element/container/section_layout/before_section_end', [ $this, 'add_sticky_position' ] );
		add_action( 'elementor/element/common/_section_style/before_section_end', [ $this, 'add_sticky_position' ] );

		// Register Elementor modules
		$this->register_elementor_modules();
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager elementor widgets manager
	 */
	public function register_widgets( $widgets_manager = null ) {
		$this->include_widget_files();

		// Get widget manager
		if ( !$widgets_manager ) {
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

		do_action( 'vlt_toolkit_elementor_widgets_registered' );
	}

	/**
	 * Register custom controls
	 *
	 * @param \Elementor\Controls_Manager $controls_manager elementor controls manager
	 */
	public function register_controls( $controls_manager ) {
		// Load custom control files
		require_once __DIR__ . '/Elementor/Controls/WidgetList.php';

		// Register custom controls
		$controls_manager->register( new \VLT\Toolkit\Modules\Integrations\Elementor\Controls\WidgetList() );
	}

	/**
	 * Register Elementor categories
	 *
	 * @param object $elements_manager elementor elements manager
	 */
	public function register_categories( $elements_manager ) {
		$dashboard  = \VLT\Toolkit\Admin\Dashboard::instance();
		$theme_name = $dashboard->theme_name ?: 'VLThemes';

		// Default categories
		$categories = [
			'vlt-elements' => [
				'title' => sprintf( esc_html__( '%s Elements', 'toolkit' ), $theme_name ),
				'icon'  => 'fa fa-plug',
			],
			'vlt-showcase' => [
				'title' => sprintf( esc_html__( '%s Showcase', 'toolkit' ), $theme_name ),
				'icon'  => 'fa fa-image',
			],
			'vlt-woocommerce' => [
				'title' => sprintf( esc_html__( '%s WooCommerce', 'toolkit' ), $theme_name ),
				'icon'  => 'fa fa-shopping-cart',
			],
		];

		/**
		 * Filter Elementor widget categories
		 *
		 * Allows themes to add or modify widget categories.
		 *
		 * @param array $categories array of categories with slug as key and args as value
		 */
		$categories = apply_filters( 'vlt_toolkit_elementor_categories', $categories );

		// Register all categories
		foreach ( $categories as $slug => $args ) {
			$elements_manager->add_category( $slug, $args );
		}
	}

	/**
	 * Register Elementor theme locations
	 *
	 * @param object $elementor_theme_manager elementor theme manager
	 */
	public function register_locations( $elementor_theme_manager ) {
		// Default locations
		$locations = [];

		/**
		 * Filter Elementor theme locations
		 *
		 * Allows themes to add or modify theme locations.
		 *
		 * @param array $locations array of location names
		 */
		$locations = apply_filters( 'vlt_toolkit_elementor_locations', $locations );

		// Register all locations
		foreach ( $locations as $location ) {
			$elementor_theme_manager->register_location( $location );
		}
	}

	/**
	 * Hide Elementor Pro promo widgets
	 *
	 * Removes promotion widgets when Elementor Pro is not installed
	 *
	 * @param array $settings elementor settings
	 *
	 * @return array modified settings
	 */
	public function hide_promo_widgets( $settings ) {
		if ( !class_exists( 'ElementorPro\Plugin' ) && !empty( $settings['promotionWidgets'] ) ) {
			$settings['promotionWidgets'] = [];
		}

		return $settings;
	}

	/**
	 * Add custom icon tabs
	 *
	 * @param array $settings icon settings
	 *
	 * @return array
	 */
	public function add_icon_tabs( $settings ) {
		return $this->icon_sets->add_icon_tabs( $settings );
	}

	/**
	 * Add sticky option to position control
	 *
	 * @param \Elementor\Element_Base $element elementor element instance
	 */
	public function add_sticky_position( $element ) {
		// Determine control name based on element type
		// Containers use 'position', widgets use '_position'
		$position_control = $element->get_name() === 'container' ? 'position' : '_position';

		// Update position control to add sticky option
		$element->update_control(
			$position_control,
			[
				'options' => [
					''         => esc_html__( 'Default', 'toolkit' ),
					'absolute' => esc_html__( 'Absolute', 'toolkit' ),
					'fixed'    => esc_html__( 'Fixed', 'toolkit' ),
					'sticky'   => esc_html__( 'Sticky', 'toolkit' ),
				],
			]
		);

		// Add hidden control to add class when position is sticky
		$element->add_control(
			'_position_sticky_class',
			[
				'type'         => \Elementor\Controls_Manager::HIDDEN,
				'default'      => 'yes',
				'prefix_class' => 'elementor-sticky-',
				'condition'    => [
					$position_control => 'sticky',
				],
			],
			[
				'position' => [
					'of' => $position_control,
				],
			]
		);

		// Add top position control for sticky
		$element->add_responsive_control(
			'position_sticky_top',
			[
				'label'      => esc_html__( 'Top', 'toolkit' ),
				'type'       => \Elementor\Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', '%', 'vh', 'custom' ],
				'default'    => [
					'unit' => 'px',
					'size' => 0,
				],
				'range'      => [
					'px' => [
						'min'  => 0,
						'max'  => 1000,
						'step' => 5,
					],
					'vh' => [
						'min' => 0,
						'max' => 100,
					],
					'em' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'selectors'  => [
					'{{WRAPPER}}.elementor-sticky-yes'                      => 'top: calc({{SIZE}}{{UNIT}} + var(--wp-admin--admin-bar--height, 0px)); --height: 100%;',
					'body:not(.admin-bar) {{WRAPPER}}.elementor-sticky-yes' => 'top: {{SIZE}}{{UNIT}}; --height: 100%;',
				],
				'condition'  => [
					$position_control => 'sticky',
				],
			],
			[
				'position' => [
					'of' => '_position_sticky_class',
				],
			]
		);
	}

	/**
	 * Check if current post/page is built with Elementor.
	 *
	 * Determines whether the current post was created using Elementor page builder.
	 * Useful for conditional logic when displaying different layouts or styles.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Post $post Current post object.
	 *
	 * @return bool true if post is built with Elementor and Elementor is active, false otherwise
	 */
	public static function is_built_with_elementor() {
		global $post;

		if ( !$post || !class_exists( '\Elementor\Plugin' ) ) {
			return false;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post->ID );

		return $document && $document->is_built_with_elementor();
	}

	/**
	 * Render Elementor template
	 *
	 * @param int $template_id template ID to render
	 *
	 * @return string rendered template HTML
	 */
	public static function render_template( $template_id ) {
		if ( !$template_id ) {
			return '';
		}

		// Only render published templates
		if ( 'publish' !== get_post_status( $template_id ) ) {
			return '';
		}

		// Check if Elementor is available and if this post was built with Elementor
		if ( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->documents->get( $template_id )->is_built_with_elementor() ) {
			// Get rendered Elementor content
			$content = \Elementor\Plugin::$instance->frontend->get_builder_content( $template_id, true );

			// Force enqueue Elementor styles for proper rendering
			\Elementor\Plugin::$instance->frontend->enqueue_styles();

			// If the post is a custom post type, enqueue its scripts
			if ( method_exists( \Elementor\Plugin::$instance->frontend, 'enqueue_scripts' ) ) {
				\Elementor\Plugin::$instance->frontend->enqueue_scripts();
			}
		} else {
			// For non-Elementor content, get the regular post content
			$post = get_post( $template_id );

			if ( $post ) {
				// Apply content filters to process shortcodes, embeds, etc.
				$content = apply_filters( 'the_content', $post->post_content );
			} else {
				$content = '';
			}
		}

		return $content?: '';
	}

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
		$this->assets_url = VLT_TOOLKIT_URL . 'includes/Modules/Integrations/Elementor/';

		// Initialize icon sets
		$this->init_icon_sets();
	}

	/**
	 * Register Elementor modules
	 */
	private function register_elementor_modules() {
		// Load module files
		require_once __DIR__ . '/Elementor/Modules/CustomCssModule.php';
		require_once __DIR__ . '/Elementor/Modules/CustomAttributesModule.php';
		require_once __DIR__ . '/Elementor/Modules/ParallaxModule.php';
		require_once __DIR__ . '/Elementor/Modules/JarallaxModule.php';
		require_once __DIR__ . '/Elementor/Modules/AosModule.php';
		require_once __DIR__ . '/Elementor/Modules/MaskModule.php';
		require_once __DIR__ . '/Elementor/Modules/LayoutModule.php';
		require_once __DIR__ . '/Elementor/Modules/EqualHeightModule.php';

		// Only register modules if Elementor Pro is not active
		// If Elementor Pro is active, it will handle these features
		if ( ! defined( 'ELEMENTOR_PRO_VERSION' ) ) {
			$this->modules['custom_css']        = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\CustomCssModule();
			$this->modules['custom_attributes'] = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\CustomAttributesModule();
		}

		// Always load these modules (no Pro dependency)
		// $this->modules['parallax'] = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\ParallaxModule();
		// $this->modules['jarallax'] = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\JarallaxModule();
		$this->modules['aos']              = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\AosModule();
		$this->modules['mask']             = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\MaskModule();
		$this->modules['layout']           = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\LayoutModule();
		$this->modules['equal_height']     = new \VLT\Toolkit\Modules\Integrations\Elementor\Module\EqualHeightModule();
	}

	/**
	 * Initialize Icon Sets manager
	 */
	private function init_icon_sets() {
		require_once __DIR__ . '/Elementor/IconSets.php';
		$this->icon_sets = new \VLT\Toolkit\Modules\Integrations\Elementor\IconSets();
	}

	/**
	 * Add badge styles to editor
	 */
	private function add_badge_styles() {
		$dashboard = \VLT\Toolkit\Admin\Dashboard::instance();

		$badge_config = apply_filters(
			'vlt_toolkit_elementor_badge',
			[
				'text' => $dashboard->theme_name,
			],
		);

		if ( empty( $badge_config['text'] ) ) {
			return;
		}

		$custom_css = sprintf(
			'#elementor-panel-elements-wrapper .elementor-element .icon i[class*="-badge"]::after,
			#elementor-panel-elements-wrapper .elementor-element .icon .vlt-badge::after {
				content: "%s";
			}',
			esc_attr( $badge_config['text'] ),
		);

		wp_add_inline_style( 'vlt-editor-styles', $custom_css );
	}

	/**
	 * Include widget files
	 */
	private function include_widget_files() {
		// Toolkit widgets
		require_once __DIR__ . '/Elementor/Widgets/TemplateWidget.php';
		require_once __DIR__ . '/Elementor/Widgets/ContactForm7Widget.php';
		require_once __DIR__ . '/Elementor/Widgets/SpacerWidget.php';
		require_once __DIR__ . '/Elementor/Widgets/WoocommercePageWidget.php';

		// Fire action to allow theme to load widget files from theme directory
		do_action( 'vlt_toolkit_elementor_register_widgets' );
	}

	/**
	 * Get widget classes
	 *
	 * @return array
	 */
	private function get_widget_classes() {
		// Toolkit widgets
		$widgets = [
			\VLT\Toolkit\Modules\Integrations\Elementor\Widgets\TemplateWidget::class,
			\VLT\Toolkit\Modules\Integrations\Elementor\Widgets\ContactForm7Widget::class,
			\VLT\Toolkit\Modules\Integrations\Elementor\Widgets\SpacerWidget::class,
			\VLT\Toolkit\Modules\Integrations\Elementor\Widgets\WoocommercePageWidget::class,
		];

		/**
		 * Filter Elementor widget classes
		 *
		 * Allows themes to add widget classes.
		 *
		 * @param array $widget_classes array of widget class names
		 */
		$theme_widgets = apply_filters( 'vlt_toolkit_elementor_widget_classes', [] );

		return array_merge( $widgets, $theme_widgets );
	}
}
