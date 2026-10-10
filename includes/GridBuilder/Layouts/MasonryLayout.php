<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Masonry: pure CSS columns (assets/css/grid.css), no library and no JS positioning
 *
 * CSS columns fill top to bottom, column by column: item 2 sits under item 1, not beside it.
 * That is accepted as the masonry reading order. Items loaded with "Load More" are appended,
 * so the columns rebalance and earlier items may move to another column.
 */
class MasonryLayout implements LayoutInterface {
	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'masonry';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Masonry', 'toolkit' );
	}

	/**
	 * Defaults — columns and gaps come from the shared responsive settings
	 *
	 * @return array
	 */
	public function defaults() {
		return [];
	}

	/**
	 * Sanitize
	 *
	 * @param array $settings Settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings ) {
		return [];
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
		return '';
	}
}
