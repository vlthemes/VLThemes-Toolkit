<?php

namespace VLT\Helper\Modules\Features;

use VLT\Helper\Modules\BaseModule;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Widgets Module
 *
 * Registers custom WordPress widgets
 */
class Widgets extends BaseModule
{

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'widgets';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Widgets path
	 *
	 * @var string
	 */
	private $widgets_path;

	/**
	 * Available widgets
	 *
	 * @var array
	 */
	private $widgets = [
		'RecentPosts'    => 'VLT\Helper\Widgets\RecentPosts',
		'PopularPosts'   => 'VLT\Helper\Widgets\PopularPosts',
		'TrendingPosts'  => 'VLT\Helper\Widgets\TrendingPosts',
	];

	/**
	 * Initialize module
	 */
	protected function init()
	{
		$this->widgets_path = VLT_HELPER_PATH . 'includes/Widgets/';

		// Load base widget class first
		require_once $this->widgets_path . 'PostsWidget.php';
	}

	/**
	 * Register module
	 */
	public function register()
	{
		add_action('widgets_init', [$this, 'register_widgets']);
	}

	/**
	 * Register widgets
	 */
	public function register_widgets()
	{
		// Allow themes/plugins to modify the widgets list
		$widgets = apply_filters('vlt_helper_widgets', $this->widgets);

		foreach ($widgets as $file => $class) {
			$this->register_single_widget($file, $class);
		}
	}

	/**
	 * Register single widget
	 *
	 * @param string $file Widget file name (without .php extension).
	 * @param string $class Widget class name.
	 */
	private function register_single_widget($file, $class)
	{
		$file_path = $this->widgets_path . sanitize_file_name($file) . '.php';

		// Check if file exists
		if (! file_exists($file_path)) {
			return;
		}

		// Include widget file
		require_once $file_path;

		// Register widget if class exists
		if (class_exists($class)) {
			register_widget($class);

			do_action('vlt_helper_widget_registered', $class, $file);
		}
	}

	/**
	 * Get registered widgets
	 *
	 * @return array
	 */
	public function get_widgets()
	{
		return $this->widgets;
	}
}
