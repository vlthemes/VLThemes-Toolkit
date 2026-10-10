<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Saved grid layouts (vlt_grid) and their meta
 *
 * Settings live in two meta keys edited by the block editor UI. The portfolio post type is its own module (Portfolio).
 */
class PostTypes {
	const LAYOUT = 'vlt_grid';

	/**
	 * Layout config meta: JSON, see Config
	 */
	const META_CONFIG = '_vlt_grid_config';

	/**
	 * Layout custom CSS meta — separate key so it can require the edit_css capability on its own
	 */
	const META_CSS = '_vlt_grid_css';

	/**
	 * Custom CSS a new layout starts with ("selector" = the grid container, see CssScoper)
	 */
	const DEFAULT_CSS = "selector {\n\n}\n";

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'init', [ __CLASS__, 'register' ] );
	}

	/**
	 * Register the layout post type and meta
	 */
	public static function register() {
		register_post_type( self::LAYOUT, [
			'labels'          => [
				'name'               => esc_html__( 'Grid Layouts', 'toolkit' ),
				'singular_name'      => esc_html__( 'Grid Layout', 'toolkit' ),
				'menu_name'          => esc_html__( 'Grid Layouts', 'toolkit' ),
				'all_items'          => esc_html__( 'Grid Layouts', 'toolkit' ),
				'add_new'            => esc_html__( 'Add New', 'toolkit' ),
				'add_new_item'       => esc_html__( 'Add New Grid Layout', 'toolkit' ),
				'edit_item'          => esc_html__( 'Edit Grid Layout', 'toolkit' ),
				'new_item'           => esc_html__( 'New Grid Layout', 'toolkit' ),
				'search_items'       => esc_html__( 'Search Grid Layouts', 'toolkit' ),
				'not_found'          => esc_html__( 'No grid layouts found', 'toolkit' ),
				'not_found_in_trash' => esc_html__( 'No grid layouts found in trash', 'toolkit' ),
			],
			'public'          => false,
			'show_ui'         => true,
			// Under Portfolio when it's on, otherwise its own menu
			'show_in_menu'    => GridBuilder::portfolio() ? \VLT\Toolkit\Portfolio\Portfolio::menu() : true,
			'menu_icon'       => 'dashicons-grid-view',
			'menu_position'   => 21,
			'show_in_rest'    => true,
			'capability_type' => 'page',
			'map_meta_cap'    => true,
			'supports'        => [ 'title', 'editor', 'custom-fields' ],
			// The whole settings UI is one locked block, see assets/src/editor
			'template'        => [ [ 'vlt-toolkit/grid-settings' ] ],
			'template_lock'   => 'all',
		] );

		register_post_meta( self::LAYOUT, self::META_CONFIG, [
			'type'              => 'string',
			'single'            => true,
			'default'           => '',
			'show_in_rest'      => true,
			'sanitize_callback' => [ Config::class, 'sanitize_meta' ],
			'auth_callback'     => [ __CLASS__, 'can_edit_layout' ],
		] );

		register_post_meta( self::LAYOUT, self::META_CSS, [
			'type'              => 'string',
			'single'            => true,
			'default'           => self::DEFAULT_CSS,
			'show_in_rest'      => true,
			'sanitize_callback' => [ CssScoper::class, 'sanitize' ],
			'auth_callback'     => [ __CLASS__, 'can_edit_css' ],
		] );

	}

	/**
	 * Meta auth: editing the layout config
	 *
	 * @param bool   $allowed  Allowed
	 * @param string $meta_key Meta key
	 * @param int    $post_id  Post ID
	 *
	 * @return bool
	 */
	public static function can_edit_layout( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Meta auth: custom CSS — same rule as WordPress' Additional CSS (edit_css → unfiltered_html)
	 *
	 * @param bool   $allowed  Allowed
	 * @param string $meta_key Meta key
	 * @param int    $post_id  Post ID
	 *
	 * @return bool
	 */
	public static function can_edit_css( $allowed, $meta_key, $post_id ) {
		return current_user_can( 'edit_post', $post_id ) && current_user_can( 'edit_css' );
	}
}
