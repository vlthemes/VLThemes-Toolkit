<?php

namespace VLT\Helper\Modules\Features;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
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
		wp_enqueue_style( 'vlt-aos' );
		wp_enqueue_script( 'vlt-aos' );
	}

	/**
	 * Get all available animations
	 *
	 * @return array Array of animation options.
	 */
	public static function get_animations() {
		$custom_animations = [];

		$custom_animations = apply_filters( 'vlt_helper_aos_animations', $custom_animations );

		$default_animations = [
			'fade'            => esc_html__( 'Fade', 'vlt-helper' ),
			'fade-up'         => esc_html__( 'Fade Up', 'vlt-helper' ),
			'fade-down'       => esc_html__( 'Fade Down', 'vlt-helper' ),
			'fade-left'       => esc_html__( 'Fade Left', 'vlt-helper' ),
			'fade-right'      => esc_html__( 'Fade Right', 'vlt-helper' ),
			'fade-up-right'   => esc_html__( 'Fade Up Right', 'vlt-helper' ),
			'fade-up-left'    => esc_html__( 'Fade Up Left', 'vlt-helper' ),
			'fade-down-right' => esc_html__( 'Fade Down Right', 'vlt-helper' ),
			'fade-down-left'  => esc_html__( 'Fade Down Left', 'vlt-helper' ),

			'flip-up'    => esc_html__( 'Flip Up', 'vlt-helper' ),
			'flip-down'  => esc_html__( 'Flip Down', 'vlt-helper' ),
			'flip-left'  => esc_html__( 'Flip Left', 'vlt-helper' ),
			'flip-right' => esc_html__( 'Flip Right', 'vlt-helper' ),

			'slide-up'    => esc_html__( 'Slide Up', 'vlt-helper' ),
			'slide-down'  => esc_html__( 'Slide Down', 'vlt-helper' ),
			'slide-left'  => esc_html__( 'Slide Left', 'vlt-helper' ),
			'slide-right' => esc_html__( 'Slide Right', 'vlt-helper' ),

			'zoom-in'        => esc_html__( 'Zoom In', 'vlt-helper' ),
			'zoom-in-up'     => esc_html__( 'Zoom In Up', 'vlt-helper' ),
			'zoom-in-down'   => esc_html__( 'Zoom In Down', 'vlt-helper' ),
			'zoom-in-left'   => esc_html__( 'Zoom In Left', 'vlt-helper' ),
			'zoom-in-right'  => esc_html__( 'Zoom In Right', 'vlt-helper' ),
			'zoom-out'       => esc_html__( 'Zoom Out', 'vlt-helper' ),
			'zoom-out-up'    => esc_html__( 'Zoom Out Up', 'vlt-helper' ),
			'zoom-out-down'  => esc_html__( 'Zoom Out Down', 'vlt-helper' ),
			'zoom-out-left'  => esc_html__( 'Zoom Out Left', 'vlt-helper' ),
			'zoom-out-right' => esc_html__( 'Zoom Out Right', 'vlt-helper' ),
		];

		$all_animations = array_merge( $custom_animations, $default_animations );

		$result = [ 'none' => esc_html__( 'None', 'vlt-helper' ) ];

		return array_merge( $result, $all_animations );
	}

	/**
	 * Build AOS data attributes string
	 *
	 * @param string $animation Animation name.
	 * @param array  $args      Additional arguments (duration, delay, offset, once, etc.).
	 * @return string Data attributes string.
	 */
	public static function render_attrs( $animation, $args = [] ) {
		if ( empty( $animation ) || $animation === 'none' ) {
			return '';
		}

		$defaults = [
			'duration' => '',
			'delay'    => '',
			'offset'   => '',
			'once'     => '',
			'mirror'   => '',
			'easing'   => '',
			'anchor'   => '',
		];

		$args = wp_parse_args( $args, $defaults );

		$attrs = [ 'data-aos="' . esc_attr( $animation ) . '"' ];

		if ( ! empty( $args['duration'] ) ) {
			$attrs[] = 'data-aos-duration="' . esc_attr( $args['duration'] ) . '"';
		}

		if ( ! empty( $args['delay'] ) ) {
			$attrs[] = 'data-aos-delay="' . esc_attr( $args['delay'] ) . '"';
		}

		if ( ! empty( $args['offset'] ) ) {
			$attrs[] = 'data-aos-offset="' . esc_attr( $args['offset'] ) . '"';
		}

		if ( ! empty( $args['once'] ) ) {
			$attrs[] = 'data-aos-once="' . esc_attr( $args['once'] ) . '"';
		}

		if ( ! empty( $args['mirror'] ) ) {
			$attrs[] = 'data-aos-mirror="' . esc_attr( $args['mirror'] ) . '"';
		}

		if ( ! empty( $args['easing'] ) ) {
			$attrs[] = 'data-aos-easing="' . esc_attr( $args['easing'] ) . '"';
		}

		if ( ! empty( $args['anchor'] ) ) {
			$attrs[] = 'data-aos-anchor="' . esc_attr( $args['anchor'] ) . '"';
		}

		return implode( ' ', $attrs );
	}
}