<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Demo Import Module
 *
 * Handles demo content import functionality
 * Integrates with One Click Demo Import plugin
 * Sets up menus, pages, Elementor, Revolution Slider after import
 */
class DemoImport extends BaseModule
{
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'demo_import';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register(): void
	{
		// Check if One Click Demo Import plugin is active
		if (! class_exists('OCDI_Plugin')) {
			return;
		}

		// Register import files
		add_filter('ocdi/import_files', [ $this, 'import_files' ]);

		// Disable regenerate thumbnails for performance
		add_filter('ocdi/regenerate_thumbnails_in_content_import', '__return_false');

		// Before content import setup
		add_action('ocdi/before_content_import', [ $this, 'before_content_import' ]);

		// After import setup
		add_action('ocdi/after_import', [ $this, 'after_import_setup' ]);
	}

	/**
	 * Register demo import files
	 *
	 * @return array Demo import configuration (array of demos).
	 */
	public function import_files()
	{
		// Allow theme to define demo files (single demo or array of demos)
		$demos = apply_filters('vlt_toolkit_demo_import_files', []);

		// If empty, return empty array
		if (empty($demos)) {
			return [];
		}

		// If single demo (associative array), wrap in array
		if (isset($demos['import_file_name']) || isset($demos['local_import_file'])) {
			$demos = [ $demos ];
		}

		// Filter and validate demos
		$valid_demos = [];
		foreach ($demos as $demo) {
			// Remove empty values
			$demo = array_filter($demo);

			// Must have at least content file
			if (! empty($demo['local_import_file'])) {
				$valid_demos[] = $demo;
			}
		}

		return $valid_demos;
	}

	/**
	 * Before content import setup
	 *
	 * Runs before content is imported
	 *
	 * @param array $selected_import Selected demo data.
	 */
	public function before_content_import($selected_import): void
	{
		// Delete default "Hello World" post
		$this->delete_default_content();

		// Delete all widgets from sidebars
		$this->delete_sidebar_widgets();

		// Update Elementor options before content import
		$elementor_options = [
			'elementor_unfiltered_files_upload'    => true,
			'elementor_disable_color_schemes'      => 'yes',
			'elementor_disable_typography_schemes' => 'yes',
		];

		foreach ($elementor_options as $key => $value) {
			update_option($key, $value);
		}

		// Action hook for themes to add custom setup before content import
		do_action('vlt_toolkit_before_content_import', $selected_import);
	}

	/**
	 * Delete all widgets from sidebars
	 *
	 * Clears all widgets from all registered sidebars before import
	 * to prevent conflicts with demo content
	 */
	private function delete_sidebar_widgets(): void
	{
		// Get all registered sidebars
		global $wp_registered_sidebars;

		if (empty($wp_registered_sidebars)) {
			return;
		}

		// Get current widgets
		$sidebars_widgets = get_option('sidebars_widgets', []);

		// Clear all sidebars except wp_inactive_widgets
		foreach (array_keys($wp_registered_sidebars) as $sidebar_id) {
			if (isset($sidebars_widgets[ $sidebar_id ])) {
				$sidebars_widgets[ $sidebar_id ] = [];
			}
		}

		// Update the option
		update_option('sidebars_widgets', $sidebars_widgets);
	}

	/**
	 * After import setup
	 *
	 * Runs after demo content is imported
	 * Sets up menus, pages, Elementor, Revolution Slider, etc.
	 *
	 * @param array $selected_import Selected demo data.
	 */
	public function after_import_setup($selected_import): void
	{
		global $wp_rewrite;

		// Setup navigation menus
		$this->setup_menus();

		// Setup front page
		$this->setup_front_page();

		// Update date format
		$this->update_date_format();

		// Update permalink structure
		$this->update_permalink_structure($wp_rewrite);

		// Import Revolution Slider
		$this->import_revolution_sliders();

		// Setup Elementor
		$this->setup_elementor();

		// Import Elementor kit
		$this->import_elementor_kit();

		// Action hook for themes to add custom setup
		do_action('vlt_toolkit_after_demo_import', $selected_import);
	}

	/**
	 * Setup navigation menus
	 */
	private function setup_menus(): void
	{
		$menus_to_find = apply_filters('vlt_toolkit_demo_menus', []);

		$locations = [];

		foreach ($menus_to_find as $location => $names) {
			foreach ((array) $names as $name) {
				$term = get_term_by('name', $name, 'nav_menu');

				if ($term && ! is_wp_error($term)) {
					$locations[ $location ] = (int) $term->term_id;
					break;
				}
			}
		}

		if (! empty($locations)) {
			$locations = apply_filters('vlt_toolkit_demo_nav_menu_locations', $locations);
			set_theme_mod('nav_menu_locations', $locations);
		}
	}

	/**
	 * Setup front page
	 */
	private function setup_front_page(): void
	{
		$front_page_title = apply_filters('vlt_toolkit_demo_front_page_title', 'Home');
		$front_page       = get_page_by_title($front_page_title);

		if ($front_page && isset($front_page->ID)) {
			update_option('show_on_front', 'page');
			update_option('page_on_front', (int) $front_page->ID);
		}
	}

	/**
	 * Update date format
	 */
	private function update_date_format(): void
	{
		$date_format = apply_filters('vlt_toolkit_demo_date_format', 'M j, Y');
		update_option('date_format', $date_format);
	}

	/**
	 * Update permalink structure
	 *
	 * @param object $wp_rewrite WordPress rewrite object.
	 */
	private function update_permalink_structure($wp_rewrite): void
	{
		$permalink = apply_filters('vlt_toolkit_demo_permalink_structure', '/%postname%/');

		if ($wp_rewrite && method_exists($wp_rewrite, 'set_permalink_structure')) {
			$wp_rewrite->set_permalink_structure($permalink);
			flush_rewrite_rules(false);
		}
	}

	/**
	 * Import Revolution Slider sliders
	 */
	private function import_revolution_sliders(): void
	{
		if (! class_exists('RevSlider')) {
			return;
		}

		try {
			$revo_slider = new \RevSlider();

			// Get existing slider aliases
			$existing_aliases = method_exists($revo_slider, 'getAllSliderAliases')
				? (array) $revo_slider->getAllSliderAliases()
				: [];

			// Get sliders from theme filter
			$slider_array = apply_filters('vlt_toolkit_demo_revsliders', []);

			foreach ($slider_array as $slider_path) {
				// Extract alias from path
				$slider_alias = basename($slider_path, '.zip');

				// Skip if slider already exists
				if (in_array($slider_alias, $existing_aliases, true)) {
					continue;
				}

				if (file_exists($slider_path) && method_exists($revo_slider, 'importSliderFromPost')) {
					$revo_slider->importSliderFromPost(true, true, $slider_path);
				}
			}
		} catch (\Exception $e) {
			// Fail silently - don't break import if RevSlider fails
			error_log('VLT Toolkit: RevSlider import failed - ' . $e->getMessage());
		}
	}

	/**
	 * Setup Elementor settings
	 */
	private function setup_elementor(): void
	{
		if (! class_exists('\Elementor\Plugin')) {
			return;
		}

		// Update Elementor kit settings
		$this->update_elementor_kit();

		// Update Elementor CPT support
		$this->update_elementor_cpt_support();
	}

	/**
	 * Update Elementor kit settings
	 */
	private function update_elementor_kit(): void
	{
		try {
			if (! \Elementor\Plugin::$instance) {
				return;
			}

			$kits_manager  = \Elementor\Plugin::$instance->kits_manager ?? null;
			$files_manager = \Elementor\Plugin::$instance->files_manager ?? null;

			// Update active kit settings
			if ($kits_manager && method_exists($kits_manager, 'get_active_kit_for_frontend')) {
				$kit = $kits_manager->get_active_kit_for_frontend();

				if ($kit && method_exists($kit, 'update_settings')) {
					// Default settings
					$settings = [
						'container_width' => [
							'size' => '1170',
							'unit' => 'px',
						],
						'space_between_widgets' => [
							'column' => '0',
							'row'    => '0',
							'unit'   => 'px',
						],
						'global_image_lightbox' => '',
					];

					// Allow theme to override
					$settings = apply_filters('vlt_toolkit_demo_elementor_kit_settings', $settings);

					$kit->update_settings($settings);
				}
			}

			// Clear Elementor cache
			if ($files_manager && method_exists($files_manager, 'clear_cache')) {
				$files_manager->clear_cache();
			}
		} catch (\Exception $e) {
			error_log('VLT Toolkit: Elementor kit setup failed - ' . $e->getMessage());
		}
	}

	/**
	 * Update Elementor CPT support
	 */
	private function update_elementor_cpt_support(): void
	{
		$cpt_support = get_option('elementor_cpt_support', false);

		// Default CPT support
		$default_cpts = [ 'page', 'post' ];

		// Allow theme to override
		$default_cpts = apply_filters('vlt_toolkit_demo_elementor_cpt_support', $default_cpts);

		if (! $cpt_support) {
			update_option('elementor_cpt_support', $default_cpts);
		} else {
			// Merge with existing
			$cpt_support = array_unique(array_merge((array) $cpt_support, $default_cpts));
			update_option('elementor_cpt_support', $cpt_support);
		}
	}

	/**
	 * Import Elementor kit
	 *
	 * Imports Elementor kit data (templates, global colors, fonts, settings)
	 */
	private function import_elementor_kit(): void
	{
		if (! class_exists('\Elementor\Plugin')) {
			return;
		}

		try {
			// Get kit file path from filter
			$kit_path = apply_filters('vlt_toolkit_demo_elementor_kit_path', '');

			// Skip if no kit path provided
			if (empty($kit_path) || ! file_exists($kit_path)) {
				return;
			}

			// Check if import-export module exists
			if (! isset(\Elementor\Plugin::$instance->app)) {
				error_log('VLT Toolkit: Elementor app not available for kit import');

				return;
			}

			$import_export_module = \Elementor\Plugin::$instance->app->get_component('import-export');

			if (! $import_export_module || ! method_exists($import_export_module, 'import_kit')) {
				error_log('VLT Toolkit: Elementor import-export module not available');

				return;
			}

			// Import settings
			$import_settings = [
				'referrer' => 'remote',
			];

			// Allow theme to override import settings
			$import_settings = apply_filters('vlt_toolkit_demo_elementor_kit_import_settings', $import_settings);

			// Import the kit
			$import_export_module->import_kit($kit_path, $import_settings);

			// Clear cache after import
			if (isset(\Elementor\Plugin::$instance->files_manager)) {
				\Elementor\Plugin::$instance->files_manager->clear_cache();
			}
		} catch (\Exception $e) {
			error_log('VLT Toolkit: Elementor kit import failed - ' . $e->getMessage());
		}
	}

	/**
	 * Delete default WordPress content
	 *
	 * Removes default "Hello World" post, "Sample Page", and default comment
	 */
	private function delete_default_content(): void
	{
		// Delete "Hello World" post
		$default_post = get_page_by_title('Hello World', OBJECT, 'post');

		if ($default_post) {
			wp_delete_post($default_post->ID, true);
		}

		// Delete "Sample Page"
		$sample_page = get_page_by_title('Sample Page');

		if ($sample_page) {
			wp_delete_post($sample_page->ID, true);
		}

		// Delete default comment
		wp_delete_comment(1, true);

		// Delete auto-draft posts
		global $wpdb;
		$wpdb->query("DELETE FROM {$wpdb->posts} WHERE post_status = 'auto-draft'");
	}
}

/**
 * USAGE EXAMPLES (in theme functions.php or includes/)
 *
 * ======================================
 * 1. DEFINE DEMO IMPORT FILES
 * ======================================
 *
 * // Single demo
 * add_filter( 'vlt_toolkit_demo_import_files', function() {
 *     return [
 *         'import_file_name'             => 'VLT Studio - Main Demo',
 *         'local_import_file'            => get_template_directory() . '/inc/demo/content.xml',
 *         'local_import_widget_file'     => get_template_directory() . '/inc/demo/widgets.json',
 *         'local_import_customizer_file' => get_template_directory() . '/inc/demo/customizer.dat',
 *         'import_preview_image_url'     => get_template_directory_uri() . '/inc/demo/preview.jpg',
 *         'preview_url'                  => 'https://demo.yoursite.com',
 *     ];
 * } );
 *
 * // Multiple demos
 * add_filter( 'vlt_toolkit_demo_import_files', function() {
 *     return [
 *         [
 *             'import_file_name'             => 'Demo 1 - Creative Agency',
 *             'categories'                   => [ 'Business', 'Creative' ],
 *             'local_import_file'            => get_template_directory() . '/inc/demo/demo1/content.xml',
 *             'local_import_widget_file'     => get_template_directory() . '/inc/demo/demo1/widgets.json',
 *             'local_import_customizer_file' => get_template_directory() . '/inc/demo/demo1/customizer.dat',
 *             'import_preview_image_url'     => get_template_directory_uri() . '/inc/demo/demo1/preview.jpg',
 *             'preview_url'                  => 'https://demo.yoursite.com/demo1',
 *         ],
 *         [
 *             'import_file_name'             => 'Demo 2 - Portfolio',
 *             'categories'                   => [ 'Portfolio' ],
 *             'local_import_file'            => get_template_directory() . '/inc/demo/demo2/content.xml',
 *             'local_import_widget_file'     => get_template_directory() . '/inc/demo/demo2/widgets.json',
 *             'import_preview_image_url'     => get_template_directory_uri() . '/inc/demo/demo2/preview.jpg',
 *             'preview_url'                  => 'https://demo.yoursite.com/demo2',
 *         ],
 *     ];
 * } );
 *
 * ======================================
 * 2. REVOLUTION SLIDER IMPORT
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_revsliders', function( $sliders ) {
 *     return [
 *         get_template_directory() . '/inc/demo/sliders/hero-slider.zip',
 *         get_template_directory() . '/inc/demo/sliders/portfolio-slider.zip',
 *         get_template_directory() . '/inc/demo/sliders/testimonials.zip',
 *     ];
 * } );
 *
 * ======================================
 * 3. NAVIGATION MENUS SETUP
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_menus', function( $menus ) {
 *     return [
 *         'primary' => [ 'Main Menu', 'Primary Menu' ],  // Try "Main Menu" first, then "Primary Menu"
 *         'footer'  => [ 'Footer Menu' ],
 *         'mobile'  => [ 'Mobile Menu', 'Main Menu' ],
 *     ];
 * } );
 *
 * // Single menu name per location
 * add_filter( 'vlt_toolkit_demo_menus', function( $menus ) {
 *     return [
 *         'primary' => 'Main Menu',
 *         'footer'  => 'Footer Links',
 *     ];
 * } );
 *
 * ======================================
 * 4. FRONT PAGE SETUP
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_front_page_title', function( $title ) {
 *     return 'Home'; // Exact title of imported page
 * } );
 *
 * add_filter( 'vlt_toolkit_demo_front_page_title', function( $title ) {
 *     return 'Landing Page';
 * } );
 *
 * ======================================
 * 5. PERMALINK STRUCTURE
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_permalink_structure', function( $structure ) {
 *     return '/%postname%/'; // Post name only
 * } );
 *
 * add_filter( 'vlt_toolkit_demo_permalink_structure', function( $structure ) {
 *     return '/blog/%postname%/'; // With blog prefix
 * } );
 *
 * add_filter( 'vlt_toolkit_demo_permalink_structure', function( $structure ) {
 *     return '/%year%/%monthnum%/%postname%/'; // Date-based
 * } );
 *
 * ======================================
 * 6. DATE FORMAT
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_date_format', function( $format ) {
 *     return 'M j, Y'; // Nov 10, 2025
 * } );
 *
 * add_filter( 'vlt_toolkit_demo_date_format', function( $format ) {
 *     return 'F j, Y'; // November 10, 2025
 * } );
 *
 * add_filter( 'vlt_toolkit_demo_date_format', function( $format ) {
 *     return 'd.m.Y'; // 10.11.2025
 * } );
 *
 * ======================================
 * 7. ELEMENTOR KIT SETTINGS
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_elementor_kit_settings', function( $settings ) {
 *     return [
 *         'container_width'       => [
 *             'size' => '1200',
 *             'unit' => 'px',
 *         ],
 *         'space_between_widgets' => [
 *             'column' => '20',
 *             'row'    => '20',
 *             'unit'   => 'px',
 *         ],
 *         'global_image_lightbox' => 'yes',
 *     ];
 * } );
 *
 * // Boxed layout
 * add_filter( 'vlt_toolkit_demo_elementor_kit_settings', function( $settings ) {
 *     return [
 *         'container_width' => [
 *             'size' => '1170',
 *             'unit' => 'px',
 *         ],
 *     ];
 * } );
 *
 * ======================================
 * 8. ELEMENTOR GLOBAL OPTIONS
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_elementor_options', function( $options ) {
 *     return [
 *         'elementor_experiment-container'               => 'active',  // Enable containers
 *         'elementor_experiment-container_grid'          => 'active',  // Enable grid containers
 *         'elementor_experiment-e_swiper_latest'         => 'active',
 *         'elementor_experiment-e_optimized_css_loading' => 'active',
 *         'elementor_experiment-e_font_icon_svg'         => 'active',  // SVG icons
 *         'elementor_unfiltered_files_upload'            => true,
 *         'elementor_disable_color_schemes'              => 'yes',
 *         'elementor_disable_typography_schemes'         => 'yes',
 *     ];
 * } );
 *
 * // Minimal setup
 * add_filter( 'vlt_toolkit_demo_elementor_options', function( $options ) {
 *     return [
 *         'elementor_disable_color_schemes'      => 'yes',
 *         'elementor_disable_typography_schemes' => 'yes',
 *     ];
 * } );
 *
 * ======================================
 * 9. ELEMENTOR CPT SUPPORT
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_elementor_cpt_support', function( $cpts ) {
 *     return [
 *         'page',
 *         'post',
 *         'portfolio',
 *         'project',
 *         'service',
 *     ];
 * } );
 *
 * // Only pages
 * add_filter( 'vlt_toolkit_demo_elementor_cpt_support', function( $cpts ) {
 *     return [ 'page' ];
 * } );
 *
 * ======================================
 * 10. ELEMENTOR KIT IMPORT
 * ======================================
 *
 * add_filter( 'vlt_toolkit_demo_elementor_kit_path', function( $path ) {
 *     return get_template_directory() . '/inc/demo/elementor-kit.zip';
 * } );
 *
 * ======================================
 * 11. CUSTOM POST-IMPORT ACTIONS
 * ======================================
 *
 * add_action( 'vlt_toolkit_after_demo_import', function() {
 *     // Mark demo as imported
 *     update_option( 'my_theme_demo_imported', true );
 *     update_option( 'my_theme_demo_import_date', current_time( 'mysql' ) );
 *
 *     // Set posts per page
 *     update_option( 'posts_per_page', 12 );
 *
 *     // Set blog page if exists
 *     $blog_page = get_page_by_title( 'Blog' );
 *     if ( $blog_page ) {
 *         update_option( 'page_for_posts', $blog_page->ID );
 *     }
 *
 *     // Clear third-party caches
 *     if ( function_exists( 'rocket_clean_domain' ) ) {
 *         rocket_clean_domain(); // WP Rocket
 *     }
 *
 *     if ( function_exists( 'w3tc_flush_all' ) ) {
 *         w3tc_flush_all(); // W3 Total Cache
 *     }
 *
 *     // Regenerate thumbnails (if needed)
 *     // if ( class_exists( 'Regenerate_Thumbnails' ) ) {
 *     //     Regenerate_Thumbnails::regenerate_all_thumbnails();
 *     // }
 *
 *     // Update Elementor CSS
 *     if ( class_exists( '\Elementor\Plugin' ) ) {
 *         \Elementor\Plugin::$instance->files_manager->clear_cache();
 *     }
 * } );
 */
