<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Extensions;

use VLT\Toolkit\Modules\Integrations\Elementor\BaseExtension;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom CSS Extension
 *
 * Allows adding custom CSS to Elementor elements
 */
class CustomCssExtension extends BaseExtension {
	/**
	 * Track which widgets have already had CSS appended
	 *
	 * @var array
	 */
	public static $hasRunCustomCSS = [];

	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'custom_css';

	/**
	 * Enqueue editor scripts for Custom CSS
	 */
	public function enqueue_scripts_editor() {
		wp_enqueue_script(
			'vlt-custom-css',
			plugin_dir_url( __FILE__ ) . 'js/CustomCssExtension.js',
			[ 'elementor-editor' ],
			VLT_TOOLKIT_VERSION,
			true,
		);
	}

	/**
	 * Register Custom CSS controls for page settings
	 *
	 * @param object $document elementor document instance
	 */
	public function register_page_settings_controls( $document ) {
		// Only add to pages and posts (not templates, sections, etc.)
		if ( !$document instanceof \Elementor\Core\DocumentTypes\PageBase && !$document instanceof \Elementor\Modules\Library\Documents\Page ) {
			return;
		}

		$document->start_controls_section(
			'vlt_section_custom_css_page',
			[
				'label' => esc_html__( 'VLT Custom CSS', 'toolkit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			],
		);

		$document->add_control(
			'vlt_custom_css_description',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Add custom CSS for this entire page. Use "selector" to target the page wrapper, or use any custom CSS selectors.', 'toolkit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			],
		);

		$document->add_control(
			'vlt_custom_css',
			[
				'label'       => esc_html__( 'Custom CSS', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::CODE,
				'language'    => 'css',
				'rows'        => 20,
				'render_type' => 'ui',
				'separator'   => 'none',
			],
		);

		$document->end_controls_section();

		// Allow themes to add custom controls
		do_action( 'vlt_toolkit_elementor_custom_css_page_settings_controls', $document );
	}

	/**
	 * Register Custom CSS controls
	 *
	 * @param object $element elementor element
	 * @param array  $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_custom_css',
			[
				'label' => esc_html__( 'VLT Custom CSS', 'toolkit' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			],
		);

		$element->add_control(
			'vlt_custom_css_description',
			[
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Use "selector" to target wrapper element. Examples:<br>selector {color: red;} // For main element<br>selector .child-element {margin: 10px;} // For child element<br>.my-class {text-align: center;} // Or use any custom selector', 'toolkit' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			],
		);

		$element->add_control(
			'vlt_custom_css',
			[
				'label'       => esc_html__( 'Custom CSS', 'toolkit' ),
				'type'        => \Elementor\Controls_Manager::CODE,
				'language'    => 'css',
				'rows'        => 20,
				'render_type' => 'ui',
				'separator'   => 'none',
			],
		);

		$element->end_controls_section();

		// Allow themes to add custom controls
		do_action( 'vlt_toolkit_elementor_custom_css_controls', $element, $args );
	}

	/**
	 * Render Custom CSS attributes
	 *
	 * Custom CSS doesn't need render attributes
	 *
	 * @param object $element elementor element instance
	 */
	public function render_attributes( $element ) {
		// Custom CSS is rendered via CSS file, no attributes needed
	}

	/**
	 * Add custom CSS to post CSS
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css post CSS instance
	 * @param \Elementor\Element_Base        $element  element instance
	 *
	 */
	public function add_post_css( $post_css, $element ) {
		if ( !$post_css || !$post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		if ( !$element || !method_exists( $element, 'get_settings' ) ) {
			return;
		}

		$settings = $element->get_settings();

		if ( empty( $settings['vlt_custom_css'] ) || !is_string( $settings['vlt_custom_css'] ) ) {
			return;
		}

		$css = trim( $settings['vlt_custom_css'] );

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

		$css = preg_replace( '#<script(.*?)>(.*?)</script>#is', '', $css );
		$css = strip_tags( $css );

		if ( '' === $css ) {
			return;
		}

		$element_name = $element->get_name() ?? 'unknown';
		$css          = "/* VLT Custom CSS for {$element_name} */\n" . $css . "\n/* End VLT Custom CSS */";

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
	 *
	 */
	public function add_page_settings_css( $post_css ) {
		if ( !$post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_css->get_post_id() );

		if ( !$document ) {
			return;
		}

		$css = $document->get_settings( 'vlt_custom_css' ) ?? '';
		$css = trim( $css );

		if ( '' === $css ) {
			return;
		}

		$css = str_replace( 'selector', $document->get_css_wrapper_selector(), $css );
		$css = strip_tags( $css );
		$css = preg_replace( '#<script.*?>.*?</script>#is', '', $css );

		if ( '' === $css ) {
			return;
		}

		$css = "/* VLT Document Custom CSS */\n" . $css . "\n/* End VLT Document CSS */";

		$css = \VLT\Toolkit\Toolkit::minify_css( $css );

		$post_css->get_stylesheet()->add_raw_css( $css );
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

		// Register controls for page settings (all document types)
		add_action( 'elementor/documents/register_controls', [ $this, 'register_page_settings_controls' ], 10, 1 );

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

		if ( !in_array( $uid, $tmp ) ) {
			$need_append = true;
			$tmp[]       = $uid;
		}

		self::$hasRunCustomCSS = $tmp;

		return $need_append;
	}
}
