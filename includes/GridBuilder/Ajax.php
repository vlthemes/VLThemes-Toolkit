<?php

namespace VLT\Toolkit\GridBuilder;

use VLT\Toolkit\GridBuilder\Sources\PostTypeSource;
use VLT\Toolkit\GridBuilder\Sources\SourceRegistry;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX handlers (admin-ajax.php — the transport the toolkit already uses; one transport, no REST routes)
 *
 * Front end (vlt_toolkit_grid_load): public and read-only, so no nonce — it would be baked into cached
 * pages and expire. Safety comes from the checks: published layout (or editable by the user), page and
 * term validated against the saved config, and queries that only ever return published items.
 *
 * Editor (terms, posts): nonce + capability checks. The preview is a front-end page, see Preview.
 */
class Ajax {
	const ACTION      = 'vlt_toolkit_grid_load';
	const ADMIN_NONCE = 'vlt_toolkit_grid_admin';
	const ADMIN_CAP   = 'edit_pages';
	const MAX_SEED    = 999999;

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::ACTION, [ __CLASS__, 'load' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ __CLASS__, 'load' ] );

		add_action( 'wp_ajax_vlt_toolkit_grid_terms', [ __CLASS__, 'terms' ] );
		add_action( 'wp_ajax_vlt_toolkit_grid_posts', [ __CLASS__, 'posts' ] );
	}

	/**
	 * Front end: a page of items (pagination, filter)
	 */
	public static function load() {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- public read-only endpoint, see class docblock
		$config = Config::get( absint( $_POST['layout'] ?? 0 ) );
		$page   = max( 1, absint( $_POST['page'] ?? 1 ) );
		$term   = absint( $_POST['term'] ?? 0 );
		$seed   = min( self::MAX_SEED, absint( $_POST['seed'] ?? 0 ) );
		// phpcs:enable

		if ( !$config ) {
			wp_send_json_error( [ 'message' => __( 'Grid layout not found.', 'toolkit' ) ], 404 );
		}

		// Editor preview: the unsaved settings — only for whoever can edit this layout, with the editor nonce
		if ( isset( $_POST['preview'] ) ) {
			check_ajax_referer( self::ADMIN_NONCE, 'nonce' );

			if ( !current_user_can( 'edit_post', $config['id'] ) ) {
				wp_send_json_error( null, 403 );
			}

			$config = [
				'id'         => $config['id'],
				'custom_css' => $config['custom_css'],
			] + Config::sanitize( wp_unslash( $_POST['config'] ?? '' ) );
		}

		if ( ( 'none' === $config['pagination']['type'] && $page > 1 ) || ( $term && !QueryBuilder::is_valid_term( $config, $term ) ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'toolkit' ) ], 400 );
		}

		$result = QueryBuilder::run( $config, $page, $term, $seed );

		wp_send_json_success( [
			'items'      => Renderer::render_items( $config, $result ),
			'pagination' => Renderer::pagination_html( $config, $result['page'], $result['max_pages'] ),
			'page'       => $result['page'],
			'maxPages'   => $result['max_pages'],
		] );
	}

	/**
	 * Editor: terms of a taxonomy used by a source
	 */
	public static function terms() {
		self::check_admin();

		$taxonomy = sanitize_key( wp_unslash( $_POST['taxonomy'] ?? '' ) );
		$known    = false;

		foreach ( SourceRegistry::all() as $source ) {
			$known = $known || isset( $source->get_taxonomies()[ $taxonomy ] );
		}

		if ( !$known ) {
			wp_send_json_error( null, 400 );
		}

		$terms = get_terms( [
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'number'     => 500,
		] );

		wp_send_json_success( array_map( fn( $term ) => [
			'id'     => $term->term_id,
			'name'   => $term->name,
			'parent' => $term->parent,
		], is_array( $terms ) ? $terms : [] ) );
	}

	/**
	 * Editor: search posts of a source (for "exclude"), or resolve IDs to titles
	 */
	public static function posts() {
		self::check_admin();

		$source = SourceRegistry::get( sanitize_key( wp_unslash( $_POST['source'] ?? '' ) ) );

		if ( !$source instanceof PostTypeSource ) {
			wp_send_json_error( null, 400 );
		}

		$include = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['include'] ?? '' ) ) ) );
		$args    = [
			'post_type'      => $source->get_id(),
			'post_status'    => 'publish',
			'posts_per_page' => $include ? count( $include ) : 20,
			'orderby'        => 'title',
			'order'          => 'ASC',
		];

		if ( $include ) {
			$args['post__in'] = $include;
		} else {
			$args['s'] = sanitize_text_field( wp_unslash( $_POST['search'] ?? '' ) );
		}

		wp_send_json_success( array_map( fn( $post ) => [
			'id'    => $post->ID,
			'title' => get_the_title( $post ) ?: '#' . $post->ID,
		], get_posts( $args ) ) );
	}

	/**
	 * Nonce + capability for editor endpoints
	 */
	private static function check_admin() {
		check_ajax_referer( self::ADMIN_NONCE, 'nonce' );

		if ( !current_user_can( self::ADMIN_CAP ) ) {
			wp_send_json_error( null, 403 );
		}
	}
}
