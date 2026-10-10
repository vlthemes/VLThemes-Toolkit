<?php

namespace VLT\Toolkit\Portfolio;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Portfolio: the vlt_portfolio post type with its own categories and tags (not the blog ones)
 *
 * Content only — how it's shown is up to the theme (single / archive templates) and Grid Builder, which works
 * without it too. A theme with its own portfolio turns it off: add_filter( 'vlt_toolkit_portfolio_enabled', '__return_false' ).
 *
 * Slugs avoid "portfolio": Visual Portfolio registers that post type and the two would collide.
 * Visual Portfolio items are converted with Migrations\VisualPortfolio.
 */
class Portfolio {
	const POST_TYPE = 'vlt_portfolio';
	const CATEGORY  = 'vlt_portfolio_category';
	const TAG       = 'vlt_portfolio_tag';

	/**
	 * Bump to flush rewrite rules once after the slugs change
	 */
	const REWRITE_VERSION = '1';

	/**
	 * Instance
	 *
	 * @var Portfolio|null
	 */
	private static $instance = null;

	/**
	 * Get instance
	 *
	 * @return Portfolio
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Whether the portfolio is on (default yes)
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) apply_filters( 'vlt_toolkit_portfolio_enabled', true );
	}

	/**
	 * Admin menu slug of the portfolio
	 *
	 * @return string
	 */
	public static function menu() {
		return 'edit.php?post_type=' . self::POST_TYPE;
	}

	/**
	 * Constructor
	 */
	private function __construct() {
		if ( !self::enabled() ) {
			return;
		}

		add_action( 'init', [ __CLASS__, 'register' ] );
		Migrations\VisualPortfolio::init();
	}

	/**
	 * Register the post type and taxonomies
	 */
	public static function register() {
		register_post_type( self::POST_TYPE, [
			'labels'        => [
				'name'               => esc_html__( 'Portfolio', 'toolkit' ),
				'singular_name'      => esc_html__( 'Portfolio Item', 'toolkit' ),
				'menu_name'          => esc_html__( 'Portfolio', 'toolkit' ),
				'all_items'          => esc_html__( 'Portfolio Items', 'toolkit' ),
				'add_new'            => esc_html__( 'Add New', 'toolkit' ),
				'add_new_item'       => esc_html__( 'Add New Portfolio Item', 'toolkit' ),
				'edit_item'          => esc_html__( 'Edit Portfolio Item', 'toolkit' ),
				'new_item'           => esc_html__( 'New Portfolio Item', 'toolkit' ),
				'view_item'          => esc_html__( 'View Portfolio Item', 'toolkit' ),
				'search_items'       => esc_html__( 'Search Portfolio Items', 'toolkit' ),
				'not_found'          => esc_html__( 'No portfolio items found', 'toolkit' ),
				'not_found_in_trash' => esc_html__( 'No portfolio items found in trash', 'toolkit' ),
			],
			'public'        => true,
			'show_in_rest'  => true,
			'has_archive'   => true,
			'menu_icon'     => 'dashicons-portfolio',
			'menu_position' => 21,
			'supports'      => [ 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes', 'revisions', 'custom-fields' ],
			'rewrite'       => [ 'slug' => apply_filters( 'vlt_toolkit_portfolio_slug', 'portfolio-item' ) ],
		] );

		register_taxonomy( self::CATEGORY, self::POST_TYPE, [
			'labels'            => [
				'name'          => esc_html__( 'Portfolio Categories', 'toolkit' ),
				'singular_name' => esc_html__( 'Portfolio Category', 'toolkit' ),
				'menu_name'     => esc_html__( 'Categories', 'toolkit' ),
			],
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => apply_filters( 'vlt_toolkit_portfolio_category_slug', 'portfolio-category' ) ],
		] );

		register_taxonomy( self::TAG, self::POST_TYPE, [
			'labels'            => [
				'name'          => esc_html__( 'Portfolio Tags', 'toolkit' ),
				'singular_name' => esc_html__( 'Portfolio Tag', 'toolkit' ),
				'menu_name'     => esc_html__( 'Tags', 'toolkit' ),
			],
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => [ 'slug' => apply_filters( 'vlt_toolkit_portfolio_tag_slug', 'portfolio-tag' ) ],
		] );

		if ( get_option( 'vlt_toolkit_portfolio_rewrite' ) !== self::REWRITE_VERSION ) {
			flush_rewrite_rules( false );
			update_option( 'vlt_toolkit_portfolio_rewrite', self::REWRITE_VERSION );
		}
	}
}
