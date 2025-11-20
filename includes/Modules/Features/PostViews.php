<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Views Module
 *
 * Tracks and displays post view counts
 * Automatically increments views when single post is viewed
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
	 * Meta key for storing view count
	 *
	 * @var string
	 */
	protected $meta_key = 'views';

	/**
	 * Register module
	 */
	public function register() {
		// Track post views on wp_head
		add_action( 'wp_head', [ $this, 'track_post_views' ] );
	}

	/**
	 * Track post views on single post pages
	 *
	 * @param int|null $post_id post ID (optional)
	 *
	 */
	public function track_post_views( $post_id = null ) {
		if ( !is_single() ) {
			return;
		}

		if ( empty( $post_id ) ) {
			global $post;
			$post_id = $post->ID;
		}

		self::set_views( $post_id );
	}

	/**
	 * Set/increment post views
	 *
	 * @param int $post_id post ID
	 *
	 */
	public static function set_views( $post_id ) {
		if ( !$post_id || !get_post( $post_id ) ) {
			return;
		}

		$meta_key = apply_filters( 'vlt_toolkit_post_views_meta_key', 'views' );
		$count    = get_post_meta( $post_id, $meta_key, true );

		if ( '' === $count ) {
			$count = 0;
			delete_post_meta( $post_id, $meta_key );
			add_post_meta( $post_id, $meta_key, '0' );
		} else {
			++$count;
			update_post_meta( $post_id, $meta_key, $count );
		}

		do_action( 'vlt_toolkit_post_views_updated', $post_id, $count );
	}

	/**
	 * Get post views count
	 *
	 * @param int $post_id post ID
	 *
	 * @return string view count
	 */
	public static function get_views( $post_id ) {
		if ( !$post_id || !get_post( $post_id ) ) {
			return '0';
		}

		$meta_key = apply_filters( 'vlt_toolkit_post_views_meta_key', 'views' );
		$count    = get_post_meta( $post_id, $meta_key, true );

		if ( '' === $count ) {
			delete_post_meta( $post_id, $meta_key );
			add_post_meta( $post_id, $meta_key, '0' );

			return '0';
		}

		return $count;
	}

	/**
	 * Reset post views
	 *
	 * @param int $post_id post ID
	 *
	 */
	public static function reset_views( $post_id ) {
		if ( !$post_id || !get_post( $post_id ) ) {
			return;
		}

		$meta_key = apply_filters( 'vlt_toolkit_post_views_meta_key', 'views' );
		update_post_meta( $post_id, $meta_key, '0' );

		do_action( 'vlt_toolkit_post_views_reset', $post_id );
	}
}
