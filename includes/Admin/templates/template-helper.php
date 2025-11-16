<?php

/**
 * Dashboard Help Center Template
 *
 * @author VLThemes
 * @version 1.0
 */

if (! defined('ABSPATH')) {
	exit;
}

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- Support Form -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Support Form', 'vlt-helper'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<p><?php esc_html_e('If you did not find an answer to your question, please submit a support ticket and describe your issue in detail.', 'vlt-helper'); ?></p>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
						printf(
							/* translators: %s: Support Policy link */
							esc_html__('Please read a %s before submitting a ticket and make sure that your question is related to our product issues.', 'vlt-helper'),
							'<a target="_blank" href="https://themeforest.net/page/item_support_policy">' . esc_html__('Support Policy', 'vlt-helper') . '</a>'
						);
						?>
					</p>
				</div>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
						printf(
							/* translators: %s: number of business days */
							esc_html__('Our team will review your request and respond within %s.', 'vlt-helper'),
							'<strong>' . esc_html__('two business days', 'vlt-helper') . '</strong>'
						);
						?>
					</p>
				</div>

				<div class="notice notice-info">
					<p>
						<?php
						printf(
							/* translators: %s: theme name */
							esc_html__('If you got %s through a subscription (for example, from Envato Elements), please remember that item support isn\'t included.', 'vlt-helper'),
							'<strong>' . esc_html($this->theme_name) . '</strong>'
						);
						?>
					</p>
				</div>

				<a target="_blank" rel="noopener" href="https://docs.vlthemes.me/support/" class="button button-primary mt-sm">
					<?php esc_html_e('Create a Ticket', 'vlt-helper'); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Documentation -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Documentation', 'vlt-helper'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<p><?php esc_html_e('Got a question? Check our Documentation or Knowledge Base first — the answer might already be there!', 'vlt-helper'); ?></p>

				<div class="vlt-btn-group mt-xs">
					<a target="_blank" rel="noopener" href="<?php echo esc_url('https://docs.vlthemes.me/docs/' . $this->theme_slug); ?>" class="button button-primary mt-sm">
						<?php esc_html_e('Visit Documentation', 'vlt-helper'); ?>
					</a>
					<a target="_blank" rel="noopener" href="https://docs.vlthemes.me/knowbase/" class="button button-secondary mt-sm">
						<?php esc_html_e('Visit Knowledge Base', 'vlt-helper'); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

	<!-- Changelog -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Changelog', 'vlt-helper'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<ul class="vlt-styled-list">
					<li><?php esc_html_e('Here\'s what\'s new and improved!', 'vlt-helper'); ?></li>
					<li><?php esc_html_e('We\'ve made some updates — check them out below!', 'vlt-helper'); ?></li>
					<li><?php esc_html_e('Fresh updates to make your experience even better.', 'vlt-helper'); ?></li>
				</ul>

				<a target="_blank" rel="noopener" href="<?php echo esc_url('https://docs.vlthemes.me/changelog/' . $this->theme_slug); ?>" class="button button-primary mt-sm">
					<?php esc_html_e('Visit Changelog', 'vlt-helper'); ?>
				</a>
			</div>
		</div>
	</div>
</div>