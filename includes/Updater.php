<?php
/**
 * VLT Helper Updater
 *
 * @package VLT Helper
 */

namespace VLT\Helper;

if ( ! defined( 'ABSPATH' ) ) {
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
	 * @param string $plugin_file Plugin file path
	 * @param string $remote_url Remote update JSON URL
	 */
	public function __construct( $plugin_file, $remote_url ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_slug = plugin_basename( $plugin_file );
		$this->remote_url  = $remote_url;

		// Get current version
		if ( ! function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$plugin_data = get_plugin_data( $plugin_file );
		$this->current_version = $plugin_data['Version'];

		// Initialize hooks
		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	* Show admin notice about available update
	*/
	public function admin_notice() {
		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$update_plugins = get_site_transient( 'update_plugins' );

		if ( ! isset( $update_plugins->response[ $this->plugin_slug ] ) ) {
			return;
		}

		$plugin_data = get_plugin_data( $this->plugin_file );
		$plugin_name = $plugin_data['Name'] ?? dirname( $this->plugin_slug );
		$new_version = $update_plugins->response[ $this->plugin_slug ]->new_version;

		// Прямая ссылка на обновление
		$update_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . urlencode( $this->plugin_slug ) ),
			'upgrade-plugin_' . $this->plugin_slug
		);

		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php echo esc_html( $plugin_name ); ?></strong> —
				<?php printf(
					esc_html__( 'version %s is available.', 'vlt-helper' ),
					'<strong>' . esc_html( $new_version ) . '</strong>'
				); ?>
			</p>
			<p>
				<a href="<?php echo esc_url( $update_url ); ?>" class="button button-primary">
					<?php esc_html_e( 'Update now', 'vlt-helper' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Check for updates
	 *
	 * @param object $transient Update transient
	 * @return object
	 */
	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		// Get remote version info
		$remote_data = $this->get_remote_data();

		if ( ! $remote_data ) {
			return $transient;
		}

		// Check if update is available
		$new_version = isset( $remote_data->new_version ) ? (string) $remote_data->new_version : null;
		$package = isset( $remote_data->package ) ? (string) $remote_data->package : '';

		if ( $new_version && $package && version_compare( $this->current_version, $new_version, '<' ) ) {
			$transient->response[ $this->plugin_slug ] = (object) array(
				'slug'        => dirname( $this->plugin_slug ),
				'plugin'      => $this->plugin_slug,
				'new_version' => $new_version,
				'package'     => $package,
			);
		}

		return $transient;
	}

	/**
	 * Get remote update data
	 *
	 * @return object|false
	 */
	private function get_remote_data() {
		// Check transient cache
		$cache_key = 'vlt_helper_update_' . md5( $this->remote_url );
		$cached_data = get_transient( $cache_key );

		if ( $cached_data !== false ) {
			return $cached_data;
		}

		// Fetch remote data
		$response = wp_remote_get( $this->remote_url, array(
			'timeout' => 10,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! $data || ! isset( $data->new_version ) || ! isset( $data->package ) ) {
			return false;
		}

		// Cache for 12 hours
		set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );

		return $data;
	}

	/**
	 * Clear update cache
	 */
	public function clear_cache() {
		$cache_key = 'vlt_helper_update_' . md5( $this->remote_url );
		delete_transient( $cache_key );
	}
}
