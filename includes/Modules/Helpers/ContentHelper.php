<?php

namespace VLT\Toolkit\Modules\Helpers;

use VLT\Toolkit\Modules\BaseModule;
use VLT\Toolkit\Modules\Features\DynamicContent;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content Helper Module
 *
 * Provides utility methods for working with post content
 */
class ContentHelper extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'content_helper';

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
		// No hooks needed - this is a utility module
	}

	/**
	 * Get trimmed content from post excerpt
	 *
	 * @param int|null $post_id   Post ID (null for current post)
	 * @param int      $max_words Maximum number of words
	 *
	 * @return string Trimmed content
	 */
	public static function get_trimmed_content( $post_id = null, $max_words = 18 ) {
		if ( !is_numeric( $max_words ) || $max_words < 1 ) {
			$max_words = 18;
		}

		$post_id = $post_id ?: get_the_ID();

		if ( !$post_id ) {
			return '';
		}

		$content = get_the_excerpt( $post_id );

		if ( empty( $content ) ) {
			return '';
		}

		// Use WordPress built-in function to trim words
		$content = wp_trim_words( $content, $max_words, '...' );

		// Apply dynamic content parsing if class exists
		if ( class_exists( DynamicContent::class ) ) {
			$content = DynamicContent::parse( $content );
		}

		return apply_filters( 'vlt_toolkit_trimmed_content', esc_html( $content ), $max_words );
	}
}
