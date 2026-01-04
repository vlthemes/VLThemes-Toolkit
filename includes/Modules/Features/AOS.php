<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AOS (Animate On Scroll) Module
 *
 * Provides scroll-based animations using AOS library
 * Integrates with Elementor for element animations
 */
class AOS extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'aos';

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
		// Enqueue AOS assets
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Enqueue AOS CSS and JS
	 */
	public function enqueue_assets() {
		wp_enqueue_style( 'aos' );
		wp_enqueue_script( 'aos' );
	}

	/**
	 * Get all available animations
	 *
	 * @return array array of animation options
	 */
	public static function get_animations() {
		$custom_animations = [];

		$custom_animations = apply_filters( 'vlt_toolkit_aos_animations', $custom_animations );

		$default_animations = [
			'fade'            => esc_html__( 'Simple Fade', 'toolkit' ),
			'fade-up'         => esc_html__( 'Fade In Up', 'toolkit' ),
			'fade-down'       => esc_html__( 'Fade In Down', 'toolkit' ),
			'fade-left'       => esc_html__( 'Fade In From Right', 'toolkit' ),
			'fade-right'      => esc_html__( 'Fade In From Left', 'toolkit' ),
			'fade-up-right'   => esc_html__( 'Fade In Up Right', 'toolkit' ),
			'fade-up-left'    => esc_html__( 'Fade In Up Left', 'toolkit' ),
			'fade-down-right' => esc_html__( 'Fade In Down Right', 'toolkit' ),
			'fade-down-left'  => esc_html__( 'Fade In Down Left', 'toolkit' ),

			'flip-up'    => esc_html__( 'Flip Up', 'toolkit' ),
			'flip-down'  => esc_html__( 'Flip Down', 'toolkit' ),
			'flip-left'  => esc_html__( 'Flip Left', 'toolkit' ),
			'flip-right' => esc_html__( 'Flip Right', 'toolkit' ),

			'slide-up'    => esc_html__( 'Slide In Up', 'toolkit' ),
			'slide-down'  => esc_html__( 'Slide In Down', 'toolkit' ),
			'slide-left'  => esc_html__( 'Slide In From Left', 'toolkit' ),
			'slide-right' => esc_html__( 'Slide In From Right', 'toolkit' ),

			'zoom-in'        => esc_html__( 'Zoom In', 'toolkit' ),
			'zoom-in-up'     => esc_html__( 'Zoom In Up', 'toolkit' ),
			'zoom-in-down'   => esc_html__( 'Zoom In Down', 'toolkit' ),
			'zoom-in-left'   => esc_html__( 'Zoom In From Left', 'toolkit' ),
			'zoom-in-right'  => esc_html__( 'Zoom In From Right', 'toolkit' ),
			'zoom-out'       => esc_html__( 'Zoom Out', 'toolkit' ),
			'zoom-out-up'    => esc_html__( 'Zoom Out Up', 'toolkit' ),
			'zoom-out-down'  => esc_html__( 'Zoom Out Down', 'toolkit' ),
			'zoom-out-left'  => esc_html__( 'Zoom Out Left', 'toolkit' ),
			'zoom-out-right' => esc_html__( 'Zoom Out Right', 'toolkit' ),
		];

		$all_animations = array_merge( $custom_animations, $default_animations );

		$result = [ 'none' => esc_html__( 'None', 'toolkit' ) ];

		return array_merge( $result, $all_animations );
	}

	/**
	 * Get AOS data attributes as array
	 *
	 * @param string $animation animation name
	 * @param array  $args      Additional arguments (duration, delay, offset, once, etc.).
	 *
	 * @return array data attributes array
	 */
	public static function get_render_attrs( $animation, $args = [] ) {
		if ( empty( $animation ) || 'none' === $animation ) {
			return [];
		}

		$defaults = [
			'duration' => '',
			'delay'    => '',
			'offset'   => '',
			'once'     => '',
			'easing'   => '',
		];

		$args = wp_parse_args( $args, $defaults );

		$attrs = [
			'data-aos' => esc_attr( $animation ),
		];

		if ( isset( $args['duration'] ) && $args['duration'] !== '' ) {
			$attrs['data-aos-duration'] = esc_attr( $args['duration'] * 1000 );
		}

		if ( isset( $args['delay'] ) && $args['delay'] !== '' ) {
			$attrs['data-aos-delay'] = esc_attr( $args['delay'] * 1000 );
		}

		if ( isset( $args['offset'] ) && $args['offset'] !== '' ) {
			$attrs['data-aos-offset'] = esc_attr( $args['offset'] );
		}

		if ( isset( $args['once'] ) && $args['once'] !== '' ) {
			$attrs['data-aos-once'] = esc_attr( $args['once'] );
		}

		if ( isset( $args['easing'] ) && $args['easing'] !== '' ) {
			$attrs['data-aos-easing'] = esc_attr( $args['easing'] );
		}

		return $attrs;
	}

	/**
	 * Build AOS data attributes string
	 *
	 * @param string $animation animation name
	 * @param array  $args      Additional arguments (duration, delay, offset, once, etc.).
	 *
	 * @return string data attributes string
	 */
	public static function render_attrs( $animation, $args = [] ) {
		$attrs = self::get_render_attrs( $animation, $args );

		if ( empty( $attrs ) ) {
			return '';
		}

		$output = [];
		foreach ( $attrs as $key => $value ) {
			$output[] = sprintf( '%s="%s"', $key, $value );
		}

		return implode( ' ', $output );
	}
}
