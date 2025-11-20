<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\AosExtension;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\CustomAttributesExtension;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\CustomCssExtension;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\ElementParallaxExtension;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\JarallaxExtension;
use VLT\Toolkit\Modules\Integrations\Elementor\Extensions\LayoutExtensions;
use VLT\Toolkit\Modules\Integrations\Elementor\IconSets;

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
	protected function init(): void
	{
		$this->assets_url = VLT_TOOLKIT_URL . 'includes/Modules/Integrations/Elementor/';

		// Initialize extensions and icon sets
		$this->init_extensions();
		$this->init_icon_sets();
	}

	/**
	 * Initialize extensions
	 */
	private function init_extensions(): void
	{
		$this->extensions = [
			'jarallax'         => new JarallaxExtension(),
			'aos'              => new AosExtension(),
			'element_parallax' => new ElementParallaxExtension(),
			'layout'           => new LayoutExtensions(),
			'custom_attrs'     => new CustomAttributesExtension(),
			'custom_css'       => new CustomCssExtension(),
		];
	}

	/**
	 * Initialize Icon Sets manager
	 */
	private function init_icon_sets(): void
	{
		$this->icon_sets = new IconSets();
	}

	/**
	 * Register module
	 */
	public function register(): void
	{
		add_action('elementor/init', [ $this, 'init_elementor' ]);
	}

	/**
	 * Enqueue editor styles
	 */
	public function editor_styles(): void
	{
		// Enqueue main editor CSS
		wp_enqueue_style(
			'vlt-editor-styles',
			$this->assets_url . 'css/editor-styles.css',
			[],
			VLT_TOOLKIT_VERSION,
		);

		// Add inline CSS for badge customization
		$this->add_badge_styles();
	}

	/**
	 * Add badge styles to editor
	 */
	private function add_badge_styles(): void
	{
		$dashboard = \VLT\Toolkit\Admin\Dashboard::instance();

		$badge_config = apply_filters(
			'vlt_toolkit_elementor_badge',
			[
				'text' => $dashboard->theme_name,
			],
		);

		if (empty($badge_config['text'])) {
			return;
		}

		$custom_css = sprintf(
			'#elementor-panel-elements-wrapper .elementor-element .icon i[class*="-badge"]::after,
			#elementor-panel-elements-wrapper .elementor-element .icon .vlt-badge::after {
				content: "%s";
			}',
			esc_attr($badge_config['text']),
		);

		wp_add_inline_style('vlt-editor-styles', $custom_css);
	}

	/**
	 * Initialize Elementor integration
	 */
	public function init_elementor(): void
	{
		// Register widgets - support both old and new Elementor versions
		add_action('elementor/widgets/register', [ $this, 'register_widgets' ]);
		add_action('elementor/widgets/widgets_registered', [ $this, 'register_widgets' ]);

		// Register other hooks
		add_action('elementor/editor/after_enqueue_styles', [ $this, 'editor_styles' ]);
		add_action('elementor/elements/categories_registered', [ $this, 'register_categories' ]);
		add_action('elementor/theme/register_locations', [ $this, 'register_locations' ]);
		add_filter('elementor/icons_manager/additional_tabs', [ $this, 'add_icon_tabs' ]);

		// Hide promo widgets
		add_filter('elementor/editor/localize_settings', [ $this, 'hide_promo_widgets' ], 20);
	}

	/**
	 * Include widget files
	 *
	 * Widget files should be loaded from theme using the action hook.
	 * Theme manages all widget file paths and loading.
	 */
	private function include_widget_files(): void
	{
		// Fire action to allow theme to load widget files from theme directory
		do_action('vlt_toolkit_elementor_register_widgets');
	}

	/**
	 * Register widgets
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 */
	public function register_widgets($widgets_manager = null): void
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

		do_action('vlt_toolkit_elementor_widgets_registered');
	}

	/**
	 * Get widget classes
	 *
	 * Returns empty array by default. Use 'vlt_toolkit_elementor_widget_classes' filter
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
		return apply_filters('vlt_toolkit_elementor_widget_classes', []);
	}

	/**
	 * Register Elementor categories
	 *
	 * @param object $elements_manager Elementor elements manager.
	 */
	public function register_categories($elements_manager): void
	{
		// Default categories
		$categories = [
			'vlthemes-elements' => [
				'title' => esc_html__('VLThemes Elements', 'toolkit'),
				'icon'  => 'fa fa-plug',
			],
			'vlthemes-showcase' => [
				'title' => esc_html__('VLThemes Showcase', 'toolkit'),
				'icon'  => 'fa fa-image',
			],
			'vlthemes-woo' => [
				'title' => esc_html__('VLThemes WooCommerce', 'toolkit'),
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
		$categories = apply_filters('vlt_toolkit_elementor_categories', $categories);

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
	public function register_locations($elementor_theme_manager): void
	{
		// Default locations
		$locations = [];

		/**
		 * Filter Elementor theme locations
		 *
		 * Allows themes to add or modify theme locations.
		 *
		 * @param array $locations Array of location names.
		 */
		$locations = apply_filters('vlt_toolkit_elementor_locations', $locations);

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
	 *
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
	 *
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
	 *
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
	 * Render Elementor template
	 *
	 * @param int $template_id Template ID to render.
	 *
	 * @return string Rendered template HTML.
	 */
	public static function render_template($template_id)
	{
		if (! $template_id) {
			return '';
		}

		// Only render published templates
		if (get_post_status($template_id) !== 'publish') {
			return '';
		}

		// Check if Elementor is available and if this post was built with Elementor
		if (class_exists('\Elementor\Plugin') && \Elementor\Plugin::$instance->documents->get($template_id)->is_built_with_elementor()) {
			// Get rendered Elementor content
			$content = \Elementor\Plugin::$instance->frontend->get_builder_content($template_id, true);

			// Force enqueue Elementor styles for proper rendering
			\Elementor\Plugin::$instance->frontend->enqueue_styles();

			// If the post is a custom post type, enqueue its scripts
			if (method_exists(\Elementor\Plugin::$instance->frontend, 'enqueue_scripts')) {
				\Elementor\Plugin::$instance->frontend->enqueue_scripts();
			}
		} else {
			// For non-Elementor content, get the regular post content
			$post = get_post($template_id);

			if ($post) {
				// Apply content filters to process shortcodes, embeds, etc.
				$content = apply_filters('the_content', $post->post_content);
			} else {
				$content = '';
			}
		}

		return $content ?: '';
	}
}
