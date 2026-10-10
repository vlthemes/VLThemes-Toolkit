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
		// Both opt-ins are read when WooCommerce enqueues, not here: modules register on plugins_loaded, before the theme adds its filters
		add_filter( 'woocommerce_enqueue_styles', [ $this, 'disable_styles' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'dequeue_scripts' ], 100 );
	}

	/**
	 * Disable WooCommerce default styles — opt-in, nothing is removed by default
	 *
	 * Example: add_filter( 'vlt_toolkit_woocommerce_disable_styles', '__return_true' );
	 *
	 * @param array $styles WooCommerce styles
	 *
	 * @return array
	 */
	public function disable_styles( $styles ) {
		return apply_filters( 'vlt_toolkit_woocommerce_disable_styles', false ) ? [] : $styles;
	}

	/**
	 * Dequeue WooCommerce scripts — opt-in, nothing is removed by default
	 *
	 * Example: add_filter( 'vlt_toolkit_woocommerce_disable_scripts', '__return_true' );
	 */
	public function dequeue_scripts() {
		if ( !apply_filters( 'vlt_toolkit_woocommerce_disable_scripts', false ) ) {
			return;
		}

		foreach ( [ 'selectWoo' ] as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
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
