<?php
/**
 * Global Helper Functions
 *
 * @package VLT Helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ========================================
// Social Icons Functions
// ========================================

if ( ! function_exists( 'vlt_get_social_icons' ) ) {
	/**
	 * Get social icons list
	 *
	 * @return array Array of social icons in format 'socicon-{network}' => 'Display Name'
	 */
	function vlt_get_social_icons() {
		return \VLT\Helper\Modules\Features\SocialIcons::get_social_icons();
	}
}

if ( ! function_exists( 'vlt_get_post_share_buttons' ) ) {
	/**
	 * Get post share buttons HTML
	 *
	 * @param int|null $post_id Post ID (uses current post if not provided).
	 * @param string   $style   Button style (e.g. 'style-1', 'style-2').
	 * @return string Share buttons HTML markup.
	 */
	function vlt_get_post_share_buttons( $post_id = null, $style = 'style-1' ) {
		return \VLT\Helper\Modules\Features\SocialIcons::get_post_share_buttons( $post_id, $style );
	}
}

// ========================================
// Post Views Functions
// ========================================

if ( ! function_exists( 'vlt_set_post_views' ) ) {
	/**
	 * Set/increment post views
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function vlt_set_post_views( $post_id ) {
		\VLT\Helper\Modules\Features\PostViews::set_views( $post_id );
	}
}

if ( ! function_exists( 'vlt_get_post_views' ) ) {
	/**
	 * Get post views count
	 *
	 * @param int $post_id Post ID.
	 * @return string View count.
	 */
	function vlt_get_post_views( $post_id ) {
		return \VLT\Helper\Modules\Features\PostViews::get_views( $post_id );
	}
}

if ( ! function_exists( 'vlt_reset_post_views' ) ) {
	/**
	 * Reset post views to zero
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function vlt_reset_post_views( $post_id ) {
		\VLT\Helper\Modules\Features\PostViews::reset_views( $post_id );
	}
}

// ========================================
// Contact Form 7 Functions
// ========================================

if ( ! function_exists( 'vlt_get_cf7_forms' ) ) {
	/**
	 * Get list of Contact Form 7 forms
	 *
	 * @return array Array of form IDs and titles.
	 */
	function vlt_get_cf7_forms() {
		return \VLT\Helper\Modules\Integrations\ContactForm7::get_forms();
	}
}

if ( ! function_exists( 'vlt_render_cf7_form' ) ) {
	/**
	 * Render Contact Form 7 by ID
	 *
	 * @param int   $form_id Form ID.
	 * @param array $args    Additional arguments.
	 * @return string Form HTML.
	 */
	function vlt_render_cf7_form( $form_id, $args = [] ) {
		return \VLT\Helper\Modules\Integrations\ContactForm7::render_form( $form_id, $args );
	}
}

// ========================================
// Visual Portfolio Functions
// ========================================

if ( ! function_exists( 'vlt_get_vp_portfolios' ) ) {
	/**
	 * Get list of Visual Portfolio layouts
	 *
	 * @return array Array of portfolio IDs and titles.
	 */
	function vlt_get_vp_portfolios() {
		return \VLT\Helper\Modules\Integrations\VisualPortfolio::get_portfolios();
	}
}

if ( ! function_exists( 'vlt_render_vp_portfolio' ) ) {
	/**
	 * Render Visual Portfolio by ID
	 *
	 * @param int   $portfolio_id Portfolio ID.
	 * @param array $args         Additional arguments.
	 * @return string Portfolio HTML.
	 */
	function vlt_render_vp_portfolio( $portfolio_id, $args = [] ) {
		return \VLT\Helper\Modules\Integrations\VisualPortfolio::render_portfolio( $portfolio_id, $args );
	}
}

// ========================================
// ACF Functions
// ========================================

if ( ! function_exists( 'vlt_acf_populate_elementor_templates' ) ) {
	/**
	 * Populate ACF field with Elementor templates
	 *
	 * @param array       $field ACF field array.
	 * @param string|null $type  Template type (page, section, widget, etc.).
	 * @return array Modified field with template choices.
	 */
	function vlt_acf_populate_elementor_templates( $field, $type = null ) {
		return \VLT\Helper\Modules\Integrations\ACF::populate_elementor_templates( $field, $type );
	}
}

if ( ! function_exists( 'vlt_acf_populate_vp_saved_layouts' ) ) {
	/**
	 * Populate ACF field with Visual Portfolio saved layouts
	 *
	 * @param array $field ACF field array.
	 * @return array Modified field with layout choices.
	 */
	function vlt_acf_populate_vp_saved_layouts( $field ) {
		return \VLT\Helper\Modules\Integrations\ACF::populate_vp_saved_layouts( $field );
	}
}

if ( ! function_exists( 'vlt_acf_populate_social_icons' ) ) {
	/**
	 * Populate ACF field with social icons
	 *
	 * @param array $field ACF field array.
	 * @return array Modified field with icon choices.
	 */
	function vlt_acf_populate_social_icons( $field ) {
		return \VLT\Helper\Modules\Integrations\ACF::populate_social_icons( $field );
	}
}

// ========================================
// AOS
// ========================================

if ( ! function_exists( 'vlt_aos_get_animations' ) ) {
	function vlt_aos_get_animations() {
		return \VLT\Helper\Modules\AOS\AOS::get_animations();
	}
}

if ( ! function_exists( 'vlt_aos_render' ) ) {
	function vlt_aos_render( $animation, $args = [] ) {
		return \VLT\Helper\Modules\AOS\AOS::render_attrs( $animation, $args );
	}
}

// vlt_aos_render( 'fade-up', [
// 	'duration' => 1000,
// 	'delay' => 100,
// 	'offset' => 200,
// 	'once' => 'true',
// ] );

// ========================================
// CUSTOM FONTS
// ========================================

// add_filter( 'vlt_helper_register_custom_fonts', function( $fonts ) {

// 	// Simple font registration
// 	$fonts['Mulish'] = [
// 		'label' => 'Mulish',
// 		'variants' => [ '300', '400', '500', '600', '700', '800' ],
// 		'category' => 'theme_fonts',
// 		'category_label' => esc_html__( 'Leedo Fonts', 'textdomain' ),
// 	];

// 	$fonts['Montserrat'] = [
// 		'label' => 'Montserrat',
// 		'variants' => [ '400', '500', '600', '700', '800' ],
// 		'category' => 'theme_fonts',
// 		'category_label' => esc_html__( 'Leedo Fonts', 'textdomain' ),
// 	];

// 	return $fonts;
// });

// ========================================
// Elementor Functions
// ========================================

if ( ! function_exists( 'vlt_get_elementor_templates' ) ) {
	/**
	 * Get list of Elementor templates
	 *
	 * @param string|null $type Template type (page, section, widget, etc.).
	 * @return array Array of template IDs and titles.
	 */
	function vlt_get_elementor_templates( $type = null ) {
		return \VLT\Helper\Modules\Integrations\Elementor::get_elementor_templates( $type );
	}
}

if ( ! function_exists( 'vlt_render_elementor_template' ) ) {
	/**
	 * Render Elementor template
	 *
	 * @param int $template_id Template ID to render.
	 * @return string Rendered template HTML.
	 */
	function vlt_render_elementor_template( $template_id ) {
		return \VLT\Helper\Modules\Integrations\Elementor::render_template( $template_id );
	}
}

/**
 * ========================================
 * Elementor Widgets Registration
 * ========================================
 *
 * The plugin provides hooks for registering Elementor widgets from your theme:
 *
 * 1. 'vlt_helper_elementor_register_widgets' - Action to load widget FILES from theme
 * 2. 'vlt_helper_elementor_widget_classes' - Filter to register widget CLASSES
 *
 * All widget files should be in the theme directory.
 * The plugin only provides the registration mechanism.
 *
 * -------------------------------------------
 * USAGE EXAMPLE
 * -------------------------------------------
 *
 * @code
 * // Step 1: Load widget files from theme
 * add_action( 'vlt_helper_elementor_register_widgets', function() {
 *     $widgets = [
 *         'block_accordion.php',
 *         'block_button.php',
 *         'block_heading.php',
 *         'block_contact_form_7.php',
 *         // ... add all widget files you need
 *     ];
 *
 *     foreach ( $widgets as $widget_file ) {
 *         $file_path = get_template_directory() . '/elementor/widgets/' . $widget_file;
 *         if ( file_exists( $file_path ) ) {
 *             require_once $file_path;
 *         }
 *     }
 * } );
 *
 * // Step 2: Register widget classes
 * add_filter( 'vlt_helper_elementor_widget_classes', function( $classes ) {
 *     return [
 *         '\Elementor\Widget_VLThemes_Accordion',
 *         '\Elementor\Widget_VLThemes_Button',
 *         '\Elementor\Widget_VLThemes_Heading',
 *         '\Elementor\Widget_VLThemes_Contact_Form_7',
 *         // ... add all widget classes you need
 *     ];
 * } );
 * @endcode
 *
 * Note:
 * - Widget files should be placed in theme's 'elementor/widgets/' directory
 * - All examples should be placed in your theme's functions.php file
 */
