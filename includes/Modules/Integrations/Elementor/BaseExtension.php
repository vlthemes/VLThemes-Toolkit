<?php

namespace VLT\Helper\Modules\Integrations\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for Elementor extensions
 *
 * Provides common functionality for all Elementor element extensions
 */
abstract class BaseExtension {

	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Assets URL
	 *
	 * @var string
	 */
	protected $assets_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->assets_url = VLT_HELPER_URL . 'assets/';
		$this->init();
		$this->register_hooks();
	}

	/**
	 * Initialize extension
	 */
	abstract protected function init();

	/**
	 * Register WordPress hooks
	 */
	abstract protected function register_hooks();

	/**
	 * Register extension controls
	 *
	 * @param object $element Elementor element instance.
	 * @param array  $args    Element arguments.
	 */
	abstract public function register_controls( $element, $args );

	/**
	 * Render extension attributes
	 *
	 * @param object $element Elementor element instance.
	 */
	abstract public function render_attributes( $element );

	/**
	 * Get Elementor breakpoints
	 *
	 * @return array Available breakpoints.
	 */
	protected function get_elementor_breakpoints() {
		$breakpoints_manager = \Elementor\Plugin::$instance->breakpoints;
		$breakpoints = $breakpoints_manager->get_active_breakpoints();

		$options = [];
		foreach ( $breakpoints as $breakpoint_key => $breakpoint ) {
			$options[ $breakpoint_key ] = $breakpoint->get_label();
		}

		return $options;
	}

	/**
	 * Get default reset devices (mobile and mobile_extra if exists)
	 *
	 * @return array
	 */
	protected function get_default_reset_devices() {
		$breakpoints = $this->get_elementor_breakpoints();

		$default_reset_devices = [ 'mobile' ];
		if ( isset( $breakpoints['mobile_extra'] ) ) {
			$default_reset_devices[] = 'mobile_extra';
		}

		return $default_reset_devices;
	}
}
