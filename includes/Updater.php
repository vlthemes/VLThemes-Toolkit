<?php

namespace VLT\Toolkit;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Updater class
 * Handles remote plugin updates
 */
class Updater {
	/**
	 * Plugin slug
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin file path
	 *
	 * @var string
	 */
	private $plugin_file;

	/**
	 * Remote update URL
	 *
	 * @var string
	 */
	private $remote_url;

	/**
	 * Current plugin version
	 *
	 * @var string
	 */
	private $current_version;

	/**
	 * Constructor
	 *
	 * Initializes the updater with plugin information and sets up
	 * WordPress hooks for automatic update checks.
	 *
	 * @param string $plugin_file full path to main plugin file
	 * @param string $remote_url  URL to remote JSON file with update info
	 */
	public function __construct( $plugin_file, $remote_url ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->remote_url  = $remote_url;

		// Get current plugin version
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data           = get_plugin_data( $plugin_file );
		$this->current_version = $plugin_data['Version'];

		// Initialize WordPress hooks
		$this->init_hooks();
	}

	/**
	 * Show admin notice about available update
	 */
	public function admin_notice() {
		if ( !current_user_can( 'update_plugins' ) ) {
			return;
		}

		$update_plugins = get_site_transient( 'update_plugins' );

		if ( !isset( $update_plugins->response[ $this->plugin_slug ] ) ) {
			return;
		}

		$plugin_data = get_plugin_data( $this->plugin_file );
		$plugin_name = $plugin_data['Name'] ?? dirname( $this->plugin_slug );
		$new_version = $update_plugins->response[ $this->plugin_slug ]->new_version;

		// Direct link to update action
		$update_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( $this->plugin_slug ) ),
			'upgrade-plugin_' . $this->plugin_slug,
		);

		?>
<div class="notice notice-warning is-dismissible">
	<p>
		<strong><?php echo esc_html( $plugin_name ); ?></strong> —
		<?php
				printf(
					esc_html__( 'version %s is available.', 'toolkit' ),
					'<strong>' . esc_html( $new_version ) . '</strong>',
				);
		?>
	</p>
	<p>
		<a href="<?php echo esc_url( $update_url ); ?>"
			class="button button-primary">
			<?php esc_html_e( 'Update now', 'toolkit' ); ?>
		</a>
	</p>
</div>
<?php
	}

	/**
	 * Check for plugin updates
	 *
	 * Hooks into WordPress update system to check for plugin updates
	 * from a remote JSON endpoint.
	 *
	 * @param object $transient wordPress update transient object
	 *
	 * @return object modified transient with update information
	 */
	public function check_update( $transient ) {
		// Skip if no plugins are checked
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get remote version info
		$remote_data = $this->get_remote_data();

		if ( !$remote_data ) {
			return $transient;
		}

		// Extract version and package information
		$new_version = isset( $remote_data->new_version ) ? (string) $remote_data->new_version : null;
		$package     = isset( $remote_data->package ) ? (string) $remote_data->package : '';

		// Add update response if newer version is available
		if ( $new_version && $package && version_compare( $this->current_version, $new_version, '<' ) ) {
			$transient->response[ $this->plugin_slug ] = (object) [
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $new_version,
				'package'     => $package,
			];
		}

		return $transient;
	}

	/**
	 * Clear update cache
	 *
	 * Removes cached update data to force a fresh check.
	 * Useful for manual update checks or troubleshooting.
	 */
	public function clear_cache() {
		$cache_key = 'vlt_toolkit_update_' . md5( $this->remote_url );
		delete_transient( $cache_key );
	}

	/**
	 * Initialize WordPress hooks
	 *
	 * Registers filters and actions for:
	 * - Checking plugin updates via WordPress update system
	 * - Displaying admin notices when updates are available
	 */
	private function init_hooks() {
		// Hook into WordPress plugin update check
		add_filter( 'site_transient_update_plugins', [ $this, 'check_update' ] );

		// Display admin notice when update is available
		add_action( 'admin_notices', [ $this, 'admin_notice' ] );
	}

	/**
	 * Get remote update data from JSON endpoint
	 *
	 * Fetches update information from remote URL and caches the result
	 * for 12 hours to reduce API calls.
	 *
	 * @return object|false update data object or false on failure
	 */
	private function get_remote_data() {
		// Check transient cache first
		$cache_key   = 'vlt_toolkit_update_' . md5( $this->remote_url );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		// Fetch remote data via HTTP
		$response = wp_remote_get(
			$this->remote_url,
			[
				'timeout' => 10,
			],
		);

		// Handle request errors
		if ( is_wp_error( $response ) ) {
			return false;
		}

		// Parse JSON response
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		// Validate required fields
		if ( !$data || !isset( $data->new_version ) || !isset( $data->package ) ) {
			return false;
		}

		// Cache for 12 hours to reduce API calls
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}
}
?>