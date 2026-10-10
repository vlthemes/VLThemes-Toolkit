<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes of a saved Grid Layout — the grid, its filter and its pagination are placed separately:
 *
 *     [vlt_grid_builder id="123"]
 *     [vlt_grid_builder_filter id="123"]
 *     [vlt_grid_builder_pagination id="123"]
 *
 * Filter and pagination control the first grid of that layout on the page; when the same layout is
 * placed more than once, instance="2" targets the second one (document order), and so on.
 * Only these numbers are taken from the attributes; everything else comes from the saved config.
 */
class Shortcode {
	const TAG        = 'vlt_grid_builder';
	const FILTER     = 'vlt_grid_builder_filter';
	const PAGINATION = 'vlt_grid_builder_pagination';

	/**
	 * Init
	 */
	public static function init() {
		add_shortcode( self::TAG, [ __CLASS__, 'render' ] );
		add_shortcode( self::FILTER, [ __CLASS__, 'render_filter' ] );
		add_shortcode( self::PAGINATION, [ __CLASS__, 'render_pagination' ] );

		// Grids in the post content get their styles in <head> instead of late in the footer
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_early' ], 20 );
	}

	/**
	 * [vlt_grid_builder]
	 *
	 * @param array|string $atts Attributes
	 *
	 * @return string
	 */
	public static function render( $atts ) {
		$config = self::config( $atts, self::TAG );

		return is_array( $config ) ? Renderer::render( $config ) : $config;
	}

	/**
	 * [vlt_grid_builder_filter]
	 *
	 * @param array|string $atts Attributes
	 *
	 * @return string
	 */
	public static function render_filter( $atts ) {
		$config = self::config( $atts, self::FILTER );

		return is_array( $config ) ? Renderer::render_filter( $config, self::instance( $atts ) ) : $config;
	}

	/**
	 * [vlt_grid_builder_pagination]
	 *
	 * @param array|string $atts Attributes
	 *
	 * @return string
	 */
	public static function render_pagination( $atts ) {
		$config = self::config( $atts, self::PAGINATION );

		return is_array( $config ) ? Renderer::render_pagination( $config, self::instance( $atts ) ) : $config;
	}

	/**
	 * Config of the layout in the attributes, or the "not found" output
	 *
	 * @param array|string $atts Attributes
	 * @param string       $tag  Shortcode
	 *
	 * @return array|string Config, or a notice for editors / empty string
	 */
	private static function config( $atts, $tag ) {
		$atts   = shortcode_atts( [ 'id' => 0, 'instance' => 1 ], $atts, $tag );
		$config = Config::get( $atts['id'] );

		if ( $config ) {
			return $config;
		}

		return current_user_can( 'edit_pages' )
			/* translators: %d: grid layout ID */
			? '<p class="vlt-gb__notice">' . esc_html( sprintf( __( 'Grid layout #%d not found or not published.', 'toolkit' ), absint( $atts['id'] ) ) ) . '</p>'
			: '';
	}

	/**
	 * Target grid instance from the attributes
	 *
	 * @param array|string $atts Attributes
	 *
	 * @return int
	 */
	private static function instance( $atts ) {
		return max( 1, absint( is_array( $atts ) ? ( $atts['instance'] ?? 1 ) : 1 ) );
	}

	/**
	 * Enqueue assets of grids found in the current post's content
	 */
	public static function enqueue_early() {
		$post = is_singular() ? get_post() : null;
		$tags = [ self::TAG, self::FILTER, self::PAGINATION ];

		if ( !$post || !array_filter( $tags, fn( $tag ) => has_shortcode( $post->post_content, $tag ) ) ) {
			return;
		}

		preg_match_all( '/' . get_shortcode_regex( $tags ) . '/', $post->post_content, $matches );

		foreach ( $matches[3] as $atts ) {
			$config = Config::get( shortcode_parse_atts( $atts )['id'] ?? 0 );

			if ( $config ) {
				Renderer::enqueue( $config );
			}
		}
	}
}
