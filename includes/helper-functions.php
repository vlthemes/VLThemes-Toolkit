<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

function vlt_toolkit_has() {
	return class_exists( 'VLT\Toolkit\Toolkit' );
}

function vlt_toolkit_helper_plugin_instance() {
	if ( vlt_toolkit_has() ) {
		return VLT\Toolkit\Toolkit::instance();
	}

	return null;
}

// ========================================
// Breadcrumbs
// ========================================

if ( !function_exists( 'vlt_toolkit_breadcrumbs' ) ) {
	function vlt_toolkit_breadcrumbs( $args = [] ) {
		return VLT\Toolkit\Modules\Features\Breadcrumbs::render( $args );
	}
}

// ========================================
// Social Icons Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_social_icons' ) ) {
	/**
	 * Get social icons list
	 */
	function vlt_toolkit_get_social_icons() {
		return VLT\Toolkit\Modules\Features\SocialIcons::get_social_icons();
	}
}

if ( !function_exists( 'vlt_toolkit_get_sharable_icons' ) ) {
	/**
	 * Get sharable social icons list
	 */
	function vlt_toolkit_get_sharable_icons() {
		return VLT\Toolkit\Modules\Features\SocialIcons::SHAREABLE_NETWORKS;
	}
}

if ( !function_exists( 'vlt_toolkit_get_post_share_data' ) ) {
	/**
	 * Get post share data
	 */
	function vlt_toolkit_get_post_share_data() {
		return VLT\Toolkit\Modules\Features\SocialIcons::get_post_share_data();
	}
}

if ( !function_exists( 'vlt_toolkit_build_sharer_data_attrs' ) ) {
	/**
	 * Build sharer data attrs
	 */
	function vlt_toolkit_build_sharer_data_attrs( $slug, $attrs ) {
		return VLT\Toolkit\Modules\Features\SocialIcons::build_sharer_data_attrs( $slug, $attrs );
	}
}

if ( !function_exists( 'vlt_toolkit_get_post_share_buttons' ) ) {
	/**
	 * Get post share buttons HTML
	 */
	function vlt_toolkit_get_post_share_buttons( $post_id = null, $style = 'style-1' ) {
		return VLT\Toolkit\Modules\Features\SocialIcons::get_post_share_buttons( $post_id, $style );
	}
}

// ========================================
// Post Views Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_set_post_views' ) ) {
	/**
	 * Set/increment post views
	 */
	function vlt_toolkit_set_post_views( $post_id ) {
		VLT\Toolkit\Modules\Features\PostViews::set_views( $post_id );
	}
}

if ( !function_exists( 'vlt_toolkit_get_post_views' ) ) {
	/**
	 * Get post views count
	 */
	function vlt_toolkit_get_post_views( $post_id ) {
		return VLT\Toolkit\Modules\Features\PostViews::get_views( $post_id );
	}
}

if ( !function_exists( 'vlt_toolkit_reset_post_views' ) ) {
	/**
	 * Reset post views to zero
	 */
	function vlt_toolkit_reset_post_views( $post_id ) {
		VLT\Toolkit\Modules\Features\PostViews::reset_views( $post_id );
	}
}

// ========================================
// Contact Form 7 Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_cf7_forms' ) ) {
	/**
	 * Get list of Contact Form 7 forms
	 */
	function vlt_toolkit_get_cf7_forms() {
		return VLT\Toolkit\Modules\Integrations\ContactForm7::get_forms();
	}
}

if ( !function_exists( 'vlt_toolkit_render_cf7_form' ) ) {
	/**
	 * Render Contact Form 7 by ID
	 */
	function vlt_toolkit_render_cf7_form( $form_id, $args = [] ) {
		return VLT\Toolkit\Modules\Integrations\ContactForm7::render_form( $form_id, $args );
	}
}

// ========================================
// Visual Portfolio
// ========================================

if ( !function_exists( 'vlt_toolkit_render_vp_portfolio' ) ) {
	/**
	 * Render Visual Portfolio by ID
	 */
	function vlt_toolkit_render_vp_portfolio( $portfolio_id, $args = [] ) {
		return VLT\Toolkit\Modules\Integrations\VisualPortfolio::render_portfolio( $portfolio_id, $args );
	}
}

// ========================================
// AOS
// ========================================

if ( !function_exists( 'vlt_toolkit_aos_get_animations' ) ) {
	function vlt_toolkit_aos_get_animations() {
		return VLT\Toolkit\Modules\Features\AOS::get_animations();
	}
}

if ( !function_exists( 'vlt_toolkit_aos_render' ) ) {
	function vlt_toolkit_aos_render( $animation, $args = [] ) {
		return VLT\Toolkit\Modules\Features\AOS::render_attrs( $animation, $args );
	}
}

// ========================================
// WooCommerce Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_is_woocommerce_page' ) ) {
	/**
	 * Check if current page is a WooCommerce page
	 */
	function vlt_toolkit_is_woocommerce_page( $page = '', $endpoint = '' ) {
		if ( class_exists( 'VLT\Toolkit\Modules\Integrations\WooCommerce' ) ) {
			return VLT\Toolkit\Modules\Integrations\WooCommerce::is_woocommerce_page( $page, $endpoint );
		}

		return false;
	}
}

// ========================================
// Elementor Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_render_elementor_template' ) ) {
	/**
	 * Render Elementor template by ID
	 */
	function vlt_toolkit_render_elementor_template( $template_id ) {
		return VLT\Toolkit\Modules\Integrations\Elementor::render_template( $template_id );
	}
}

if ( !function_exists( 'vlt_toolkit_is_built_with_elementor' ) ) {
	/**
	 * Check if current post/page is built with Elementor
	 */
	function vlt_toolkit_is_built_with_elementor() {
		return VLT\Toolkit\Modules\Integrations\Elementor::is_built_with_elementor();
	}
}

// ========================================
// Populate Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_populate_post_name' ) ) {
	/**
	 * Get post names by post type
	 */
	function vlt_toolkit_populate_post_name( $post_type = 'post' ) {
		return VLT\Toolkit\Modules\Integrations\Elementor\Helpers::populate_post_name( $post_type );
	}
}

if ( !function_exists( 'vlt_toolkit_populate_taxonomies' ) ) {
	/**
	 * Get taxonomies by taxonomy name
	 */
	function vlt_toolkit_populate_taxonomies( $taxonomy = 'category' ) {
		return VLT\Toolkit\Modules\Integrations\Elementor\Helpers::populate_taxonomies( $taxonomy );
	}
}

if ( !function_exists( 'vlt_toolkit_populate_available_menus' ) ) {
	/**
	 * Get available menus
	 */
	function vlt_toolkit_populate_available_menus() {
		return VLT\Toolkit\Modules\Integrations\Elementor\Helpers::populate_available_menus();
	}
}

if ( !function_exists( 'vlt_toolkit_populate_elementor_templates' ) ) {
	/**
	 * Get list of Elementor templates
	 */
	function vlt_toolkit_populate_elementor_templates( $type = null ) {
		return VLT\Toolkit\Modules\Integrations\Elementor\Helpers::populate_elementor_templates( $type );
	}
}

if ( !function_exists( 'vlt_toolkit_populate_elementor_template_types' ) ) {
	/**
	 * Get list of Elementor template types
	 */
	function vlt_toolkit_populate_elementor_template_types() {
		return VLT\Toolkit\Modules\Integrations\Elementor\Helpers::populate_elementor_template_types();
	}
}

if ( !function_exists( 'vlt_toolkit_populate_vp_portfolios' ) ) {
	/**
	 * Get list of Visual Portfolio layouts
	 */
	function vlt_toolkit_populate_vp_portfolios() {
		return VLT\Toolkit\Modules\Integrations\VisualPortfolio::populate_portfolios();
	}
}

// ========================================
// Dynamic Content Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_parse_dynamic_content' ) ) {
	/**
	 * Parse dynamic content variables in text
	 *
	 * @param string $text Text containing dynamic variables
	 *
	 * @return string Parsed text
	 */
	function vlt_toolkit_parse_dynamic_content( $text ) {
		return VLT\Toolkit\Modules\Features\DynamicContent::parse( $text );
	}
}

// ========================================
// Image Helper Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_attachment_image' ) ) {
	/**
	 * Get attachment image HTML
	 *
	 * @param int   $image_id Attachment ID
	 * @param array $args     Arguments for image output
	 *
	 * @return string|false Image HTML or false on failure
	 */
	function vlt_toolkit_get_attachment_image( $image_id, $args = [] ) {
		return VLT\Toolkit\Modules\Helpers\ImageHelper::get_attachment_image( $image_id, $args );
	}
}

if ( !function_exists( 'vlt_toolkit_get_attachment_image_src' ) ) {
	/**
	 * Get attachment image source URL
	 *
	 * @param int   $image_id Attachment ID
	 * @param array $args     Arguments for image source
	 *
	 * @return string|false Image URL or false on failure
	 */
	function vlt_toolkit_get_attachment_image_src( $image_id, $args = [] ) {
		return VLT\Toolkit\Modules\Helpers\ImageHelper::get_attachment_image_src( $image_id, $args );
	}
}

if ( !function_exists( 'vlt_toolkit_get_placeholder_image' ) ) {
	/**
	 * Get placeholder image HTML
	 *
	 * @param string $class CSS class for the image
	 * @param string $alt   Alt text for the image
	 *
	 * @return string Placeholder image HTML
	 */
	function vlt_toolkit_get_placeholder_image( $class = '', $alt = '' ) {
		return VLT\Toolkit\Modules\Helpers\ImageHelper::get_placeholder_image( $class, $alt );
	}
}

if ( !function_exists( 'vlt_toolkit_get_placeholder_image_src' ) ) {
	/**
	 * Get placeholder image source URL
	 *
	 * @return string Placeholder image URL
	 */
	function vlt_toolkit_get_placeholder_image_src() {
		return VLT\Toolkit\Modules\Helpers\ImageHelper::get_placeholder_image_src();
	}
}

// ========================================
// Content Helper Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_trimmed_content' ) ) {
	/**
	 * Get trimmed content from post excerpt
	 *
	 * @param int|null $post_id   Post ID (null for current post)
	 * @param int      $max_words Maximum number of words
	 *
	 * @return string Trimmed content
	 */
	function vlt_toolkit_get_trimmed_content( $post_id = null, $max_words = 18 ) {
		return VLT\Toolkit\Modules\Helpers\ContentHelper::get_trimmed_content( $post_id, $max_words );
	}
}

// ========================================
// Media Helper Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_parse_video_id' ) ) {
	/**
	 * Parse video ID from URL
	 *
	 * @param string $url Video URL
	 *
	 * @return array Array with vendor and video ID [vendor, id]
	 */
	function vlt_toolkit_parse_video_id( $url ) {
		return VLT\Toolkit\Modules\Helpers\MediaHelper::parse_video_id( $url );
	}
}

// ========================================
// Template Parts Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_template_by_type' ) ) {
	/**
	 * Get template parts by type
	 *
	 * @param string|null $type Template type (header, footer, above_footer, 404, submenu, custom) or null for all
	 *
	 * @return array Array of template posts [ID => title]
	 */
	function vlt_toolkit_get_template_by_type( $type = null ) {
		return VLT\Toolkit\Modules\Features\TemplateParts::get_templates_by_type( $type );
	}
}
