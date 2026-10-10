<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid layout type
 *
 * A layout only decides how items are placed: its own settings, per-item classes and the CSS of one
 * layout. Querying, skins and pagination are shared (Renderer), so a layout never renders items itself.
 *
 * Register more with the 'vlt_toolkit_grid_layouts' filter.
 */
interface LayoutInterface {
	/**
	 * Unique ID (stored in configs, used in the .vlt-gb--layout-{id} class)
	 *
	 * @return string
	 */
	public function get_id();

	/**
	 * Label for the editor
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Default settings
	 *
	 * @return array
	 */
	public function defaults();

	/**
	 * Validate settings
	 *
	 * @param array $settings Raw settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings );

	/**
	 * Extra classes of the item at a position
	 *
	 * @param int   $index  0-based position in the whole result (continues across pages)
	 * @param array $config Layout config
	 *
	 * @return array
	 */
	public function item_classes( $index, array $config );

	/**
	 * Layout-specific CSS of one layout (printed once per page)
	 *
	 * @param array  $config      Layout config
	 * @param string $scope       Scope selector, e.g. .vlt-gb--123
	 * @param array  $breakpoints [ 'tablet' => px, 'mobile' => px ] max-widths, widest first
	 *
	 * @return string
	 */
	public function css( array $config, $scope, array $breakpoints );
}
