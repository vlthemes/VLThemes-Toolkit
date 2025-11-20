<?php

namespace VLT\Toolkit\Modules;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base Module class
 *
 * All modules should extend this class
 */
abstract class BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = '';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Instance
	 *
	 * @var BaseModule
	 */
	private static $instances = [];

	/**
	 * Constructor
	 */
	protected function __construct() {
		// Check if module can be registered before initializing
		if ( !$this->can_register() ) {
			return;
		}

		$this->init();
		$this->register();
	}

	/**
	 * Get instance
	 *
	 * @return BaseModule
	 */
	public static function instance() {
		$class = get_called_class();

		if ( !isset( self::$instances[ $class ] ) ) {
			self::$instances[ $class ] = new $class();
		}

		return self::$instances[ $class ];
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->name;
	}

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Check if module can be registered
	 *
	 * Override this method in child classes to add conditional loading
	 *
	 * @return bool true if module can be registered, false otherwise
	 */
	protected function can_register() {
		return true; // By default, allow module to register
	}

	/**
	 * Initialize module
	 *
	 * Override this method in child classes to add initialization logic
	 */
	protected function init() {
		// Override in child class if needed
	}

	/**
	 * Register module
	 *
	 * Override this method in child classes to register hooks
	 */
	abstract protected function register();
}
