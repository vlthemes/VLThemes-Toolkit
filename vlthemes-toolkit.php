<?php

/**
 * Plugin Name: VLThemes Toolkit
 * Plugin URI: https://vlthemes.me/
 * Description: VLThemes Toolkit expands the functionality of the theme. Adds new icons, widgets and much more.
 * Version: 1.0.0
 * Author: VLThemes
 * Author URI: https://themeforest.net/user/vlthemes
 * License: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vlthemes-toolkit
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
	exit;
}

define('VLT_TOOLKIT_VERSION', '1.0.0');
define('VLT_TOOLKIT_FILE', __FILE__);
define('VLT_TOOLKIT_PATH', plugin_dir_path(__FILE__));
define('VLT_TOOLKIT_URL', plugin_dir_url(__FILE__));
define('VLT_TOOLKIT_BASENAME', plugin_basename(__FILE__));

// Define update URL
define('VLT_TOOLKIT_UPDATE_URL', 'https://vlthemes.me/plugins/updates/vlthemes-toolkit.json');

// Load helper functions
require_once VLT_TOOLKIT_PATH . 'includes/helper-functions.php';

// Load main helper class
require_once VLT_TOOLKIT_PATH . 'includes/Toolkit.php';

// Load theme activation
require_once VLT_TOOLKIT_PATH . 'includes/ThemeActivation/ThemeActivation.php';
require_once VLT_TOOLKIT_PATH . 'includes/ThemeActivation/Init.php';

// Initialize on plugins_loaded
add_action(
	'plugins_loaded',
	function (): void {
		VLT\Toolkit\Toolkit::instance();
	},
	15,
);

// Initialize updater
add_action(
	'plugins_loaded',
	function (): void {
		// Only initialize updater if a remote URL is defined
		if (defined('VLT_TOOLKIT_UPDATE_URL') && VLT_TOOLKIT_UPDATE_URL) {
			require_once VLT_TOOLKIT_PATH . 'includes/Updater.php';
			new VLT\Toolkit\Updater(VLT_TOOLKIT_FILE, VLT_TOOLKIT_UPDATE_URL);
		}
	},
	20,
);

// Initialize dashboard
add_action(
	'plugins_loaded',
	function (): void {
		if (is_admin()) {
			require_once VLT_TOOLKIT_PATH . 'includes/Admin/Dashboard.php';
			VLT\Toolkit\Admin\Dashboard::instance();
		}
	},
	25,
);
