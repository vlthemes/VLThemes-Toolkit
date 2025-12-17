<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Extensions;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Stack;
use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Utils;
use Elementor\Core\Base\Module;
use Elementor\Plugin;

/**
 * Custom Attributes Extension
 *
 * Allows adding custom HTML attributes to Elementor elements
 * Compatible with Elementor Pro naming conventions
 */
class CustomAttributesExtension extends Module {

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->add_actions();
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'custom-attributes';
	}

	/**
	 * Get blacklist attributes
	 *
	 * @return array
	 */
	private function get_black_list_attributes() {
		static $black_list = null;

		if ( null === $black_list ) {
			$black_list = [ 'id', 'class', 'data-id', 'data-settings', 'data-element_type', 'data-widget_type', 'data-model-cid' ];

			/**
			 * Elementor attributes black list.
			 *
			 * Filters the attributes that won't be rendered in the wrapper element.
			 *
			 * @since 1.0.0
			 *
			 * @param array $black_list A black list of attributes.
			 */
			$black_list = apply_filters( 'elementor/element/attributes/black_list', $black_list );
		}

		return $black_list;
	}

	/**
	 * Register custom attributes controls
	 *
	 * @param Element_Base $element elementor element
	 * @param string       $tab     tab name
	 */
	public function register_custom_attributes_controls( Element_Base $element, $tab ) {
		$element->start_controls_section(
			'_section_attributes',
			[
				'label' => esc_html__( 'Attributes', 'toolkit' ),
				'tab'   => $tab,
			]
		);

		$element->add_control(
			'_attributes',
			[
				'label'       => esc_html__( 'Custom Attributes', 'toolkit' ),
				'type'        => Controls_Manager::TEXTAREA,
				'placeholder' => 'key|value',
				'description' => sprintf(
					/* translators: %s: The `|` separate char. */
					esc_html__( 'Set custom attributes for the wrapper element. Each attribute in a separate line. Separate attribute key from the value using %s character.', 'toolkit' ),
					'<code>|</code>'
				)
			]
		);

		$element->end_controls_section();
	}

	/**
	 * Register controls
	 *
	 * @param Controls_Stack $element    elementor element
	 * @param string         $section_id section ID
	 */
	public function register_controls( Controls_Stack $element, $section_id ) {
		if ( ! $element instanceof Element_Base ) {
			return;
		}

		// Remove Custom Attributes Banner (From free version)
		if ( 'section_custom_attributes_pro' !== $section_id ) {
			return;
		}

		// Get old section info BEFORE removing it
		$old_section = Plugin::instance()->controls_manager->get_control_from_stack(
			$element->get_unique_name(),
			'section_custom_attributes_pro'
		);

		// Remove Elementor Pro promotion controls
		Plugin::instance()->controls_manager->remove_control_from_stack(
			$element->get_unique_name(),
			[ 'section_custom_attributes_pro', 'custom_attributes_pro' ]
		);

		// Register custom attributes controls with the same tab
		if ( $old_section && isset( $old_section['tab'] ) ) {
			$this->register_custom_attributes_controls( $element, $old_section['tab'] );
		}
	}

	/**
	 * Render attributes
	 *
	 * @param Element_Base $element elementor element instance
	 */
	public function render_attributes( Element_Base $element ) {
		$settings = $element->get_settings_for_display();

		if ( ! empty( $settings['_attributes'] ) ) {
			$attributes = Utils::parse_custom_attributes( $settings['_attributes'], "\n" );

			$black_list = $this->get_black_list_attributes();

			foreach ( $attributes as $attribute => $value ) {
				if ( ! in_array( $attribute, $black_list, true ) ) {
					$element->add_render_attribute( '_wrapper', $attribute, $value );
				}
			}
		}
	}

	/**
	 * Register WordPress hooks
	 */
	protected function add_actions() {
		add_action( 'elementor/element/after_section_end', [ $this, 'register_controls' ], 10, 2 );
		add_action( 'elementor/element/after_add_attributes', [ $this, 'render_attributes' ] );
	}
}
