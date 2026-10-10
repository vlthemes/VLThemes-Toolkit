<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Views Module
 *
 * Counts views over AJAX (assets/js/feature-post-views.js), so pages served from a full-page cache count too:
 * once per browser session, editors of the post aren't counted.
 *
 * Filters:
 * - vlt_toolkit_post_views_post_types — post types with the counter (default: post)
 * - vlt_toolkit_post_views_meta_key   — meta key holding the count (default: views)
 */
class PostViews extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'post_views';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * AJAX action
	 *
	 * @var string
	 */
	const ACTION = 'vlt_toolkit_post_view';

	/**
	 * Admin column / orderby key
	 *
	 * @var string
	 */
	const COLUMN = 'vlt_views';

	/**
	 * Register module
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_ajax_' . self::ACTION, [ $this, 'ajax_view' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ $this, 'ajax_view' ] );

		// Admin: sortable "Views" column
		add_action( 'admin_init', [ $this, 'admin_columns' ] );
		add_action( 'pre_get_posts', [ $this, 'admin_orderby' ] );
	}

	/**
	 * Post types with the views counter
	 *
	 * @return array
	 */
	public static function get_post_types() {
		return (array) apply_filters( 'vlt_toolkit_post_views_post_types', [ 'post' ] );
	}

	/**
	 * Meta key holding the count
	 *
	 * @return string
	 */
	public static function get_meta_key() {
		return apply_filters( 'vlt_toolkit_post_views_meta_key', 'views' );
	}

	/**
	 * Enqueue the counter on singular pages of tracked post types
	 */
	public function enqueue_scripts() {
		if ( !is_singular( self::get_post_types() ) ) {
			return;
		}

		wp_enqueue_script(
			'vlt-post-views',
			VLT_TOOLKIT_URL . 'assets/js/feature-post-views.js',
			[],
			VLT_TOOLKIT_VERSION,
			true
		);

		wp_localize_script( 'vlt-post-views', 'vltPostViews', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'action'  => self::ACTION,
			'postId'  => get_queried_object_id(),
		] );
	}

	/**
	 * AJAX: count a view
	 *
	 * No nonce on purpose: it would be baked into cached pages and expire, and it protects nothing
	 * for guests — everyone shares the same one. The checks below are what matters.
	 */
	public function ajax_view() {
		$post_id = absint( $_POST['post_id'] ?? 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( !in_array( get_post_type( $post_id ), self::get_post_types(), true ) || 'publish' !== get_post_status( $post_id ) ) {
			wp_send_json_error( null, 400 );
		}

		if ( !current_user_can( 'edit_post', $post_id ) ) {
			self::set_views( $post_id );
		}

		wp_send_json_success();
	}

	/**
	 * Increment post views
	 *
	 * @param int|null $post_id post ID or null for current post
	 */
	public static function set_views( $post_id = null ) {
		$post_id = $post_id ?: get_the_ID();

		if ( !$post_id || !get_post( $post_id ) ) {
			return;
		}

		$count = self::get_views( $post_id ) + 1;
		update_post_meta( $post_id, self::get_meta_key(), $count );

		do_action( 'vlt_toolkit_post_views_updated', $post_id, $count );
	}

	/**
	 * Get post views count
	 *
	 * @param int|null $post_id post ID or null for current post
	 *
	 * @return int view count
	 */
	public static function get_views( $post_id = null ) {
		return (int) get_post_meta( $post_id ?: get_the_ID(), self::get_meta_key(), true );
	}

	/**
	 * Get "1,234 views" label
	 *
	 * @param int|null $post_id post ID or null for current post
	 *
	 * @return string
	 */
	public static function get_views_label( $post_id = null ) {
		$views = self::get_views( $post_id );

		/* translators: %s: number of views */
		return sprintf( _n( '%s view', '%s views', $views, 'toolkit' ), number_format_i18n( $views ) );
	}

	/**
	 * Reset post views
	 *
	 * @param int|null $post_id post ID or null for current post
	 */
	public static function reset_views( $post_id = null ) {
		$post_id = $post_id ?: get_the_ID();

		if ( !$post_id || !get_post( $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, self::get_meta_key(), 0 );

		do_action( 'vlt_toolkit_post_views_reset', $post_id );
	}

	/**
	 * Admin: add the "Views" column to tracked post type lists
	 */
	public function admin_columns() {
		foreach ( self::get_post_types() as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_column' ], 10, 2 );
			add_filter( "manage_edit-{$post_type}_sortable_columns", [ $this, 'sortable_column' ] );
		}
	}

	/**
	 * Add column
	 *
	 * @param array $columns Columns
	 *
	 * @return array
	 */
	public function add_column( $columns ) {
		$columns[ self::COLUMN ] = esc_html__( 'Views', 'toolkit' );

		return $columns;
	}

	/**
	 * Render column value
	 *
	 * @param string $column  Column key
	 * @param int    $post_id Post ID
	 */
	public function render_column( $column, $post_id ) {
		if ( self::COLUMN === $column ) {
			echo esc_html( number_format_i18n( self::get_views( $post_id ) ) );
		}
	}

	/**
	 * Make column sortable
	 *
	 * @param array $columns Sortable columns
	 *
	 * @return array
	 */
	public function sortable_column( $columns ) {
		$columns[ self::COLUMN ] = self::COLUMN;

		return $columns;
	}

	/**
	 * Sort by views; the NOT EXISTS clause keeps posts that have never been viewed in the list
	 *
	 * @param \WP_Query $query Query
	 */
	public function admin_orderby( $query ) {
		if ( !is_admin() || !$query->is_main_query() || self::COLUMN !== $query->get( 'orderby' ) ) {
			return;
		}

		$meta_key = self::get_meta_key();

		$query->set( 'meta_query', [
			'relation'   => 'OR',
			[ 'key' => $meta_key, 'compare' => 'EXISTS' ],
			self::COLUMN => [ 'key' => $meta_key, 'compare' => 'NOT EXISTS', 'type' => 'NUMERIC' ],
		] );
		$query->set( 'orderby', [ self::COLUMN => $query->get( 'order' ) ?: 'DESC' ] );
	}
}
