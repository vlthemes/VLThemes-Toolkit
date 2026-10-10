<?php

namespace VLT\Toolkit\GridBuilder\Sources;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Posts of one public post type (post, page, portfolio, any public CPT)
 *
 * Only published posts are ever queried, so drafts and private posts can't leak through a grid;
 * password-protected posts are listed without their excerpt.
 */
class PostTypeSource implements SourceInterface {
	/**
	 * Post type object
	 *
	 * @var \WP_Post_Type
	 */
	private $post_type;

	/**
	 * Constructor
	 *
	 * @param \WP_Post_Type $post_type Post type
	 */
	public function __construct( \WP_Post_Type $post_type ) {
		$this->post_type = $post_type;
	}

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->post_type->name;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->post_type->labels->name;
	}

	/**
	 * Get taxonomies
	 *
	 * @return array
	 */
	public function get_taxonomies() {
		$taxonomies = [];

		foreach ( get_object_taxonomies( $this->post_type->name, 'objects' ) as $taxonomy ) {
			if ( $taxonomy->public && $taxonomy->show_ui && 'post_format' !== $taxonomy->name ) {
				$taxonomies[ $taxonomy->name ] = $taxonomy->labels->name;
			}
		}

		return (array) apply_filters( 'vlt_toolkit_grid_source_taxonomies', $taxonomies, $this->get_id() );
	}

	/**
	 * Get orderby options
	 *
	 * @return array
	 */
	public function get_orderby_options() {
		return [
			'date'          => __( 'Date', 'toolkit' ),
			'post__in'      => __( 'Selection order (specific items)', 'toolkit' ),
			'modified'      => __( 'Last modified', 'toolkit' ),
			'title'         => __( 'Title', 'toolkit' ),
			'ID'            => __( 'ID', 'toolkit' ),
			'menu_order'    => __( 'Menu order', 'toolkit' ),
			'comment_count' => __( 'Comment count', 'toolkit' ),
			'rand'          => __( 'Random', 'toolkit' ),
		];
	}

	/**
	 * Fetch items
	 *
	 * @param array $query Query config
	 * @param array $args  Runtime args
	 *
	 * @return array
	 */
	public function fetch( array $query, array $args ) {
		$query_args = [
			'post_type'           => $this->post_type->name,
			'post_status'         => 'publish',
			'posts_per_page'      => self::limit( $args ),
			'offset'              => $args['skip'],
			'order'               => $query['order'],
			// Seeded RAND() keeps random order stable across AJAX pages, so "Load More" doesn't repeat items
			'orderby'             => 'rand' === $query['orderby'] ? 'RAND(' . (int) $args['seed'] . ')' : $query['orderby'],
			'post__not_in'        => $query['exclude'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => !$args['count'],
		];

		// Specific items: only these (still narrowed by terms, exclusions and search). WP_Query ignores
		// post__not_in when post__in is set, so exclusions are taken out here; [ 0 ] = nothing left (an empty post__in means "no limit")
		if ( $query['include'] ) {
			$include = array_values( array_diff( $query['include'], $query['exclude'] ) );

			$query_args['post__in'] = $include ?: [ 0 ];
			unset( $query_args['post__not_in'] );
		}

		if ( '' !== $query['search'] ) {
			$query_args['s'] = $query['search'];
		}

		$tax_query = [];

		if ( $query['taxonomies'] ) {
			$configured = [ 'relation' => $query['tax_relation'] ];

			foreach ( $query['taxonomies'] as $taxonomy => $terms ) {
				$configured[] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $terms,
				];
			}

			$tax_query[] = $configured;
		}

		// The front-end filter narrows the configured set, it never widens it
		if ( $args['filter'] ) {
			$tax_query[] = [
				'taxonomy' => $args['filter']['taxonomy'],
				'field'    => 'term_id',
				'terms'    => [ $args['filter']['term'] ],
			];
		}

		if ( $tax_query ) {
			$query_args['tax_query'] = [ 'relation' => 'AND' ] + $tax_query; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		$query_args = apply_filters( 'vlt_toolkit_grid_query_args', $query_args, $query, $args, $this->get_id() );
		$wp_query   = new \WP_Query( $query_args );

		return [
			'items' => array_map( [ $this, 'normalize' ], $wp_query->posts ),
			'total' => $args['count'] ? (int) $wp_query->found_posts : count( $wp_query->posts ),
		];
	}

	/**
	 * Normalise a post
	 *
	 * @param \WP_Post $post Post
	 *
	 * @return array
	 */
	public function normalize( $post ) {
		return [
			'id'       => $post->ID,
			'type'     => 'post',
			'title'    => get_the_title( $post ),
			'url'      => get_permalink( $post ),
			'image_id' => (int) get_post_thumbnail_id( $post ),
			'excerpt'  => post_password_required( $post ) ? '' : wp_strip_all_tags( get_the_excerpt( $post ) ),
			'date'     => get_the_date( '', $post ),
			'object'   => $post,
		];
	}

	/**
	 * Get item terms
	 *
	 * @param array  $item     Item
	 * @param string $taxonomy Taxonomy
	 *
	 * @return array
	 */
	public function get_item_terms( array $item, $taxonomy ) {
		$terms = $taxonomy ? get_the_terms( $item['id'], $taxonomy ) : [];

		return is_array( $terms ) ? $terms : [];
	}

	/**
	 * posts_per_page for WP_Query: "all" with an offset needs a real LIMIT, WP_Query ignores offset with -1
	 *
	 * @param array $args Runtime args
	 *
	 * @return int
	 */
	private static function limit( array $args ) {
		return -1 === $args['limit'] && $args['skip'] > 0 ? PHP_INT_MAX : $args['limit'];
	}
}
