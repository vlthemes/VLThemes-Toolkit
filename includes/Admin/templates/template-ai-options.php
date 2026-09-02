<?php

/**
 * Dashboard AI Options Template
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use VLT\Toolkit\Toolkit;
use VLT\Toolkit\Modules\Features\AI;

/** @var AI $ai_module */
$ai_module = Toolkit::instance()->get_module( 'Features\\AI' );

if ( !$ai_module ) {
	return;
}

// Handle settings form submission.
if ( isset( $_POST[ 'vlt_toolkit_ai_settings_nonce' ] ) && current_user_can( 'manage_options' ) ) {
	if ( wp_verify_nonce( $_POST[ 'vlt_toolkit_ai_settings_nonce' ], 'vlt_toolkit_ai_save_settings' ) ) {
		update_option(
			AI::OPTION_NAME,
			[
				'api_key'           => isset( $_POST[ 'api_key' ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'api_key' ] ) ) : '',
				'assistant_enabled' => !empty( $_POST[ 'assistant_enabled' ] ),
			]
		);

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'AI settings saved.', 'toolkit' ) . '</p></div>';
	}
}

$settings = $ai_module->get_settings();

?>

<div class="vlt-toolkit-ai-options">

	<p><?php esc_html_e( 'Connect a Claude (Anthropic) API key to enable an AI writing assistant inside the Elementor editor.', 'toolkit' ); ?></p>

	<form method="post" action="">
		<?php wp_nonce_field( 'vlt_toolkit_ai_save_settings', 'vlt_toolkit_ai_settings_nonce' ); ?>

		<table class="form-table" role="presentation">
			<tbody>
				<tr>
					<th scope="row">
						<label for="vlt_toolkit_ai_api_key"><?php esc_html_e( 'Claude API Key', 'toolkit' ); ?></label>
					</th>
					<td>
						<input type="password" id="vlt_toolkit_ai_api_key" name="api_key" class="regular-text" autocomplete="off" value="<?php echo esc_attr( $settings[ 'api_key' ] ); ?>">
						<p class="description">
							<?php
							printf(
								/* translators: %s: link to Anthropic API keys page */
								esc_html__( 'Get your API key %s.', 'toolkit' ),
								'<a href="' . esc_url( 'https://console.anthropic.com/settings/keys' ) . '" target="_blank" rel="noopener noreferrer">' . esc_html__( 'here', 'toolkit' ) . '</a>'
							);
							?>
						</p>
						<p class="description">
							<?php
							printf(
								/* translators: %s: the literal word "test" */
								esc_html__( 'For testing without a real key or cost, enter %s — the assistant will return sample text instead of calling Claude.', 'toolkit' ),
								'<code>test</code>'
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'AI Content Assistant', 'toolkit' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="assistant_enabled" value="1" <?php checked( !empty( $settings[ 'assistant_enabled' ] ) ); ?>>
							<?php esc_html_e( 'Show a floating AI writing assistant panel inside the Elementor editor.', 'toolkit' ); ?>
						</label>
					</td>
				</tr>
			</tbody>
		</table>

		<?php submit_button( esc_html__( 'Save Settings', 'toolkit' ) ); ?>
	</form>

</div>
