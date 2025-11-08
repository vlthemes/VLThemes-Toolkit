<?php

namespace VLT\Helper\Modules\Integrations;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact Form 7 Integration Module
 *
 * Provides integration with Contact Form 7 plugin
 * Handles form modifications and helper functions
 */
class ContactForm7 extends BaseModule {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'contact_form_7';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register() {
		return function_exists( 'wpcf7_add_form_tag' );
	}

	/**
	 * Register module
	 */
	public function register() {
		// Disable automatic paragraph formatting
		add_filter( 'wpcf7_autop_or_not', '__return_false' );

		// Allow themes to add custom CF7 modifications
		do_action( 'vlt_helper_cf7_init' );
	}

	/**
	 * Get Contact Form 7 forms list
	 *
	 * Returns array of available Contact Form 7 forms
	 *
	 * @return array Array of form IDs and titles.
	 */
	public static function get_forms() {
		$options = [];

		if ( ! class_exists( 'WPCF7_ContactForm' ) ) {
			return $options;
		}

		$wpcf7_form_list = get_posts( [
			'post_type'   => 'wpcf7_contact_form',
			'numberposts' => -1,
		] );

		$options[0] = esc_html__( 'Select a Contact Form', 'vlt-helper' );

		if ( ! empty( $wpcf7_form_list ) && ! is_wp_error( $wpcf7_form_list ) ) {
			foreach ( $wpcf7_form_list as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		} else {
			$options[0] = esc_html__( 'Create a Form First', 'vlt-helper' );
		}

		return $options;
	}

	/**
	 * Render Contact Form 7 by ID
	 *
	 * @param int   $form_id Form ID.
	 * @param array $args    Additional arguments.
	 * @return string Form HTML.
	 */
	public static function render_form( $form_id, $args = [] ) {
		if ( ! function_exists( 'wpcf7_contact_form' ) || ! $form_id ) {
			return '';
		}

		$defaults = [
			'ajax' => true,
		];

		$args = wp_parse_args( $args, $defaults );

		return do_shortcode( '[contact-form-7 id="' . absint( $form_id ) . '"]' );
	}
}
