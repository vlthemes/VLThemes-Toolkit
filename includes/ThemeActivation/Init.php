<?php

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check if theme is activated
 *
 * Sites activated with the old licenser keep their `{slug}_is_activated` flag.
 *
 * @return bool
 */
if ( !function_exists( 'vlt_is_theme_activated' ) ) {
	function vlt_is_theme_activated() {
		return VLThemesThemeActivation::is_active() || 1 == get_option( get_template() . '_is_activated', 0 );
	}
}

/**
 * Theme Activation Manager
 *
 * Activation with a ThemeForest purchase code or a Gumroad license key on the VLThemes license server,
 * daily license check and theme updates from the same server.
 * Without activation the theme works as usual: updates come without a package.
 *
 * Config (filter `vlt_toolkit_license`): [ 'item' => theme slug on the server, 'server' => REST base ].
 */
if ( !class_exists( 'VLThemesThemeActivation' ) ) {
	class VLThemesThemeActivation {
		const CRON   = 'vlt_toolkit_license_check';
		const UPDATE = 'vlt_toolkit_theme_update';
		const PAGE   = 'vlt-dashboard-activate-theme';

		/**
		 * Theme slug on the license server
		 *
		 * @var string
		 */
		public $item;

		/**
		 * License server REST base
		 *
		 * @var string
		 */
		public $server;

		/**
		 * Constructor
		 */
		public function __construct() {
			$config = wp_parse_args(
				(array) apply_filters( 'vlt_toolkit_license', [] ),
				[
					'item'   => get_template(),
					'server' => 'https://vlthemes.me/wp-json/vlthemes/v1/',
				],
			);

			$this->item   = (string) $config['item'];
			$this->server = trailingslashit( (string) $config['server'] );

			add_action( 'vlt_toolkit_print_activation_form', [ $this, 'render' ] );
			add_action( 'vlt_toolkit_print_support_reminder', [ $this, 'render_support' ] );
			add_action( 'admin_post_vlt_toolkit_license', [ $this, 'handle' ] );
			add_action( self::CRON, [ $this, 'check' ] );
			add_action( 'init', [ $this, 'schedule' ] );
			add_filter( 'pre_set_site_transient_update_themes', [ $this, 'update' ] );
		}

		/**
		 * Stored license of the current theme: [ key, status, product, type, supported_until, renew_url, checked ]
		 *
		 * @return array
		 */
		public static function get() {
			return wp_parse_args( (array) get_option( 'vlt_toolkit_license_' . get_template(), [] ), [ 'key' => '', 'status' => '', 'product' => '', 'type' => '', 'supported_until' => '', 'renew_url' => '', 'checked' => 0 ] );
		}

		/**
		 * Is the theme activated on this site
		 *
		 * @return bool
		 */
		public static function is_active() {
			return 'active' === self::get()['status'];
		}

		/**
		 * Daily check while there is a key
		 */
		public function schedule() {
			if ( self::get()['key'] && !wp_next_scheduled( self::CRON ) ) {
				wp_schedule_event( time() + DAY_IN_SECONDS, 'daily', self::CRON );
			}
		}

		/**
		 * This site's domain
		 *
		 * @return string
		 */
		private function domain() {
			return (string) wp_parse_url( home_url(), PHP_URL_HOST );
		}

		/**
		 * Request to the license server → decoded answer or WP_Error (server message when there is one)
		 *
		 * @return array|WP_Error
		 */
		private function request( $route, $key, $method = 'POST' ) {
			$args = [ 'key' => $key, 'domain' => $this->domain(), 'item' => $this->item ];

			$response = 'GET' === $method
				? wp_remote_get( add_query_arg( array_map( 'rawurlencode', $args ), $this->server . $route ), [ 'timeout' => 15 ] )
				: wp_remote_post( $this->server . $route, [ 'timeout' => 15, 'body' => $args ] );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( 200 !== $code || !is_array( $body ) ) {
				return new WP_Error(
					is_array( $body ) && !empty( $body['code'] ) ? $body['code'] : 'vlt_toolkit_license_server',
					is_array( $body ) && !empty( $body['message'] ) ? $body['message'] : esc_html__( 'The license server can\'t be reached. Please try again later.', 'toolkit' ),
					[ 'status' => $code ],
				);
			}

			return $body;
		}

		/**
		 * Save the server answer
		 */
		private function save( $key, $body ) {
			update_option(
				'vlt_toolkit_license_' . get_template(),
				[
					'key'             => $key,
					'status'          => (string) ( $body['status'] ?? '' ),
					'product'         => (string) ( $body['product'] ?? '' ),
					'type'            => (string) ( $body['type'] ?? '' ),
					'supported_until' => (string) ( $body['supported_until'] ?? '' ),
					'renew_url'       => esc_url_raw( (string) ( $body['renew_url'] ?? '' ) ),
					'checked'         => time(),
				],
				false,
			);

			$this->flush_updates();
		}

		/**
		 * Updates: ask the server again with the new license
		 */
		private function flush_updates() {
			delete_transient( self::UPDATE );
			delete_site_transient( 'update_themes' );
		}

		/**
		 * Action: activate / deactivate / refresh (the support reminder's "Refresh")
		 */
		public function handle() {
			if ( !current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'toolkit' ) );
			}

			check_admin_referer( 'vlt_toolkit_license' );

			$license = self::get();
			$message = [ 'type' => 'success', 'text' => '' ];

			if ( 'deactivate' === ( $_POST['license_action'] ?? '' ) ) {
				$body = $this->request( 'license/deactivate', $license['key'] );

				// Server can't be reached: keep the key, the domain is still taken there
				if ( is_wp_error( $body ) && 'http_request_failed' === $body->get_error_code() ) {
					$message = [ 'type' => 'error', 'text' => $body->get_error_message() ];
				} else {
					delete_option( 'vlt_toolkit_license_' . get_template() );
					delete_option( get_template() . '_is_activated' );
					wp_clear_scheduled_hook( self::CRON );
					$this->flush_updates();
					$message['text'] = esc_html__( 'License deactivated.', 'toolkit' );
				}
			} elseif ( 'refresh' === ( $_POST['license_action'] ?? '' ) ) {
				// "Refresh" of the support reminder: ask the server now, e.g. right after renewing support
				$body = $license['key'] ? $this->request( 'license/check', $license['key'] ) : new WP_Error( 'vlt_toolkit_license_empty', esc_html__( 'Enter your purchase code or license key.', 'toolkit' ) );

				if ( is_wp_error( $body ) ) {
					$message = [ 'type' => 'error', 'text' => $body->get_error_message() ];

					// The key is gone from the server: not active here either (same as the daily check)
					if ( 404 === ( $body->get_error_data()['status'] ?? 0 ) ) {
						$this->save( $license['key'], [ 'status' => 'inactive' ] );
					}
				} else {
					$this->save( $license['key'], $body );
					$message['text'] = esc_html__( 'License details updated.', 'toolkit' );
				}
			} else {
				$key  = trim( sanitize_text_field( wp_unslash( $_POST['license_key'] ?? '' ) ) );
				$body = $key ? $this->request( 'license/activate', $key ) : new WP_Error( 'vlt_toolkit_license_empty', esc_html__( 'Enter your purchase code or license key.', 'toolkit' ) );

				if ( is_wp_error( $body ) ) {
					$message = [ 'type' => 'error', 'text' => $body->get_error_message() ];
				} else {
					$this->save( $key, $body );
					$message['text'] = (string) ( $body['message'] ?? '' );
				}
			}

			set_transient( 'vlt_toolkit_license_message_' . get_current_user_id(), $message, MINUTE_IN_SECONDS );

			wp_safe_redirect( admin_url( 'admin.php?page=' . self::PAGE ) );

			exit;
		}

		/**
		 * Daily check: changes made on the license server reach the site. Server down → the last status stays.
		 */
		public function check() {
			$license = self::get();

			if ( !$license['key'] ) {
				return;
			}

			$body = $this->request( 'license/check', $license['key'] );

			if ( !is_wp_error( $body ) ) {
				$this->save( $license['key'], $body );
			} elseif ( 404 === ( $body->get_error_data()['status'] ?? 0 ) ) {
				$this->save( $license['key'], [ 'status' => 'inactive' ] );
			}
		}

		/**
		 * Theme updates from the license server (package only for an active license)
		 */
		public function update( $transient ) {
			if ( empty( $transient->checked ) ) {
				return $transient;
			}

			$remote = get_transient( self::UPDATE );

			if ( false === $remote ) {
				$remote = $this->request( 'theme/update', self::get()['key'], 'GET' );
				$remote = is_wp_error( $remote ) ? [] : $remote;

				set_transient( self::UPDATE, $remote, $remote ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
			}

			$theme = get_template();

			if ( !empty( $remote['version'] ) && version_compare( $remote['version'], wp_get_theme( $theme )->get( 'Version' ), '>' ) ) {
				$transient->response[ $theme ] = [
					'theme'        => $theme,
					'new_version'  => $remote['version'],
					'url'          => $remote['url'] ?? '',
					'package'      => $remote['package'] ?? '',
					'requires'     => $remote['requires'] ?? '',
					'requires_php' => $remote['requires_php'] ?? '',
				];
			}

			return $transient;
		}

		/**
		 * Support end date: 07-11-2026 (active, 37 days left) / 07-11-2026 (expired 3 days ago)
		 */
		private function support_label( $date ) {
			$until = $date ? strtotime( $date ) : 0;

			if ( !$until ) {
				return '';
			}

			$days = (int) floor( ( $until - strtotime( gmdate( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );

			return gmdate( 'd-m-Y', $until ) . ' (' . ( $days >= 0
				/* translators: %d: days */
				? sprintf( _n( 'active, %d day left', 'active, %d days left', $days, 'toolkit' ), $days )
				/* translators: %d: days */
				: sprintf( _n( 'expired %d day ago', 'expired %d days ago', -$days, 'toolkit' ), -$days ) ) . ')';
		}

		/**
		 * Support reminder column of the Activate Theme page: shown while the license is active and support
		 * ends within N days (filter `vlt_toolkit_support_reminder_days`, 30) or is over. "Renew support" leads to
		 * the server's `renew_url` (ThemeForest item page or the Gumroad support extension); for Gumroad the key
		 * can be copied for the extension's "License key" field.
		 */
		public function render_support() {
			$license = self::get();
			$until   = $license['supported_until'] ? strtotime( $license['supported_until'] ) : 0;

			if ( !self::is_active() || !$until ) {
				return;
			}

			$days = (int) floor( ( $until - strtotime( gmdate( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );

			if ( $days > (int) apply_filters( 'vlt_toolkit_support_reminder_days', 30 ) ) {
				return;
			}

			$dashboard = VLT\Toolkit\Admin\Dashboard::instance();
			$renew     = $license['renew_url'] ?: $dashboard->products_url . get_template() . '/';
			$envato    = 'envato' === $license['type'];
			$date      = date_i18n( 'j F Y', $until );
			$masked    = $license['key'] ? substr( $license['key'], 0, 4 ) . str_repeat( '•', 8 ) . substr( $license['key'], -4 ) : '';

			if ( $days < 0 ) {
				/* translators: 1: days, 2: date */
				$status = sprintf( _n( 'Your support ended %1$d day ago — on %2$s.', 'Your support ended %1$d days ago — on %2$s.', -$days, 'toolkit' ), -$days, $date );
			} elseif ( 0 === $days ) {
				$status = __( 'Your support ends today.', 'toolkit' );
			} elseif ( 1 === $days ) {
				$status = __( 'Your support ends tomorrow.', 'toolkit' );
			} else {
				/* translators: 1: days, 2: date */
				$status = sprintf( _n( 'Your support ends in %1$d day — on %2$s.', 'Your support ends in %1$d days — on %2$s.', $days, 'toolkit' ), $days, $date );
			}
			?>

<div class="vlt-masonry-item">
	<div class="vlt-widget">
		<div class="vlt-widget__title">
			<mark><?php esc_html_e( 'Support', 'toolkit' ); ?></mark>
			<span class="vlt-badge false"><?php echo $days >= 0 ? esc_html__( 'Ends soon', 'toolkit' ) : esc_html__( 'Expired', 'toolkit' ); ?></span>
		</div>

		<div class="vlt-widget__content">
			<div class="notice notice-<?php echo $days >= 0 ? 'warning' : 'error'; ?> inline mb-sm">
				<p><?php echo esc_html( $status ); ?></p>
			</div>

			<p>
				<?php
				echo $days >= 0
					? esc_html__( 'Renew to keep getting help from our team in support tickets. Theme updates stay with you either way.', 'toolkit' )
					: esc_html__( 'Renew to get help from our team in support tickets again. Theme updates stay with you either way.', 'toolkit' );
				?>
			</p>

			<?php if ( $envato ) { ?>
			<p class="mt-sm"><?php esc_html_e( 'Renew on ThemeForest — the new date will show up here within a day.', 'toolkit' ); ?></p>
			<?php } elseif ( $license['key'] ) { ?>
			<p class="mt-sm"><?php esc_html_e( 'When renewing, paste your license key into the "License key" field at checkout — your support will be extended automatically.', 'toolkit' ); ?></p>
			<div class="vlt-form-group vlt-form-group--copy mt-xs">
				<input type="text" value="<?php echo esc_attr( $masked ); ?>" readonly aria-label="<?php esc_attr_e( 'License key', 'toolkit' ); ?>">
				<button class="button button-secondary" type="button" data-key="<?php echo esc_attr( $license['key'] ); ?>" onclick="navigator.clipboard.writeText(this.dataset.key).then(() => { this.textContent = '<?php echo esc_js( __( 'Copied', 'toolkit' ) ); ?>'; })"><?php esc_html_e( 'Copy key', 'toolkit' ); ?></button>
			</div>
			<?php } ?>

			<form class="vlt-btn-group mt-sm" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<a href="<?php echo esc_url( $dashboard->utm( $renew, 'renew-support' ) ); ?>" target="_blank" rel="noopener" class="button button-primary"><?php echo $envato ? esc_html__( 'Renew on ThemeForest', 'toolkit' ) : esc_html__( 'Renew support', 'toolkit' ); ?></a>
				<input type="hidden" name="action" value="vlt_toolkit_license">
				<input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'vlt_toolkit_license' ) ); ?>">
				<button class="button button-secondary" type="submit" name="license_action" value="refresh" title="<?php esc_attr_e( 'Already renewed? Get the new date now', 'toolkit' ); ?>"><?php esc_html_e( 'Refresh', 'toolkit' ); ?></button>
			</form>
		</div>
	</div>
</div>

			<?php
		}

		/**
		 * Render license widget
		 */
		public function render() {
			$license = self::get();
			$active  = self::is_active();
			$user    = 'vlt_toolkit_license_message_' . get_current_user_id();
			$message = get_transient( $user );
			$masked  = $license['key'] ? substr( $license['key'], 0, 4 ) . str_repeat( '•', 8 ) . substr( $license['key'], -4 ) : '';

			delete_transient( $user );
			?>

<div class="vlt-widget">
	<div class="vlt-widget__title">
		<mark<?php echo $active ? ' class="true"' : ''; ?>><?php esc_html_e( 'Theme License', 'toolkit' ); ?></mark>
		<span
			class="vlt-badge <?php echo $active ? 'true' : 'false'; ?>"><?php echo $active ? esc_html__( 'Active', 'toolkit' ) : esc_html__( 'Not Active', 'toolkit' ); ?></span>
	</div>

	<div class="vlt-widget__content">

		<?php if ( !empty( $message['text'] ) ) { ?>
		<div class="notice notice-<?php echo esc_attr( $message['type'] ); ?> inline mb-sm">
			<p><?php echo esc_html( $message['text'] ); ?></p>
		</div>
		<?php } ?>

		<form method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="vlt_toolkit_license">
			<?php wp_nonce_field( 'vlt_toolkit_license' ); ?>

			<?php if ( $license['key'] ) { ?>

			<table class="widefat" cellspacing="0">
				<tbody>
					<tr>
						<td><?php esc_html_e( 'Status:', 'toolkit' ); ?></td>
						<td><mark class="<?php echo $active ? 'true' : 'false'; ?>"><?php echo esc_html( $license['status'] ? ucfirst( $license['status'] ) : '—' ); ?></mark></td>
					</tr>
					<?php if ( $license['product'] ) { ?>
					<tr>
						<td><?php esc_html_e( 'Product:', 'toolkit' ); ?></td>
						<td><?php echo esc_html( $license['product'] ); ?></td>
					</tr>
					<?php } ?>
					<tr>
						<td><?php esc_html_e( 'Domain:', 'toolkit' ); ?></td>
						<td><?php echo esc_html( $this->domain() ); ?></td>
					</tr>
					<?php if ( $license['supported_until'] ) { ?>
					<tr>
						<td><?php esc_html_e( 'Support until:', 'toolkit' ); ?></td>
						<td><?php echo esc_html( $this->support_label( $license['supported_until'] ) ); ?></td>
					</tr>
					<?php } ?>
					<tr>
						<td><?php esc_html_e( 'Your License Key:', 'toolkit' ); ?></td>
						<td>
							<div class="vlt-form-group">
								<input class="license-key" type="text" value="<?php echo esc_attr( $masked ); ?>" readonly>
							</div>
						</td>
					</tr>
				</tbody>
			</table>

			<?php if ( !$active ) { ?>
			<input type="hidden" name="license_key" value="<?php echo esc_attr( $license['key'] ); ?>">
			<button class="button button-primary mt-sm" type="submit" name="license_action" value="activate"><?php esc_html_e( 'Activate', 'toolkit' ); ?></button>
			<?php } ?>
			<button class="button button-secondary mt-sm" type="submit" name="license_action" value="deactivate"><?php esc_html_e( 'Deactivate', 'toolkit' ); ?></button>

			<?php } else { ?>

			<p class="mb-sm">
				<?php /* translators: %s: theme name */ printf( esc_html__( 'To activate your copy of %s, enter your purchase code or license key.', 'toolkit' ), esc_html( VLT\Toolkit\Admin\Dashboard::instance()->theme_name ) ); ?>
			</p>

			<div class="vlt-form-group">
				<label
					for="vlt-license-key"><?php esc_html_e( 'Purchase code / license key', 'toolkit' ); ?></label>
				<input type="text" id="vlt-license-key" name="license_key" size="50" autocomplete="off" spellcheck="false"
					placeholder="xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx" required="required">
				<p class="small">
					<a href="<?php echo esc_url( VLT\Toolkit\Admin\Dashboard::instance()->utm( VLT\Toolkit\Admin\Dashboard::instance()->license_help_url, 'activate-find-key' ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Where to find your purchase code or license key?', 'toolkit' ); ?></a>
				</p>
			</div>

			<button class="button button-primary mt-sm" type="submit" name="license_action" value="activate"><?php esc_html_e( 'Activate', 'toolkit' ); ?></button>

			<?php } ?>

		</form>

		<div class="notice notice-info inline mt-sm">
			<p>
				<?php esc_html_e( 'One license works on one live site; local and staging sites (localhost, *.local, *.test, staging.*, dev.*) don\'t take the slot. To move the license to another site, deactivate it here first.', 'toolkit' ); ?>
			</p>
		</div>

		<div class="notice notice-info inline mt-sm">
			<p>
				<?php esc_html_e( 'Note that you are not required to separately register any of the plugins which came bundled with the theme.', 'toolkit' ); ?>
			</p>
		</div>
	</div>
</div>

<?php
		}
	}
}

// Initialize theme activation
new VLThemesThemeActivation();
