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
				'label'        => esc_html__( 'Mask Direction', 'toolkit' ),
				'type'         => Controls_Manager::SELECT,
				'options'      => [
					'horizontal' => esc_html__( 'Horizontal', 'toolkit' ),
					'vertical'   => esc_html__( 'Vertical', 'toolkit' ),
					'top'        => esc_html__( 'Top', 'toolkit' ),
					'right'      => esc_html__( 'Right', 'toolkit' ),
					'bottom'     => esc_html__( 'Bottom', 'toolkit' ),
					'left'       => esc_html__( 'Left', 'toolkit' ),
				],
				'default'      => 'horizontal',
				'prefix_class' => 'vlt-mask-',
				'condition'    => [
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
					'{{WRAPPER}}.vlt-mask-horizontal' => 'mask-image: linear-gradient(to right, #0000 0%, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}, #0000 100%); -webkit-mask-image: linear-gradient(to right, #0000 0%, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}, #0000 100%);',
					'{{WRAPPER}}.vlt-mask-vertical'   => 'mask-image: linear-gradient(#0000 0%, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}, #0000 100%); -webkit-mask-image: linear-gradient(#0000 0%, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}, #0000 100%);',
					'{{WRAPPER}}.vlt-mask-top'        => 'mask-image: linear-gradient(#0000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(#0000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}});',
					'{{WRAPPER}}.vlt-mask-bottom'     => 'mask-image: linear-gradient(#000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #0000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(#000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #0000 {{SIZE}}{{UNIT}});',
					'{{WRAPPER}}.vlt-mask-right'      => 'mask-image: linear-gradient(to right, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #0000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(to right, #000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #0000 {{SIZE}}{{UNIT}});',
					'{{WRAPPER}}.vlt-mask-left'       => 'mask-image: linear-gradient(to right, #0000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(to right, #0000 {{mask_start.SIZE}}{{mask_start.UNIT}}, #000 {{SIZE}}{{UNIT}});',
				],
				'condition'  => [
					'enable_mask' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}
}
