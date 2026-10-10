<?php

/**
 * Grid Builder bootstrap
 *
 * Portfolio items, saved grid layouts and their rendering ([vlt_grid_builder id="…"]).
 * Loaded from Toolkit::init_modules(); everything the module needs lives in this directory.
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VLT_TOOLKIT_GRID_PATH', plugin_dir_path( __FILE__ ) );
define( 'VLT_TOOLKIT_GRID_URL', plugin_dir_url( __FILE__ ) );

foreach ( [
	'Config.php',
	'PostTypes.php',
	'Sources/SourceInterface.php',
	'Sources/VirtualTermsInterface.php',
	'Sources/PostTypeSource.php',
	'Sources/MediaSource.php',
	'Sources/SourceRegistry.php',
	'Layouts/LayoutInterface.php',
	'Layouts/ThemeLayout.php',
	'Layouts/JustifiedLayout.php',
	'Layouts/TilesParser.php',
	'Layouts/GridLayout.php',
	'Layouts/TilesLayout.php',
	'Layouts/MasonryLayout.php',
	'Layouts/LayoutRegistry.php',
	'QueryBuilder.php',
	'SkinManager.php',
	'CssScoper.php',
	'Animations.php',
	'Renderer.php',
	'Shortcode.php',
	'Ajax.php',
	'Admin.php',
	'Settings.php',
	'Preview.php',
	'GridBuilder.php',
] as $vlt_toolkit_grid_file ) {
	require_once VLT_TOOLKIT_GRID_PATH . $vlt_toolkit_grid_file;
}

unset( $vlt_toolkit_grid_file );

VLT\Toolkit\GridBuilder\GridBuilder::instance();
