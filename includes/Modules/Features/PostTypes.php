<?php

namespace VLT\Helper\Modules\Features;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Types Module
 *
 * Registers custom post types and taxonomies
 * Includes Slide post type with slide_category taxonomy
 */
class PostTypes extends BaseModule {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'post_types';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register() {
		// Register custom post types
		add_action( 'init', [ $this, 'register_post_types' ], 0 );

		// Register custom taxonomies
		add_action( 'init', [ $this, 'register_taxonomies' ], 0 );
	}

	/**
	 * Register custom post types
	 */
	public function register_post_types() {
		// Register Slide post type
		$this->register_slide_post_type();

		// Allow themes to register additional post types
		do_action( 'vlt_helper_register_post_types' );
	}

	/**
	 * Register custom taxonomies
	 */
	public function register_taxonomies() {
		// Register Slide Category taxonomy
		$this->register_slide_category_taxonomy();

		// Allow themes to register additional taxonomies
		do_action( 'vlt_helper_register_taxonomies' );
	}

	/**
	 * Register Slide post type
	 */
	private function register_slide_post_type() {
		$labels = [
			'name'               => esc_html__( 'Slides', 'vlt-helper' ),
			'singular_name'      => esc_html__( 'Slide', 'vlt-helper' ),
			'add_new'            => esc_html__( 'Add New Slide', 'vlt-helper' ),
			'add_new_item'       => esc_html__( 'Add New Slide', 'vlt-helper' ),
			'edit_item'          => esc_html__( 'Edit Slide', 'vlt-helper' ),
			'new_item'           => esc_html__( 'New Slide', 'vlt-helper' ),
			'view_item'          => esc_html__( 'View Slide', 'vlt-helper' ),
			'search_items'       => esc_html__( 'Search Slides', 'vlt-helper' ),
			'not_found'          => esc_html__( 'No Slide Found', 'vlt-helper' ),
			'not_found_in_trash' => esc_html__( 'No slide found in Trash', 'vlt-helper' ),
		];

		$args = [
			'labels'              => $labels,
			'supports'            => [ 'title', 'editor', 'elementor' ],
			'hierarchical'        => false,
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-images-alt2',
			'show_in_admin_bar'   => true,
			'show_in_nav_menus'   => true,
			'can_export'          => true,
			'has_archive'         => false,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'rewrite'             => false,
			'capability_type'     => 'page',
		];

		// Allow themes to modify slide post type args
		$args = apply_filters( 'vlt_helper_slide_post_type_args', $args );

		register_post_type( 'slide', $args );
	}

	/**
	 * Register Slide Category taxonomy
	 */
	private function register_slide_category_taxonomy() {
		$labels = [
			'name'                       => _x( 'Slide Categories', 'Taxonomy General Name', 'vlt-helper' ),
			'singular_name'              => _x( 'Slide Category', 'Taxonomy Singular Name', 'vlt-helper' ),
			'menu_name'                  => esc_html__( 'Slide Category', 'vlt-helper' ),
			'all_items'                  => esc_html__( 'All Item Categories', 'vlt-helper' ),
			'parent_item'                => esc_html__( 'Parent Item', 'vlt-helper' ),
			'parent_item_colon'          => esc_html__( 'Parent Item:', 'vlt-helper' ),
			'new_item_name'              => esc_html__( 'New Item Category', 'vlt-helper' ),
			'add_new_item'               => esc_html__( 'Add New Item', 'vlt-helper' ),
			'edit_item'                  => esc_html__( 'Edit Item', 'vlt-helper' ),
			'update_item'                => esc_html__( 'Update Item', 'vlt-helper' ),
			'view_item'                  => esc_html__( 'View Item', 'vlt-helper' ),
			'separate_items_with_commas' => esc_html__( 'Separate items with commas', 'vlt-helper' ),
			'add_or_remove_items'        => esc_html__( 'Add or remove items', 'vlt-helper' ),
			'choose_from_most_used'      => esc_html__( 'Choose from the most used', 'vlt-helper' ),
			'popular_items'              => esc_html__( 'Popular Items', 'vlt-helper' ),
			'search_items'               => esc_html__( 'Search Items', 'vlt-helper' ),
			'not_found'                  => esc_html__( 'Not Found', 'vlt-helper' ),
			'no_terms'                   => esc_html__( 'No items', 'vlt-helper' ),
			'items_list'                 => esc_html__( 'Items list', 'vlt-helper' ),
			'items_list_navigation'      => esc_html__( 'Items list navigation', 'vlt-helper' ),
		];

		$args = [
			'labels'            => $labels,
			'hierarchical'      => true,
			'public'            => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'show_in_nav_menus' => true,
			'show_tagcloud'     => true,
		];

		// Allow themes to modify slide category taxonomy args
		$args = apply_filters( 'vlt_helper_slide_category_taxonomy_args', $args );

		register_taxonomy( 'slide_category', [ 'slide' ], $args );
	}
}