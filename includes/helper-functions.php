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
// Sprite Icons Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_get_icon' ) ) {
	/**
	 * Get icon markup referencing the SVG sprite
	 *
	 * @param string $id   Icon id without the "i-" prefix
	 * @param array  $args Arguments: class, label
	 *
	 * @return string
	 */
	function vlt_toolkit_get_icon( $id, $args = [] ) {
		return VLT\Toolkit\Modules\Features\SpriteIcons::get_icon( $id, $args );
	}
}

if ( !function_exists( 'vlt_toolkit_get_icons' ) ) {
	/**
	 * Get all sprite icons as standalone SVG markup [ id => svg ]
	 */
	function vlt_toolkit_get_icons() {
		return VLT\Toolkit\Modules\Features\SpriteIcons::get_icons();
	}
}

// ========================================
// Post Views Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_set_post_views' ) ) {
	/**
	 * Set/increment post views
	 *
	 * @param int|null $post_id Post ID or null for current post
	 */
	function vlt_toolkit_set_post_views( $post_id = null ) {
		VLT\Toolkit\Modules\Features\PostViews::set_views( $post_id );
	}
}

if ( !function_exists( 'vlt_toolkit_get_post_views' ) ) {
	/**
	 * Get post views count
	 *
	 * @param int|null $post_id Post ID or null for current post
	 * @return int Views count
	 */
	function vlt_toolkit_get_post_views( $post_id = null ) {
		return VLT\Toolkit\Modules\Features\PostViews::get_views( $post_id );
	}
}

if ( !function_exists( 'vlt_toolkit_get_post_views_label' ) ) {
	/**
	 * Get "1,234 views" label
	 *
	 * @param int|null $post_id Post ID or null for current post
	 * @return string
	 */
	function vlt_toolkit_get_post_views_label( $post_id = null ) {
		return VLT\Toolkit\Modules\Features\PostViews::get_views_label( $post_id );
	}
}

if ( !function_exists( 'vlt_toolkit_reset_post_views' ) ) {
	/**
	 * Reset post views to zero
	 *
	 * @param int|null $post_id Post ID or null for current post
	 */
	function vlt_toolkit_reset_post_views( $post_id = null ) {
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
// Populate Functions
// ========================================

if ( !function_exists( 'vlt_toolkit_populate_template_by_type' ) ) {
	/**
	 * Get template parts by type
	 *
	 * @param string|null $type Template type (header, footer, above_footer, 404, submenu, custom) or null for all
	 *
	 * @return array Array of template posts [ID => title]
	 */
	function vlt_toolkit_populate_template_by_type( $type = null ) {
		return VLT\Toolkit\TemplateParts\TemplateParts::get_templates_by_type( $type );
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

if ( !function_exists( 'vlt_toolkit_reading_time' ) ) {
	/**
	 * Get estimated reading time for post content
	 *
	 * @param int|null $post_id        Post ID (null for current post)
	 * @param int      $words_per_minute Average reading speed (default: 200 words per minute)
	 * @param string   $format         Output format: 'string' (e.g., "5 min read") or 'number' (just the number)
	 *
	 * @return string|int Reading time
	 */
	function vlt_toolkit_reading_time( $post_id = null, $words_per_minute = 200, $format = 'string' ) {
		return VLT\Toolkit\Modules\Helpers\ContentHelper::get_reading_time( $post_id, $words_per_minute, $format );
	}
}

