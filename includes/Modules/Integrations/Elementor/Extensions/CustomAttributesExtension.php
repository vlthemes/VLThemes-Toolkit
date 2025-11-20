<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Extensions;

use VLT\Toolkit\Modules\Integrations\Elementor\BaseExtension;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Custom Attributes Extension
 *
 * Allows adding custom HTML attributes to Elementor elements
 */
class CustomAttributesExtension extends BaseExtension
{
	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'custom_attributes';

	/**
	 * Initialize extension
	 */
	protected function init(): void
	{
		// Extension initialization
	}

	/**
	 * Register WordPress hooks
	 */
	protected function register_hooks(): void
	{
		// Register controls for containers
		add_action('elementor/element/container/section_layout/after_section_end', [ $this, 'register_controls' ], 10, 2);

		// Register controls for common widgets
		add_action('elementor/element/common/_section_style/after_section_end', [ $this, 'register_controls' ], 10, 2);

		// Render attributes
		add_action('elementor/element/after_add_attributes', [ $this, 'render_attributes' ]);
	}

	/**
	 * Register Custom Attributes controls
	 *
	 * @param object $element Elementor element.
	 * @param array  $args    Element arguments.
	 */
	public function register_controls($element, $args): void
	{
		$element->start_controls_section(
			'vlt_section_custom_attributes',
			[
				'label' => esc_html__('VLT Custom Attributes', 'toolkit'),
				'tab'   => \Elementor\Controls_Manager::TAB_ADVANCED,
			],
		);

		$element->add_control(
			'vlt_custom_attributes',
			[
				'label'       => esc_html__('Custom Attributes', 'toolkit'),
				'type'        => \Elementor\Controls_Manager::TEXTAREA,
				'dynamic'     => [ 'active' => true ],
				'placeholder' => 'key|value',
				'description' => sprintf(
					esc_html__('Set custom attributes for the wrapper element. Each attribute in a separate line. Separate key from value using %s.', 'toolkit'),
					'<code>|</code>',
				),
			],
		);

		$element->end_controls_section();

		// Allow themes to add custom controls
		do_action('vlt_toolkit_elementor_add_custom_attributes_controls', $element, $args);
	}

	/**
	 * Render Custom Attributes
	 *
	 * @param object $element Elementor element instance.
	 */
	public function render_attributes($element): void
	{
		$settings = $element->get_settings_for_display();

		if (empty($settings['vlt_custom_attributes'])) {
			return;
		}

		$attributes = \Elementor\Utils::parse_custom_attributes($settings['vlt_custom_attributes'], "\n");
		$blacklist  = [ 'id', 'class', 'data-id', 'data-settings', 'data-element_type', 'data-widget_type', 'data-model-cid' ];

		foreach ($attributes as $key => $value) {
			if (! in_array($key, $blacklist, true)) {
				$element->add_render_attribute('_wrapper', $key, $value);
			}
		}
	}
}
