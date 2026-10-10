<?php

namespace VLT\Toolkit\GridBuilder;

use VLT\Toolkit\GridBuilder\Layouts\LayoutRegistry;
use VLT\Toolkit\GridBuilder\Sources\SourceRegistry;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layout configuration
 *
 * Stored as JSON in the _vlt_grid_config meta of a vlt_grid post. sanitize() is a whitelist that runs
 * on save and on every load, so stored data is always complete and valid — values from an older
 * format or a removed source/skin fall back to defaults instead of reaching queries or file paths.
 */
class Config {
	/**
	 * Loaded configs per layout ID (false = missing / not readable)
	 *
	 * @var array
	 */
	private static $cache = [];

	/**
	 * Default configuration
	 *
	 * @return array
	 */
	public static function defaults() {
		$layout = [ 'type' => 'grid' ];

		foreach ( LayoutRegistry::all() as $id => $layout_type ) {
			$layout[ $id ] = $layout_type->defaults();
		}

		return [
			'query'      => [
				'source'       => GridBuilder::portfolio() ?: 'post',
				'per_page'     => 9,
				'orderby'      => 'date',
				'order'        => 'DESC',
				'offset'       => 0,
				'include'      => [],
				'exclude'      => [],
				'taxonomies'   => [],
				'tax_relation' => 'OR',
				'search'       => '',
				'images'       => [],
				'image_data'   => [],
			],
			'layout'     => $layout,
			'responsive' => [
				'columns' => [
					'desktop' => 3,
					'tablet'  => 2,
					'mobile'  => 1,
				],
				'gap_x'   => [
					'desktop' => 24,
					'tablet'  => 24,
					'mobile'  => 24,
				],
				'gap_y'   => [
					'desktop' => 24,
					'tablet'  => 24,
					'mobile'  => 24,
				],
			],
			'filters'    => [
				'enabled'    => false,
				'taxonomy'   => '',
				'template'   => '',
				'align'      => 'left',
				'all_label'  => '',
				'show_count' => false,
			],
			'pagination' => [
				'type'            => 'none',
				'template'        => '',
				'align'           => 'center',
				'load_more_label' => '',
				'loading_label'   => '',
				'no_more_label'   => '',
			],
			'display'    => [
				'attach_controls' => false,
			],
			// Template Parts (vlt_tp) placed between the items: [ [ 'template', 'position', 'every' ], … ]
			'inserts'    => [],
			'animation'  => [
				'effect'   => 'fade-up',
				'duration' => 500,
				'stagger'  => 60,
			],
			// Skins (card templates) come from the theme, see SkinManager
			'skin'       => (string) array_key_first( SkinManager::all() ),
		];
	}

	/**
	 * Get a layout's configuration
	 *
	 * Published layouts are public; drafts/private ones only for users who can edit them.
	 *
	 * @param int $layout_id vlt_grid post ID
	 *
	 * @return array|null Config with 'id' and 'custom_css' added, or null
	 */
	public static function get( $layout_id ) {
		$layout_id = absint( $layout_id );

		if ( !isset( self::$cache[ $layout_id ] ) ) {
			self::$cache[ $layout_id ] = false;
			$post                      = $layout_id ? get_post( $layout_id ) : null;

			if ( $post && PostTypes::LAYOUT === $post->post_type
				&& ( 'publish' === $post->post_status || current_user_can( 'edit_post', $layout_id ) ) ) {
				$config               = self::sanitize( get_post_meta( $layout_id, PostTypes::META_CONFIG, true ) );
				$config['id']         = $layout_id;
				$config['custom_css'] = (string) get_post_meta( $layout_id, PostTypes::META_CSS, true );

				self::$cache[ $layout_id ] = $config;
			}
		}

		return self::$cache[ $layout_id ] ?: null;
	}

	/**
	 * Meta sanitize callback
	 *
	 * @param mixed $value JSON string from the editor
	 *
	 * @return string Normalised JSON
	 */
	public static function sanitize_meta( $value ) {
		return wp_json_encode( self::sanitize( $value ) );
	}

	/**
	 * Validate a configuration against the defaults and the registered sources, layouts and skins
	 *
	 * @param mixed $raw JSON string or array
	 *
	 * @return array
	 */
	public static function sanitize( $raw ) {
		if ( is_string( $raw ) ) {
			$raw = json_decode( $raw, true );
		}

		$raw      = is_array( $raw ) ? $raw : [];
		$defaults = self::defaults();
		$config   = [];

		// Query
		$query   = self::section( $raw, 'query' );
		$sources = SourceRegistry::all();
		$source  = isset( $sources[ $query['source'] ?? '' ] ) ? $query['source'] : $defaults['query']['source'];
		$source  = isset( $sources[ $source ] ) ? $source : (string) array_key_first( $sources );
		$object  = $sources[ $source ] ?? null;

		$taxonomies = $object ? $object->get_taxonomies() : [];
		$tax_terms  = [];

		foreach ( (array) ( $query['taxonomies'] ?? [] ) as $taxonomy => $terms ) {
			$terms = self::ids( $terms );

			if ( isset( $taxonomies[ $taxonomy ] ) && $terms ) {
				$tax_terms[ $taxonomy ] = $terms;
			}
		}

		$config['query'] = [
			'source'       => $source,
			// -1 = all items (no pagination then)
			'per_page'     => -1 === (int) ( $query['per_page'] ?? 0 ) ? -1 : self::int( $query['per_page'] ?? null, 1, 100, $defaults['query']['per_page'] ),
			'orderby'      => self::choice( $query['orderby'] ?? '', $object ? array_keys( $object->get_orderby_options() ) : [ 'date' ], $defaults['query']['orderby'] ),
			'order'        => self::choice( $query['order'] ?? '', [ 'ASC', 'DESC' ], 'DESC' ),
			'offset'       => self::int( $query['offset'] ?? null, 0, 1000, 0 ),
			'include'      => array_slice( self::ids( $query['include'] ?? [] ), 0, 200 ),
			'exclude'      => array_slice( self::ids( $query['exclude'] ?? [] ), 0, 200 ),
			'taxonomies'   => $tax_terms,
			'tax_relation' => self::choice( $query['tax_relation'] ?? '', [ 'AND', 'OR' ], 'OR' ),
			'search'       => mb_substr( sanitize_text_field( (string) ( $query['search'] ?? '' ) ), 0, 100 ),
			'images'       => array_slice( self::ids( $query['images'] ?? [] ), 0, 500 ),
		];

		$config['query']['image_data'] = self::image_data( $query['image_data'] ?? [], $config['query']['images'] );

		// Layout: every type keeps its own settings, so switching type and back doesn't lose them
		$layout           = self::section( $raw, 'layout' );
		$layouts          = LayoutRegistry::all();
		$config['layout'] = [ 'type' => self::choice( $layout['type'] ?? '', array_keys( $layouts ), 'grid' ) ];

		foreach ( $layouts as $id => $layout_type ) {
			$config['layout'][ $id ] = $layout_type->sanitize( is_array( $layout[ $id ] ?? null ) ? $layout[ $id ] : [] );
		}

		// Responsive
		$responsive = self::section( $raw, 'responsive' );
		$columns    = is_array( $responsive['columns'] ?? null ) ? $responsive['columns'] : [];

		$config['responsive'] = [
			'columns' => [
				'desktop' => self::int( $columns['desktop'] ?? null, 1, 12, 3 ),
				'tablet'  => self::int( $columns['tablet'] ?? null, 1, 12, 2 ),
				'mobile'  => self::int( $columns['mobile'] ?? null, 1, 12, 1 ),
			],
			'gap_x'   => self::gaps( $responsive['gap_x'] ?? null ),
			'gap_y'   => self::gaps( $responsive['gap_y'] ?? null ),
		];

		// Filters: only a taxonomy the selected source really has
		$filters  = self::section( $raw, 'filters' );
		$taxonomy = isset( $taxonomies[ $filters['taxonomy'] ?? '' ] ) ? $filters['taxonomy'] : '';

		$config['filters'] = [
			'enabled'    => $taxonomy && !empty( $filters['enabled'] ),
			'taxonomy'   => $taxonomy,
			// Style: '' = the built-in neutral buttons, or a theme filter template (filters/{Name}/filter.php)
			'template'   => self::choice( $filters['template'] ?? '', array_keys( SkinManager::all( 'filters' ) ), '' ),
			// Alignment of the built-in style (theme templates lay themselves out)
			'align'      => self::choice( $filters['align'] ?? '', [ 'left', 'center', 'right' ], 'left' ),
			'all_label'  => sanitize_text_field( (string) ( $filters['all_label'] ?? '' ) ),
			'show_count' => !empty( $filters['show_count'] ),
		];

		// Pagination
		$pagination = self::section( $raw, 'pagination' );

		$config['pagination'] = [
			'type'            => self::choice( $pagination['type'] ?? '', [ 'none', 'numbers', 'loadmore' ], 'none' ),
			// Theme pagination template (pagination/{Name}/pagination.php); '' = built-in markup
			'template'        => self::choice( $pagination['template'] ?? '', array_keys( SkinManager::all( 'pagination' ) ), '' ),
			'align'           => self::choice( $pagination['align'] ?? '', [ 'left', 'center', 'right' ], 'center' ),
			'load_more_label' => sanitize_text_field( (string) ( $pagination['load_more_label'] ?? '' ) ),
			'loading_label'   => sanitize_text_field( (string) ( $pagination['loading_label'] ?? '' ) ),
			'no_more_label'   => sanitize_text_field( (string) ( $pagination['no_more_label'] ?? '' ) ),
		];

		// Inserts: real Template Parts only, at most 10; position = before item N (1-based), every = repeat (0 = once)
		$config['inserts'] = [];

		foreach ( array_slice( (array) ( $raw['inserts'] ?? [] ), 0, 10 ) as $insert ) {
			$template = absint( $insert['template'] ?? 0 );

			if ( $template && 'vlt_tp' === get_post_type( $template ) ) {
				$config['inserts'][] = [
					'template' => $template,
					'position' => self::int( $insert['position'] ?? null, 1, 1000, 1 ),
					'every'    => self::int( $insert['every'] ?? null, 0, 1000, 0 ),
				];
			}
		}

		// Filter and pagination printed by the grid shortcode itself (instead of their own shortcodes)
		$config['display'] = [ 'attach_controls' => !empty( self::section( $raw, 'display' )['attach_controls'] ) ];

		// Entrance animation: a registered preset or none
		$animation = self::section( $raw, 'animation' );

		$config['animation'] = [
			'effect'   => self::choice( $animation['effect'] ?? '', array_merge( [ 'none' ], array_keys( Animations::presets() ) ), $defaults['animation']['effect'] ),
			'duration' => self::int( $animation['duration'] ?? null, 100, 2000, 500 ),
			'stagger'  => self::int( $animation['stagger'] ?? null, 0, 300, 60 ),
		];

		// Skin: only a registered id — never used as a path itself
		$config['skin'] = self::choice( $raw['skin'] ?? '', array_keys( SkinManager::all() ), $defaults['skin'] );

		return apply_filters( 'vlt_toolkit_grid_config', $config, $raw );
	}

	/**
	 * Gap per device (px); an older single number applies to every device, a missing device takes the wider one's
	 *
	 * @param mixed $value Raw value
	 *
	 * @return array [ 'desktop', 'tablet', 'mobile' ]
	 */
	private static function gaps( $value ) {
		$value = is_array( $value ) ? $value : [ 'desktop' => $value ];
		$gaps  = [];
		$wider = 24;

		foreach ( [ 'desktop', 'tablet', 'mobile' ] as $device ) {
			$gaps[ $device ] = self::int( $value[ $device ] ?? null, 0, 200, $wider );
			$wider           = $gaps[ $device ];
		}

		return $gaps;
	}

	/**
	 * Get an array section
	 *
	 * @param array  $raw Raw config
	 * @param string $key Section
	 *
	 * @return array
	 */
	private static function section( $raw, $key ) {
		return is_array( $raw[ $key ] ?? null ) ? $raw[ $key ] : [];
	}

	/**
	 * Clamp an integer
	 *
	 * @param mixed $value   Value
	 * @param int   $min     Min
	 * @param int   $max     Max
	 * @param int   $default Default when not numeric
	 *
	 * @return int
	 */
	private static function int( $value, $min, $max, $default ) {
		return is_numeric( $value ) ? max( $min, min( $max, (int) $value ) ) : $default;
	}

	/**
	 * Value from a whitelist
	 *
	 * @param mixed  $value   Value
	 * @param array  $allowed Allowed values
	 * @param string $default Default
	 *
	 * @return string
	 */
	private static function choice( $value, $allowed, $default ) {
		return in_array( $value, $allowed, true ) ? $value : $default;
	}

	/**
	 * Per-image overrides of the Media source, only for selected images
	 *
	 * @param mixed $data   Raw [ attachment ID => [ 'title', 'caption', 'link', 'labels' ] ]
	 * @param array $images Selected image IDs
	 *
	 * @return array Entries without empty fields; images without overrides are left out
	 */
	private static function image_data( $data, array $images ) {
		$clean = [];

		foreach ( $images as $id ) {
			$entry = is_array( $data[ $id ] ?? null ) ? $data[ $id ] : [];
			$item  = array_filter( [
				'title'   => mb_substr( sanitize_text_field( (string) ( $entry['title'] ?? '' ) ), 0, 200 ),
				'caption' => mb_substr( sanitize_textarea_field( (string) ( $entry['caption'] ?? '' ) ), 0, 1000 ),
				'link'    => esc_url_raw( trim( (string) ( $entry['link'] ?? '' ) ) ),
				'labels'  => self::labels( $entry['labels'] ?? [] ),
			] );

			if ( $item ) {
				$clean[ $id ] = $item;
			}
		}

		return $clean;
	}

	/**
	 * Image labels: plain text, unique ignoring case, at most 10 of 50 characters
	 *
	 * @param mixed $labels Raw labels
	 *
	 * @return array
	 */
	private static function labels( $labels ) {
		$clean = [];

		foreach ( (array) $labels as $label ) {
			$label = mb_substr( trim( sanitize_text_field( (string) $label ) ), 0, 50 );

			if ( '' !== $label ) {
				$clean[ mb_strtolower( $label ) ] ??= $label;
			}
		}

		return array_slice( array_values( $clean ), 0, 10 );
	}

	/**
	 * Unique positive integer IDs
	 *
	 * @param mixed $ids Value
	 *
	 * @return array
	 */
	private static function ids( $ids ) {
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $ids ) ) ) );
	}
}
