<?php

namespace VLT\Toolkit\GridBuilder\Sources;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Images picked from the Media Library
 *
 * Works on the explicit list in query.images (chosen in the editor), not on the whole library:
 * attachments of private posts are only shown when an editor put them in the grid on purpose.
 * Items link to the full-size image; the caption is the excerpt.
 *
 * query.image_data overrides that per image, inside the layout only (the Media Library is untouched):
 * [ attachment ID => [ 'title', 'caption', 'link', 'labels' => [ names ] ] ], '' = the attachment's own value.
 * Labels form the virtual taxonomy LABELS: filter buttons and card terms, no WordPress taxonomy behind them.
 */
class MediaSource implements SourceInterface, VirtualTermsInterface {
	/**
	 * Virtual taxonomy of the per-image labels
	 */
	const LABELS = 'vlt_gb_label';

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return 'media';
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return __( 'Media Library images', 'toolkit' );
	}

	/**
	 * Get taxonomies: the labels given to images in the layout
	 *
	 * @return array
	 */
	public function get_taxonomies() {
		return [ self::LABELS => __( 'Categories', 'toolkit' ) ];
	}

	/**
	 * Labels used in the layout as terms, in the order they first appear in the selection
	 *
	 * ID = 1-based position in that list, stable for one config; count = images with the label.
	 *
	 * @param array  $query    Query config
	 * @param string $taxonomy Taxonomy
	 *
	 * @return \WP_Term[]|null
	 */
	public function get_virtual_terms( array $query, $taxonomy ) {
		if ( self::LABELS !== $taxonomy ) {
			return null;
		}

		$terms = [];

		foreach ( $query['images'] as $image_id ) {
			foreach ( $query['image_data'][ $image_id ]['labels'] ?? [] as $name ) {
				$key = mb_strtolower( $name );

				if ( !isset( $terms[ $key ] ) ) {
					$terms[ $key ] = new \WP_Term( (object) [
						'term_id'  => count( $terms ) + 1,
						'name'     => $name,
						'slug'     => sanitize_title( $name ),
						'taxonomy' => self::LABELS,
						'count'    => 0,
					] );
				}

				++$terms[ $key ]->count;
			}
		}

		return array_values( $terms );
	}

	/**
	 * Get orderby options
	 *
	 * @return array
	 */
	public function get_orderby_options() {
		return [
			'post__in' => __( 'Selection order', 'toolkit' ),
			'date'     => __( 'Upload date', 'toolkit' ),
			'title'    => __( 'Title', 'toolkit' ),
			'rand'     => __( 'Random', 'toolkit' ),
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
		$terms  = $this->get_virtual_terms( $query, self::LABELS );
		$images = $query['images'];

		// Front-end filter: the images carrying that label
		if ( $args['filter'] && self::LABELS === $args['filter']['taxonomy'] ) {
			$term   = wp_list_filter( $terms, [ 'term_id' => (int) $args['filter']['term'] ] );
			$term   = $term ? mb_strtolower( reset( $term )->name ) : null;
			$images = array_values( array_filter( $images, fn( $id ) => null !== $term && in_array( $term, array_map( 'mb_strtolower', $query['image_data'][ $id ]['labels'] ?? [] ), true ) ) );
		}

		if ( !$images ) {
			return [
				'items' => [],
				'total' => 0,
			];
		}

		$query_args = [
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'post_mime_type' => 'image',
			'post__in'       => $images,
			'posts_per_page' => self::limit( $args ),
			'offset'         => $args['skip'],
			'order'          => $query['order'],
			'orderby'        => 'rand' === $query['orderby'] ? 'RAND(' . (int) $args['seed'] . ')' : $query['orderby'],
			'no_found_rows'  => !$args['count'],
		];

		if ( '' !== $query['search'] ) {
			$query_args['s'] = $query['search'];
		}

		$query_args = apply_filters( 'vlt_toolkit_grid_query_args', $query_args, $query, $args, $this->get_id() );
		$wp_query   = new \WP_Query( $query_args );

		return [
			'items' => array_map( fn( $attachment ) => $this->normalize( $attachment, $query['image_data'][ $attachment->ID ] ?? [], $terms ), $wp_query->posts ),
			'total' => $args['count'] ? (int) $wp_query->found_posts : count( $wp_query->posts ),
		];
	}

	/**
	 * Normalise an attachment
	 *
	 * @param \WP_Post   $attachment Attachment
	 * @param array      $data       Overrides of this layout (query.image_data entry)
	 * @param \WP_Term[] $terms      Labels of the layout (get_virtual_terms())
	 *
	 * @return array
	 */
	public function normalize( $attachment, array $data = [], array $terms = [] ) {
		$labels = array_map( 'mb_strtolower', $data['labels'] ?? [] );

		return [
			'id'       => $attachment->ID,
			'type'     => 'image',
			'title'    => ( $data['title'] ?? '' ) ?: get_the_title( $attachment ),
			'url'      => ( $data['link'] ?? '' ) ?: (string) wp_get_attachment_url( $attachment->ID ),
			'image_id' => $attachment->ID,
			'excerpt'  => ( $data['caption'] ?? '' ) ?: wp_strip_all_tags( (string) wp_get_attachment_caption( $attachment->ID ) ),
			'date'     => get_the_date( '', $attachment ),
			'object'   => $attachment,
			'labels'   => array_values( array_filter( $terms, fn( $term ) => in_array( mb_strtolower( $term->name ), $labels, true ) ) ),
		];
	}

	/**
	 * Labels of an image
	 *
	 * @param array  $item     Item
	 * @param string $taxonomy Taxonomy
	 *
	 * @return \WP_Term[]
	 */
	public function get_item_terms( array $item, $taxonomy ) {
		return self::LABELS === $taxonomy ? $item['labels'] ?? [] : [];
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
