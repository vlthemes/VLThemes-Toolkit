<?php

namespace VLT\Toolkit\ThemeActivation;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Theme Activation class
 *
 * Handles license activation and updates for VLThemes products
 */
class ThemeActivation {
	/**
	 * Encryption key (set via theme config)
	 *
	 * @var string
	 */
	public $key = '';

	/**
	 * Product ID
	 *
	 * @var string
	 */
	private $product_id;

	/**
	 * Product base slug
	 *
	 * @var string
	 */
	private $product_base;

	/**
	 * License server host
	 *
	 * @var string
	 */
	private $server_host = 'https://docs.vlthemes.me/wp-json/license-api/';

	/**
	 * Plugin/Theme file path
	 *
	 * @var string
	 */
	private $pluginFile;

	/**
	 * Singleton instance
	 *
	 * @var self
	 */
	private static $selfobj = null;

	/**
	 * Current version
	 *
	 * @var string
	 */
	private $version = '';

	/**
	 * Email address
	 *
	 * @var string
	 */
	private $emailAddress = '';

	/**
	 * On delete license callbacks
	 *
	 * @var array
	 */
	private static $_onDeleteLicense = [];

	/**
	 * Constructor
	 *
	 * @param string $plugin_base_file plugin/Theme file path
	 * @param string $product_id       product ID
	 * @param string $product_base     product slug
	 */
	public function __construct( $plugin_base_file = '', $product_id = '', $product_base = '' ) {
		$this->pluginFile   = $plugin_base_file;
		$this->product_id   = $product_id;
		$this->product_base = $product_base;

		// Always treat as theme activation (plugin updates not supported)

		$this->version = $this->getCurrentVersion();

		// Setup theme update checks
		$this->setupUpdateChecks();
	}

	/**
	 * Check old tied license
	 *
	 * @param object $oldRespons  old response reference
	 * @param object $responseObj response object reference
	 *
	 * @return bool success status
	 */
	private function __checkoldtied( &$oldRespons, &$responseObj ) {
		if ( !empty( $oldRespons ) && ( empty( $oldRespons->tried ) || $oldRespons->tried <= 2 ) ) {
			$oldRespons->next_request = strtotime( '+ 1 hour' );
			$oldRespons->tried        = empty( $oldRespons->tried ) ? 1 : ( $oldRespons->tried + 1 );
			$responseObj              = clone $oldRespons;
			unset( $responseObj->next_request );

			if ( isset( $responseObj->tried ) ) {
				unset( $responseObj->tried );
			}
			$this->SaveWPResponse( $oldRespons );

			return true;
		}

		return false;
	}

	/**
	 * Set email address
	 *
	 * @param string $emailAddress email address
	 */
	public function setEmailAddress( $emailAddress ) {
		$this->emailAddress = $emailAddress;
	}

	/**
	 * Add on delete callback
	 *
	 * @param callable $func callback function
	 */
	public static function addOnDelete( $func ) {
		self::$_onDeleteLicense[] = $func;
	}

	/**
	 * Clean update info cache
	 *
	 * Removes all cached update information to force a fresh check.
	 * Called when license is deactivated or manual refresh is needed.
	 */
	public function cleanUpdateInfo() {
		update_option( '_site_transient_update_themes', '' );
		delete_transient( $this->product_base . '_up' );
	}

	/**
	 * Check for theme updates
	 *
	 * WordPress filter callback that runs when checking for theme updates.
	 * Compares current version with server version and adds update to transient if newer version exists.
	 *
	 * @param object $transient update transient object from WordPress
	 *
	 * @return object modified transient with update information added
	 */
	public function checkThemeUpdate( $transient ) {
		// Fetch update info from license server
		$response = $this->getThemeUpdateInfo();

		if ( !empty( $response->theme ) ) {
			$theme_data = wp_get_theme();
			$index_name = $theme_data->get_template();

			// Check if server version is newer than current version
			if ( !empty( $response ) && version_compare( $this->version, $response->new_version, '<' ) ) {
				// Remove download_link field (we use 'package' field instead)
				unset( $response->download_link );

				// Add theme to update response (must be array for themes)
				$transient->response[ $index_name ] = (array) $response;
			} elseif ( isset( $transient->response[ $index_name ] ) ) {
				// Remove from updates if no newer version available
				unset( $transient->response[ $index_name ] );
			}
		}

		return $transient;
	}

	/**
	 * Get theme update information for WordPress API
	 *
	 * WordPress filter callback for 'themes_api' filter.
	 * Provides detailed information about the theme update when user clicks "View details".
	 * Returns update data including changelog, version info, and download package.
	 *
	 * @param mixed  $false  default false value
	 * @param string $action action being performed (theme_information, etc)
	 * @param object $arg    action arguments containing theme slug
	 *
	 * @return mixed theme info object or false if not our theme
	 */
	public function themeUpdateInfo( $false, $action, $arg ) {
		// Validate that slug is provided
		if ( empty( $arg->slug ) ) {
			return $false;
		}

		// Check if this is our theme being queried
		if ( !empty( $arg->slug ) && $arg->slug === $this->product_base ) {
			$response = $this->getThemeUpdateInfo();

			if ( !empty( $response ) ) {
				// Return detailed theme information
				return $response;
			}
		}

		// Not our theme, return default
		return $false;
	}

	/**
	 * Get instance
	 *
	 * @param string $plugin_base_file plugin/Theme file
	 * @param string $product_id       product ID
	 * @param string $product_base     product slug
	 *
	 * @return self instance
	 */
	public static function &getInstance( $plugin_base_file = null, $product_id = '', $product_base = '' ) {
		if ( empty( self::$selfobj ) ) {
			if ( !empty( $plugin_base_file ) ) {
				self::$selfobj = new self( $plugin_base_file, $product_id, $product_base );
			}
		}

		return self::$selfobj;
	}

	/**
	 * Get renew link
	 *
	 * @param object $responseObj response object
	 * @param string $type        type (s=support, l=license)
	 *
	 * @return string renew link
	 */
	public static function getRenewLink( $responseObj, $type = 's' ) {
		if ( empty( $responseObj->renew_link ) ) {
			return '';
		}
		$isShowButton = false;

		if ( 's' == $type ) {
			$support_str = strtolower( trim( $responseObj->support_end ) );

			if ( 'no support' == strtolower( trim( $responseObj->support_end ) ) ) {
				$isShowButton = true;
			} elseif ( !in_array( $support_str, [ 'unlimited' ] ) ) {
				if ( strtotime( 'ADD 30 DAYS', strtotime( $responseObj->support_end ) ) < time() ) {
					$isShowButton = true;
				}
			}

			if ( $isShowButton ) {
				return $responseObj->renew_link . ( false === strpos( $responseObj->renew_link, '?' ) ? '?type=s&lic=' . rawurlencode( $responseObj->license_key ) : '&type=s&lic=' . rawurlencode( $responseObj->license_key ) );
			}

			return '';
		}
		$isShowButton = false;
		$expire_str   = strtolower( trim( $responseObj->expire_date ) );

		if ( !in_array( $expire_str, [ 'unlimited', 'no expiry' ] ) ) {
			if ( strtotime( 'ADD 30 DAYS', strtotime( $responseObj->expire_date ) ) < time() ) {
				$isShowButton = true;
			}
		}

		if ( $isShowButton ) {
			return $responseObj->renew_link . ( false === strpos( $responseObj->renew_link, '?' ) ? '?type=l&lic=' . rawurlencode( $responseObj->license_key ) : '&type=l&lic=' . rawurlencode( $responseObj->license_key ) );
		}

		return '';
	}

	/**
	 * Remove license key
	 *
	 * @param string $plugin_base_file plugin/Theme file
	 * @param string $message          message reference
	 * @param string $product_id       product ID
	 * @param string $product_base     product slug
	 *
	 * @return bool success status
	 */
	public static function RemoveLicenseKey( $plugin_base_file, &$message = '', $product_id = '', $product_base = '' ) {
		$obj = self::getInstance( $plugin_base_file, $product_id, $product_base );
		$obj->cleanUpdateInfo();

		return $obj->_removeWPPluginLicense( $message );
	}

	/**
	 * Check WordPress plugin/theme license
	 *
	 * @param string $purchase_key     purchase key
	 * @param string $email            email address
	 * @param string $error            error reference
	 * @param object $responseObj      response object reference
	 * @param string $plugin_base_file plugin/Theme file
	 * @param string $product_id       product ID
	 * @param string $product_base     product slug
	 *
	 * @return bool success status
	 */
	public static function CheckWPPlugin( $purchase_key, $email, &$error = '', &$responseObj = null, $plugin_base_file = '', $product_id = '', $product_base = '' ) {
		$obj = self::getInstance( $plugin_base_file, $product_id, $product_base );
		$obj->setEmailAddress( $email );

		return $obj->_CheckWPPlugin( $purchase_key, $error, $responseObj );
	}

	/**
	 * Remove WordPress plugin/theme license
	 *
	 * @param string $message message reference
	 *
	 * @return bool success status
	 */
	final public function _removeWPPluginLicense( &$message = '' ) {
		$oldRespons = $this->getOldWPResponse();

		if ( !empty( $oldRespons->is_valid ) ) {
			if ( !empty( $oldRespons->license_key ) ) {
				$param    = $this->getParam( $oldRespons->license_key, $this->version );
				$response = $this->_request( 'product/deactive/' . $this->product_id, $param, $message );

				if ( empty( $response->code ) ) {
					if ( !empty( $response->status ) ) {
						$message = $response->msg;
						$this->removeOldWPResponse();

						return true;
					}
					$message = $response->msg;
				} else {
					$message = $response->message;
				}
			}
		} else {
			$this->removeOldWPResponse();

			return true;
		}

		return false;
	}

	/**
	 * Get register info
	 *
	 * @return object|null register info
	 */
	public static function GetRegisterInfo() {
		if ( !empty( self::$selfobj ) ) {
			return self::$selfobj->getOldWPResponse();
		}

		return null;
	}

	/**
	 * Check WordPress plugin/theme
	 *
	 * @param string $purchase_key purchase key
	 * @param string $error        error reference
	 * @param object $responseObj  response object reference
	 *
	 * @return bool success status
	 */
	final public function _CheckWPPlugin( $purchase_key, &$error = '', &$responseObj = null ) {
		if ( empty( $purchase_key ) ) {
			$this->removeOldWPResponse();
			$error = '';

			return false;
		}
		$oldRespons = $this->getOldWPResponse();
		$isForce    = false;

		if ( !empty( $oldRespons ) ) {
			if ( !empty( $oldRespons->expire_date ) && 'no expiry' != strtolower( $oldRespons->expire_date ) && strtotime( $oldRespons->expire_date ) < time() ) {
				$isForce = true;
			}

			if ( !$isForce && !empty( $oldRespons->is_valid ) && $oldRespons->next_request > time() && ( !empty( $oldRespons->license_key ) && $purchase_key == $oldRespons->license_key ) ) {
				$responseObj = clone $oldRespons;
				unset( $responseObj->next_request );

				return true;
			}
		}

		$param = $this->getParam( $purchase_key, $this->version );

		// Debug logging
		if ( WP_DEBUG ) {
			error_log( 'VLT Activation - Request URL: ' . $this->server_host . 'product/active/' . $this->product_id );
			error_log( 'VLT Activation - Product ID: ' . $this->product_id );
			error_log( 'VLT Activation - Product Base: ' . $this->product_base );
			error_log( 'VLT Activation - Domain: ' . $param->domain );
		}

		$response = $this->_request( 'product/active/' . $this->product_id, $param, $error );

		if ( empty( $response->is_request_error ) ) {
			if ( empty( $response->code ) ) {
				if ( !empty( $response->status ) ) {
					if ( !empty( $response->data ) ) {
						$serialObj = $this->decrypt( $response->data, $param->domain );

						$licenseObj = unserialize( $serialObj );

						if ( $licenseObj->is_valid ) {
							$responseObj           = new \stdClass();
							$responseObj->is_valid = $licenseObj->is_valid;

							if ( $licenseObj->request_duration > 0 ) {
								$responseObj->next_request = strtotime( "+ {$licenseObj->request_duration} hour" );
							} else {
								$responseObj->next_request = time();
							}
							$responseObj->expire_date        = $licenseObj->expire_date;
							$responseObj->support_end        = $licenseObj->support_end;
							$responseObj->license_title      = $licenseObj->license_title;
							$responseObj->license_key        = $purchase_key;
							$responseObj->msg                = $response->msg;
							$responseObj->renew_link         = !empty( $licenseObj->renew_link ) ? $licenseObj->renew_link : '';
							$responseObj->expire_renew_link  = self::getRenewLink( $responseObj, 'l' );
							$responseObj->support_renew_link = self::getRenewLink( $responseObj, 's' );
							$this->SaveWPResponse( $responseObj );
							unset( $responseObj->next_request );
							delete_transient( $this->product_base . '_up' );

							return true;
						} elseif ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {
							return true;
						}
						$this->removeOldWPResponse();
						$error = !empty( $response->msg ) ? $response->msg : '';
					} else {
						$error = 'Invalid data';
					}
				} else {
					$error = $response->msg;
				}
			} else {
				$error = $response->message;
			}
		} elseif ( $this->__checkoldtied( $oldRespons, $responseObj, $response ) ) {
			return true;
		} else {
			$this->removeOldWPResponse();
			$error = !empty( $response->msg ) ? $response->msg : '';
		}

		return $this->__checkoldtied( $oldRespons, $responseObj );
	}

	/**
	 * Setup theme update checks
	 *
	 * Initializes WordPress hooks for automatic theme updates.
	 * Registers filters to check for updates and provide update information.
	 */
	private function setupUpdateChecks() {
		if ( !function_exists( 'add_action' ) || !function_exists( 'add_filter' ) ) {
			return;
		}

		// Add action to force update check - accessible via admin-post.php?action=vlt_force_update_check
		add_action(
			'admin_post_vlt_force_update_check',
			function () {
				// Clear all cached update data
				update_option( '_site_transient_update_themes', '' );
				set_site_transient( 'update_themes', null );
				delete_transient( $this->product_base . '_up' );

				// Redirect back to themes page
				wp_redirect( admin_url( 'themes.php' ) );

				exit;
			},
		);

		// Hook into WordPress theme update system
		// This filter is called when WordPress checks for theme updates
		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'checkThemeUpdate' ] );

		// This filter provides detailed information about available updates
		add_filter( 'themes_api', [ $this, 'themeUpdateInfo' ], 10, 3 );
	}

	/**
	 * Get current version
	 *
	 * @return string version number
	 */
	private function getCurrentVersion() {
		if ( !function_exists( 'get_plugin_data' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$data = get_plugin_data( $this->pluginFile );

		if ( isset( $data['Version'] ) ) {
			return $data['Version'];
		}

		return '0';
	}

	/**
	 * Get theme update information from server
	 *
	 * Fetches update information from the license server.
	 * Uses cached data if available (1 day cache).
	 * Sends license key and current version to get download URL if available.
	 *
	 * @return object|null update information object or null if no update available
	 */
	private function getThemeUpdateInfo() {
		if ( !function_exists( 'wp_remote_get' ) ) {
			return null;
		}

		// Try to get cached response (prevents too many requests to license server)
		$response = get_transient( $this->product_base . '_up' );
		$oldFound = false;

		if ( !empty( $response['data'] ) ) {
			$response = unserialize( $this->decrypt( $response['data'] ) );

			if ( is_array( $response ) ) {
				$oldFound = true;
			}
		}

		// If no cached response, fetch from server
		if ( !$oldFound ) {
			$licenseInfo = self::GetRegisterInfo();
			$url         = $this->server_host . 'product/update/' . $this->product_id;

			// Append license key and version to URL if license is active
			// Format: /product/update/{product_id}/{license_key}/{current_version}
			if ( !empty( $licenseInfo->license_key ) ) {
				$url .= '/' . $licenseInfo->license_key . '/' . $this->version;
			}

			$args = [
				'sslverify'   => true,
				'timeout'     => 120,
				'redirection' => 5,
				'cookies'     => [],
			];

			$response = wp_remote_get( $url, $args );

			// Retry without SSL verification if first attempt fails
			if ( is_wp_error( $response ) ) {
				$args['sslverify'] = false;
				$response          = wp_remote_get( $url, $args );
			}
		}

		if ( is_wp_error( $response ) ) {
			return null;
		}

		$body         = $response['body'];
		$responseJson = @json_decode( $body );

		// Cache the response for 1 day to reduce server load
		if ( !$oldFound ) {
			set_transient(
				$this->product_base . '_up',
				[ 'data' => $this->encrypt( serialize( [ 'body' => $body ] ) ) ],
				DAY_IN_SECONDS,
			);
		}

		// Decrypt if response is encrypted (determined by checking if JSON decode fails)
		if ( !( is_object( $responseJson ) && isset( $responseJson->status ) ) ) {
			$body         = $this->decrypt( $body, $this->key );
			$responseJson = json_decode( $body );
		}

		// Process valid response with update information
		if ( is_object( $responseJson ) && !empty( $responseJson->status ) && !empty( $responseJson->data->new_version ) ) {
			$theme_data = wp_get_theme();

			// Set required fields for WordPress update system
			$responseJson->data->theme              = $theme_data->get_template();
			$responseJson->data->new_version        = !empty( $responseJson->data->new_version ) ? $responseJson->data->new_version : '';
			$responseJson->data->url                = !empty( $responseJson->data->url ) ? $responseJson->data->url : '';
			$responseJson->data->package            = !empty( $responseJson->data->download_link ) ? $responseJson->data->download_link : '';
			$responseJson->data->update_denied_type = !empty( $responseJson->data->update_denied_type ) ? $responseJson->data->update_denied_type : '';

			return $responseJson->data;
		}

		return null;
	}

	/**
	 * Encrypt data
	 *
	 * @param string $plainText plain text
	 * @param string $password  password
	 *
	 * @return string encrypted data
	 */
	private function encrypt( $plainText, $password = '' ) {
		if ( empty( $password ) ) {
			$password = $this->key;
		}
		$plainText = rand( 10, 99 ) . $plainText . rand( 10, 99 );
		$method    = 'aes-256-cbc';
		$key       = substr( hash( 'sha256', $password, true ), 0, 32 );
		$iv        = substr( strtoupper( md5( $password ) ), 0, 16 );

		return base64_encode( openssl_encrypt( $plainText, $method, $key, OPENSSL_RAW_DATA, $iv ) );
	}

	/**
	 * Decrypt data
	 *
	 * @param string $encrypted encrypted data
	 * @param string $password  password
	 *
	 * @return string decrypted data
	 */
	private function decrypt( $encrypted, $password = '' ) {
		if ( empty( $password ) ) {
			$password = $this->key;
		}

		// Debug logging
		if ( WP_DEBUG ) {
			error_log( 'VLT Activation - Decrypt key: ' . $password );
			error_log( 'VLT Activation - Encrypted length: ' . strlen( $encrypted ) );
		}

		$method    = 'aes-256-cbc';
		$key       = substr( hash( 'sha256', $password, true ), 0, 32 );
		$iv        = substr( strtoupper( md5( $password ) ), 0, 16 );
		$plaintext = openssl_decrypt( base64_decode( $encrypted ), $method, $key, OPENSSL_RAW_DATA, $iv );

		// Debug logging
		if ( WP_DEBUG ) {
			error_log( 'VLT Activation - Plaintext result: ' . ( false === $plaintext ? 'FALSE' : substr( $plaintext, 0, 100 ) ) );
			error_log( 'VLT Activation - Plaintext length: ' . strlen( $plaintext ) );
		}

		if ( false === $plaintext || strlen( $plaintext ) <= 4 ) {
			if ( WP_DEBUG ) {
				error_log( 'VLT Activation - Decryption failed or too short' );
			}

			return '';
		}

		return substr( $plaintext, 2, -2 );
	}

	/**
	 * Decrypt object
	 *
	 * @param string $ciphertext encrypted text
	 *
	 * @return object decrypted object
	 */
	private function decryptObj( $ciphertext ) {
		$text = $this->decrypt( $ciphertext );

		return unserialize( $text );
	}

	/**
	 * Get domain
	 *
	 * @return string domain URL
	 */
	private function getDomain() {
		// Try to use site_url() if function is available
		if ( function_exists( 'site_url' ) ) {
			return site_url();
		}

		// Try to use bloginfo() if WordPress is defined
		if ( defined( 'WPINC' ) && function_exists( 'get_bloginfo' ) ) {
			return get_bloginfo( 'url' );
		}

		// Fallback to $_SERVER
		$scheme = 'http';

		if ( !empty( $_SERVER['HTTPS'] ) && 'on' === $_SERVER['HTTPS'] ) {
			$scheme = 'https';
		} elseif ( !empty( $_SERVER['REQUEST_SCHEME'] ) ) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		}

		$host   = !empty( $_SERVER['HTTP_HOST'] ) ? $_SERVER['HTTP_HOST'] : 'localhost';
		$script = !empty( $_SERVER['SCRIPT_NAME'] ) ? $_SERVER['SCRIPT_NAME'] : '';

		$base_url = $scheme . '://' . $host;
		$base_url .= str_replace( basename( $script ), '', $script );

		return rtrim( $base_url, '/' );
	}

	/**
	 * Get email
	 *
	 * @return string email address
	 */
	private function getEmail() {
		return $this->emailAddress;
	}

	/**
	 * Process response
	 *
	 * @param string $response response data
	 *
	 * @return object processed response
	 */
	private function processs_response( $response ) {
		$resbk = '';

		if ( !empty( $response ) ) {
			// Debug logging
			if ( WP_DEBUG ) {
				error_log( 'VLT Activation - Raw response: ' . substr( $response, 0, 200 ) );
			}

			if ( !empty( $this->key ) ) {
				$resbk    = $response;
				$response = $this->decrypt( $response );

				// Debug logging
				if ( WP_DEBUG ) {
					error_log( 'VLT Activation - Decrypted response: ' . substr( $response, 0, 200 ) );
				}
			}
			$response = json_decode( $response );

			if ( is_object( $response ) ) {
				return $response;
			}

			// Debug logging
			if ( WP_DEBUG ) {
				error_log( 'VLT Activation - JSON decode failed' );
				error_log( 'VLT Activation - Trying to decode backup: ' . substr( $resbk, 0, 200 ) );
			}

			$response         = new \stdClass();
			$response->status = false;
			$response->msg    = 'Response Error, contact with the author or update the plugin or theme';

			// Try to get error message from non-encrypted response
			$bkjson = @json_decode( $resbk );

			if ( !empty( $bkjson->msg ) ) {
				$response->msg = $bkjson->msg;
			}

			$response->data = null;

			return $response;
		}
		$response         = new \stdClass();
		$response->msg    = 'unknown response';
		$response->status = false;
		$response->data   = null;

		return $response;
	}

	/**
	 * Make request to server
	 *
	 * @param string $relative_url relative URL
	 * @param object $data         request data
	 * @param string $error        error message reference
	 *
	 * @return object response object
	 */
	private function _request( $relative_url, $data, &$error = '' ) {
		$response                   = new \stdClass();
		$response->status           = false;
		$response->msg              = 'Empty Response';
		$response->is_request_error = false;
		$finalData                  = json_encode( $data );

		if ( !empty( $this->key ) ) {
			$finalData = $this->encrypt( $finalData );
		}
		$url = rtrim( $this->server_host, '/' ) . '/' . ltrim( $relative_url, '/' );

		if ( function_exists( 'wp_remote_post' ) ) {
			$rq_params = [
				'method'      => 'POST',
				'sslverify'   => true,
				'timeout'     => 120,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,
				'headers'     => [],
				'body'        => $finalData,
				'cookies'     => [],
			];
			$serverResponse = wp_remote_post( $url, $rq_params );

			if ( is_wp_error( $serverResponse ) ) {
				$rq_params['sslverify'] = false;
				$serverResponse         = wp_remote_post( $url, $rq_params );

				if ( is_wp_error( $serverResponse ) ) {
					$response->msg = $serverResponse->get_error_message();

					$response->status           = false;
					$response->data             = null;
					$response->is_request_error = true;

					return $response;
				} elseif ( !empty( $serverResponse['body'] ) && ( is_array( $serverResponse ) && 200 === (int) wp_remote_retrieve_response_code( $serverResponse ) ) && 'GET404' != $serverResponse['body'] ) {
					return $this->processs_response( $serverResponse['body'] );
				}
			} elseif ( !empty( $serverResponse['body'] ) && ( is_array( $serverResponse ) && 200 === (int) wp_remote_retrieve_response_code( $serverResponse ) ) && 'GET404' != $serverResponse['body'] ) {
				return $this->processs_response( $serverResponse['body'] );
			}
		}

		if ( !extension_loaded( 'curl' ) ) {
			$response->msg              = 'Curl extension is missing';
			$response->status           = false;
			$response->data             = null;
			$response->is_request_error = true;

			return $response;
		}
		// curl when fall back
		$curlParams = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => true,
			CURLOPT_ENCODING       => '',
			CURLOPT_MAXREDIRS      => 10,
			CURLOPT_TIMEOUT        => 120,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_POSTFIELDS     => $finalData,
			CURLOPT_HTTPHEADER     => [
				'Content-Type: text/plain',
				'cache-control: no-cache',
			],
		];
		$curl = curl_init();
		curl_setopt_array( $curl, $curlParams );
		$serverResponse = curl_exec( $curl );
		$curlErrorNo    = curl_errno( $curl );
		$error          = curl_error( $curl );
		curl_close( $curl );

		if ( !$curlErrorNo ) {
			if ( !empty( $serverResponse ) ) {
				return $this->processs_response( $serverResponse );
			}
		} else {
			$curl                                 = curl_init();
			$curlParams[ CURLOPT_SSL_VERIFYPEER ] = false;
			$curlParams[ CURLOPT_SSL_VERIFYHOST ] = false;
			curl_setopt_array( $curl, $curlParams );
			$serverResponse = curl_exec( $curl );
			$curlErrorNo    = curl_errno( $curl );
			$error          = curl_error( $curl );
			curl_close( $curl );

			if ( !$curlErrorNo ) {
				if ( !empty( $serverResponse ) ) {
					return $this->processs_response( $serverResponse );
				}
			} else {
				$response->msg              = $error;
				$response->status           = false;
				$response->data             = null;
				$response->is_request_error = true;

				return $response;
			}
		}
		$response->msg              = 'unknown response';
		$response->status           = false;
		$response->data             = null;
		$response->is_request_error = true;

		return $response;
	}

	/**
	 * Get request parameters
	 *
	 * @param string $purchase_key purchase key
	 * @param string $app_version  app version
	 * @param string $admin_email  admin email
	 *
	 * @return object request parameters
	 */
	private function getParam( $purchase_key, $app_version, $admin_email = '' ) {
		$req               = new \stdClass();
		$req->license_key  = $purchase_key;
		$req->email        = !empty( $admin_email ) ? $admin_email : $this->getEmail();
		$req->domain       = $this->getDomain();
		$req->app_version  = $app_version;
		$req->product_id   = $this->product_id;
		$req->product_base = $this->product_base;

		return $req;
	}

	/**
	 * Get key name for storing license
	 *
	 * @return string key name
	 */
	private function getKeyName() {
		return hash( 'crc32b', $this->getDomain() . $this->pluginFile . $this->product_id . $this->product_base . $this->key . 'LIC' );
	}

	/**
	 * Save WordPress response
	 *
	 * @param object $response response to save
	 */
	private function SaveWPResponse( $response ) {
		$key  = $this->getKeyName();
		$data = $this->encrypt( serialize( $response ), $this->getDomain() );
		update_option( $key, $data ) or add_option( $key, $data );
	}

	/**
	 * Get old WordPress response
	 *
	 * @return object|null saved response
	 */
	private function getOldWPResponse() {
		$key      = $this->getKeyName();
		$response = get_option( $key, null );

		if ( empty( $response ) ) {
			return null;
		}

		return unserialize( $this->decrypt( $response, $this->getDomain() ) );
	}

	/**
	 * Remove old WordPress response
	 *
	 * @return bool success status
	 */
	private function removeOldWPResponse() {
		$key       = $this->getKeyName();
		$isDeleted = delete_option( $key );
		foreach ( self::$_onDeleteLicense as $func ) {
			if ( is_callable( $func ) ) {
				call_user_func( $func );
			}
		}

		return $isDeleted;
	}
}
