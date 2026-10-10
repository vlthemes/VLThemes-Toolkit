<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme templates of a grid: skins (cards), filter and pagination templates
 *
 * Each is a folder in the theme with the template (required), style.css and script.js (optional):
 *
 *     your-theme/vlthemes-toolkit/grid-builder/skins/{Name}/skin.php
 *     your-theme/vlthemes-toolkit/grid-builder/filters/{Name}/filter.php
 *     your-theme/vlthemes-toolkit/grid-builder/pagination/{Name}/pagination.php
 *     your-theme/vlthemes-toolkit/grid-builder/layouts/{Name}/layout.php   (layout types, see Layouts\ThemeLayout)
 *
 * The plugin ships a neutral built-in skin (includes/GridBuilder/skins/default), so a grid always has cards;
 * a theme folder skins/default replaces it.
 *
 * Folders are discovered automatically (child theme, then parent theme); the ID is the lowercased
 * folder name, the label comes from the "Name:" header of the template ("Skin Name:" works for skins).
 * Each file is looked up child → parent → registered path, so a child theme can override a single file.
 *
 * From elsewhere (another plugin), per kind: vlt_toolkit_grid_skins / vlt_toolkit_grid_filters / vlt_toolkit_grid_pagination
 *     add_filter( 'vlt_toolkit_grid_skins', fn( $list ) => $list + [ 'cards' => [ 'label' => 'Cards', 'path' => DIR, 'url' => URL ] ] );
 *
 * Template variables:
 * - skin.php:       $item (see Sources\SourceInterface), $index, $config, $source
 * - filter.php:     $terms ([ id, name, count, active ]), $label, $config — use data-vlt-grid-term / data-vlt-grid-filter
 * - pagination.php: $data (type, page, max_pages, pages, label, loading, no_more), $config — use data-vlt-grid-page / data-vlt-grid-more
 */
class SkinManager {
	/**
	 * Kinds: folder => template file
	 */
	const KINDS = [
		'skins'      => 'skin.php',
		'filters'    => 'filter.php',
		'pagination' => 'pagination.php',
		'layouts'    => 'layout.php',
	];

	/**
	 * Optional files next to the template — the only other names ever appended to a folder
	 */
	const FILES = [
		'style'  => 'style.css',
		'script' => 'script.js',
	];

	/**
	 * Cache per kind
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Theme folders of a kind, child first
	 *
	 * @param string $kind Kind
	 *
	 * @return array [ [ path, url ], … ]
	 */
	public static function roots( $kind = 'skins' ) {
		$relative = 'vlthemes-toolkit/grid-builder/' . $kind . '/';
		$roots    = [ [ trailingslashit( get_stylesheet_directory() ) . $relative, trailingslashit( get_stylesheet_directory_uri() ) . $relative ] ];

		if ( get_template_directory() !== get_stylesheet_directory() ) {
			$roots[] = [ trailingslashit( get_template_directory() ) . $relative, trailingslashit( get_template_directory_uri() ) . $relative ];
		}

		return $roots;
	}

	/**
	 * All templates of a kind
	 *
	 * @param string $kind Kind (key of KINDS)
	 *
	 * @return array [ id => [ 'id', 'label', 'description', 'dirs' => [ [ path, url ], … ] ] ]
	 */
	public static function all( $kind = 'skins' ) {
		if ( !isset( self::KINDS[ $kind ] ) ) {
			return [];
		}

		if ( isset( self::$cache[ $kind ] ) ) {
			return self::$cache[ $kind ];
		}

		$file = self::KINDS[ $kind ];
		$list = [];

		foreach ( self::roots( $kind ) as [ $root_path, $root_url ] ) {
			foreach ( (array) glob( $root_path . '*', GLOB_ONLYDIR ) as $dir ) {
				$name = basename( $dir );

				if ( !preg_match( '/^[A-Za-z0-9_-]+$/', $name ) ) {
					continue;
				}

				$id = sanitize_key( $name );

				// A parent-theme folder without the template can still override a file of a child one, so only
				// require the template for the first appearance
				if ( !isset( $list[ $id ] ) && !is_file( $dir . '/' . $file ) ) {
					continue;
				}

				$list[ $id ] ??= self::entry( $id, $dir . '/' . $file );
				$list[ $id ]['dirs'][] = [ $dir . '/', $root_url . $name . '/' ];
			}
		}

		foreach ( (array) apply_filters( 'vlt_toolkit_grid_' . $kind, [] ) as $id => $item ) {
			$id = sanitize_key( $id );

			if ( !$id || empty( $item['path'] ) || empty( $item['url'] ) || !is_file( trailingslashit( $item['path'] ) . $file ) ) {
				continue;
			}

			$list[ $id ] ??= self::entry( $id, trailingslashit( $item['path'] ) . $file, $item['label'] ?? '' );
			$list[ $id ]['dirs'][] = [ trailingslashit( $item['path'] ), trailingslashit( $item['url'] ) ];
		}

		// Built-in templates of the plugin (skins/default): last, so the theme's come first and a theme folder of
		// the same name overrides them file by file
		foreach ( (array) glob( VLT_TOOLKIT_GRID_PATH . $kind . '/*', GLOB_ONLYDIR ) as $dir ) {
			$id = sanitize_key( basename( $dir ) );

			if ( is_file( $dir . '/' . $file ) ) {
				$list[ $id ] ??= self::entry( $id, $dir . '/' . $file );
				$list[ $id ]['dirs'][] = [ $dir . '/', VLT_TOOLKIT_GRID_URL . $kind . '/' . basename( $dir ) . '/' ];
			}
		}

		if ( did_action( 'after_setup_theme' ) ) {
			self::$cache[ $kind ] = $list;
		}

		return $list;
	}

	/**
	 * Build an entry from its template header
	 *
	 * @param string $id       ID
	 * @param string $template Template path
	 * @param string $label    Label override
	 *
	 * @return array
	 */
	private static function entry( $id, $template, $label = '' ) {
		$header = get_file_data( $template, [
			'name'        => 'Name',
			'skin'        => 'Skin Name',
			'description' => 'Description',
		] );

		return [
			'id'          => $id,
			'label'       => $label ?: ( $header['skin'] ?: ( $header['name'] ?: ucfirst( $id ) ) ),
			'description' => $header['description'],
			'dirs'        => [],
		];
	}

	/**
	 * Locate a file of a template
	 *
	 * @param string $id   ID (must be registered)
	 * @param string $key  'template', or a key of FILES
	 * @param string $kind Kind
	 *
	 * @return array|null [ path, url ]
	 */
	public static function locate( $id, $key, $kind = 'skins' ) {
		$list = self::all( $kind );
		$file = 'template' === $key ? ( self::KINDS[ $kind ] ?? null ) : ( self::FILES[ $key ] ?? null );

		if ( !$file || !isset( $list[ $id ] ) ) {
			return null;
		}

		foreach ( $list[ $id ]['dirs'] as [ $path, $url ] ) {
			if ( is_file( $path . $file ) ) {
				return [ $path . $file, $url . $file ];
			}
		}

		return null;
	}

	/**
	 * Enqueue a template's style and script
	 *
	 * @param string $id   ID
	 * @param string $kind Kind
	 */
	public static function enqueue( $id, $kind = 'skins' ) {
		$handle = 'vlt-grid-' . ( 'skins' === $kind ? 'skin' : $kind ) . '-' . $id;
		$style  = self::locate( $id, 'style', $kind );
		$script = self::locate( $id, 'script', $kind );

		if ( $style ) {
			wp_enqueue_style( $handle, $style[1], [ 'vlt-grid' ], (string) filemtime( $style[0] ) );
		}

		if ( $script ) {
			wp_enqueue_script( $handle, $script[1], [ 'vlt-grid' ], (string) filemtime( $script[0] ), true );
		}
	}

	/**
	 * Render a template
	 *
	 * @param string $id   ID
	 * @param array  $vars Template variables
	 * @param string $kind Kind
	 *
	 * @return string
	 */
	public static function render( $id, array $vars, $kind = 'skins' ) {
		$template = self::locate( $id, 'template', $kind );

		if ( !$template ) {
			return '';
		}

		ob_start();

		// Own scope: the template sees only its variables
		( static function ( $__template, $__vars ) {
			extract( $__vars, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
			include $__template;
		} )( $template[0], $vars );

		return (string) ob_get_clean();
	}
}
