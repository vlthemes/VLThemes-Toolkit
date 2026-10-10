<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiles pattern parser
 *
 * Grammar (whitespace is ignored):
 *
 *     pattern := columns "|" tile ( ";" tile )* [ ";" ]
 *     columns := integer 1–12
 *     tile    := width "," height
 *     width   := integer 1–columns           columns the tile spans
 *     height  := number 0.01–4, up to 2 decimals    height in column widths (1 = square cell)
 *
 * Example: "3|2,1;1,.5;1,.5" — 3 columns; a 2×1 tile, then two half-height tiles stacked beside it.
 * Tiles repeat for the following items. A height of h spans h × 100 grid rows (ROWS_PER_UNIT), so any
 * combination of heights lines up on the same row grid.
 *
 * Visual Portfolio patterns measure the height in the tile's own width instead: theirs "2,0.5" is our "2,1".
 */
class TilesParser {
	const MAX_COLUMNS   = 12;
	const MAX_HEIGHT    = 4;
	const ROWS_PER_UNIT = 100;

	/**
	 * Parse a pattern
	 *
	 * @param string $pattern Pattern
	 *
	 * @return array|\WP_Error [ 'columns' => int, 'tiles' => [ [ 'width' => int, 'height' => float, 'rows' => int ], … ] ]
	 */
	public static function parse( $pattern ) {
		$pattern = preg_replace( '/\s+/', '', (string) $pattern );
		$parts   = explode( '|', $pattern );

		if ( 2 !== count( $parts ) ) {
			return new \WP_Error( 'tiles_syntax', __( 'Use the format COLUMNS|WIDTH,HEIGHT;WIDTH,HEIGHT, e.g. 3|2,1;1,.5;1,.5', 'toolkit' ) );
		}

		[ $columns, $list ] = $parts;

		if ( !ctype_digit( $columns ) || (int) $columns < 1 || (int) $columns > self::MAX_COLUMNS ) {
			/* translators: %d: maximum number of columns */
			return new \WP_Error( 'tiles_columns', sprintf( __( 'Columns must be a whole number from 1 to %d.', 'toolkit' ), self::MAX_COLUMNS ) );
		}

		$columns = (int) $columns;
		$tiles   = [];

		foreach ( explode( ';', rtrim( $list, ';' ) ) as $i => $tile ) {
			$number = $i + 1;
			$size   = explode( ',', $tile );

			if ( 2 !== count( $size ) ) {
				/* translators: %d: tile number */
				return new \WP_Error( 'tiles_tile', sprintf( __( 'Tile %d: use WIDTH,HEIGHT.', 'toolkit' ), $number ) );
			}

			[ $width, $height ] = $size;

			if ( !ctype_digit( $width ) || (int) $width < 1 || (int) $width > $columns ) {
				/* translators: 1: tile number, 2: number of columns */
				return new \WP_Error( 'tiles_width', sprintf( __( 'Tile %1$d: width must be a whole number from 1 to %2$d.', 'toolkit' ), $number, $columns ) );
			}

			$rows = preg_match( '/^(\d+(\.\d{1,2})?|\.\d{1,2})$/', $height ) ? (float) $height * self::ROWS_PER_UNIT : -1;

			if ( $rows < 1 || $rows > self::MAX_HEIGHT * self::ROWS_PER_UNIT ) {
				/* translators: 1: tile number, 2: maximum height */
				return new \WP_Error( 'tiles_height', sprintf( __( 'Tile %1$d: height must be from 0.01 to %2$d, up to two decimals.', 'toolkit' ), $number, self::MAX_HEIGHT ) );
			}

			$tiles[] = [
				'width'  => (int) $width,
				'height' => (float) $height,
				'rows'   => (int) round( $rows ),
			];
		}

		return [
			'columns' => $columns,
			'tiles'   => $tiles,
		];
	}
}
