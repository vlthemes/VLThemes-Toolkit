<?php

namespace VLT\Toolkit\GridBuilder;

use VLT\Toolkit\GridBuilder\Layouts\LayoutRegistry;
use VLT\Toolkit\GridBuilder\Sources\SourceRegistry;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renderer — shared by the shortcodes (first page) and AJAX (later pages, filters)
 *
 * The grid, its filter and its pagination are separate pieces, each placed with its own shortcode:
 *
 *     <style>  layout CSS, once per layout per page
 *     <div id="vlt-grid-{ID}-{N}" class="vlt-grid vlt-gb--{ID} vlt-gb--layout-{type} vlt-gb--skin-{skin}" data-vlt-grid="{…}" data-vlt-grid-layout="{ID}">
 *         <div class="vlt-gb__items">  <div class="vlt-gb__item …">skin output</div> …
 *     </div>
 *
 *     <div class="vlt-gb__filters" data-vlt-grid-for="{ID}" data-vlt-grid-instance="{n}"> … </div>
 *     <nav class="vlt-gb__pagination" data-vlt-grid-for="{ID}" data-vlt-grid-instance="{n}"> … </nav>
 *
 * A filter / pagination controls the n-th grid of that layout in document order (default: the first).
 * The item wrapper belongs to the layout (tile spans, masonry breaks); what is inside it is the theme's skin.
 * The markup inside the filter and pagination wrappers can be replaced by the theme too (see render_filter(), pagination_html()).
 */
class Renderer {
	/**
	 * Grids rendered in this request (unique element IDs)
	 *
	 * @var int
	 */
	private static $instances = 0;

	/**
	 * Layout IDs whose CSS is already printed
	 *
	 * @var array
	 */
	private static $printed = [];

	/**
	 * Page count of each layout's unfiltered first page (pagination is rendered apart from the grid)
	 *
	 * @var array
	 */
	private static $max_pages = [];

	/**
	 * Grids rendered per layout ID — the n-th one is instance n for its attached filter / pagination
	 *
	 * @var array
	 */
	private static $layout_instances = [];

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', [ __CLASS__, 'register_assets' ], 5 );
	}

	/**
	 * Register front-end assets (enqueued only by grids that are rendered)
	 */
	public static function register_assets() {
		if ( wp_script_is( 'vlt-grid', 'registered' ) ) {
			return;
		}

		wp_register_style( 'vlt-grid', VLT_TOOLKIT_GRID_URL . 'assets/css/grid.css', [], VLT_TOOLKIT_VERSION );
		wp_register_script( 'vlt-grid', VLT_TOOLKIT_GRID_URL . 'assets/js/grid.js', [], VLT_TOOLKIT_VERSION, true );

		wp_localize_script( 'vlt-grid', 'vltGridBuilder', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => Ajax::ACTION,
			'i18n'    => [
				'error' => __( 'Could not load items. Please try again.', 'toolkit' ),
			],
		] );
	}

	/**
	 * Enqueue the assets a layout needs
	 *
	 * @param array $config Layout config
	 */
	public static function enqueue( array $config ) {
		self::register_assets();

		wp_enqueue_style( 'vlt-grid' );
		wp_enqueue_script( 'vlt-grid' );
		SkinManager::enqueue( $config['skin'] );

		if ( LayoutRegistry::get( $config['layout']['type'] ) instanceof Layouts\ThemeLayout ) {
			SkinManager::enqueue( $config['layout']['type'], 'layouts' );
		}
	}

	/**
	 * --vlt-ratio of an item's image (width / height from the attachment metadata, no extra query)
	 *
	 * @param int $image_id Attachment ID
	 *
	 * @return string ";--vlt-ratio:N" or ''
	 */
	private static function ratio_style( $image_id ) {
		$meta = $image_id ? wp_get_attachment_metadata( $image_id ) : null;

		if ( empty( $meta['width'] ) || empty( $meta['height'] ) ) {
			return '';
		}

		return ';--vlt-ratio:' . round( $meta['width'] / $meta['height'], 4 );
	}

	/**
	 * Settings of the layout type for scripts: theme layouts give the values in use (per device where responsive)
	 *
	 * @param array $config Layout config
	 *
	 * @return array
	 */
	private static function layout_settings( array $config ) {
		$layout = LayoutRegistry::get( $config['layout']['type'] );

		return $layout instanceof Layouts\ThemeLayout ? $layout->values( $config ) : ( $config['layout'][ $config['layout']['type'] ] ?? [] );
	}

	/**
	 * Responsive breakpoints (max-width, px), widest first
	 *
	 * @return array [ 'tablet' => int, 'mobile' => int ]
	 */
	public static function breakpoints() {
		// Settings page, then the filter
		$breakpoints = (array) apply_filters( 'vlt_toolkit_grid_breakpoints', Settings::get( 'breakpoints' ) );

		return [
			'tablet' => absint( $breakpoints['tablet'] ?? 1024 ),
			'mobile' => absint( $breakpoints['mobile'] ?? 767 ),
		];
	}

	/**
	 * Render a grid
	 *
	 * @param array $config  Layout config (Config::get())
	 * @param bool  $preview Editor preview: placeholders show the layout's shape when there is nothing to show
	 *
	 * @return string
	 */
	public static function render( array $config, $preview = false ) {
		self::enqueue( $config );

		// Random order gets a seed so every page of this grid continues the same shuffle
		$seed   = 'rand' === $config['query']['orderby'] ? wp_rand( 1, 999999 ) : 0;
		$result = QueryBuilder::run( $config, 1, 0, $seed );

		self::$max_pages[ $config['id'] ] = $result['max_pages'];

		$attributes = apply_filters( 'vlt_toolkit_grid_container_attributes', [
			'id'                   => 'vlt-grid-' . $config['id'] . '-' . ( ++self::$instances ),
			'class'                => implode( ' ', array_filter( [
				'vlt-gb',
				'vlt-gb--' . $config['id'],
				'vlt-gb--layout-' . $config['layout']['type'],
				$config['skin'] ? 'vlt-gb--skin-' . $config['skin'] : '',
			] ) ),
			'data-vlt-grid'        => wp_json_encode( [
				'layout'         => $config['id'],
				'page'           => $result['page'],
				'maxPages'       => $result['max_pages'],
				'seed'           => $seed,
				// Settings of the layout type, for theme layout scripts
				'layoutSettings' => self::layout_settings( $config ),
			] ),
			'data-vlt-grid-layout' => $config['id'],
		], $config );

		$instance = self::$layout_instances[ $config['id'] ] = ( self::$layout_instances[ $config['id'] ] ?? 0 ) + 1;

		$grid = '<div' . self::attributes( $attributes ) . '>'
			. ( $preview ? '' : self::notice( $config ) )
			. '<div class="vlt-gb__items" aria-live="polite">' . ( $preview ? self::preview_items( $config, $result ) : self::render_items( $config, $result ) ) . '</div>'
			. '</div>';

		// "Output with the grid": filter before, pagination after, both bound to this very grid
		if ( $config['display']['attach_controls'] ) {
			// One block for the theme: its content flow spacing applies to the group, ours inside it
			$grid = '<div class="vlt-gb-group">' . self::render_filter( $config, $instance ) . $grid . self::render_pagination( $config, $instance ) . '</div>';
		}

		return self::styles( $config ) . $grid;
	}

	/**
	 * State of the last preview: 'ok', 'empty' (nothing matches the query) or 'no-skin' (the theme has no skin)
	 *
	 * @var string
	 */
	public static $preview_state = 'ok';

	/**
	 * Preview items: the real ones, or placeholders in the layout's shape when the theme has no skin
	 * (with no items at all the editor shows no preview, only a note)
	 *
	 * @param array $config Layout config
	 * @param array $result Query result
	 *
	 * @return string
	 */
	private static function preview_items( array $config, array $result ) {
		$has_skin = $config['skin'] && SkinManager::locate( $config['skin'], 'template' );

		self::$preview_state = !$result['items'] ? 'empty' : ( $has_skin ? 'ok' : 'no-skin' );

		if ( 'no-skin' !== self::$preview_state ) {
			return self::render_items( $config, $result );
		}

		$layout = LayoutRegistry::get( $config['layout']['type'] );
		$count  = $config['query']['per_page'] > 0 ? min( 12, max( 6, $config['query']['per_page'] ) ) : 9;
		$html   = '';

		for ( $i = 0; $i < $count; $i++ ) {
			$classes = array_merge( [ 'vlt-gb__item' ], $layout ? $layout->item_classes( $i, $config ) : [] );
			// Placeholders in a few typical proportions, so ratio-based layouts (Justified) show their shape
			$html   .= '<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="--vlt-i:' . $i . ';--vlt-ratio:' . [ 1.5, 0.75, 1.33, 1, 1.78 ][ $i % 5 ] . '"><div class="vlt-gb__placeholder"></div></div>';
		}

		return $html;
	}

	/**
	 * Render items of a query result
	 *
	 * @param array $config Layout config
	 * @param array $result QueryBuilder::run() result
	 *
	 * @return string
	 */
	public static function render_items( array $config, array $result ) {
		if ( !$result['items'] ) {
			$message = apply_filters( 'vlt_toolkit_grid_empty_message', __( 'No items found.', 'toolkit' ), $config );

			return '<p class="vlt-gb__empty">' . esc_html( $message ) . '</p>';
		}

		$layout  = LayoutRegistry::get( $config['layout']['type'] );
		$inserts = GridBuilder::is_activated() ? $config['inserts'] : []; // inserts need an activated theme
		$html    = '';
		$batch   = 0; // position in this batch: animation stagger

		foreach ( $result['items'] as $i => $item ) {
			$index  = $result['start'] + $i;
			$number = $index + 1;

			// Visual position: items before + inserts before — what the layout (tile spans) counts
			$visual = $index + self::inserts_upto( $inserts, $number - 1 );

			foreach ( self::inserts_at( $inserts, $number ) as $template ) {
				$html .= self::render_insert( $config, $layout, $template, $visual++, $batch++ );
			}

			$classes = array_merge( [ 'vlt-gb__item' ], $layout ? $layout->item_classes( $visual, $config ) : [] );
			$content = SkinManager::render( $config['skin'], [
				'item'   => $item,
				'index'  => $index,
				'config' => $config,
				'source' => $result['source'],
			] );

			// --vlt-i: position in this batch, drives the animation stagger (Animations);
			// --vlt-ratio: the image's width / height, for layouts and skins that size by it (Justified)
			$html .= apply_filters(
				'vlt_toolkit_grid_item_html',
				'<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="--vlt-i:' . (int) $batch++ . self::ratio_style( $item['image_id'] ) . '">' . $content . '</div>',
				$item,
				$config,
				$index,
			);
		}

		return $html;
	}

	/**
	 * Template Part rendering depth — a template holding a grid holding the template must stop somewhere
	 *
	 * @var int
	 */
	private static $template_depth = 0;

	/**
	 * Insert occurrences at slots 1…n ("slot k" = right before item k)
	 *
	 * @param array $inserts Inserts config
	 * @param int   $n       Last slot
	 *
	 * @return int
	 */
	private static function inserts_upto( array $inserts, $n ) {
		$count = 0;

		foreach ( $inserts as $insert ) {
			if ( $n >= $insert['position'] ) {
				$count += 1 + ( $insert['every'] ? intdiv( $n - $insert['position'], $insert['every'] ) : 0 );
			}
		}

		return $count;
	}

	/**
	 * Templates inserted right before item n
	 *
	 * @param array $inserts Inserts config
	 * @param int   $n       Item number (1-based, whole result)
	 *
	 * @return array Template IDs
	 */
	private static function inserts_at( array $inserts, $n ) {
		$templates = [];

		foreach ( $inserts as $insert ) {
			$offset = $n - $insert['position'];

			if ( 0 === $offset || ( $insert['every'] && $offset > 0 && 0 === $offset % $insert['every'] ) ) {
				$templates[] = $insert['template'];
			}
		}

		return $templates;
	}

	/**
	 * Render an insert: a Template Part in its own grid cell
	 *
	 * @param array                          $config   Layout config
	 * @param Layouts\LayoutInterface|null $layout   Layout type
	 * @param int                            $template vlt_tp post ID
	 * @param int                            $visual   Visual position (tile spans)
	 * @param int                            $batch    Position in this batch (animation)
	 *
	 * @return string
	 */
	private static function render_insert( array $config, $layout, $template, $visual, $batch ) {
		$classes = array_merge( [ 'vlt-gb__item', 'vlt-gb__item--insert' ], $layout ? $layout->item_classes( $visual, $config ) : [] );

		return apply_filters(
			'vlt_toolkit_grid_insert_html',
			'<div class="' . esc_attr( implode( ' ', $classes ) ) . '" style="--vlt-i:' . (int) $batch . '">' . self::render_template( $template ) . '</div>',
			$template,
			$config,
		);
	}

	/**
	 * Render a Template Part ([vlt_template_part], TemplateParts module)
	 *
	 * @param int $template_id vlt_tp post ID
	 *
	 * @return string
	 */
	private static function render_template( $template_id ) {
		static $styled = [];

		if ( !$template_id || self::$template_depth >= 2 ) {
			return '';
		}

		// Elementor-built template: its CSS once per page
		if ( !isset( $styled[ $template_id ] ) && class_exists( '\\Elementor\\Core\\Files\\CSS\\Post' ) ) {
			$styled[ $template_id ] = true;
			( new \Elementor\Core\Files\CSS\Post( $template_id ) )->enqueue();
		}

		++self::$template_depth;
		$html = do_shortcode( '[vlt_template_part id="' . (int) $template_id . '"]' );
		--self::$template_depth;

		return $html;
	}

	/**
	 * Render the filter of a layout ([vlt_grid_builder_filter])
	 *
	 * The theme can replace the markup inside the wrapper with the 'vlt_toolkit_grid_filter_html' filter:
	 *
	 *     add_filter( 'vlt_toolkit_grid_filter_html', fn( $html, $terms, $config ) => …, 10, 3 );
	 *
	 * $terms: [ [ 'id' => int (0 = all), 'name' => string, 'count' => int|null, 'active' => bool ], … ].
	 * Markup the script understands: an element with data-vlt-grid-term="{id}" (click), or a
	 * <select data-vlt-grid-filter> whose option values are term IDs (change). The active term gets
	 * .is-active and aria-pressed="true".
	 *
	 * @param array $config   Layout config
	 * @param int   $instance Which grid of this layout it controls (1-based, document order)
	 *
	 * @return string
	 */
	public static function render_filter( array $config, $instance = 1 ) {
		$list = QueryBuilder::filter_terms( $config );

		if ( !$list ) {
			return '';
		}

		self::enqueue( $config );

		$filters = $config['filters'];
		$source  = SourceRegistry::get( $config['query']['source'] );
		$label   = $source ? $source->get_taxonomies()[ $filters['taxonomy'] ] ?? __( 'Filter', 'toolkit' ) : __( 'Filter', 'toolkit' );
		$terms   = [
			[
				'id'     => 0,
				'name'   => $filters['all_label'] ?: __( 'All', 'toolkit' ),
				'count'  => null,
				'active' => true,
			],
		];

		foreach ( $list as $term ) {
			$terms[] = [
				'id'     => $term->term_id,
				'name'   => $term->name,
				'count'  => $filters['show_count'] ? (int) $term->count : null,
				'active' => false,
			];
		}

		$template = $config['filters']['template'];

		if ( $template ) {
			SkinManager::enqueue( $template, 'filters' );
		}

		$html = $template
			? SkinManager::render( $template, [
				'terms'  => $terms,
				'label'  => $label,
				'config' => $config,
			], 'filters' )
			: self::filter_default_html( $terms, $config, $label );

		$html = apply_filters( 'vlt_toolkit_grid_filter_html', $html, $terms, $config );

		return '<div class="vlt-gb__filters' . self::align_class( $template, $config['filters']['align'] ) . '" role="group" aria-label="' . esc_attr( $label ) . '"' . self::link( $config, $instance ) . '>' . $html . '</div>';
	}

	/**
	 * Default (built-in) filter markup: neutral buttons. Other looks — a dropdown included — are theme templates.
	 *
	 * @param array  $terms  Terms
	 * @param array  $config Layout config
	 * @param string $label  Accessible label
	 *
	 * @return string
	 */
	private static function filter_default_html( array $terms, array $config, $label ) {
		$name = fn( $term ) => $term['name'] . ( null !== $term['count'] ? ' (' . number_format_i18n( $term['count'] ) . ')' : '' );
		$html = '';

		foreach ( $terms as $term ) {
			$html .= sprintf(
				'<button type="button" class="vlt-gb__filter%s" data-vlt-grid-term="%d" aria-pressed="%s">%s</button>',
				$term['active'] ? ' is-active' : '',
				$term['id'],
				$term['active'] ? 'true' : 'false',
				esc_html( $name( $term ) ),
			);
		}

		return $html;
	}

	/**
	 * Render the pagination of a layout ([vlt_grid_builder_pagination]) — the wrapper stays even when empty, so AJAX can fill it
	 *
	 * @param array $config   Layout config
	 * @param int   $instance Which grid of this layout it controls
	 *
	 * @return string
	 */
	public static function render_pagination( array $config, $instance = 1 ) {
		if ( 'none' === $config['pagination']['type'] ) {
			return '';
		}

		self::enqueue( $config );

		if ( $config['pagination']['template'] ) {
			SkinManager::enqueue( $config['pagination']['template'], 'pagination' );
		}

		// Placed before the grid, or the grid isn't on this page: count once here
		if ( !isset( self::$max_pages[ $config['id'] ] ) ) {
			self::$max_pages[ $config['id'] ] = QueryBuilder::run( $config, 1 )['max_pages'];
		}

		return '<nav class="vlt-gb__pagination' . self::align_class( $config['pagination']['template'], $config['pagination']['align'] ) . '" aria-label="' . esc_attr__( 'Pagination', 'toolkit' ) . '"' . self::link( $config, $instance ) . '>'
			. self::pagination_html( $config, 1, self::$max_pages[ $config['id'] ] )
			. '</nav>';
	}

	/**
	 * Inner HTML of the pagination for a page (also sent by AJAX)
	 *
	 * The theme can replace it with the 'vlt_toolkit_grid_pagination_html' filter:
	 *
	 *     add_filter( 'vlt_toolkit_grid_pagination_html', fn( $html, $data, $config ) => …, 10, 3 );
	 *
	 * $data: [ 'type' => 'numbers'|'loadmore', 'page' => int, 'max_pages' => int,
	 *          'pages' => [ int|null, … ] (null = gap), 'label' => Load More text,
	 *          'loading' => text while loading, 'no_more' => text once everything is loaded ].
	 * Markup the script understands: data-vlt-grid-page="{n}" (go to page) and data-vlt-grid-more
	 * (load the next page; it gets disabled + .is-loading while loading, and its text becomes the value of
	 * data-vlt-grid-loading when set). Return '' when there's nothing to show.
	 *
	 * @param array $config Layout config
	 * @param int   $page   Current page
	 * @param int   $max    Max pages
	 *
	 * @return string
	 */
	public static function pagination_html( array $config, $page, $max ) {
		$data = [
			'type'      => $config['pagination']['type'],
			'page'      => $page,
			'max_pages' => $max,
			'pages'     => self::page_list( $page, $max ),
			'label'     => $config['pagination']['load_more_label'] ?: __( 'Load More', 'toolkit' ),
			'loading'   => $config['pagination']['loading_label'] ?: __( 'Loading…', 'toolkit' ),
			'no_more'   => $config['pagination']['no_more_label'] ?: __( 'No more items', 'toolkit' ),
		];

		$html = $config['pagination']['template']
			? SkinManager::render( $config['pagination']['template'], [
				'data'   => $data,
				'config' => $config,
			], 'pagination' )
			: self::pagination_default_html( $data );

		return (string) apply_filters( 'vlt_toolkit_grid_pagination_html', $html, $data, $config );
	}

	/**
	 * Default pagination markup
	 *
	 * @param array $data Pagination data
	 *
	 * @return string
	 */
	private static function pagination_default_html( array $data ) {
		$html = '';

		if ( 'loadmore' === $data['type'] && $data['page'] < $data['max_pages'] ) {
			$html = '<button type="button" class="vlt-gb__more" data-vlt-grid-more data-vlt-grid-loading="' . esc_attr( $data['loading'] ) . '">' . esc_html( $data['label'] ) . '</button>';
		} elseif ( 'loadmore' === $data['type'] && $data['page'] > 1 ) {
			// Everything is loaded (only after loading more — a single page shows no button at all)
			$html = '<button type="button" class="vlt-gb__more is-done" disabled>' . esc_html( $data['no_more'] ) . '</button>';
		}

		if ( 'numbers' === $data['type'] && $data['max_pages'] > 1 ) {
			foreach ( $data['pages'] as $number ) {
				if ( null === $number ) {
					$html .= '<span class="vlt-gb__page-gap" aria-hidden="true">&hellip;</span>';
				} elseif ( $number === $data['page'] ) {
					$html .= '<span class="vlt-gb__page is-current" aria-current="page">' . esc_html( number_format_i18n( $number ) ) . '</span>';
				} else {
					/* translators: %s: page number */
					$html .= '<button type="button" class="vlt-gb__page" data-vlt-grid-page="' . esc_attr( $number ) . '" aria-label="' . esc_attr( sprintf( __( 'Page %s', 'toolkit' ), number_format_i18n( $number ) ) ) . '">' . esc_html( number_format_i18n( $number ) ) . '</button>';
				}
			}
		}

		return $html;
	}

	/**
	 * Page numbers to show: first, last, current ±1; null marks a gap
	 *
	 * @param int $page Current page
	 * @param int $max  Max pages
	 *
	 * @return array
	 */
	private static function page_list( $page, $max ) {
		$pages = array_unique( array_filter( [ 1, $page - 1, $page, $page + 1, $max ], fn( $p ) => $p >= 1 && $p <= $max ) );
		sort( $pages );

		$list = [];
		$prev = 0;

		foreach ( $pages as $p ) {
			if ( $p - $prev === 2 ) {
				$list[] = $p - 1;
			} elseif ( $p - $prev > 2 ) {
				$list[] = null;
			}

			$list[] = $p;
			$prev   = $p;
		}

		return $list;
	}

	/**
	 * Alignment modifier — for the built-in style only (theme templates lay themselves out)
	 *
	 * @param string $template Theme template ID ('' = built-in)
	 * @param string $align    left | center | right
	 *
	 * @return string
	 */
	private static function align_class( $template, $align ) {
		return $template ? '' : ' is-align-' . $align;
	}

	/**
	 * Attributes linking a control to its grid
	 *
	 * @param array $config   Layout config
	 * @param int   $instance Grid instance
	 *
	 * @return string
	 */
	private static function link( array $config, $instance ) {
		return self::attributes( [
			'data-vlt-grid-for'      => $config['id'],
			'data-vlt-grid-instance' => max( 1, (int) $instance ),
		] );
	}

	/**
	 * Build an attribute string
	 *
	 * @param array $attributes Attributes
	 *
	 * @return string
	 */
	private static function attributes( array $attributes ) {
		$html = '';

		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
		}

		return $html;
	}

	/**
	 * CSS of a layout: columns and gaps per breakpoint, layout rules, custom CSS — once per layout per page
	 *
	 * @param array $config Layout config
	 *
	 * @return string
	 */
	public static function styles( array $config ) {
		if ( isset( self::$printed[ $config['id'] ] ) ) {
			return '';
		}

		self::$printed[ $config['id'] ] = true;

		$scope       = '.vlt-gb--' . $config['id'];
		$responsive  = GridBuilder::responsive( $config );
		$breakpoints = self::breakpoints();
		$vars        = fn( $device ) => sprintf(
			'--vlt-grid-cols:%d;--vlt-grid-gap-x:%dpx;--vlt-grid-gap-y:%dpx',
			$responsive['columns'][ $device ],
			$responsive['gap_x'][ $device ],
			$responsive['gap_y'][ $device ],
		);

		$css = $scope . '{' . $vars( 'desktop' ) . '}';

		foreach ( $breakpoints as $device => $max_width ) {
			$css .= sprintf( '@media (max-width:%dpx){%s{%s}}', $max_width, $scope, $vars( $device ) );
		}

		$layout = LayoutRegistry::get( $config['layout']['type'] );
		$css   .= $layout ? $layout->css( $config, $scope, $breakpoints ) : '';
		$css   .= Animations::css( $config, $scope );
		// Custom CSS needs an activated theme; it stays saved and applies once it is
		$css   .= GridBuilder::is_activated() ? CssScoper::scope( $config['custom_css'] ?? '', $scope ) : '';

		return '<style id="vlt-grid-style-' . esc_attr( $config['id'] ) . '">' . $css . '</style>';
	}

	/**
	 * Editor-only notice when the theme has no skin
	 *
	 * @param array $config Layout config
	 *
	 * @return string
	 */
	private static function notice( array $config ) {
		if ( $config['skin'] && SkinManager::locate( $config['skin'], 'template' ) ) {
			return '';
		}

		if ( !current_user_can( 'edit_theme_options' ) ) {
			return '';
		}

		return '<p class="vlt-gb__notice">' . esc_html__( 'Grid Builder: no skin found. Add one to your theme in vlthemes-toolkit/grid-builder/skins/{Name}/skin.php.', 'toolkit' ) . '</p>';
	}
}
