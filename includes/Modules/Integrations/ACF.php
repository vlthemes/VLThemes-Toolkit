<?php

namespace VLT\Toolkit\Modules\Integrations;

use VLT\Toolkit\Modules\BaseModule;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Advanced Custom Fields Integration Module
 *
 * Provides integration hooks for ACF plugin
 * Handles JSON save/load paths and admin visibility
 * Provides static helper methods for dynamic field population (used in themes)
 */
class ACF extends BaseModule
{
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
	 * Check if module should load
	 *
	 * @return bool
	 */
	protected function can_register()
	{
		return class_exists('ACF');
	}

	/**
	 * Register module
	 */
	public function register(): void
	{
		// Hide ACF in admin if needed
		add_filter('acf/settings/show_admin', [ $this, 'show_admin' ]);

		// Set save/load points for JSON
		add_filter('acf/settings/save_json', [ $this, 'save_json' ]);
		add_filter('acf/settings/load_json', [ $this, 'load_json' ]);
	}

	/**
	 * Control ACF admin visibility
	 *
	 * @param bool $show Whether to show ACF in admin.
	 *
	 * @return bool Filtered value.
	 */
	public function show_admin($show)
	{
		return apply_filters('vlt_toolkit_acf_show_admin', $show);
	}

	/**
	 * Set ACF JSON save path
	 *
	 * @param string $path Default save path.
	 *
	 * @return string Filtered save path.
	 */
	public function save_json($path)
	{
		return apply_filters('vlt_toolkit_acf_save_json', $path);
	}

	/**
	 * Set ACF JSON load paths
	 *
	 * @param array $paths Default load paths.
	 *
	 * @return array Filtered load paths.
	 */
	public function load_json($paths)
	{
		return apply_filters('vlt_toolkit_acf_load_json', $paths);
	}

}
