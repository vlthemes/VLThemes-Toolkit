<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Module
 */
class WooCommerce extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'woocommerce';

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
		// Disable WooCommerce default styles
		add_filter( 'woocommerce_enqueue_styles', '__return_empty_array' );

		// Dequeue unnecessary scripts
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_scripts' ], 100 );
	}

	/**
	 * Dequeue unnecessary WooCommerce scripts
	 */
	public function dequeue_scripts() {
		// Dequeue selectWoo script
		wp_dequeue_script( 'selectWoo' );
		wp_deregister_script( 'selectWoo' );

		// Allow themes/plugins to dequeue additional scripts
		do_action( 'vlt_toolkit_woocommerce_dequeue_scripts' );
	}

	/**
	 * Check if current page is a WooCommerce page
	 *
	 * Determines if viewing cart, checkout, account pages, or WC endpoints.
	 * More reliable than is_woocommerce() for specific page checks.
	 *
	 * @param string $page     Optional. Specific page type: 'cart', 'checkout', 'account', 'endpoint'.
	 * @param string $endpoint Optional. Specific endpoint slug to check.
	 *
	 * @return bool true if on specified WooCommerce page type
	 */
	public static function is_woocommerce_page( $page = '', $endpoint = '' ) {
		// Check all WooCommerce pages if no specific page requested
		if ( !$page ) {
			return ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() )
				|| ( function_exists( 'is_account_page' ) && is_account_page() )
				|| ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() );
		}

		// Check specific page types
		switch ( $page ) {
			case 'cart':
				return function_exists( 'is_cart' ) && is_cart();

			case 'checkout':
				return function_exists( 'is_checkout' ) && is_checkout();

			case 'account':
				return function_exists( 'is_account_page' ) && is_account_page();

			case 'endpoint':
				if ( function_exists( 'is_wc_endpoint_url' ) ) {
					return $endpoint ? is_wc_endpoint_url( $endpoint ) : is_wc_endpoint_url();
				}

				return false;
		}

		return false;
	}

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register() {
		return class_exists( 'WooCommerce' );
	}
}
