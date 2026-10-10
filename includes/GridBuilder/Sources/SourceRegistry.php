<?php

namespace VLT\Toolkit\GridBuilder\Sources;

use VLT\Toolkit\GridBuilder\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Available data sources
 *
 * Built from the post types that are really registered, so the editor never offers one that doesn't exist.
 * Extend with: add_filter( 'vlt_toolkit_grid_sources', fn( $sources ) => $sources + [ 'my' => new MySource() ] );
 */
class SourceRegistry {
	/**
	 * Sources cache
	 *
	 * @var SourceInterface[]|null
	 */
	private static $sources = null;

	/**
	 * Get all sources
	 *
	 * @return SourceInterface[] [ id => source ]
	 */
	public static function all() {
		if ( null !== self::$sources ) {
			return self::$sources;
		}

		$sources = [];

		// Portfolio first — it's what grids are mostly built from
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		$portfolio = GridBuilder::portfolio();
		uksort( $post_types, fn( $a, $b ) => ( $portfolio === $b ) <=> ( $portfolio === $a ) );

		foreach ( $post_types as $post_type ) {
			if ( 'attachment' !== $post_type->name ) {
				$sources[ $post_type->name ] = new PostTypeSource( $post_type );
			}
		}

		$sources['media'] = new MediaSource();

		$sources = array_filter(
			(array) apply_filters( 'vlt_toolkit_grid_sources', $sources ),
			fn( $source ) => $source instanceof SourceInterface,
		);

		// Post types are registered on init — don't freeze an incomplete list if asked earlier
		if ( did_action( 'wp_loaded' ) ) {
			self::$sources = $sources;
		}

		return $sources;
	}

	/**
	 * Get a source
	 *
	 * @param string $id Source ID
	 *
	 * @return SourceInterface|null
	 */
	public static function get( $id ) {
		return self::all()[ $id ] ?? null;
	}
}
