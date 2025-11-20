<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visual Portfolio Integration Module
 *
 * Provides hooks for Visual Portfolio plugin customization
 * Handles items styles, tiles, and FontAwesome disabling
 */
class VisualPortfolio extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'visual_portfolio';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register() {
		// Disable FontAwesome 5 from Visual Portfolio
		add_filter( 'vpf_enqueue_plugin_font_awesome', '__return_false' );

		// Hooks for theme extension
		add_filter( 'vpf_extend_items_styles', [ $this, 'extend_items_styles' ], 10, 1 );
		add_filter( 'vpf_extend_tiles', [ $this, 'extend_tiles' ], 10, 1 );
	}

	/**
	 * Extend items styles
	 *
	 * Allows theme to add custom item styles
	 *
	 * @param array $items_styles current items styles
	 *
	 * @return array modified items styles
	 */
	public function extend_items_styles( $items_styles ) {
		return apply_filters( 'vlt_toolkit_vp_items_styles', $items_styles );
	}

	/**
	 * Extend tiles
	 *
	 * Allows theme to add custom tiles
	 *
	 * @param array $tiles current tiles
	 *
	 * @return array modified tiles
	 */
	public function extend_tiles( $tiles ) {
		return apply_filters( 'vlt_toolkit_vp_tiles', $tiles );
	}

	/**
	 * Populate Visual Portfolio layouts list
	 *
	 * Returns array of available Visual Portfolio layouts
	 *
	 * @return array array of portfolio IDs and titles
	 */
	public static function populate_portfolios() {
		$options = [];

		if ( !class_exists( 'Visual_Portfolio' ) ) {
			return $options;
		}

		$portfolios = get_posts(
			[
				'post_type'   => 'vp_lists',
				'numberposts' => -1,
				'post_status' => 'publish',
			],
		);

		if ( !empty( $portfolios ) && !is_wp_error( $portfolios ) ) {
			foreach ( $portfolios as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		return $options;
	}

	/**
	 * Render Visual Portfolio by ID
	 *
	 * @param int   $portfolio_id portfolio ID
	 * @param array $args         additional arguments
	 *
	 * @return string portfolio HTML
	 */
	public static function render_portfolio( $portfolio_id, $args = [] ) {
		if ( !class_exists( 'Visual_Portfolio' ) || !$portfolio_id ) {
			return '';
		}

		$portfolio_id = absint( $portfolio_id );

		// Build shortcode attributes
		$atts = [ 'id="' . $portfolio_id . '"' ];

		if ( !empty( $args ) ) {
			foreach ( $args as $key => $value ) {
				$atts[] = sanitize_key( $key ) . '="' . esc_attr( $value ) . '"';
			}
		}

		return do_shortcode( '[visual_portfolio ' . implode( ' ', $atts ) . ']' );
	}

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register() {
		return class_exists( 'Visual_Portfolio' );
	}
}
