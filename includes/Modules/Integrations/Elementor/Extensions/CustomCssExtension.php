<?php

namespace VLT\Helper\Modules\Integrations\Elementor\Extensions;

use VLT\Helper\Modules\Integrations\Elementor\BaseExtension;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom CSS Extension
 *
 * Allows adding custom CSS to Elementor elements
 */
class CustomCssExtension extends BaseExtension {

	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'custom_css';

	/**
	 * Track which widgets have already had CSS appended
	 *
	 * @var array
	 */
	public static $hasRunCustomCSS = [];

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

		// Register CSS hooks
		add_action( 'elementor/element/parse_css', [ $this, 'add_post_css' ], 10, 2 );
		add_action( 'elementor/css-file/post/parse', [ $this, 'add_page_settings_css' ] );

		// Enqueue editor scripts
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'enqueue_scripts_editor' ] );
	}

	/**
	 * Enqueue editor scripts for Custom CSS
	 */
	public function enqueue_scripts_editor() {
		wp_enqueue_script(
			'vlt-custom-css',
			$this->assets_url . 'extensions/elementor/elementor-custom-css.js',
			[],
			VLT_HELPER_VERSION,
			true
		);
	}

	/**
	 * Register Custom CSS controls
	 *
	 * @param object $element Elementor element.
	 * @param array  $args    Element arguments.
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'vlt_section_custom_css', [
				'label' => esc_html__( 'VLT Custom CSS', 'vlt-helper' ),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			]
		);

		$element->add_control(
			'vlt_custom_css_description', [
				'type'            => \Elementor\Controls_Manager::RAW_HTML,
				'raw'             => __( 'Use "selector" to target wrapper element. Examples:<br>selector {color: red;} // For main element<br>selector .child-element {margin: 10px;} // For child element<br>.my-class {text-align: center;} // Or use any custom selector', 'vlt-helper' ),
				'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
			]
		);

		$element->add_control(
			'vlt_custom_css', [
				'label'       => esc_html__( 'Custom CSS', 'vlt-helper' ),
				'type'        => \Elementor\Controls_Manager::CODE,
				'language'    => 'css',
				'rows'        => 20,
				'render_type' => 'ui',
				'separator'   => 'none',
			]
		);

		$element->end_controls_section();

		// Allow themes to add custom controls
		do_action( 'vlt_helper_elementor_custom_css_controls', $element, $args );
	}

	/**
	 * Render Custom CSS attributes
	 *
	 * Custom CSS doesn't need render attributes
	 *
	 * @param object $element Elementor element instance.
	 */
	public function render_attributes( $element ) {
		// Custom CSS is rendered via CSS file, no attributes needed
	}

	/**
	 * Check if custom CSS needs to be appended for widget
	 *
	 * @param string $uid Unique widget ID.
	 * @return bool
	 */
	private function needAppendCustomCSSforWidget( $uid ) {
		$need_append = false;
		$tmp = self::$hasRunCustomCSS;

		if ( ! in_array( $uid, $tmp ) ) {
			$need_append = true;
			$tmp[] = $uid;
		}

		self::$hasRunCustomCSS = $tmp;
		return $need_append;
	}

	/**
	 * Add custom CSS to post CSS
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css Post CSS instance.
	 * @param \Elementor\Element_Base        $element  Element instance.
	 * @return void
	 */
	public function add_post_css( $post_css, $element ) {
		if ( ! $post_css || ! $post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		if ( ! $element || ! method_exists( $element, 'get_settings' ) ) {
			return;
		}

		$settings = $element->get_settings();
		if ( empty( $settings['vlt_custom_css'] ) || ! is_string( $settings['vlt_custom_css'] ) ) {
			return;
		}

		$css = trim( $settings['vlt_custom_css'] );
		if ( $css === '' ) {
			return;
		}

		$unique_uid = $element->get_name() . $element->get_id();

		if ( ! $this->needAppendCustomCSSforWidget( $unique_uid ) ) {
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

		if ( $css === '' ) {
			return;
		}

		$element_name = $element->get_name() ?? 'unknown';
		$css = "/* VLT Custom CSS for {$element_name} */\n" . $css . "\n/* End VLT Custom CSS */";

		$css = \VLT\Helper\Helper::minify_css( $css );

		$stylesheet = $post_css->get_stylesheet();
		if ( ! $stylesheet || ! method_exists( $stylesheet, 'add_raw_css' ) ) {
			return;
		}

		$stylesheet->add_raw_css( $css );
	}

	/**
	 * Add page settings custom CSS
	 *
	 * @param \Elementor\Core\Files\CSS\Post $post_css Post CSS instance.
	 * @return void
	 */
	public function add_page_settings_css( $post_css ) {
		if ( ! $post_css instanceof \Elementor\Core\Files\CSS\Post ) {
			return;
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_css->get_post_id() );
		if ( ! $document ) {
			return;
		}

		$css = $document->get_settings( 'vlt_custom_css' ) ?? '';
		$css = trim( $css );

		if ( $css === '' ) {
			return;
		}

		$css = str_replace( 'selector', $document->get_css_wrapper_selector(), $css );
		$css = strip_tags( $css );
		$css = preg_replace( '#<script.*?>.*?</script>#is', '', $css );

		if ( $css === '' ) {
			return;
		}

		$css = "/* VLT Document Custom CSS */\n" . $css . "\n/* End VLT Document CSS */";

		$css = \VLT\Helper\Helper::minify_css( $css );

		$post_css->get_stylesheet()->add_raw_css( $css );
	}
}
