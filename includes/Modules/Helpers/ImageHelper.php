<?php

namespace VLT\Toolkit\Modules\Helpers;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Image Helper Module
 *
 * Provides utility methods for working with WordPress attachment images
 */
class ImageHelper extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'image_helper';

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
	 * Get attachment image
	 *
	 * @param int   $image_id Attachment ID
	 * @param array $args     Arguments for image output
	 *
	 * @return string|false Image HTML or false on failure
	 */
	public static function get_attachment_image( $image_id, $args = [] ) {
		if ( empty( $image_id ) ) {
			return false;
		}

		$defaults = [
			'size'      => 'full',
			'class'     => '',
			'image_key' => '',
			'settings'  => [],
			'lazy_load' => true,
		];

		$args = wp_parse_args( $args, $defaults );

		$size = self::parse_image_size( $args['size'], $args['image_key'], $args['settings'] );

		$attrs = [];

		if ( !empty( $args['class'] ) ) {
			$attrs['class'] = trim( $args['class'] );
		}

		if ( $args['lazy_load'] ) {
			$attrs['loading'] = 'lazy';
		}

		$output = wp_get_attachment_image( $image_id, $size, false, $attrs );

		return apply_filters( 'vlt_toolkit_attachment_image', $output, $image_id, $size, $args['class'], $args['settings'] );
	}

	/**
	 * Get attachment image src
	 *
	 * @param int   $image_id Attachment ID
	 * @param array $args     Arguments for image source
	 *
	 * @return string|false Image URL or false on failure
	 */
	public static function get_attachment_image_src( $image_id, $args = [] ) {
		if ( empty( $image_id ) ) {
			return false;
		}

		$defaults = [
			'size'      => 'full',
			'image_key' => '',
			'settings'  => [],
		];

		$args = wp_parse_args( $args, $defaults );

		$size = self::parse_image_size( $args['size'], $args['image_key'], $args['settings'] );

		$image_src = wp_get_attachment_image_src( $image_id, $size );

		if ( !$image_src ) {
			return false;
		}

		$output = $image_src[0];

		return apply_filters( 'vlt_toolkit_attachment_image_src', $output, $image_id, $size, $args['settings'] );
	}

	/**
	 * Get placeholder image source URL
	 *
	 * @return string Placeholder image URL
	 */
	public static function get_placeholder_image_src() {
		$default_url = '';

		// Use Elementor placeholder if available
		if ( class_exists( '\Elementor\Utils' ) ) {
			$default_url = \Elementor\Utils::get_placeholder_image_src();
		}

		// Fallback to local placeholder image
		if ( empty( $default_url ) && defined( 'VLT_TOOLKIT_URL' ) ) {
			$default_url = VLT_TOOLKIT_URL . 'assets/img/placeholder.png';
		}

		// Allow filtering of placeholder image URL
		return apply_filters( 'vlt_toolkit_placeholder_image_src', $default_url );
	}

	/**
	 * Get placeholder image HTML
	 *
	 * @param string $class CSS class for the image
	 * @param string $alt   Alt text for the image
	 *
	 * @return string Placeholder image HTML
	 */
	public static function get_placeholder_image( $class = '', $alt = '' ) {
		$image_src = self::get_placeholder_image_src();

		if ( empty( $image_src ) ) {
			return '';
		}

		$attrs = [
			'src'     => esc_url( $image_src ),
			'alt'     => esc_attr( $alt ?: __( 'Placeholder', 'toolkit' ) ),
			'loading' => 'lazy',
		];

		if ( !empty( $class ) ) {
			$attrs['class'] = trim( $class );
		}

		$attrs_string = '';
		foreach ( $attrs as $key => $value ) {
			$attrs_string .= sprintf( ' %s="%s"', $key, $value );
		}

		$output = sprintf( '<img%s />', $attrs_string );

		return apply_filters( 'vlt_toolkit_placeholder_image', $output, $image_src, $class, $alt );
	}

	/**
	 * Parse image size from arguments
	 *
	 * @param string|array $size      Image size
	 * @param string       $image_key Image key for custom dimensions
	 * @param array        $settings  Settings array
	 *
	 * @return string|array Parsed image size
	 */
	private static function parse_image_size( $size, $image_key = '', $settings = [] ) {
		// If already array, return as is
		if ( is_array( $size ) ) {
			return $size;
		}

		// Handle custom size
		if ( 'custom' === $size && !empty( $image_key ) ) {
			$custom_key = $image_key . '_custom_dimension';
			$dim        = $settings[ $custom_key ] ?? [];

			$w = !empty( $dim['width'] ) && is_numeric( $dim['width'] ) ? (int) $dim['width'] : null;
			$h = !empty( $dim['height'] ) && is_numeric( $dim['height'] ) ? (int) $dim['height'] : null;

			if ( $w || $h ) {
				return [ $w ?? 0, $h ?? 0, true ];
			}

			return 'full';
		}

		return $size;
	}
}
