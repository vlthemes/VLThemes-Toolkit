<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tiles: a repeating composition of differently sized tiles
 *
 * Patterns are predefined (pattern syntax: TilesParser); a layout stores the pattern key. The theme adds its own:
 *
 *     add_filter( 'vlt_toolkit_grid_tiles_patterns', fn( $patterns ) => $patterns + [
 *         'my-pattern' => [ 'label' => 'My pattern', 'pattern' => '4|2,2;1,1;1,1;2,1' ],
 *     ] );
 *
 * Patterns that don't parse are skipped (with a _doing_it_wrong notice for the developer).
 *
 * Placement rules:
 * - Item N uses tile N mod count, so the pattern repeats; the last group simply ends where items end.
 * - CSS grid places tiles in order (row flow, no "dense"), so the visual order matches the query order;
 *   a pattern whose tiles don't fill rows evenly leaves holes — that is the pattern, not a bug.
 * - Row height is a quarter of a column width (container query units), so heights line up across tiles.
 * - Narrow screens: columns become min(pattern columns, the responsive Tablet/Mobile columns) and every
 *   tile is clamped to that width; heights stay the same multiple of the (now wider) column.
 */
class TilesLayout implements LayoutInterface {
	const DEFAULT_PATTERN = 'classic';

	/**
	 * Parsed patterns cache
	 *
	 * @var array|null
	 */
	private $patterns = null;

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'tiles';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Tiles', 'toolkit' );
	}

	/**
	 * Available patterns, parsed
	 *
	 * @return array [ key => [ 'label', 'pattern', 'columns', 'tiles' ] ]
	 */
	public function get_patterns() {
		if ( null !== $this->patterns ) {
			return $this->patterns;
		}

		$patterns = (array) apply_filters( 'vlt_toolkit_grid_tiles_patterns', [
			'classic' => [
				'label'   => __( 'Classic: wide + two stacked', 'toolkit' ),
				'pattern' => '3|2,1;1,.5;1,.5',
			],
			'mosaic'  => [
				'label'   => __( 'Mosaic: big square + four', 'toolkit' ),
				'pattern' => '4|2,2;1,1;1,1;1,1;1,1',
			],
			'tall'    => [
				'label'   => __( 'Tall + wide', 'toolkit' ),
				'pattern' => '3|1,2;2,1;1,1;1,1',
			],
			'bricks'  => [
				'label'   => __( 'Bricks', 'toolkit' ),
				'pattern' => '4|2,1;1,1;1,1;1,1;1,1;2,1',
			],
			'feature' => [
				'label'   => __( 'Feature row + two', 'toolkit' ),
				'pattern' => '2|2,1;1,1;1,1',
			],
		] + self::catalogue() );

		$this->patterns = [];

		foreach ( $patterns as $key => $pattern ) {
			$key    = sanitize_key( $key );
			$parsed = $key ? TilesParser::parse( $pattern['pattern'] ?? '' ) : null;

			if ( !is_array( $parsed ) ) {
				_doing_it_wrong(
					'vlt_toolkit_grid_tiles_patterns',
					/* translators: 1: pattern key, 2: error */
					esc_html( sprintf( __( 'Tiles pattern "%1$s" is skipped: %2$s', 'toolkit' ), $key, is_wp_error( $parsed ) ? $parsed->get_error_message() : __( 'invalid key', 'toolkit' ) ) ),
					'1.0.0'
				);
				continue;
			}

			$this->patterns[ $key ] = [
				'label'   => (string) ( $pattern['label'] ?? $key ),
				'pattern' => $pattern['pattern'],
			] + $parsed;
		}

		// The default can't be filtered away
		$this->patterns[ self::DEFAULT_PATTERN ] ??= [
			'label'   => __( 'Classic: wide + two stacked', 'toolkit' ),
			'pattern' => '3|2,1;1,.5;1,.5',
		] + TilesParser::parse( '3|2,1;1,.5;1,.5' );

		return $this->patterns;
	}

	/**
	 * More presets: the Visual Portfolio tiles catalogue, rewritten in our notation
	 * (their heights are relative to the tile's own width — "2,0.5" there is "2,1" here)
	 *
	 * @return array [ key => [ 'label', 'pattern' ] ]
	 */
	private static function catalogue() {
		$list = [];

		foreach ( [
			'tiles-1' => '1|1,0.5',
			'tiles-2' => '2|1,1',
			'tiles-3' => '2|1,0.8',
			'tiles-4' => '2|1,1.34',
			'tiles-5' => '2|1,1.2;1,1.2;1,0.67;1,0.67',
			'tiles-6' => '2|1,1.2;1,0.67;1,1.2;1,0.67',
			'tiles-7' => '2|1,0.67;1,1;1,1;1,1;1,1;1,0.67',
			'tiles-8' => '3|1,1',
			'tiles-9' => '3|1,0.8',
			'tiles-10' => '3|1,1.3',
			'tiles-11' => '3|1,1;1,1;1,1;1,1.3;1,1.3;1,1.3',
			'tiles-12' => '3|1,1;1,1;1,2;1,1;1,1;1,1;1,1;1,1',
			'tiles-13' => '3|1,2;1,1;1,1;1,1;1,1;1,1;1,1;1,1',
			'tiles-14' => '3|1,1;1,2;1,1;1,1;1,1;1,1;1,1;1,1',
			'tiles-15' => '3|1,1;1,2;1,1;1,1;1,1;1,1;2,1',
			'tiles-16' => '3|1,0.8;1,1.6;1,0.8;1,0.8;1,1.6;1,0.8;1,0.8;1,0.8;1,0.8;1,0.8',
			'tiles-17' => '3|1,0.8;1,1.6;1,0.8;1,0.8;1,1.6;1,1.6;1,0.8;1,0.8;1,0.8',
			'tiles-18' => '3|1,0.8;1,0.8;1,1.6;1,0.8;1,0.8;1,1.6;1,1.6;1,0.8;1,0.8',
			'tiles-19' => '3|1,0.8;1,0.8;1,1.6;1,0.8;1,0.8;1,0.8;1,1.6;1,1.6;1,0.8',
			'tiles-20' => '3|1,1;2,2;1,1;2,1;1,1',
			'tiles-21' => '3|1,1;2,2;1,1;1,1;1,1;1,1;2,1;1,1',
			'tiles-22' => '3|1,2;2,1;1,1;1,2;2,1',
			'tiles-23' => '4|1,1',
			'tiles-24' => '4|1,1;1,1.34;1,1;1,1.34;1,1.34;1,1.34;1,1;1,1',
			'tiles-25' => '4|1,0.8;1,1;1,0.8;1,1;1,1;1,1;1,0.8;1,0.8',
			'tiles-26' => '4|1,1;1,1;2,2;1,1;1,1;2,2;1,1;1,1;1,1;1,1',
			'tiles-27' => '4|2,2;2,1;2,1;2,1;2,2;2,1',
		] as $key => $pattern ) {
			$columns = (int) $pattern;
			$tiles   = substr_count( $pattern, ';' ) + 1;

			$list[ $key ] = [
				/* translators: 1: number of columns, 2: number of tiles */
				'label'   => sprintf( _n( '%1$d column, %2$d tiles', '%1$d columns, %2$d tiles', $columns, 'toolkit' ), $columns, $tiles ),
				'pattern' => $pattern,
			];
		}

		return $list;
	}

	/**
	 * Defaults — tablet / mobile '' = inherit (tablet from desktop, mobile from tablet)
	 *
	 * @return array
	 */
	public function defaults() {
		return [
			'pattern'        => self::DEFAULT_PATTERN,
			'pattern_tablet' => '',
			'pattern_mobile' => '',
		];
	}

	/**
	 * Sanitize — only registered pattern keys
	 *
	 * @param array $settings Settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings ) {
		$patterns = $this->get_patterns();
		$key      = fn( $name, $fallback ) => isset( $patterns[ (string) ( $settings[ $name ] ?? '' ) ] ) ? (string) $settings[ $name ] : $fallback;

		return [
			'pattern'        => $key( 'pattern', self::DEFAULT_PATTERN ),
			'pattern_tablet' => $key( 'pattern_tablet', '' ),
			'pattern_mobile' => $key( 'pattern_mobile', '' ),
		];
	}

	/**
	 * Parsed desktop pattern of a layout
	 *
	 * @param array $config Config
	 *
	 * @return array [ 'columns', 'tiles', … ]
	 */
	public function get_pattern( array $config ) {
		$patterns = $this->get_patterns();

		return $patterns[ $config['layout']['tiles']['pattern'] ] ?? $patterns[ self::DEFAULT_PATTERN ];
	}

	/**
	 * Pattern and columns used on each device
	 *
	 * A device with its own pattern uses exactly that pattern. Without one it inherits the wider device's
	 * pattern, narrowed to its Columns & spacing columns (wider tiles are clamped).
	 *
	 * @param array $config Config
	 *
	 * @return array [ 'desktop' | 'tablet' | 'mobile' => [ 'pattern' => parsed, 'columns' => int, 'own' => bool ] ]
	 */
	public function devices( array $config ) {
		$patterns = $this->get_patterns();
		$settings = $config['layout']['tiles'];
		$desktop  = $this->get_pattern( $config );
		$devices  = [
			'desktop' => [
				'pattern' => $desktop,
				'columns' => $desktop['columns'],
				'own'     => true,
			],
		];

		$previous = $devices['desktop'];

		// Own tablet / mobile patterns need an activated theme; the choice is kept and applies once it is
		$activated = \VLT\Toolkit\GridBuilder\GridBuilder::is_activated();
		$limits    = \VLT\Toolkit\GridBuilder\GridBuilder::columns( $config );

		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			$own = $activated ? ( $settings[ 'pattern_' . $device ] ?? '' ) : '';

			$devices[ $device ] = isset( $patterns[ $own ] )
				? [
					'pattern' => $patterns[ $own ],
					'columns' => $patterns[ $own ]['columns'],
					'own'     => true,
				]
				: [
					'pattern' => $previous['pattern'],
					'columns' => min( $previous['columns'], $limits[ $device ] ),
					'own'     => false,
				];

			$previous = $devices[ $device ];
		}

		return $devices;
	}

	/**
	 * Item classes: the item's tile on each device (patterns may have different lengths)
	 *
	 * @param int   $index  Index
	 * @param array $config Config
	 *
	 * @return array
	 */
	public function item_classes( $index, array $config ) {
		$classes = [];

		foreach ( $this->devices( $config ) as $device => $use ) {
			$classes[] = 'vlt-gb__item--' . self::prefix( $device ) . ( $index % count( $use['pattern']['tiles'] ) );
		}

		return $classes;
	}

	/**
	 * CSS: columns and tile spans per device; later breakpoints override earlier ones (same specificity)
	 *
	 * @param array  $config      Config
	 * @param string $scope       Scope
	 * @param array  $breakpoints Breakpoints
	 *
	 * @return string
	 */
	public function css( array $config, $scope, array $breakpoints ) {
		$devices = $this->devices( $config );
		$css     = $this->rules( $devices['desktop'], 'desktop', $scope );

		foreach ( $breakpoints as $device => $max_width ) {
			$css .= sprintf( '@media (max-width:%dpx){%s}', $max_width, $this->rules( $devices[ $device ], $device, $scope ) );
		}

		return $css;
	}

	/**
	 * Column count and tile spans of one device
	 *
	 * @param array  $use    [ 'pattern', 'columns' ]
	 * @param string $device Device
	 * @param string $scope  Scope
	 *
	 * @return string
	 */
	private function rules( array $use, $device, $scope ) {
		$css = sprintf( '%s .vlt-gb__items{--vlt-grid-cols:%d}', $scope, $use['columns'] );

		foreach ( $use['pattern']['tiles'] as $i => $tile ) {
			$css .= sprintf( '%s .vlt-gb__item--%s%d{grid-column:span %d;grid-row:span %d}', $scope, self::prefix( $device ), $i, min( $tile['width'], $use['columns'] ), $tile['rows'] );
		}

		return $css;
	}

	/**
	 * Tile class prefix per device: tile-3 (desktop), tile-t3 (tablet), tile-m3 (mobile)
	 *
	 * @param string $device Device
	 *
	 * @return string
	 */
	private static function prefix( $device ) {
		return [
			'desktop' => 'tile-',
			'tablet'  => 'tile-t',
			'mobile'  => 'tile-m',
		][ $device ];
	}
}
