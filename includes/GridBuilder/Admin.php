<?php

namespace VLT\Toolkit\GridBuilder;

use VLT\Toolkit\Admin\ListTable;
use VLT\Toolkit\GridBuilder\Layouts\LayoutRegistry;
use VLT\Toolkit\GridBuilder\Sources\SourceRegistry;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin: the Grid Layout editor (built from assets/src/editor with @wordpress/scripts) and the list screen
 */
class Admin {
	/**
	 * Init
	 */
	public static function init() {
		add_action( 'enqueue_block_editor_assets', [ __CLASS__, 'enqueue_editor' ] );
		add_action( 'enqueue_block_assets', [ __CLASS__, 'enqueue_editor_style' ] );

		add_filter( 'manage_' . PostTypes::LAYOUT . '_posts_columns', [ __CLASS__, 'add_column' ] );
		add_action( 'manage_' . PostTypes::LAYOUT . '_posts_custom_column', [ __CLASS__, 'render_column' ], 10, 2 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_list_style' ] );
		add_action( 'admin_head', [ __CLASS__, 'menu_style' ] );
	}

	/**
	 * Whether the current screen edits a Grid Layout
	 *
	 * @return bool
	 */
	private static function is_layout_screen() {
		$screen = is_admin() && function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		return $screen && PostTypes::LAYOUT === $screen->post_type && $screen->is_block_editor();
	}

	/**
	 * Enqueue the settings UI
	 */
	public static function enqueue_editor() {
		$asset_file = VLT_TOOLKIT_GRID_PATH . 'assets/build/editor.asset.php';

		if ( !self::is_layout_screen() || !is_file( $asset_file ) ) {
			return;
		}

		$asset = require $asset_file;

		// WordPress' CodeMirror for the Custom CSS field (false when the user disabled syntax highlighting)
		$code_editor = wp_enqueue_code_editor( [ 'type' => 'text/css' ] );

		wp_enqueue_script( 'vlt-grid-editor', VLT_TOOLKIT_GRID_URL . 'assets/build/editor.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'vlt-grid-editor', 'toolkit' );
		wp_add_inline_script( 'vlt-grid-editor', 'window.vltGridEditor = ' . wp_json_encode( self::editor_data() + [ 'codeEditor' => $code_editor ] ) . ';', 'before' );

		// Sidebar panels live in the main document, the preview in the canvas iframe (enqueue_editor_style)
		self::enqueue_editor_style();

		// Fonts: Geist comes with editor.css (@import), as in the dashboard
	}

	/**
	 * Editor styles — also on enqueue_block_assets to reach the iframed canvas
	 */
	public static function enqueue_editor_style() {
		$style = VLT_TOOLKIT_GRID_PATH . 'assets/build/editor.css';

		if ( self::is_layout_screen() && is_file( $style ) ) {
			wp_enqueue_style( 'vlt-grid-editor', VLT_TOOLKIT_GRID_URL . 'assets/build/editor.css', [ 'wp-components' ], (string) filemtime( $style ) );
			wp_style_add_data( 'vlt-grid-editor', 'rtl', 'replace' );
		}
	}

	/**
	 * Data for the settings UI: everything is built from what is really registered
	 *
	 * @return array
	 */
	private static function editor_data() {
		$options = fn( array $list ) => array_map( fn( $value, $label ) => [
			'value' => (string) $value,
			'label' => $label,
		], array_keys( $list ), $list );

		$sources = [];

		foreach ( SourceRegistry::all() as $id => $source ) {
			$sources[] = [
				'value'      => $id,
				'label'      => $source->get_label(),
				'taxonomies' => $options( $source->get_taxonomies() ),
				'orderby'    => $options( $source->get_orderby_options() ),
				'isMedia'    => 'media' === $id,
				'isPostType' => $source instanceof Sources\PostTypeSource,
			];
		}

		$layouts         = [];
		$layout_settings = [];

		foreach ( LayoutRegistry::all() as $id => $layout ) {
			$layouts[ $id ] = $layout->get_label();

			// Theme layout types: their settings schema, rendered as generic controls
			if ( $layout instanceof Layouts\ThemeLayout ) {
				$layout_settings[ $id ] = [
					'description' => $layout->get_description(),
					'columns'     => $layout->uses_columns(),
					'settings'    => array_map( fn( $setting ) => [ 'options' => $options( $setting['options'] ) ] + $setting, $layout->settings() ),
				];
			}
		}

		$labels = fn( $kind ) => array_map( fn( $entry ) => $entry['label'], SkinManager::all( $kind ) );
		$skins  = $labels( 'skins' );

		return [
			'metaConfig'   => PostTypes::META_CONFIG,
			'metaCss'      => PostTypes::META_CSS,
			'defaults'     => Config::defaults(),
			'sources'      => $sources,
			'layouts'      => $options( $layouts ),
			'layoutSettings' => (object) $layout_settings,
			'skins'        => $options( $skins ),
			'filterTemplates'     => $options( $labels( 'filters' ) ),
			'paginationTemplates' => $options( $labels( 'pagination' ) ),
			'ratios'       => $options( array_combine( array_keys( Layouts\GridLayout::RATIOS ), array_keys( Layouts\GridLayout::RATIOS ) ) ),
			'tilesPatterns' => self::tiles_patterns(),
			'activated'     => GridBuilder::is_activated(),
			'activateUrl'   => GridBuilder::activate_url(),
			'templates'     => $options( class_exists( '\VLT\Toolkit\TemplateParts\TemplateParts' ) ? \VLT\Toolkit\TemplateParts\TemplateParts::get_templates_by_type() : [] ),
			'templatesUrl'  => admin_url( 'edit.php?post_type=vlt_tp' ),
			'animations'    => array_merge(
				[ [ 'value' => 'none', 'label' => __( 'None', 'toolkit' ) ] ],
				$options( array_map( fn( $preset ) => $preset['label'], Animations::presets() ) ),
			),
			// Classes listed under Custom CSS — the theme adds its skin's: add_filter( 'vlt_toolkit_grid_css_classes', … )
			'cssClasses'    => array_values( (array) apply_filters( 'vlt_toolkit_grid_css_classes', [
				[ 'selector' => 'selector', 'depth' => 0, 'description' => __( 'Grid container', 'toolkit' ) ],
				[ 'selector' => 'selector .vlt-gb__items', 'depth' => 1, 'description' => __( 'Items wrapper (grid / columns)', 'toolkit' ) ],
				[ 'selector' => 'selector .vlt-gb__item', 'depth' => 2, 'description' => __( 'Item (wraps the skin)', 'toolkit' ) ],
				[ 'selector' => 'selector .vlt-gb__media', 'depth' => 3, 'description' => __( 'Image wrapper, when the skin uses it', 'toolkit' ) ],
				[ 'selector' => 'selector .vlt-gb__empty', 'depth' => 2, 'description' => __( '"No items found" message', 'toolkit' ) ],
			] ) ),
			'breakpoints'  => Renderer::breakpoints(),
			'shortcodes'   => [
				'grid'       => Shortcode::TAG,
				'filter'     => Shortcode::FILTER,
				'pagination' => Shortcode::PAGINATION,
			],
			'canEditCss'   => current_user_can( 'edit_css' ),
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'loadAction'   => Ajax::ACTION,
			'previewUrl'   => Preview::url(),
			'nonce'        => wp_create_nonce( Ajax::ADMIN_NONCE ),
			'skinsPath'    => 'vlthemes-toolkit/grid-builder/skins/{Name}/skin.php',
		];
	}

	/**
	 * Tiles patterns for the picker (already parsed, so the editor only draws them)
	 *
	 * @return array
	 */
	private static function tiles_patterns() {
		$tiles = LayoutRegistry::get( 'tiles' );

		if ( !$tiles instanceof Layouts\TilesLayout ) {
			return [];
		}

		$list = [];

		foreach ( $tiles->get_patterns() as $key => $pattern ) {
			$list[] = [
				'value'   => $key,
				'label'   => $pattern['label'],
				'columns' => $pattern['columns'],
				'tiles'   => $pattern['tiles'],
			];
		}

		return $list;
	}

	/**
	 * List screen: compact summary columns
	 *
	 * @param array $columns Columns
	 *
	 * @return array
	 */
	public static function add_column( $columns ) {
		$date = $columns['date'] ?? null;
		unset( $columns['date'] );

		// Column keys shared by the toolkit lists (Admin\ListTable styles)
		$columns['vlt_summary']   = esc_html__( 'Layout', 'toolkit' );
		$columns['vlt_shortcode'] = esc_html__( 'Shortcode', 'toolkit' );

		if ( $date ) {
			$columns['date'] = $date;
		}

		return $columns;
	}

	/**
	 * List screen: column values
	 *
	 * @param string $column  Column
	 * @param int    $post_id Post ID
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'vlt_shortcode' === $column ) {
			echo ListTable::shortcode( sprintf( '[%s id="%d"]', Shortcode::TAG, absint( $post_id ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside
		}

		if ( 'vlt_summary' === $column ) {
			echo self::summary( Config::sanitize( get_post_meta( $post_id, PostTypes::META_CONFIG, true ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside
		}
	}

	/**
	 * Mini summary of a layout: type (with the tiles sketch), source, and what is switched on
	 *
	 * @param array $config Layout config
	 *
	 * @return string
	 */
	private static function summary( array $config ) {
		$type    = $config['layout']['type'];
		$layout  = LayoutRegistry::get( $type );
		$source  = SourceRegistry::get( $config['query']['source'] );
		$columns = GridBuilder::columns( $config );
		$detail  = '';

		if ( 'grid' === $type ) {
			$detail = 'auto' === $config['layout']['grid']['ratio'] ? __( 'original ratio', 'toolkit' ) : $config['layout']['grid']['ratio'];
		}

		if ( $layout instanceof Layouts\JustifiedLayout ) {
			$heights = $layout->row_heights( $config );
			/* translators: 1: desktop, 2: tablet, 3: mobile row height in px */
			$detail = sprintf( __( 'rows %1$d / %2$d / %3$dpx', 'toolkit' ), $heights['desktop'], $heights['tablet'], $heights['mobile'] );
		}

		if ( $layout instanceof Layouts\TilesLayout ) {
			$devices = $layout->devices( $config );
			$detail  = $devices['desktop']['pattern']['label'];
			$columns = array_map( fn( $use ) => $use['columns'], $devices );
		}

		$meta = [
			$source ? $source->get_label() : $config['query']['source'],
			-1 === $config['query']['per_page']
				? __( 'all items', 'toolkit' )
				/* translators: %d: items per page */
				: sprintf( __( '%d per page', 'toolkit' ), $config['query']['per_page'] ),
			/* translators: 1: desktop, 2: tablet, 3: mobile columns */
			sprintf( __( '%1$d / %2$d / %3$d columns', 'toolkit' ), $columns['desktop'], $columns['tablet'], $columns['mobile'] ),
		];

		// Types without columns (Justified…): the count means nothing there
		if ( $layout instanceof Layouts\JustifiedLayout || ( $layout instanceof Layouts\ThemeLayout && !$layout->uses_columns() ) ) {
			array_pop( $meta );
		}

		// Only what is switched on; a missing skin is a warning
		$badges = [];

		if ( $config['filters']['enabled'] ) {
			$badges[] = [ __( 'Filter', 'toolkit' ), '', self::icon( 'filter' ) ];
		}

		if ( 'none' !== $config['pagination']['type'] && -1 !== $config['query']['per_page'] ) {
			$badges[] = [ 'loadmore' === $config['pagination']['type'] ? __( 'Load More', 'toolkit' ) : __( 'Pages', 'toolkit' ), '', self::icon( 'pagination' ) ];
		}

		$animations = Animations::presets();

		if ( isset( $animations[ $config['animation']['effect'] ] ) ) {
			$badges[] = [ $animations[ $config['animation']['effect'] ]['label'], '', self::icon( 'animation' ) ];
		}

		$skins = SkinManager::all();

		if ( $config['inserts'] ) {
			/* translators: %d: number of inserted template parts */
			$badges[] = [ sprintf( _n( '%d insert', '%d inserts', count( $config['inserts'] ), 'toolkit' ), count( $config['inserts'] ) ), '', self::icon( 'inserts' ) ];
		}

		$badges[] = isset( $skins[ $config['skin'] ] )
			/* translators: %s: skin name */
			? [ sprintf( __( 'Skin: %s', 'toolkit' ), $skins[ $config['skin'] ]['label'] ), '', self::icon( 'skin' ) ]
			: [ __( 'No skin', 'toolkit' ), 'is-warning' ];

		return ListTable::summary( $layout ? $layout->get_label() : $type, (string) $detail, [ esc_html( implode( ' · ', $meta ) ) ], $badges, self::sketch( $config, $layout ) );
	}

	/**
	 * A section icon of the editor (assets/icons/{name}.svg, the same files icons.js imports)
	 *
	 * @param string $name Icon name
	 *
	 * @return string SVG markup, '' when there is no such icon
	 */
	public static function icon( $name ) {
		static $cache = [];

		if ( !isset( $cache[ $name ] ) ) {
			$file           = VLT_TOOLKIT_GRID_PATH . 'assets/icons/' . sanitize_key( $name ) . '.svg';
			$cache[ $name ] = is_file( $file ) ? str_replace( '<svg ', '<svg aria-hidden="true" focusable="false" ', trim( (string) file_get_contents( $file ) ) ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin file
		}

		return $cache[ $name ];
	}

	/**
	 * Thumbnail sketch of a layout — every type gets one, so rows line up
	 *
	 * @param array                          $config Layout config
	 * @param Layouts\LayoutInterface|null $layout Layout type
	 *
	 * @return string
	 */
	private static function sketch( array $config, $layout ) {
		$cells = '';
		$style = '';

		if ( $layout instanceof Layouts\TilesLayout ) {
			// Real proportions (rows = 1/100 of a column width), repeated to fill two rows, cut at the box height
			$pattern = $layout->get_pattern( $config );
			$columns = $pattern['columns'];
			$repeat  = max( 2, (int) ceil( $columns * 2 / count( $pattern['tiles'] ) ) );

			for ( $r = 0; $r < $repeat; $r++ ) {
				foreach ( $pattern['tiles'] as $tile ) {
					$cells .= sprintf( '<span style="grid-column:span %d;grid-row:span %d"></span>', $tile['width'], $tile['rows'] );
				}
			}

			$style = sprintf( ';grid-auto-rows:calc((100cqw - %d * 2px) / %d / 100)', $columns - 1, $columns );
		} elseif ( $layout instanceof Layouts\JustifiedLayout ) {
			// Rows of one height, blocks of different widths
			foreach ( [ [ 3, 2, 2 ], [ 2, 4 ], [ 2, 3, 2 ] ] as $row ) {
				$cells .= '<span class="is-row">';

				foreach ( $row as $grow ) {
					$cells .= '<i style="flex-grow:' . (int) $grow . '"></i>';
				}

				$cells .= '</span>';
			}

			return '<span class="vlt-grid-list__sketch is-justified" aria-hidden="true"><span class="vlt-grid-list__cells">' . $cells . '</span></span>';
		} elseif ( 'masonry' === $config['layout']['type'] ) {
			// Columns of different heights
			$columns = min( 4, max( 2, $config['responsive']['columns']['desktop'] ) );

			for ( $i = 0; $i < $columns; $i++ ) {
				$cells .= '<span class="is-col">' . str_repeat( '<i></i>', 2 ) . '</span>';
			}

			return '<span class="vlt-grid-list__sketch is-masonry" aria-hidden="true"><span class="vlt-grid-list__cells" style="grid-template-columns:repeat(' . $columns . ',1fr)">' . $cells . '</span></span>';
		} else {
			$columns = min( 4, max( 1, $config['responsive']['columns']['desktop'] ) );
			$cells   = str_repeat( '<span></span>', $columns * 2 );
			$style   = ';grid-auto-rows:1fr;height:100%';
		}

		return '<span class="vlt-grid-list__sketch' . ( $layout instanceof Layouts\TilesLayout ? ' is-tiles' : '' ) . '" aria-hidden="true"><span class="vlt-grid-list__cells" style="grid-template-columns:repeat(' . (int) $columns . ',1fr)' . $style . '">' . $cells . '</span></span>';
	}

	/**
	 * Admin menu: "Grid Layouts" set apart from the portfolio items — divider above, dashboard brand colour
	 */
	public static function menu_style() {
		// The divider separates Grid Layouts from the portfolio items; in its own menu there is nothing to separate
		if ( !GridBuilder::portfolio() ) {
			return;
		}

		$link = 'edit.php?post_type=' . PostTypes::LAYOUT;
		?>
		<style>
			#adminmenu li:has(> a[href="<?php echo esc_attr( $link ); ?>"]) {
				margin-top: 6px;
				padding-top: 6px;
				border-top: 1px solid rgba(255, 255, 255, .12);
			}

			#adminmenu a[href="<?php echo esc_attr( $link ); ?>"] {
				font-weight: 600;
				color: #87e64b !important;
			}
		</style>
		<?php
	}

	/**
	 * List screen styles
	 */
	public static function enqueue_list_style() {
		$screen = get_current_screen();

		ListTable::screen( 'edit-' . PostTypes::LAYOUT );

		// The layout sketch; the rest of the cells are the shared list styles
		if ( $screen && 'edit-' . PostTypes::LAYOUT === $screen->id ) {
			wp_enqueue_style( 'vlt-grid-list', VLT_TOOLKIT_GRID_URL . 'assets/css/admin-list.css', [ 'vlt-admin-list' ], VLT_TOOLKIT_VERSION );
		}
	}
}
