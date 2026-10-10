<?php

namespace VLT\Toolkit\GridBuilder;

use VLT\Toolkit\GridBuilder\Sources\SourceRegistry;
use VLT\Toolkit\GridBuilder\Sources\VirtualTermsInterface;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Query Builder
 *
 * Runs a layout's query for one page — the same code for the first render and for AJAX.
 *
 * Offset rule: query.offset skips the first N items of the whole result, once. Pages are counted
 * from what is left: page P shows items offset + (P − 1) × per_page …, and the total excludes the skipped ones.
 */
class QueryBuilder {
	/**
	 * Run the query
	 *
	 * @param array $config Layout config
	 * @param int   $page   Page (1-based), forced to 1 without pagination
	 * @param int   $term   Front-end filter term ID (0 = all); validate with is_valid_term() first
	 * @param int   $seed   Random order seed
	 *
	 * @return array [ 'items', 'source', 'page', 'max_pages', 'total', 'start' ]
	 */
	public static function run( array $config, $page = 1, $term = 0, $seed = 0 ) {
		$source    = SourceRegistry::get( $config['query']['source'] );
		$per_page  = $config['query']['per_page'];
		$offset    = $config['query']['offset'];
		$all       = -1 === $per_page;
		$paginated = !$all && 'none' !== $config['pagination']['type'];
		$page      = $paginated ? max( 1, (int) $page ) : 1;
		$start     = $all ? 0 : ( $page - 1 ) * $per_page;

		$args = [
			'limit'  => $per_page, // -1 = no limit
			'skip'   => $offset + $start,
			'filter' => $term ? [
				'taxonomy' => $config['filters']['taxonomy'],
				'term'     => (int) $term,
			] : null,
			'seed'   => (int) $seed,
			'count'  => $paginated,
		];

		$result = $source ? $source->fetch( $config['query'], $args ) : [
			'items' => [],
			'total' => 0,
		];

		// A page past the end returns no rows, and WP_Query skips counting then — count separately
		if ( $source && $paginated && !$result['items'] && $start > 0 ) {
			$result['total'] = $source->fetch( $config['query'], [ 'limit' => 1, 'skip' => 0 ] + $args )['total'];
		}

		$total = $paginated ? max( 0, $result['total'] - $offset ) : count( $result['items'] );

		return [
			'items'     => $result['items'],
			'source'    => $source,
			'page'      => $page,
			'max_pages' => $paginated ? max( 1, (int) ceil( $total / $per_page ) ) : 1,
			'total'     => $total,
			'start'     => $start,
		];
	}

	/**
	 * Terms shown in the front-end filter
	 *
	 * The configured query terms of the filter taxonomy limit the list (with their children); otherwise all non-empty terms.
	 *
	 * @param array $config Layout config
	 *
	 * @return \WP_Term[]
	 */
	public static function filter_terms( array $config ) {
		if ( !$config['filters']['enabled'] ) {
			return [];
		}

		$virtual = self::virtual_terms( $config );

		if ( null !== $virtual ) {
			return $virtual;
		}

		$taxonomy = $config['filters']['taxonomy'];
		$args     = [
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		];

		$allowed = self::allowed_terms( $config );

		if ( null !== $allowed ) {
			$args['include'] = $allowed ?: [ 0 ];
		}

		$terms = get_terms( apply_filters( 'vlt_toolkit_grid_filter_terms_args', $args, $config ) );

		return is_array( $terms ) ? $terms : [];
	}

	/**
	 * Whether a term may be used as the front-end filter of this layout
	 *
	 * @param array $config Layout config
	 * @param int   $term   Term ID
	 *
	 * @return bool
	 */
	public static function is_valid_term( array $config, $term ) {
		$term = (int) $term;

		if ( !$config['filters']['enabled'] || $term < 1 ) {
			return false;
		}

		$virtual = self::virtual_terms( $config );

		if ( null !== $virtual ) {
			return in_array( $term, array_map( 'intval', wp_list_pluck( $virtual, 'term_id' ) ), true );
		}

		if ( !term_exists( $term, $config['filters']['taxonomy'] ) ) {
			return false;
		}

		$allowed = self::allowed_terms( $config );

		return null === $allowed || in_array( $term, $allowed, true );
	}

	/**
	 * Terms of the filter taxonomy when the source keeps them itself (VirtualTermsInterface)
	 *
	 * @param array $config Layout config
	 *
	 * @return \WP_Term[]|null Null for a WordPress taxonomy
	 */
	private static function virtual_terms( array $config ) {
		$source = SourceRegistry::get( $config['query']['source'] );

		return $source instanceof VirtualTermsInterface ? $source->get_virtual_terms( $config['query'], $config['filters']['taxonomy'] ) : null;
	}

	/**
	 * Terms of the filter taxonomy the query is restricted to, with their descendants
	 *
	 * @param array $config Layout config
	 *
	 * @return array|null Term IDs, or null when the query doesn't restrict this taxonomy
	 */
	private static function allowed_terms( array $config ) {
		$taxonomy = $config['filters']['taxonomy'];

		if ( empty( $config['query']['taxonomies'][ $taxonomy ] ) ) {
			return null;
		}

		$allowed = $config['query']['taxonomies'][ $taxonomy ];

		foreach ( $config['query']['taxonomies'][ $taxonomy ] as $term_id ) {
			$children = get_term_children( $term_id, $taxonomy );
			$allowed  = array_merge( $allowed, is_array( $children ) ? $children : [] );
		}

		return array_values( array_unique( array_map( 'intval', $allowed ) ) );
	}
}
