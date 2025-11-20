<?php

/**
 * Dashboard Help Center Template
 */

if (! defined('ABSPATH')) {
	exit;
}

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- Documentation -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Documentation', 'toolkit'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<p><?php esc_html_e('Got a question? Check our Documentation or Knowledge Base first — the answer might already be there!', 'toolkit'); ?></p>

				<div class="vlt-btn-group mt-xs">
					<a target="_blank" rel="noopener" href="<?php echo esc_url($this->docs_url . $this->theme_slug); ?>" class="button button-primary mt-sm">
						<?php esc_html_e('Visit Documentation', 'toolkit'); ?>
					</a>
					<a target="_blank" rel="noopener" href="<?php echo esc_url($this->knowledge_base_url); ?>" class="button button-secondary mt-sm">
						<?php esc_html_e('Visit Knowledge Base', 'toolkit'); ?>
					</a>
				</div>
			</div>
		</div>

		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Changelog', 'toolkit'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<ul class="vlt-styled-list">
					<li><?php esc_html_e('Here\'s what\'s new and improved!', 'toolkit'); ?></li>
					<li><?php esc_html_e('We\'ve made some updates — check them out below!', 'toolkit'); ?></li>
					<li><?php esc_html_e('Fresh updates to make your experience even better.', 'toolkit'); ?></li>
				</ul>

				<a target="_blank" rel="noopener" href="<?php echo esc_url($this->changelog_url . $this->theme_slug); ?>" class="button button-primary mt-sm">
					<?php esc_html_e('Visit Changelog', 'toolkit'); ?>
				</a>
			</div>
		</div>

	</div>

	<!-- Support Form -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Support Form', 'toolkit'); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<p><?php esc_html_e('If you did not find an answer to your question, please submit a support ticket and describe your issue in detail.', 'toolkit'); ?></p>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
						printf(
							/* translators: %s: Support Policy link */
							esc_html__('Please read a %s before submitting a ticket and make sure that your question is related to our product issues.', 'toolkit'),
							'<a target="_blank" href="' . esc_url($this->support_policy_url) . '">' . esc_html__('Support Policy', 'toolkit') . '</a>',
						);
?>
					</p>
				</div>

				<div class="notice notice-info inline mt-sm">
					<p>
						<?php
printf(
	/* translators: %s: number of business days */
	esc_html__('Our team will review your request and respond within %s.', 'toolkit'),
	'<strong>' . esc_html__('two business days', 'toolkit') . '</strong>',
);
?>
					</p>
				</div>

				<div class="notice notice-info">
					<p>
						<?php
printf(
	/* translators: %s: theme name */
	esc_html__('If you got %s through a subscription (for example, from Envato Elements), please remember that item support isn\'t included.', 'toolkit'),
	'<strong>' . esc_html($this->theme_name) . '</strong>',
);
?>
					</p>
				</div>

				<a target="_blank" rel="noopener" href="<?php echo esc_url($this->support_url); ?>" class="button button-primary mt-sm">
					<?php esc_html_e('Create a Ticket', 'toolkit'); ?>
				</a>
			</div>
		</div>
	</div>

	<!-- Partners -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">

			<div class="vlt-widget__title">
				<mark><?php esc_html_e('Our Partners *', 'toolkit'); ?></mark>
			</div>

			<div class="vlt-widget__content">

				<div class="postbox-header pb-sm">

					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 142 30" style="max-height: 30px;">
						<path fill=" currentColor" d="M15.046 0C6.736 0 0 6.715 0 15c0 8.282 6.736 15 15.046 15 8.31 0 15.047-6.715 15.047-15-.003-8.285-6.74-15-15.047-15Zm-3.761 21.248H8.78v-12.5h2.505v12.5Zm10.03 0H13.79V18.75h7.523v2.5Zm0-5H13.79v-2.499h7.523v2.499Zm0-5H13.79V8.75h7.523v2.498Zm72.356 4.256-2.069.477-1.442.316h-.013c0-.375.028-.77.122-1.134.118-.468.38-1.013.836-1.253a1.987 1.987 0 0 1 1.621-.09c.543.207.792.712.898 1.236.03.147.049.293.064.44l-.017.008Zm3.76.793c0-3.63-2.37-5.191-5.397-5.191-3.422 0-5.565 2.287-5.565 5.21 0 3.177 1.824 5.247 5.753 5.247 2.124 0 3.327-.363 4.757-1.053l-.544-2.379c-1.09.472-2.106.761-3.46.761-1.486 0-2.331-.543-2.653-1.56h7.014c.057-.272.095-.582.095-1.035Zm-35.233-.793-2.069.477-1.442.316h-.013c0-.375.028-.77.123-1.134.118-.468.38-1.013.836-1.253a1.987 1.987 0 0 1 1.62-.09c.543.207.792.712.899 1.236.03.147.049.293.064.44l-.018.008Zm3.76.793c0-3.63-2.37-5.191-5.396-5.191-3.422 0-5.566 2.287-5.566 5.21 0 3.177 1.825 5.247 5.754 5.247 2.124 0 3.327-.363 4.757-1.053l-.544-2.379c-1.091.472-2.107.761-3.461.761-1.485 0-2.331-.543-2.652-1.56h7.014c.056-.272.094-.582.094-1.035Zm-12.434-7.86H50.05v12.76h3.474V8.437Zm44.64 2.994h3.647l.768 2.257c.48-1.114 1.562-2.547 3.479-2.547 2.633 0 4.062 1.29 4.062 4.612V21.2h-3.648c0-1.137.002-2.27.004-3.405 0-.52-.01-1.04-.002-1.56.005-.48.041-.976-.224-1.407-.181-.291-.474-.506-.791-.652a2.226 2.226 0 0 0-1.973.029c-.155.08-.907.472-.907.652V21.2h-3.647v-7.133l-.769-2.634v-.001Zm14.714 2.561h-1.673v-2.56h1.673v-1.6l3.648-.83v2.43h3.666v2.56h-3.666v2.868c0 1.125.563 1.65 1.409 1.65.864 0 1.354-.109 2.086-.343l.433 2.65c-.996.417-2.237.617-3.498.617-2.652 0-4.08-1.216-4.08-3.576v-3.866h.002Zm14.14 4.664c1.335 0 2.124-.927 2.124-2.414s-.751-2.342-2.068-2.342c-1.335 0-2.106.853-2.106 2.395 0 1.455.752 2.361 2.05 2.361Zm.037-7.606c3.422 0 5.923 2.07 5.923 5.282 0 3.23-2.501 5.173-5.96 5.173-3.441 0-5.886-1.997-5.886-5.173 0-3.213 2.426-5.282 5.923-5.282Zm-43.857.352a4.141 4.141 0 0 0-2.074-.254c-.355.047-.7.15-1.025.302-.884.422-1.571 1.383-1.942 2.24a3.308 3.308 0 0 0-1.984-2.286 4.138 4.138 0 0 0-2.073-.254c-.355.047-.7.15-1.024.302-.883.419-1.57 1.377-1.94 2.232v-.062l-.744-2.186h-3.648l.768 2.634v7.132h3.623v-6.375c.014-.047.174-.135.203-.156.425-.29.924-.59 1.455-.628a1.66 1.66 0 0 1 1.496.782c.267.43.23.927.224 1.406-.005.52.002 1.04.002 1.56-.002 1.135-.004 2.27-.004 3.404h3.65v-6.364c.006-.046.173-.138.203-.157.425-.29.925-.59 1.455-.628.543-.039 1.078.227 1.401.643.033.046.066.09.096.139.265.43.23.927.224 1.406-.006.52.002 1.04.002 1.56-.002 1.135-.004 2.27-.004 3.404h3.648v-5.445c0-1.598-.237-3.654-1.988-4.35v-.001Zm58.405-.26c-1.917 0-2.997 1.434-3.478 2.546l-.769-2.257h-3.647l.768 2.635v7.13h3.648v-6.588c.519-.088 3.34.415 3.875.603v-4.057a6.895 6.895 0 0 0-.397-.013Zm-96.784 4.362-2.07.477-1.442.316h-.013c0-.375.028-.77.122-1.134.12-.468.38-1.013.836-1.253a1.988 1.988 0 0 1 1.622-.09c.543.207.79.712.897 1.236.03.147.05.293.064.44l-.016.008Zm3.76.793c0-3.63-2.37-5.191-5.398-5.191-3.422 0-5.565 2.287-5.565 5.21 0 3.177 1.824 5.247 5.753 5.247 2.126 0 3.327-.363 4.759-1.053l-.546-2.379c-1.09.472-2.105.761-3.46.761-1.484 0-2.33-.543-2.652-1.56h7.013c.056-.272.095-.582.095-1.035Z" />
					</svg>

					<a target="_blank" href="<?php echo esc_url($this->elementor_partner_url); ?>" class="button button-primary"><?php esc_html_e('Explore More', 'toolkit'); ?></a>

				</div>

				<p class="mt-sm"><?php esc_html_e('A visual drag-and-drop builder for designing forms, posts, pages, and more.', 'toolkit'); ?></p>

				<div class="postbox-header pb-sm mt-lg">

					<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 126 30" style="max-height: 30px;">
						<path fill=" currentColor" d="M58.889 11.34c0-1.977-1.83-3.711-3.916-3.711h-5.525a3.832 3.832 0 0 0-2.824 1.206 4.034 4.034 0 0 0-1.082 2.726v9.082c0 .993.427 1.948 1.203 2.693.751.72 1.737 1.133 2.703 1.133l5.014.003.643-.003c.945 0 1.896-.4 2.608-1.1a3.931 3.931 0 0 0 1.176-2.832v-9.198.001Zm-2.907 8.753a1.68 1.68 0 0 1-1.676 1.684h-4.17a1.686 1.686 0 0 1-1.676-1.684v-8.086c0-.45.174-.872.491-1.19a1.66 1.66 0 0 1 1.186-.494h4.169c.809 0 1.676.677 1.676 1.684v8.086Zm42.014-9.978v11.874c0 1.37 1.11 2.485 2.474 2.485h8.883v-2.695h-8.45v-4.63h7.751v-2.547h-7.751v-4.275h8.45V7.632h-8.883a2.482 2.482 0 0 0-2.474 2.484v-.001Zm-66.827.192-.004 14.166h2.91V16.55h7.796v-2.548h-7.795v-2.638c0-.573.463-1.038 1.033-1.038h7.486V7.629h-8.617a2.818 2.818 0 0 0-2.808 2.676l-.001.002Zm89.733 5.75.039-.063L126 7.63l-3.171-.003-3.517 5.807-.105-.175-3.413-5.632-3.169.003 5.058 8.362.04.065-.04.065-5.058 8.35 3.185.001 3.397-5.614.105-.175.106.175 3.396 5.614 3.186-.002-5.059-8.35-.039-.064ZM72.72 17.57l.1-.05c1.32-.652 2.174-1.948 2.174-3.3v-2.878c0-1.978-1.83-3.711-3.917-3.711h-8.6v16.841h2.908v-6.53h4.283l.03.08 2.44 6.45h3.198l-2.576-6.798-.039-.105h-.001Zm-.633-4.016c0 1.02-.857 1.668-1.649 1.683h-5.055v-4.911h5.027c.809 0 1.675.677 1.675 1.684v1.544h.002Zm19.111 8.167c0 .074-.101.098-.133.03L85.059 9.056a2.479 2.479 0 0 0-2.235-1.427h-1.763c-1.012 0-2.096.845-2.096 2.101v14.74h2.908V10.319c0-.074.101-.098.133-.03l6.013 12.756a2.472 2.472 0 0 0 2.234 1.427h1.168c1.988 0 2.693-.649 2.693-2.477V7.63h-2.907l-.009 14.09ZM25.372 24.594V5.402H5.38v13.79H19.99v-8.37H10.9v2.95h6.153v2.471H8.317V8.351h14.118v13.294H2.936V2.95h9.628L11.446 0H0v24.594h11.095v2.456H0V30h25.372v-2.95h-11.34v-2.456h11.34Z" />
					</svg>

					<a target="_blank" href="<?php echo esc_url($this->fornex_partner_url); ?>" class="button button-primary"><?php esc_html_e('Explore More', 'toolkit'); ?></a>

				</div>

				<p class="mt-sm"><?php esc_html_e('A hosting provider offering dedicated servers, VPS, shared hosting, VPNs, and backup services.', 'toolkit'); ?></p>

				<p class="small mt-sm"><?php esc_html_e('* Here we provide some partners and services that we use.', 'toolkit'); ?></p>

			</div>

		</div>

	</div>

</div>