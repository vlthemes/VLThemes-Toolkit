<?php

namespace VLT\Toolkit\Modules\Integrations\ACF;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF Pro Plugin Updater
 *
 * Handles ACF Pro plugin updates via TGM source
 */
class ACFProUpdater {
	/**
	 * Plugin name
	 *
	 * @var string
	 */
	private $plugin_name = 'advanced-custom-fields-pro';

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Return ACF pro fake license to prevent notices
	 *
	 * @param mixed $license license
	 */
	public function acf_pro_license( $license ) {
		if ( !$license ) {
			return base64_encode(
				maybe_serialize(
					[
						'key' => 'fake',
						'url' => home_url(),
					],
				),
			);
		}

		return $license;
	}

	/**
	 * Modify plugin update information
	 *
	 * @param object $transient plugin data
	 *
	 * @return object
	 */
	public function modify_plugin_transient( $transient ) {
		// Bail early if no response (error)
		if ( !isset( $transient->response ) ) {
			return $transient;
		}

		// Get TGM plugin data
		$plugin = $this->get_tgm_plugin_data();

		if ( !$plugin || empty( $plugin ) ) {
			return $transient;
		}

		// Only for external source type
		if ( 'external' !== $plugin['source_type'] ) {
			return $transient;
		}

		// Check if available transient for this plugin
		if ( !isset( $transient->response[ $plugin['file_path'] ] ) ) {
			return $transient;
		}

		$transient->response[ $plugin['file_path'] ]->package = $plugin['source'];

		return $transient;
	}

	/**
	 * Register hooks
	 */
	private function register() {
		// For active themes only.
		if ( function_exists( 'vlt_is_theme_activated' ) && !vlt_is_theme_activated() ) {
			return;
		}

		// Don't run on ACF Settings page to prevent conflicts
		if ( isset( $_GET['post_type'] ) && 'acf-field-group' === $_GET['post_type'] ) {
			return;
		}

		// Already active
		if ( get_option( 'acf_pro_license' ) ) {
			return;
		}

		// Return ACF pro fake license to prevent notices
		add_filter( 'option_acf_pro_license', [ $this, 'acf_pro_license' ], 20, 1 );
		add_filter( 'pre_option_acf_pro_license', [ $this, 'acf_pro_license' ], 20, 1 );

		// Modify update information for plugin
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'modify_plugin_transient' ], 20, 1 );
	}

	/**
	 * Get TGM plugin data
	 *
	 * @return array|false
	 */
	private function get_tgm_plugin_data() {
		if ( !class_exists( 'TGM_Plugin_Activation' ) ) {
			return false;
		}

		$plugins = \TGM_Plugin_Activation::$instance->plugins;

		if ( isset( $plugins[ $this->plugin_name ] ) ) {
			return $plugins[ $this->plugin_name ];
		}

		return false;
	}
}
