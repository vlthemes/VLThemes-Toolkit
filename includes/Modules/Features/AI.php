<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;
use WP_Error;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AI Module
 *
 * Adds an in-editor AI writing assistant for Elementor, powered by Claude.
 */
class AI extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'ai';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Settings option name
	 *
	 * @var string
	 */
	const OPTION_NAME = 'vlt_toolkit_ai_settings';

	/**
	 * Claude model used for content generation
	 *
	 * @var string
	 */
	const MODEL = 'claude-haiku-4-5';

	/**
	 * Special API key value that enables mock mode (no real API calls, no cost).
	 * Lets the whole feature be tested end-to-end without a billed API key.
	 *
	 * @var string
	 */
	const MOCK_API_KEY = 'test';

	/**
	 * Register module
	 */
	public function register() {
		// Elementor editor assistant
		add_action( 'elementor/editor/after_enqueue_scripts', [ $this, 'enqueue_elementor_assistant' ] );

		// Dashboard "AI Options" page assets
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_dashboard_assets' ] );

		// AJAX endpoint
		add_action( 'wp_ajax_vlt_toolkit_ai_generate_content', [ $this, 'ajax_generate_content' ] );
	}

	/**
	 * Enqueue assets on the "AI Options" dashboard page
	 *
	 * @param string $hook current admin page hook suffix
	 */
	public function enqueue_dashboard_assets( $hook ) {
		if ( false === strpos( $hook, 'vlt-dashboard-ai-options' ) ) {
			return;
		}

		wp_enqueue_style( 'vlt-toolkit-ai', VLT_TOOLKIT_URL . 'assets/css/ai.css', [], VLT_TOOLKIT_VERSION );
	}

	/**
	 * Get settings
	 *
	 * @return array
	 */
	public function get_settings() {
		$defaults = [
			'api_key'           => '',
			'assistant_enabled' => false,
		];

		return wp_parse_args( get_option( self::OPTION_NAME, [] ), $defaults );
	}

	/**
	 * Get the configured Claude API key
	 *
	 * @return string
	 */
	public function get_api_key() {
		$settings = $this->get_settings();

		return trim( $settings[ 'api_key' ] );
	}

	/**
	 * Whether an API key is configured
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_api_key();
	}

	/**
	 * Whether the AI content assistant is enabled and usable
	 *
	 * @return bool
	 */
	public function is_assistant_enabled() {
		$settings = $this->get_settings();

		return $this->is_configured() && !empty( $settings[ 'assistant_enabled' ] );
	}

	/**
	 * Request a chat completion from Claude
	 *
	 * @param string $prompt user prompt
	 * @param string $system system instructions
	 *
	 * @return string|WP_Error
	 */
	public function request_completion( $prompt, $system = '' ) {
		$api_key = $this->get_api_key();

		if ( '' === $api_key ) {
			return new WP_Error( 'vlt_toolkit_ai_no_key', esc_html__( 'Claude API key is not configured.', 'toolkit' ) );
		}

		$prompt = trim( $prompt );

		if ( '' === $prompt ) {
			return new WP_Error( 'vlt_toolkit_ai_empty_prompt', esc_html__( 'Please enter a prompt.', 'toolkit' ) );
		}

		if ( self::MOCK_API_KEY === $api_key ) {
			return $this->mock_completion( $prompt, $system );
		}

		$request_body = [
			'model'      => self::MODEL,
			'max_tokens' => 2048,
			'messages'   => [
				[ 'role' => 'user', 'content' => $prompt ],
			],
		];

		if ( '' !== $system ) {
			$request_body[ 'system' ] = $system;
		}

		$response = wp_remote_post(
			'https://api.anthropic.com/v1/messages',
			[
				'timeout' => 60,
				'headers' => [
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
					'content-type'      => 'application/json',
				],
				'body' => wp_json_encode( $request_body ),
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( 200 !== $code ) {
			$message = $body[ 'error' ][ 'message' ] ?? esc_html__( 'Unknown Claude API error.', 'toolkit' );

			return new WP_Error( 'vlt_toolkit_ai_api_error', $message );
		}

		if ( isset( $body[ 'stop_reason' ] ) && 'refusal' === $body[ 'stop_reason' ] ) {
			return new WP_Error( 'vlt_toolkit_ai_refusal', esc_html__( 'Claude declined to generate this content.', 'toolkit' ) );
		}

		$content = '';

		foreach ( $body[ 'content' ] ?? [] as $block ) {
			if ( isset( $block[ 'type' ] ) && 'text' === $block[ 'type' ] ) {
				$content .= $block[ 'text' ];
			}
		}

		if ( '' === trim( $content ) ) {
			return new WP_Error( 'vlt_toolkit_ai_bad_response', esc_html__( 'Unexpected response from Claude.', 'toolkit' ) );
		}

		return trim( $content );
	}

	/**
	 * Return a canned response instead of calling the Claude API.
	 *
	 * Lets the assistant's plumbing (settings, Elementor panel, AJAX wiring,
	 * insertion into the widget) be tested end-to-end with no API key and
	 * no cost. Enabled by setting the API key field to "test".
	 *
	 * @param string $prompt user prompt
	 * @param string $system system instructions
	 *
	 * @return string
	 */
	protected function mock_completion( $prompt, $system ) {
		$is_wysiwyg = false !== strpos( $system, 'rich text widget' );

		if ( $is_wysiwyg ) {
			return sprintf(
				'<p><strong>%s</strong></p><p>%s</p>',
				esc_html__( '[Mock AI response — no API call was made]', 'toolkit' ),
				esc_html( sprintf( __( 'Prompt: "%s"', 'toolkit' ), $prompt ) )
			);
		}

		/* translators: %s: the prompt that was submitted */
		return sprintf( __( '[Mock AI text for: "%s"]', 'toolkit' ), $prompt );
	}

	/**
	 * Enqueue the AI assistant script inside the Elementor editor
	 */
	public function enqueue_elementor_assistant() {
		if ( !$this->is_assistant_enabled() ) {
			return;
		}

		wp_enqueue_style( 'vlt-toolkit-ai', VLT_TOOLKIT_URL . 'assets/css/ai.css', [], VLT_TOOLKIT_VERSION );

		wp_enqueue_script(
			'vlt-toolkit-ai-assistant',
			VLT_TOOLKIT_URL . 'assets/js/ai-elementor-assistant.js',
			[ 'jquery', 'elementor-editor' ],
			VLT_TOOLKIT_VERSION,
			true,
		);

		wp_localize_script(
			'vlt-toolkit-ai-assistant',
			'vlt_toolkit_ai_assistant',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'vlt_toolkit_ai_generate' ),
				'strings' => [
					'title'             => esc_html__( 'AI Content Assistant', 'toolkit' ),
					'noSelection'       => esc_html__( 'Select a text element on the page first.', 'toolkit' ),
					'promptLabel'       => esc_html__( 'Describe what to write:', 'toolkit' ),
					'promptPlaceholder' => esc_html__( 'e.g. Write a short, punchy headline about our new product launch.', 'toolkit' ),
					'generate'          => esc_html__( 'Generate', 'toolkit' ),
					'insert'            => esc_html__( 'Insert', 'toolkit' ),
					'generating'        => esc_html__( 'Generating…', 'toolkit' ),
					'emptyPrompt'       => esc_html__( 'Please describe what you want to write.', 'toolkit' ),
					'error'             => esc_html__( 'Something went wrong. Please try again.', 'toolkit' ),
					'targetLabel'       => esc_html__( 'Will be inserted into:', 'toolkit' ),
				],
			]
		);
	}

	/**
	 * AJAX: generate content from a prompt
	 */
	public function ajax_generate_content() {
		check_ajax_referer( 'vlt_toolkit_ai_generate', 'nonce' );

		if ( !current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => esc_html__( 'Permission denied.', 'toolkit' ) ] );
		}

		if ( !$this->is_assistant_enabled() ) {
			wp_send_json_error( [ 'message' => esc_html__( 'AI Content Assistant is disabled.', 'toolkit' ) ] );
		}

		$prompt     = isset( $_POST[ 'prompt' ] ) ? sanitize_textarea_field( wp_unslash( $_POST[ 'prompt' ] ) ) : '';
		$field_type = isset( $_POST[ 'fieldType' ] ) ? sanitize_key( $_POST[ 'fieldType' ] ) : 'text';

		if ( 'wysiwyg' === $field_type ) {
			$system = 'You are a helpful writing assistant for a WordPress/Elementor website editor. ' .
				'Write clear, well-formatted HTML content (using tags like <p>, <h2>, <h3>, <ul>, <strong>) ' .
				'suitable for pasting directly into a rich text widget. Do not include <html>, <head>, or <body> tags. ' .
				'Do not wrap the response in markdown code fences.';
		} else {
			$system = 'You are a helpful writing assistant for a WordPress/Elementor website editor. ' .
				'The output will be placed directly into a single plain-text field (such as a heading, button label, or short tagline). ' .
				'Reply with ONLY the requested text, no quotes, no HTML tags, no markdown, no explanations, and keep it concise.';
		}

		$result = $this->request_completion( $prompt, $system );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'content' => $result ] );
	}
}
