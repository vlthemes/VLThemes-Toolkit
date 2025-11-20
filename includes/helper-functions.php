<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function vlt_toolkit_has_helper_plugin() {
	return class_exists( 'VLT\Toolkit\Toolkit' );
}

function vlt_toolkit_helper_plugin_instance() {
	if ( vlt_toolkit_has_helper_plugin() ) {
		return \VLT\Toolkit\Toolkit::instance();
	}
	return null;
}

// ========================================
// Breadcrumbs
// ========================================

if ( ! function_exists( 'vlt_toolkit_breadcrumbs' ) ) {
	function vlt_toolkit_breadcrumbs( $args = array() ) {
		return \VLT\Toolkit\Modules\Features\Breadcrumbs::render( $args );
	}
}

// ========================================
// Social Icons Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_get_social_icons' ) ) {
	/**
	 * Get social icons list
	 *
	 * @return array Array of social icons in format 'socicon-{network}' => 'Display Name'
	 */
	function vlt_toolkit_get_social_icons() {
		return \VLT\Toolkit\Modules\Features\SocialIcons::get_social_icons();
	}
}

if ( ! function_exists( 'vlt_toolkit_get_post_share_buttons' ) ) {
	/**
	 * Get post share buttons HTML
	 *
	 * @param int|null $post_id Post ID (uses current post if not provided).
	 * @param string   $style   Button style (e.g. 'style-1', 'style-2').
	 * @return string Share buttons HTML markup.
	 */
	function vlt_toolkit_get_post_share_buttons( $post_id = null, $style = 'style-1' ) {
		return \VLT\Toolkit\Modules\Features\SocialIcons::get_post_share_buttons( $post_id, $style );
	}
}

// ========================================
// Post Views Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_set_post_views' ) ) {
	/**
	 * Set/increment post views
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function vlt_toolkit_set_post_views( $post_id ) {
		\VLT\Toolkit\Modules\Features\PostViews::set_views( $post_id );
	}
}

if ( ! function_exists( 'vlt_toolkit_get_post_views' ) ) {
	/**
	 * Get post views count
	 *
	 * @param int $post_id Post ID.
	 * @return string View count.
	 */
	function vlt_toolkit_get_post_views( $post_id ) {
		return \VLT\Toolkit\Modules\Features\PostViews::get_views( $post_id );
	}
}

if ( ! function_exists( 'vlt_toolkit_reset_post_views' ) ) {
	/**
	 * Reset post views to zero
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	function vlt_toolkit_reset_post_views( $post_id ) {
		\VLT\Toolkit\Modules\Features\PostViews::reset_views( $post_id );
	}
}

// ========================================
// Contact Form 7 Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_get_cf7_forms' ) ) {
	/**
	 * Get list of Contact Form 7 forms
	 *
	 * @return array Array of form IDs and titles.
	 */
	function vlt_toolkit_get_cf7_forms() {
		return \VLT\Toolkit\Modules\Integrations\ContactForm7::get_forms();
	}
}

if ( ! function_exists( 'vlt_toolkit_render_cf7_form' ) ) {
	/**
	 * Render Contact Form 7 by ID
	 *
	 * @param int   $form_id Form ID.
	 * @param array $args    Additional arguments.
	 * @return string Form HTML.
	 */
	function vlt_toolkit_render_cf7_form( $form_id, $args = array() ) {
		return \VLT\Toolkit\Modules\Integrations\ContactForm7::render_form( $form_id, $args );
	}
}

// ========================================
// Visual Portfolio Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_get_vp_portfolios' ) ) {
	/**
	 * Get list of Visual Portfolio layouts
	 *
	 * @return array Array of portfolio IDs and titles.
	 */
	function vlt_toolkit_get_vp_portfolios() {
		return \VLT\Toolkit\Modules\Integrations\VisualPortfolio::get_portfolios();
	}
}

if ( ! function_exists( 'vlt_toolkit_render_vp_portfolio' ) ) {
	/**
	 * Render Visual Portfolio by ID
	 *
	 * @param int   $portfolio_id Portfolio ID.
	 * @param array $args         Additional arguments.
	 * @return string Portfolio HTML.
	 */
	function vlt_toolkit_render_vp_portfolio( $portfolio_id, $args = array() ) {
		return \VLT\Toolkit\Modules\Integrations\VisualPortfolio::render_portfolio( $portfolio_id, $args );
	}
}

// ========================================
// ACF Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_acf_populate_elementor_templates' ) ) {
	/**
	 * Populate ACF field with Elementor templates
	 *
	 * @param array       $field ACF field array.
	 * @param string|null $type  Template type (page, section, widget, etc.).
	 * @return array Modified field with template choices.
	 */
	function vlt_toolkit_acf_populate_elementor_templates( $field, $type = null ) {
		return \VLT\Toolkit\Modules\Integrations\ACF::populate_elementor_templates( $field, $type );
	}
}

if ( ! function_exists( 'vlt_toolkit_acf_populate_vlt_tp' ) ) {
	/**
	 * Populate ACF field with Template Parts
	 *
	 * @param array  $field ACF field array.
	 * @param string $type  Template type to filter by (header, footer, 404, custom, submenu).
	 * @return array Modified field with template part choices.
	 */
	function vlt_toolkit_acf_populate_vlt_tp( $field, $type = null ) {
		return \VLT\Toolkit\Modules\Integrations\ACF::populate_vlt_tp( $field, $type );
	}
}

if ( ! function_exists( 'vlt_toolkit_acf_populate_vp_saved_layouts' ) ) {
	/**
	 * Populate ACF field with Visual Portfolio saved layouts
	 *
	 * @param array $field ACF field array.
	 * @return array Modified field with layout choices.
	 */
	function vlt_toolkit_acf_populate_vp_saved_layouts( $field ) {
		return \VLT\Toolkit\Modules\Integrations\ACF::populate_vp_saved_layouts( $field );
	}
}

if ( ! function_exists( 'vlt_toolkit_acf_populate_social_icons' ) ) {
	/**
	 * Populate ACF field with social icons
	 *
	 * @param array $field ACF field array.
	 * @return array Modified field with icon choices.
	 */
	function vlt_toolkit_acf_populate_social_icons( $field ) {
		return \VLT\Toolkit\Modules\Integrations\ACF::populate_social_icons( $field );
	}
}

// ========================================
// AOS
// ========================================

if ( ! function_exists( 'vlt_toolkit_aos_get_animations' ) ) {
	function vlt_toolkit_aos_get_animations() {
		return \VLT\Toolkit\Modules\Features\AOS::get_animations();
	}
}

if ( ! function_exists( 'vlt_toolkit_aos_render' ) ) {
	function vlt_toolkit_aos_render( $animation, $args = array() ) {
		return \VLT\Toolkit\Modules\Features\AOS::render_attrs( $animation, $args );
	}
}

// ========================================
// WooCommerce Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_is_woocommerce_page' ) ) {
	/**
	 * Check if current page is a WooCommerce page
	 *
	 * Determines if viewing cart, checkout, account pages, or WC endpoints.
	 * More reliable than is_woocommerce() for specific page checks.
	 *
	 * @param string $page     Optional. Specific page type: 'cart', 'checkout', 'account', 'endpoint'.
	 * @param string $endpoint Optional. Specific endpoint slug to check.
	 * @return bool True if on specified WooCommerce page type.
	 */
	function vlt_toolkit_is_woocommerce_page( $page = '', $endpoint = '' ) {
		if ( class_exists( 'VLT\Toolkit\Modules\Integrations\WooCommerce' ) ) {
			return \VLT\Toolkit\Modules\Integrations\WooCommerce::is_woocommerce_page( $page, $endpoint );
		}
		return false;
	}
}

// ========================================
// Template Parts Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_get_vlt_tp_templates' ) ) {
	/**
	 * Get list of VLT Template Parts
	 *
	 * Retrieves published template parts filtered by type (header, footer, above_footer, 404, custom, submenu).
	 * Returns an associative array with template IDs as keys and titles as values.
	 * Commonly used to populate dropdowns in ACF fields or Elementor controls.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $type Optional. Template type to filter by (header, footer, above_footer, 404, custom, submenu).
	 *                          If null, returns all template parts. Default null.
	 * @return array Array of template IDs and titles in format [id => title].
	 *               Returns [0 => 'Select a Template'] if none found.
	 */
	function vlt_toolkit_get_vlt_tp_templates( $type = null ) {
		return \VLT\Toolkit\Modules\Features\TemplateParts::get_templates( $type );
	}
}

// ========================================
// Elementor Functions
// ========================================

if ( ! function_exists( 'vlt_toolkit_get_elementor_templates' ) ) {
	/**
	 * Get list of Elementor templates
	 *
	 * Retrieves Elementor library templates filtered by type.
	 * Useful for populating template selectors in custom widgets or theme options.
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $type Optional. Template type (page, section, widget, container, etc.).
	 *                          If null, returns all template types. Default null.
	 * @return array Array of template IDs and titles in format [id => title].
	 *
	 * @example
	 * // Get all Elementor templates
	 * $all = vlt_toolkit_get_elementor_templates();
	 *
	 * // Get only section templates
	 * $sections = vlt_toolkit_get_elementor_templates('section');
	 */
	function vlt_toolkit_get_elementor_templates( $type = null ) {
		return \VLT\Toolkit\Modules\Integrations\Elementor::get_elementor_templates( $type );
	}
}

if ( ! function_exists( 'vlt_toolkit_render_elementor_template' ) ) {
	/**
	 * Render Elementor template by ID
	 *
	 * Outputs the complete Elementor template content with all styles and scripts.
	 * Use this to programmatically display Elementor templates in your theme.
	 *
	 * @since 1.0.0
	 *
	 * @param int $template_id Template ID to render.
	 * @return string Rendered template HTML markup.
	 *
	 * @example
	 * // Render a specific template
	 * echo vlt_toolkit_render_elementor_template(123);
	 */
	function vlt_toolkit_render_elementor_template( $template_id ) {
		return \VLT\Toolkit\Modules\Integrations\Elementor::render_template( $template_id );
	}
}

if ( ! function_exists( 'vlt_toolkit_is_built_with_elementor' ) ) {
	/**
	 * Check if current post/page is built with Elementor
	 *
	 * Determines whether the current post was created using Elementor page builder.
	 * Useful for conditional logic when displaying different layouts or styles.
	 * Returns false if Elementor is not active or post is not built with it.
	 *
	 * @since 1.0.0
	 *
	 * @global WP_Post $post Current post object.
	 * @return bool True if post is built with Elementor and Elementor is active, false otherwise.
	 *
	 * @example
	 * if (vlt_toolkit_is_built_with_elementor()) {
	 *     // Load Elementor-specific styles
	 * }
	 */
	function vlt_toolkit_is_built_with_elementor() {
		return \VLT\Toolkit\Modules\Integrations\Elementor::is_built_with_elementor();
	}
}
