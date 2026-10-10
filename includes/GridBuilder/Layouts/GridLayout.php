<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid: equal cells, columns per breakpoint (shared responsive settings)
 *
 * Image ratio and fit are handed to the theme's skin as CSS variables
 * (--vlt-grid-ratio, --vlt-grid-fit); the card markup itself is the theme's.
 */
class GridLayout implements LayoutInterface {
	/**
	 * Image ratios
	 *
	 * @var array [ value => CSS aspect-ratio ]
	 */
	const RATIOS = [
		'auto' => 'auto',
		'1:1'  => '1 / 1',
		'4:3'  => '4 / 3',
		'3:2'  => '3 / 2',
		'16:9' => '16 / 9',
		'3:4'  => '3 / 4',
		'2:3'  => '2 / 3',
	];

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'grid';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Grid', 'toolkit' );
	}

	/**
	 * Defaults
	 *
	 * @return array
	 */
	public function defaults() {
		return [
			'ratio' => '4:3',
			'fit'   => 'cover',
		];
	}

	/**
	 * Sanitize
	 *
	 * @param array $settings Settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings ) {
		return [
			'ratio' => isset( self::RATIOS[ $settings['ratio'] ?? '' ] ) ? $settings['ratio'] : '4:3',
			'fit'   => in_array( $settings['fit'] ?? '', [ 'cover', 'contain' ], true ) ? $settings['fit'] : 'cover',
		];
	}

	/**
	 * Item classes
	 *
	 * @param int   $index  Index
	 * @param array $config Config
	 *
	 * @return array
	 */
	public function item_classes( $index, array $config ) {
		return [];
	}

	/**
	 * CSS
	 *
	 * @param array  $config      Config
	 * @param string $scope       Scope
	 * @param array  $breakpoints Breakpoints
	 *
	 * @return string
	 */
	public function css( array $config, $scope, array $breakpoints ) {
		$settings = $config['layout']['grid'];

		return sprintf( '%s{--vlt-grid-ratio:%s;--vlt-grid-fit:%s}', $scope, self::RATIOS[ $settings['ratio'] ], $settings['fit'] );
	}
}
