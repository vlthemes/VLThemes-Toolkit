<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid Builder
 *
 * Wires the module parts together. Rendering flow (shortcode and AJAX share it):
 * Config::get() → QueryBuilder::run() → Renderer (layout + skin) → isolated container → assets/js/grid.js
 */
class GridBuilder {
	/**
	 * Instance
	 *
	 * @var GridBuilder|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return GridBuilder
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Theme activated (license) — entrance animations and inserts need it
	 *
	 * Without the toolkit's activation module everything stays available. Developers get no limits either:
	 * define( 'VLT_TOOLKIT_DEV', true ) in wp-config.php, or a "local" / "development" WP_ENVIRONMENT_TYPE.
	 *
	 * @return bool
	 */
	public static function is_activated() {
		$developer = ( defined( 'VLT_TOOLKIT_DEV' ) && VLT_TOOLKIT_DEV ) || in_array( wp_get_environment_type(), [ 'local', 'development' ], true );
		$activated = $developer || !function_exists( 'vlt_is_theme_activated' ) || vlt_is_theme_activated();

		return (bool) apply_filters( 'vlt_toolkit_grid_is_activated', $activated );
	}

	/**
	 * Columns and gaps actually used per device
	 *
	 * Desktop is always the saved value. Tablet / mobile need an activated theme; without it columns are the
	 * defaults (2 / 1, never more than desktop) and gaps are the desktop ones. Saved values are kept and
	 * apply once the theme is activated.
	 *
	 * @param array $config Layout config
	 *
	 * @return array [ 'columns' | 'gap_x' | 'gap_y' => [ 'desktop', 'tablet', 'mobile' ] ]
	 */
	public static function responsive( array $config ) {
		$responsive = [
			'columns' => $config['responsive']['columns'],
			'gap_x'   => $config['responsive']['gap_x'],
			'gap_y'   => $config['responsive']['gap_y'],
		];

		if ( !self::is_activated() ) {
			$responsive['columns']['tablet'] = min( $responsive['columns']['desktop'], 2 );
			$responsive['columns']['mobile'] = 1;

			foreach ( [ 'gap_x', 'gap_y' ] as $gap ) {
				$responsive[ $gap ]['tablet'] = $responsive[ $gap ]['desktop'];
				$responsive[ $gap ]['mobile'] = $responsive[ $gap ]['desktop'];
			}
		}

		return $responsive;
	}

	/**
	 * Columns actually used per device (see responsive())
	 *
	 * @param array $config Layout config
	 *
	 * @return array [ 'desktop' => int, 'tablet' => int, 'mobile' => int ]
	 */
	public static function columns( array $config ) {
		return self::responsive( $config )['columns'];
	}

	/**
	 * Portfolio post type when the Portfolio module is on — the default source and the menu home
	 *
	 * @return string Post type, or '' without it
	 */
	public static function portfolio() {
		return class_exists( '\VLT\Toolkit\Portfolio\Portfolio' ) && \VLT\Toolkit\Portfolio\Portfolio::enabled() ? \VLT\Toolkit\Portfolio\Portfolio::POST_TYPE : '';
	}

	/**
	 * Admin menu the Grid Builder pages live in: Portfolio, or the Grid Layouts menu without it
	 *
	 * @return string
	 */
	public static function menu_parent() {
		return 'edit.php?post_type=' . ( self::portfolio() ?: PostTypes::LAYOUT );
	}

	/**
	 * Theme activation page
	 *
	 * @return string
	 */
	public static function activate_url() {
		return admin_url( 'admin.php?page=vlt-dashboard-activate-theme' );
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		PostTypes::init();
		Renderer::init();
		Shortcode::init();
		Ajax::init();
		Admin::init();
		Settings::init();
		Preview::init();
	}
}
