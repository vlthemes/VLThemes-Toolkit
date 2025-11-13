<?php

namespace VLT\Helper\Modules\Integrations;

use VLT\Helper\Modules\BaseModule;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\LayoutExtensions;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\JarallaxExtension;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\AosExtension;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\ElementParallaxExtension;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\CustomAttributesExtension;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\CustomCssExtension;
use VLT\Helper\Modules\Integrations\Elementor\Extensions\HeaderFooterExtensions;
use VLT\Helper\Modules\Integrations\Elementor\IconSets;
use VLT\Helper\Modules\Integrations\Elementor\Helpers;

if (! defined('ABSPATH')) {
	exit;
}

// Load required files
require_once __DIR__ . '/Elementor/BaseExtension.php';
require_once __DIR__ . '/Elementor/Extensions/LayoutExtensions.php';
require_once __DIR__ . '/Elementor/Extensions/JarallaxExtension.php';
require_once __DIR__ . '/Elementor/Extensions/AosExtension.php';
require_once __DIR__ . '/Elementor/Extensions/ElementParallaxExtension.php';
require_once __DIR__ . '/Elementor/Extensions/CustomAttributesExtension.php';
require_once __DIR__ . '/Elementor/Extensions/CustomCssExtension.php';
require_once __DIR__ . '/Elementor/Extensions/HeaderFooterExtensions.php';
require_once __DIR__ . '/Elementor/IconSets.php';
require_once __DIR__ . '/Elementor/Helpers.php';

/**
 * Elementor Integration Module
 *
 * Handles Elementor widgets registration and integration
 */
class Elementor extends BaseModule
{

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
	 * Extension instances
	 *
	 * @var array
	 */
	private $extensions = [];

	/**
	 * Icon Sets manager
	 *
	 * @var IconSets
	 */
	private $icon_sets;

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register()
	{
		return defined('ELEMENTOR_VERSION');
	}

	/**
	 * Initialize module
	 */
	protected function init()
	{
		$this->assets_url = VLT_HELPER_URL . 'assets/';

		// Enqueue all assets
		add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 1);

		// Initialize extensions and icon sets
		$this->init_extensions();
		$this->init_icon_sets();
	}

	/**
	 * Initialize extensions
	 */
	private function init_extensions()
	{
		$this->extensions = [
			'jarallax'         => new JarallaxExtension(),
			'aos'              => new AosExtension(),
			'element_parallax' => new ElementParallaxExtension(),
			'layout'           => new LayoutExtensions(),
			'custom_attrs'     => new CustomAttributesExtension(),
			'custom_css'       => new CustomCssExtension(),
			'header_footer'    => new HeaderFooterExtensions(),
		];
	}

	/**
	 * Initialize Icon Sets manager
	 */
	private function init_icon_sets()
	{
		$this->icon_sets = new IconSets();
	}

	/**
	 * Register module
	 */
	public function register()
	{
		add_action('elementor/init', [$this, 'init_elementor']);
	}

	/**
	 * Enqueue all elementor assets
	 */
	public function enqueue_assets()
	{
		// ===================================
		// SCRIPTS
		// ===================================
		wp_enqueue_script(
			'vlt-extension-elementor',
			$this->assets_url . 'extensions/elementor/elementor-bundle.js',
			[
				'aos',
				'gsap',
				'scrolltrigger',
				'jarallax',
				'jarallax-video'
			],
			VLT_HELPER_VERSION,
			true
		);

		// ===================================
		// STYLES
		// ===================================
		wp_enqueue_style('aos');
		wp_enqueue_style('jarallax');
	}

	/**
	 * Enqueue editor styles
	 */
	public function editor_styles()
	{
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
	private function add_badge_styles()
	{
		$theme = wp_get_theme();
		$theme_name = $theme->get('Name');

		$badge_config = apply_filters('vlt_helper_elementor_badge', [
			'text' => $theme_name,
		]);

		if (empty($badge_config['text'])) {
			return;
		}

		$custom_css = sprintf(
			'#elementor-panel-elements-wrapper .elementor-element .icon i[class*="-badge"]::after,
			#elementor-panel-elements-wrapper .elementor-element .icon .vlt-badge::after {
				content: "%s";
			}',
			esc_attr($badge_config['text'])
		);

		wp_add_inline_style('vlt-elementor-editor', $custom_css);
	}

	/**
	 * Initialize Elementor integration
	 */
	public function init_elementor()
	{
		// Register widgets - support both old and new Elementor versions
		add_action('elementor/widgets/register', [$this, 'register_widgets']);
		add_action('elementor/widgets/widgets_registered', [$this, 'register_widgets']);

		// Register other hooks
		add_action('elementor/editor/after_enqueue_styles', [$this, 'editor_styles']);
		add_action('elementor/elements/categories_registered', [$this, 'register_categories']);
		add_action('elementor/theme/register_locations', [$this, 'register_locations']);
		add_filter('elementor/icons_manager/additional_tabs', [$this, 'add_icon_tabs']);

		// Hide promo widgets
		add_filter('elementor/editor/localize_settings', [$this, 'hide_promo_widgets'], 20);
	}

	/**
	 * Include widget files
	 *
	 * Widget files should be loaded from theme using the action hook.
	 * Theme manages all widget file paths and loading.
	 */
	private function include_widget_files()
	{
		// Fire action to allow theme to load widget files from theme directory
		do_action('vlt_helper_elementor_register_widgets');
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets($widgets_manager = null)
	{
		$this->include_widget_files();

		// Get widget manager
		if (! $widgets_manager) {
			$widgets_manager = \Elementor\Plugin::instance()->widgets_manager;
		}

		// Get widget classes
		$widgets = $this->get_widget_classes();

		// Register each widget
		foreach ($widgets as $widget_class) {
			if (class_exists($widget_class)) {
				$widgets_manager->register(new $widget_class());
			}
		}

		do_action('vlt_helper_elementor_widgets_registered');
	}

	/**
	 * Get widget classes
	 *
	 * Returns empty array by default. Use 'vlt_helper_elementor_widget_classes' filter
	 * in theme to register widget classes.
	 *
	 * @return array
	 */
	private function get_widget_classes()
	{
		/**
		 * Filter Elementor widget classes
		 *
		 * Allows themes to specify which widget classes to register.
		 *
		 * @param array $widget_classes Array of widget class names.
		 */
		return apply_filters('vlt_helper_elementor_widget_classes', []);
	}

	/**
	 * Register Elementor categories
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_categories($elements_manager)
	{
		// Default categories
		$categories = [
			'vlthemes-elements' => [
				'title' => esc_html__('VLThemes Elements', 'vlt-helper'),
				'icon'  => 'fa fa-plug',
			],
			'vlthemes-showcase' => [
				'title' => esc_html__('VLThemes Showcase', 'vlt-helper'),
				'icon'  => 'fa fa-image',
			],
			'vlthemes-woo' => [
				'title' => esc_html__('VLThemes WooCommerce', 'vlt-helper'),
				'icon'  => 'fa fa-shopping-cart',
			],
		];

		/**
		 * Filter Elementor widget categories
		 *
		 * Allows themes to add or modify widget categories.
		 *
		 * @param array $categories Array of categories with slug as key and args as value.
		 */
		$categories = apply_filters('vlt_helper_elementor_categories', $categories);

		// Register all categories
		foreach ($categories as $slug => $args) {
			$elements_manager->add_category($slug, $args);
		}
	}

	/**
	 * Register Elementor theme locations
	 *
	 * @param object $elementor_theme_manager Elementor theme manager.
	 */
	public function register_locations($elementor_theme_manager)
	{
		// Default locations
		$locations = ['header', 'footer', '404'];

		/**
		 * Filter Elementor theme locations
		 *
		 * Allows themes to add or modify theme locations.
		 *
		 * @param array $locations Array of location names.
		 */
		$locations = apply_filters('vlt_helper_elementor_locations', $locations);

		// Register all locations
		foreach ($locations as $location) {
			$elementor_theme_manager->register_location($location);
		}
	}

	/**
	 * Hide Elementor Pro promo widgets
	 *
	 * Removes promotion widgets when Elementor Pro is not installed
	 *
	 * @param array $settings Elementor settings.
	 * @return array Modified settings.
	 */
	public function hide_promo_widgets($settings)
	{
		if (! class_exists('ElementorPro\Plugin') && ! empty($settings['promotionWidgets'])) {
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
	public function add_icon_tabs($settings)
	{
		return $this->icon_sets->add_icon_tabs($settings);
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
	 * @return bool True if post is built with Elementor and Elementor is active, false otherwise.
	 */
	public static function is_built_with_elementor()
	{
		global $post;

		if (! $post || ! class_exists('\Elementor\Plugin')) {
			return false;
		}

		$document = \Elementor\Plugin::$instance->documents->get($post->ID);

		return $document && $document->is_built_with_elementor();
	}

	/**
	 * Static helper methods - delegated to Helpers class
	 */

	/**
	 * Get post names by post type
	 *
	 * @param string $post_type Post type.
	 * @return array Posts list.
	 */
	public static function get_post_name($post_type = 'post')
	{
		return Helpers::get_post_name($post_type);
	}

	/**
	 * Get post types
	 *
	 * @param array $args Arguments.
	 * @return array Post types list.
	 */
	public static function get_post_types($args = [])
	{
		return Helpers::get_post_types($args);
	}

	/**
	 * Get all sidebars
	 *
	 * @return array Sidebars list.
	 */
	public static function get_all_sidebars()
	{
		return Helpers::get_all_sidebars();
	}

	/**
	 * Get all types of posts
	 *
	 * @return array Posts list.
	 */
	public static function get_all_types_post()
	{
		return Helpers::get_all_types_post();
	}

	/**
	 * Get post type categories
	 *
	 * @param string $type Type of value to return (term_id, slug, etc).
	 * @return array Categories list.
	 */
	public static function get_post_type_categories($type = 'term_id')
	{
		return Helpers::get_post_type_categories($type);
	}

	/**
	 * Get taxonomies
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array Taxonomies list.
	 */
	public static function get_taxonomies($taxonomy = 'category')
	{
		return Helpers::get_taxonomies($taxonomy);
	}

	/**
	 * Get available menus
	 *
	 * @return array Menus list.
	 */
	public static function get_available_menus()
	{
		return Helpers::get_available_menus();
	}

	/**
	 * Get Elementor templates
	 *
	 * @param string|null $type Template type.
	 * @return array Templates list.
	 */
	public static function get_elementor_templates($type = null)
	{
		return Helpers::get_elementor_templates($type);
	}

	/**
	 * Render Elementor template
	 *
	 * @param int $template_id Template ID to render.
	 * @return string Rendered template HTML.
	 */
	public static function render_template($template_id)
	{
		return Helpers::render_template($template_id);
	}
}
