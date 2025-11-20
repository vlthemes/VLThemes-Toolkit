<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Advanced Custom Fields Integration Module
 *
 * Provides integration hooks for ACF plugin
 * Handles JSON save/load paths and admin visibility
 * Provides static helper methods for dynamic field population (used in themes)
 */
class ACF extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'acf';

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
		// Hide ACF in admin if needed
		add_filter( 'acf/settings/show_admin', [ $this, 'show_admin' ] );

		// Set save/load points for JSON
		add_filter( 'acf/settings/save_json', [ $this, 'save_json' ] );
		add_filter( 'acf/settings/load_json', [ $this, 'load_json' ] );
	}

	/**
	 * Control ACF admin visibility
	 *
	 * @param bool $show whether to show ACF in admin
	 *
	 * @return bool filtered value
	 */
	public function show_admin( $show ) {
		return apply_filters( 'vlt_toolkit_acf_show_admin', $show );
	}

	/**
	 * Set ACF JSON save path
	 *
	 * @param string $path default save path
	 *
	 * @return string filtered save path
	 */
	public function save_json( $path ) {
		return apply_filters( 'vlt_toolkit_acf_save_json', $path );
	}

	/**
	 * Set ACF JSON load paths
	 *
	 * @param array $paths default load paths
	 *
	 * @return array filtered load paths
	 */
	public function load_json( $paths ) {
		return apply_filters( 'vlt_toolkit_acf_load_json', $paths );
	}

	/**
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register() {
		return class_exists( 'ACF' );
	}
}
