<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Core\Base\Module;
use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Core\DynamicTags\Dynamic_CSS;

/**
 * Custom CSS Extension
 *
 * Allows adding custom CSS to Elementor elements
 */
class CustomCssModule extends Module {
	/**
	 * Track which widgets have already had CSS appended
	 *
	 * @var array
	 */
	public static $hasRunCustomCSS = [];

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
		return 'custom-css';
	}

	/**
	 * Enqueue editor scripts for Custom CSS
	 */
	public function enqueue_scripts_editor() {
		wp_enqueue_script(
			'vlt-custom-css',
			plugin_dir_url( __FILE__ ) . 'js/CustomCssModule.js',
			[ 'elementor-editor' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Register Custom CSS controls
	 *
	 * @param object $element    elementor element
	 * @param string $section_id section ID
	 */
	public function register_controls( $element, $section_id ) {

		// Remove Custom CSS Banner (From free version)
		if ( 'section_custom_css_pro' !== $section_id ) {
			return;
		}

		Plugin::instance()->controls_manager->remove_control_from_stack( $element->get_unique_name(), [ 'section_custom_css_pro', 'custom_css_pro' ] );

		$element->start_controls_section(
			'section_custom_css',
			[
				'label' => esc_html__( 'Custom CSS', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'custom_css',
			[
				'label'       => esc_html__( 'Add your own custom CSS', 'toolkit' ),
				'type'        => Controls_Manager::CODE,
				'description' => sprintf(
					/* translators: 1: Link opening tag, 2: Link opening tag, 3: Link closing tag. */
					esc_html__( 'Use %1$scustom CSS%3$s to style your content or add %2$sthe "selector" prefix%3$s to target specific elements.', 'toolkit' ),
					'<a href="https://go.elementor.com/learn-more-panel-custom-css/" target="_blank">',
					'<a href="https://go.elementor.com/learn-more-panel-custom-css-selectors/" target="_blank">',
					'</a>'
				),
				'language'    => 'css',
				'render_type' => 'ui',
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Add custom CSS to post CSS
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css post CSS instance
	 * @param \Elementor\Element_Base        $element  element instance
	 */
	public function add_post_css( $post_css, $element ) {
		// Skip Dynamic CSS
		if ( $post_css instanceof Dynamic_CSS ) {
			return;
		}

		if ( !$post_css || !$post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		if ( !$element || !method_exists( $element, 'get_settings' ) ) {
			return;
		}

		$settings = $element->get_settings();

		if ( empty( $settings['custom_css'] ) || !is_string( $settings['custom_css'] ) ) {
			return;
		}

		$css = trim( $settings['custom_css'] );

		if ( '' === $css ) {
			return;
		}

		$unique_uid = $element->get_name() . $element->get_id();

		if ( !$this->needAppendCustomCSSforWidget( $unique_uid ) ) {
			return;
		}

		$selector = '';

		if ( method_exists( $post_css, 'get_element_unique_selector' ) && $element ) {
			$selector = $post_css->get_element_unique_selector( $element );
		}

		if ( empty( $selector ) ) {
			return;
		}

		$css = str_replace( 'selector', $selector, $css );

		// Security: Strip script tags and HTML
		$css = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $css );
		$css = strip_tags( $css );

		if ( '' === $css ) {
			return;
		}

		$element_name = $element->get_name() ?? 'unknown';
		$css          = sprintf(
			'/* VLT Custom CSS for %s, class: %s */',
			$element_name,
			$element->get_unique_selector()
		) . $css . '/* End VLT Custom CSS */';

		// Minify CSS
		$css = \VLT\Toolkit\Toolkit::minify_css( $css );

		$stylesheet = $post_css->get_stylesheet();

		if ( !$stylesheet || !method_exists( $stylesheet, 'add_raw_css' ) ) {
			return;
		}

		$stylesheet->add_raw_css( $css );
	}

	/**
	 * Add page settings custom CSS
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css post CSS instance
	 */
	public function add_page_settings_css( $post_css ) {
		if ( !$post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		$document = Plugin::instance()->documents->get( $post_css->get_post_id() );

		if ( !$document ) {
			return;
		}

		$css = $document->get_settings( 'custom_css' ) ?? '';
		$css = trim( $css );

		if ( '' === $css ) {
			return;
		}

		$css = str_replace( 'selector', $document->get_css_wrapper_selector(), $css );

		// Security: Strip script tags and HTML
		$css = strip_tags( $css );
		$css = preg_replace( '#<script.*?>.*?</script>#is', '', $css );

		if ( '' === $css ) {
			return;
		}

		$css = '/* VLT Document Custom CSS */' . $css . '/* End VLT Document CSS */';

		// Minify CSS
		$css = \VLT\Toolkit\Toolkit::minify_css( $css );

		$post_css->get_stylesheet()->add_raw_css( $css );
	}

	/**
	 * Register WordPress hooks
	 */
	protected function add_actions() {

		// Register controls for page settings (replaces Elementor Pro banner)
		add_action( 'elementor/element/after_section_end', [ $this, 'register_controls' ], 10, 2 );

		// Register CSS hooks
		add_action( 'elementor/element/parse_css', [ $this, 'add_post_css' ], 10, 2 );
		add_action( 'elementor/css-file/post/parse', [ $this, 'add_page_settings_css' ] );

		// Enqueue editor scripts
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_scripts_editor' ] );
	}

	/**
	 * Check if custom CSS needs to be appended for widget
	 *
	 * @param string $uid unique widget ID
	 *
	 * @return bool
	 */
	private function needAppendCustomCSSforWidget( $uid ) {
		$need_append = false;
		$tmp         = self::$hasRunCustomCSS;

		if ( !in_array( $uid, $tmp, true ) ) {
			$need_append = true;
			$tmp[]       = $uid;
		}

		self::$hasRunCustomCSS = $tmp;

		return $need_append;
	}
}
