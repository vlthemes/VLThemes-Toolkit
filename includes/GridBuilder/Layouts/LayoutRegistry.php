<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

use VLT\Toolkit\GridBuilder\SkinManager;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available layout types
 *
 * The theme adds layout types as folders (layouts/{Name}/layout.php, see ThemeLayout); built-in IDs can't be taken.
 * From code: add_filter( 'vlt_toolkit_grid_layouts', fn( $layouts ) => $layouts + [ 'my' => new MyLayout() ] );
 */
class LayoutRegistry {
	/**
	 * Layouts cache
	 *
	 * @var LayoutInterface[]|null
	 */
	private static $layouts = null;

	/**
	 * Get all layouts
	 *
	 * @return LayoutInterface[] [ id => layout ]
	 */
	public static function all() {
		if ( null !== self::$layouts ) {
			return self::$layouts;
		}

		$layouts = [
			'grid'      => new GridLayout(),
			'tiles'     => new TilesLayout(),
			'masonry'   => new MasonryLayout(),
			'justified' => new JustifiedLayout(),
		];

		foreach ( SkinManager::all( 'layouts' ) as $id => $entry ) {
			$layouts[ $id ] ??= new ThemeLayout( $entry );
		}

		$layouts = array_filter( (array) apply_filters( 'vlt_toolkit_grid_layouts', $layouts ), fn( $layout ) => $layout instanceof LayoutInterface );

		// Theme folders are only known once the theme is set up
		if ( did_action( 'after_setup_theme' ) ) {
			self::$layouts = $layouts;
		}

		return $layouts;
	}

	/**
	 * Get a layout
	 *
	 * @param string $id Layout ID
	 *
	 * @return LayoutInterface|null
	 */
	public static function get( $id ) {
		return self::all()[ $id ] ?? null;
	}
}
