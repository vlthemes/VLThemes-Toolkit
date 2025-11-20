<?php

namespace VLT\Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main Toolkit class
 */
class Toolkit {


	/**
	 * Instance
	 *
	 * @var Toolkit
	 */
	private static $instance = null;

	/**
	 * Modules
	 *
	 * @var array
	 */
	private $modules = array();

	/**
	 * Plugin assets directory URL
	 *
	 * @var string
	 */
	private $plugin_assets_dir;

	/**
	 * Get instance
	 *
	 * @return Toolkit
	 */
	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->load_textdomain();
		$this->load_base_module();
		$this->init_modules();
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		$this->plugin_assets_dir = VLT_TOOLKIT_URL . 'assets/';

		// Enqueue admin scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'customize_controls_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

		// Register all helper assets (don't enqueue yet)
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 1 );
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function enqueue_admin_scripts() {
		wp_enqueue_script(
			'vlt-entire-admin',
			$this->plugin_assets_dir . 'js/entire-admin.js',
			array(),
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register all helper assets
	 *
	 * Registers scripts and styles but doesn't enqueue them
	 * Other modules can enqueue these as dependencies
	 */
	public function register_assets() {
		// ===================================
		// VENDORS
		// ===================================
		wp_register_script( 'gsap', $this->plugin_assets_dir . 'vendors/js/gsap.js', array(), VLT_TOOLKIT_VERSION, true );
		wp_register_script( 'scrolltrigger', $this->plugin_assets_dir . 'vendors/js/gsap-scrolltrigger.js', array( 'gsap' ), VLT_TOOLKIT_VERSION, true );

		wp_register_script( 'scrolltoplugin', $this->plugin_assets_dir . 'vendors/js/gsap-scrolltoplugin.js', array( 'gsap' ), VLT_TOOLKIT_VERSION, true );
		wp_register_script( 'textplugin', $this->plugin_assets_dir . 'vendors/js/gsap-textplugin.js', array( 'gsap' ), VLT_TOOLKIT_VERSION, true );
		wp_register_script( 'observer', $this->plugin_assets_dir . 'vendors/js/gsap-observer.js', array( 'gsap' ), VLT_TOOLKIT_VERSION, true );
		wp_register_script( 'draggable', $this->plugin_assets_dir . 'vendors/js/gsap-draggable.js', array( 'gsap' ), VLT_TOOLKIT_VERSION, true );

		wp_register_script( 'jarallax', $this->plugin_assets_dir . 'vendors/js/jarallax.js', array(), VLT_TOOLKIT_VERSION, true );
		wp_register_script( 'jarallax-video', $this->plugin_assets_dir . 'vendors/js/jarallax-video.js', array(), VLT_TOOLKIT_VERSION, true );
		wp_register_style( 'jarallax', $this->plugin_assets_dir . 'vendors/css/jarallax.css', array(), VLT_TOOLKIT_VERSION );

		wp_register_script( 'aos', $this->plugin_assets_dir . 'vendors/js/aos.js', array(), VLT_TOOLKIT_VERSION, true );
		wp_register_style( 'aos', $this->plugin_assets_dir . 'vendors/css/aos.css', array(), VLT_TOOLKIT_VERSION );

		wp_register_script( 'sharer', $this->plugin_assets_dir . 'vendors/js/sharer.js', array(), VLT_TOOLKIT_VERSION, true );

		wp_register_style( 'socicons', $this->plugin_assets_dir . 'fonts/socicons/socicons.css', array(), VLT_TOOLKIT_VERSION );

		// Allow themes/plugins to register additional assets
		do_action( 'vlt_toolkit/register_assets' );
	}

	/**
	 * Load plugin text domain
	 */
	private function load_textdomain() {
		load_plugin_textdomain(
			'vlthemes-toolkit',
			false,
			dirname( plugin_basename( VLT_TOOLKIT_FILE ) ) . '/languages/'
		);
	}

	/**
	 * Load base module class
	 */
	private function load_base_module() {
		require_once VLT_TOOLKIT_PATH . 'includes/Modules/BaseModule.php';
	}

	/**
	 * Initialize modules
	 */
	private function init_modules() {
		$modules = array(
			// Core feature modules
			'Features\\UploadMimes',
			'Features\\Widgets',
			'Features\\CustomFonts',
			'Features\\DemoImport',
			'Features\\SocialIcons',
			'Features\\TemplateParts',
			'Features\\PostViews',
			'Features\\AOS',
			'Features\\Breadcrumbs',
			// Integrations
			'Integrations\\Elementor',
			'Integrations\\ContactForm7',
			'Integrations\\VisualPortfolio',
			'Integrations\\WooCommerce',
			'Integrations\\ACF',
			'Integrations\\ACFProUpdater',
		);

		foreach ( $modules as $module ) {
			$this->load_module( $module );
		}

		do_action( 'vlt_toolkit/modules_loaded' );
	}

	/**
	 * Load module
	 *
	 * @param string $module Module class name.
	 */
	private function load_module( $module ) {
		$class_name = 'VLT\\Toolkit\\Modules\\' . $module;
		$file_path  = VLT_TOOLKIT_PATH . 'includes/Modules/' . str_replace( '\\', '/', $module ) . '.php';

		if ( file_exists( $file_path ) ) {
			require_once $file_path;

			if ( class_exists( $class_name ) ) {
				$this->modules[ $module ] = $class_name::instance();
			}
		}
	}

	/**
	 * Get module
	 *
	 * @param string $module Module name.
	 * @return object|null
	 */
	public function get_module( $module ) {
		return isset( $this->modules[ $module ] ) ? $this->modules[ $module ] : null;
	}

	/**
	 * Minify CSS code
	 *
	 * Removes unnecessary whitespace, comments, and formatting from CSS
	 * to reduce file size and improve loading performance.
	 *
	 * @param string $css CSS code to minify.
	 * @return string Minified CSS code.
	 */
	public static function minify_css( $css ) {
		// Reduce multiple spaces to single space
		$css = preg_replace( '/\s+/', ' ', $css );

		// Remove comments (except /*! important comments)
		$css = preg_replace( '/\/\*[^\!](.*?)\*\//s', '', $css );

		// Remove spaces around CSS syntax characters
		$css = preg_replace( '/\s?([\{\};,])\s?/', '$1', $css );

		// Clean up trailing semicolons and spaces after closing braces
		$css = str_replace( array( ';}', '} ' ), '}', $css );

		return trim( $css );
	}
}
