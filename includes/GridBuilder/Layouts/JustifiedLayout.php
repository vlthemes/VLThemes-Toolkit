<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

use VLT\Toolkit\GridBuilder\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Justified: rows of one height, every image in its own proportions — pure CSS (assets/css/grid.css), no JS
 *
 * Each item carries its image ratio as --vlt-ratio (Renderer, from the attachment metadata), so the flex rows are
 * laid out by the browser before any image loads, and items added by filters / Load More need nothing either.
 * Items grow with their ratio to fill a row; a spacer keeps the last row at the row height (unless stretched).
 * No columns: the row height per device decides (tablet / mobile need an activated theme, like columns and gaps).
 */
class JustifiedLayout implements LayoutInterface {
	const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

	const MIN_HEIGHT = 60;
	const MAX_HEIGHT = 800;

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'justified';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Justified', 'toolkit' );
	}

	/**
	 * Defaults
	 *
	 * @return array
	 */
	public function defaults() {
		return [
			'row_height'   => [
				'desktop' => 260,
				'tablet'  => 200,
				'mobile'  => 150,
			],
			'stretch_last' => false,
		];
	}

	/**
	 * Sanitize — one number (older configs) applies to every device
	 *
	 * @param array $settings Settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings ) {
		$defaults = $this->defaults();
		$raw      = $settings['row_height'] ?? null;
		$heights  = [];

		foreach ( self::DEVICES as $device ) {
			$value              = is_array( $raw ) ? ( $raw[ $device ] ?? null ) : $raw;
			$heights[ $device ] = is_numeric( $value ) ? max( self::MIN_HEIGHT, min( self::MAX_HEIGHT, (int) $value ) ) : $defaults['row_height'][ $device ];
		}

		return [
			'row_height'   => $heights,
			'stretch_last' => !empty( $settings['stretch_last'] ),
		];
	}

	/**
	 * Row heights in use: tablet / mobile = desktop without an activated theme
	 *
	 * @param array $config Layout config
	 *
	 * @return array [ desktop, tablet, mobile ] px
	 */
	public function row_heights( array $config ) {
		$heights = $this->sanitize( (array) ( $config['layout']['justified'] ?? [] ) )['row_height'];

		if ( !GridBuilder::is_activated() ) {
			$heights['tablet'] = $heights['desktop'];
			$heights['mobile'] = $heights['desktop'];
		}

		return $heights;
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
	 * Row height per device and the last-row behaviour as variables
	 *
	 * @param array  $config      Config
	 * @param string $scope       Scope
	 * @param array  $breakpoints Breakpoints
	 *
	 * @return string
	 */
	public function css( array $config, $scope, array $breakpoints ) {
		$heights = $this->row_heights( $config );
		$stretch = $this->sanitize( (array) ( $config['layout']['justified'] ?? [] ) )['stretch_last'];
		$css     = $scope . '{--vlt-gb-row-height:' . $heights['desktop'] . 'px;--vlt-gb-stretch-last:' . ( $stretch ? 1 : 0 ) . '}';
		$wider   = $heights['desktop'];

		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			if ( $heights[ $device ] !== $wider && !empty( $breakpoints[ $device ] ) ) {
				$css .= '@media (max-width:' . (int) $breakpoints[ $device ] . 'px){' . $scope . '{--vlt-gb-row-height:' . $heights[ $device ] . 'px}}';
			}

			$wider = $heights[ $device ];
		}

		return $css;
	}
}
