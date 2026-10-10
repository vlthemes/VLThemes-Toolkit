<?php

namespace VLT\Toolkit\GridBuilder\Sources;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid data source
 *
 * A source turns the "query" part of a layout config into normalised items, so the renderer
 * never has to know whether it shows posts, attachments or anything else.
 *
 * Item shape: [
 *     'id'       => int,
 *     'type'     => string  source-specific kind ('post', 'image', …),
 *     'title'    => string  plain text,
 *     'url'      => string  link target ('' = no link),
 *     'image_id' => int     attachment ID (0 = none),
 *     'excerpt'  => string  plain text,
 *     'date'     => string  formatted date ('' = none),
 *     'object'   => mixed   original object (WP_Post, …) for custom skins,
 * ]
 *
 * Register more sources with the 'vlt_toolkit_grid_sources' filter.
 */
interface SourceInterface {
	/**
	 * Unique ID (stored in configs)
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
	 * Taxonomies usable for query restrictions and filters
	 *
	 * @return array [ slug => label ]
	 */
	public function get_taxonomies();

	/**
	 * Supported sort orders
	 *
	 * @return array [ value => label ]
	 */
	public function get_orderby_options();

	/**
	 * Fetch items
	 *
	 * @param array $query Sanitised "query" config section
	 * @param array $args  {
	 *     @type int        $limit  Items to return
	 *     @type int        $skip   Items to skip from the start of the full result
	 *     @type array|null $filter [ 'taxonomy' => string, 'term' => int ] from the front-end filter
	 *     @type int        $seed   Seed for random order, stable across pages
	 *     @type bool       $count  Whether the total is needed (pagination)
	 * }
	 *
	 * @return array [ 'items' => array, 'total' => int matching items, ignoring $skip ]
	 */
	public function fetch( array $query, array $args );

	/**
	 * Terms of an item in a taxonomy (for card labels)
	 *
	 * @param array  $item     Normalised item
	 * @param string $taxonomy Taxonomy
	 *
	 * @return \WP_Term[]
	 */
	public function get_item_terms( array $item, $taxonomy );
}
