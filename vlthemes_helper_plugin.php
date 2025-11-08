<?php

/**
 * Plugin Name: VLThemes Helper Plugin
 * Plugin URI: https://vlthemes.me/
 * Description: VLThemes Elementor Helper Plugin expands the functionality of the theme. Adds new icons, widgets and much more.
 * Version: 1.0.0
 * Author: VLThemes
 * Author URI: https://themeforest.net/user/vlthemes
 * License: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: vlt-helper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VLT_HELPER_VERSION', '1.0.0' );
define( 'VLT_HELPER_FILE', __FILE__ );
define( 'VLT_HELPER_PATH', plugin_dir_path( __FILE__ ) );
define( 'VLT_HELPER_URL', plugin_dir_url( __FILE__ ) );
define( 'VLT_HELPER_BASENAME', plugin_basename( __FILE__ ) );

// Define update URL (uncomment and set your update JSON URL)
// define( 'VLT_HELPER_UPDATE_URL', 'https://your-domain.com/updates/vlthemes-helper.json' );

// Load helper functions
require_once VLT_HELPER_PATH . 'includes/helper-functions.php';

// Load main helper class
require_once VLT_HELPER_PATH . 'includes/Helper.php';

// Initialize on plugins_loaded
add_action( 'plugins_loaded', function() {
	VLT\Helper\Helper::instance();
}, 15 );

// Initialize updater
add_action( 'plugins_loaded', function() {
	// Only initialize updater if a remote URL is defined
	if ( defined( 'VLT_HELPER_UPDATE_URL' ) && VLT_HELPER_UPDATE_URL ) {
		require_once VLT_HELPER_PATH . 'includes/Updater.php';
		new VLT\Helper\Updater( VLT_HELPER_FILE, VLT_HELPER_UPDATE_URL );
	}
}, 20 );

// Initialize dashboard
add_action( 'plugins_loaded', function() {
	if ( is_admin() ) {
		require_once VLT_HELPER_PATH . 'includes/Admin/Dashboard.php';
		VLT\Helper\Admin\Dashboard::instance();
	}
}, 25 );