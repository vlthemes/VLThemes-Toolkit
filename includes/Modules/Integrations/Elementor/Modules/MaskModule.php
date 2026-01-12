<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Mask Extension
 *
 * Adds gradient mask effects to containers
 */
class MaskModule extends Module_Base {

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
		return 'mask';
	}

	/**
	 * Register actions
	 */
	protected function add_actions() {
		add_action( 'elementor/element/container/section_border/after_section_end', [ $this, 'register_controls' ], 10, 2 );
	}

	/**
	 * Register mask controls
	 *
	 * @param Element_Base $element elementor element instance
	 * @param array        $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'section_mask',
			[
				'label' => esc_html__( 'Mask', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$element->add_control(
			'enable_mask',
			[
				'label'        => esc_html__( 'Enable Mask', 'toolkit' ),
				'type'         => Controls_Manager::SWITCHER,
				'label_on'     => esc_html__( 'Yes', 'toolkit' ),
				'label_off'    => esc_html__( 'No', 'toolkit' ),
				'return_value' => 'yes',
				'default'      => '',
			]
		);

		$element->add_control(
			'mask_direction',
			[
				'label'     => esc_html__( 'Mask Direction', 'toolkit' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => [
					'to right' => esc_html__( 'Horizontal', 'toolkit' ),
					'to top'   => esc_html__( 'Vertical', 'toolkit' ),
				],
				'default'   => 'to right',
				'condition' => [
					'enable_mask' => 'yes',
				],
			]
		);

		$element->add_control(
			'mask_start',
			[
				'label'      => esc_html__( 'Start Fade (%)', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [
						'min'  => 0,
						'max'  => 50,
						'step' => 0.5,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 10,
				],
				'condition'  => [
					'enable_mask' => 'yes',
				],
			]
		);

		$element->add_control(
			'mask_end',
			[
				'label'      => esc_html__( 'End Fade (%)', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [
						'min'  => 50,
						'max'  => 100,
						'step' => 0.5,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 90,
				],
				'selectors'  => [
					'{{WRAPPER}}' => 'mask-image: linear-gradient({{mask_direction.VALUE}}, rgba(0, 0, 0, 0) 0%, rgb(0, 0, 0) {{mask_start.SIZE}}{{mask_start.UNIT}}, rgb(0, 0, 0) {{SIZE}}{{UNIT}}, rgba(0, 0, 0, 0) 100%); -webkit-mask-image: linear-gradient({{mask_direction.VALUE}}, rgba(0, 0, 0, 0) 0%, rgb(0, 0, 0) {{mask_start.SIZE}}{{mask_start.UNIT}}, rgb(0, 0, 0) {{SIZE}}{{UNIT}}, rgba(0, 0, 0, 0) 100%);',
				],
				'condition'  => [
					'enable_mask' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}
}
