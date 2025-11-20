<?php

if (! defined('ABSPATH')) {
	exit;
}

use VLT\Toolkit\ThemeActivation\ThemeActivation;

/**
 * Check if theme is activated
 *
 * @return bool
 */
if (! function_exists('vlt_is_theme_activated')) {
	function vlt_is_theme_activated()
	{
		$theme = wp_get_theme();
		$slug  = $theme->get_template();

		$status = get_option($slug . '_is_activated', 0);

		return $status == 1;
	}
}

/**
 * Theme Activation Manager
 */
if (! class_exists('VLThemesThemeActivation')) {
	class VLThemesThemeActivation
	{
		/**
		 * Plugin file
		 *
		 * @var string
		 */
		public $plugin_file = __FILE__;

		/**
		 * Response object
		 *
		 * @var object
		 */
		public $responseObj;

		/**
		 * License message
		 *
		 * @var string
		 */
		public $licenseMessage;

		/**
		 * Show message flag
		 *
		 * @var bool
		 */
		public $showMessage = false;

		/**
		 * Theme slug
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * License key option name
		 *
		 * @var string
		 */
		public $lic_key_slug;

		/**
		 * License email option name
		 *
		 * @var string
		 */
		public $lic_email_slug;

		/**
		 * Product ID
		 *
		 * @var string
		 */
		public $product_id;

		/**
		 * Product base
		 *
		 * @var string
		 */
		public $product_base;

		/**
		 * Theme file path
		 *
		 * @var string
		 */
		public $theme_file;

		/**
		 * Constructor
		 */
		public function __construct()
		{
			// Get current theme
			$theme      = wp_get_theme();
			$this->slug = $theme->get_template();

			// Get theme configurations
			$theme_configs = apply_filters(
				'vlt_theme_activation_configs',
				[
					'leedo' => [
						'key'          => '8488A777D6F7E194',
						'product_id'   => '1',
						'product_base' => 'leedo',
					],
				],
			);

			// Check if current theme has activation config
			if (! isset($theme_configs[ $this->slug ])) {
				return;
			}

			$config               = $theme_configs[ $this->slug ];
			$this->product_id     = $config['product_id'];
			$this->product_base   = $config['product_base'];
			$encryption_key       = isset($config['key']) ? $config['key'] : 'A1C7A8768D996F69';
			$this->theme_file     = get_template_directory() . '/style.css';
			$this->lic_key_slug   = ucfirst($this->slug) . '_lic_Key';
			$this->lic_email_slug = ucfirst($this->slug) . '_lic_email';

			// Initialize activation
			$instance = ThemeActivation::getInstance(
				$this->theme_file,
				$this->product_id,
				$this->product_base,
			);

			// Set custom encryption key for this theme
			$instance->key = $encryption_key;

			$licenseKey = get_option($this->lic_key_slug, '');
			$liceEmail  = get_option($this->lic_email_slug, '');

			// Add on delete callback
			ThemeActivation::addOnDelete(
				function (): void {
					delete_option($this->lic_key_slug);
				},
			);

			// Check license
			if (ThemeActivation::CheckWPPlugin($licenseKey, $liceEmail, $this->licenseMessage, $this->responseObj, $this->theme_file, $this->product_id, $this->product_base)) {
				add_action('vlt_toolkit_print_activation_form', [ $this, 'activated_form' ]);
				add_action('admin_post_' . $this->slug . '_deactivate_license', [ $this, 'action_deactivate_license' ]);

				if ($this->responseObj->is_valid) {
					update_option($this->slug . '_is_activated', 1);
				}
			} else {
				if (! empty($licenseKey) && ! empty($this->licenseMessage)) {
					$this->showMessage = true;
				}
				update_option($this->lic_key_slug, '') || add_option($this->lic_key_slug, '');
				update_option($this->slug . '_is_activated', 0);
				add_action('admin_post_' . $this->slug . '_activate_license', [ $this, 'action_activate_license' ]);
				add_action('vlt_toolkit_print_activation_form', [ $this, 'license_form' ]);
			}
		}

		/**
		 * Action: Activate license
		 */
		public function action_activate_license(): void
		{
			check_admin_referer('el-license');
			$licenseKey   = ! empty($_POST['el_license_key']) ? sanitize_text_field(wp_unslash($_POST['el_license_key'])) : '';
			$licenseEmail = ! empty($_POST['el_license_email']) ? sanitize_email(wp_unslash($_POST['el_license_email'])) : '';
			update_option($this->lic_key_slug, $licenseKey) || add_option($this->lic_key_slug, $licenseKey);
			update_option($this->lic_email_slug, $licenseEmail) || add_option($this->lic_email_slug, $licenseEmail);
			update_option('_site_transient_update_plugins', '');
			wp_safe_redirect(admin_url('admin.php?page=vlt-dashboard-activate-theme'));
			exit;
		}

		/**
		 * Action: Deactivate license
		 */
		public function action_deactivate_license(): void
		{
			check_admin_referer('el-license');
			$message = '';

			if (ThemeActivation::RemoveLicenseKey($this->theme_file, $message, $this->product_id, $this->product_base)) {
				update_option($this->lic_key_slug, '') || add_option($this->lic_key_slug, '');
			}
			wp_safe_redirect(admin_url('admin.php?page=vlt-dashboard-activate-theme'));
			exit;
		}

		/**
		 * Render activated form
		 */
		public function activated_form(): void
		{
			?>
			<div class="vlt-widget">
				<div class="vlt-widget__title">
					<mark class="true"><?php esc_html_e('Theme License', 'toolkit'); ?></mark>
					<span class="vlt-badge true"><?php esc_html_e('Active', 'toolkit'); ?></span>
				</div>

				<div class="vlt-widget__content">
					<table class="widefat" cellspacing="0">
						<tbody>
							<tr>
								<td><?php esc_html_e('Status:', 'toolkit'); ?></td>
								<td>
									<?php
									if ($this->responseObj->is_valid) {
										echo '<mark class="true">✅ ' . esc_html__('Valid', 'toolkit') . '</mark>';
									} else {
										echo '<mark class="false">❌ ' . esc_html__('Invalid', 'toolkit') . '</mark>';
									}
			?>
								</td>
							</tr>
							<tr>
								<td><?php esc_html_e('License Type:', 'toolkit'); ?></td>
								<td><?php echo esc_html($this->responseObj->license_title); ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('License Expired on:', 'toolkit'); ?></td>
								<td>
								<?php
								echo esc_html($this->responseObj->expire_date);

			if (! empty($this->responseObj->expire_renew_link)) {
				?>
									<a target="_blank" href="<?php echo esc_url($this->responseObj->expire_renew_link); ?>"><?php esc_html_e('Renew', 'toolkit'); ?></a><?php } ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Support Expired on:', 'toolkit'); ?></td>
								<td>
								<?php
								echo esc_html($this->responseObj->support_end);

			if (! empty($this->responseObj->support_renew_link)) {
				?>
									<a target="_blank" href="<?php echo esc_url($this->responseObj->support_renew_link); ?>"><?php esc_html_e('Renew', 'toolkit'); ?></a><?php } ?></td>
							</tr>
							<tr>
								<td><?php esc_html_e('Your License Key:', 'toolkit'); ?></td>
								<td>
									<div class="vlt-form-group">
										<input class="license-key" type="text"
											value="<?php echo esc_attr(substr($this->responseObj->license_key, 0, 9) . 'XXXXXXXX-XXXXXXXX' . substr($this->responseObj->license_key, -9)); ?>"
											readonly>
									</div>
								</td>
							</tr>
						</tbody>
					</table>

					<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
						<input type="hidden" name="action" value="<?php echo esc_attr($this->slug); ?>_deactivate_license">
						<?php wp_nonce_field('el-license'); ?>
						<?php submit_button('Deactivate', 'secondary mt-sm'); ?>
					</form>

				</div>
			</div>

			<?php
		}

		/**
		 * Render license form
		 */
		public function license_form(): void
		{
			?>

			<div class="vlt-widget">
				<div class="vlt-widget__title">
					<mark><?php esc_html_e('Theme License', 'toolkit'); ?></mark>
					<span class="vlt-badge false"><?php esc_html_e('Not Active', 'toolkit'); ?></span>
				</div>

				<div class="vlt-widget__content">
						<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

						<input type="hidden" name="action" value="<?php echo esc_attr($this->slug); ?>_activate_license">

						<p class="mb-sm"><?php printf(esc_html__('To activate your copy of %s, enter your purchase code and email address to register the theme.', 'toolkit'), esc_html(VLT\Toolkit\Admin\Dashboard::instance()->theme_name)); ?></p>

						<?php if (! empty($this->showMessage) && ! empty($this->licenseMessage)) { ?>
							<div class="notice notice-error is-dismissible mb-sm">
								<p><?php echo esc_html($this->licenseMessage); ?></p>
							</div>
						<?php } ?>

						<div class="vlt-form-group">
							<label for="el_license_key"><?php esc_html_e('License code', 'toolkit'); ?></label>
							<input type="text" id="el_license_key" name="el_license_key" size="50" placeholder="xxxxxxxx-xxxxxxxx-xxxxxxxx-xxxxxxxx" required="required">
						</div>

						<div class="vlt-form-group mt-xs">
							<label for="el_license_email"><?php esc_html_e('Email Address', 'toolkit'); ?></label>
							<?php $purchaseEmail = get_option($this->lic_email_slug, get_bloginfo('admin_email')); ?>
							<input type="email" id="el_license_email" name="el_license_email" size="50" value="<?php echo esc_attr($purchaseEmail); ?>" placeholder required="required">
							<p class="small"><?php esc_html_e('We will send update news of this product by this email address, don\'t worry, we hate spam.', 'toolkit'); ?></p>
						</div>

						<?php wp_nonce_field('el-license'); ?>
						<?php submit_button('Activate', 'primary mt-sm'); ?>

					</form>

					<div class="notice notice-info inline mt-sm">
						<p>
							<?php esc_html_e('Note that you are not required to separately register any of the plugins which came bundled with the theme.', 'toolkit'); ?>
						</p>
					</div>

					<div class="notice notice-info inline mt-sm">
						<p>
							<?php esc_html_e('Please note that if you used your purchase code on one installation, you are required to Deactivate in order to use the purchase code on a different installation.', 'toolkit'); ?>
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
