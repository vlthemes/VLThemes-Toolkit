<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Module;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Controls_Manager;
use Elementor\Element_Base;
use Elementor\Core\Base\Module as Module_Base;

/**
 * Progressive Blur Extension
 *
 * Adds a progressive backdrop-blur overlay to containers
 */
class ProgressiveBlurModule extends Module_Base {

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
		return 'progressive-blur';
	}

	/**
	 * Register actions
	 */
	protected function add_actions() {
		add_action( 'elementor/element/container/section_border/after_section_end', [ $this, 'register_controls' ], 10, 2 );
		add_action( 'elementor/frontend/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
		add_action( 'elementor/editor/after_enqueue_styles', [ $this, 'enqueue_styles' ] );
		add_action( 'elementor/frontend/after_enqueue_scripts', [ $this, 'register_scripts' ] );
		add_action( 'elementor/editor/before_enqueue_scripts', [ $this, 'register_scripts' ] );
	}

	/**
	 * Register module scripts
	 *
	 * JS only creates/removes the empty overlay div and appends it last, so it
	 * always ends up as the final DOM child of the container — backdrop-filter
	 * only blurs what is visually behind it, and child widgets render after any
	 * ::before pseudo-element, which left it with nothing to blur. All visual
	 * styling (size, blur, mask, z-index) is applied by Elementor via the
	 * `selectors` on the controls below, not by the script.
	 */
	public function register_scripts() {
		wp_enqueue_script(
			'vlt-progressive-blur-module',
			VLT_TOOLKIT_URL . 'assets/js/elementor-progressive-blur.js',
			[ 'jquery', 'elementor-frontend' ],
			VLT_TOOLKIT_VERSION,
			true
		);
	}

	/**
	 * Enqueue base styles for the progressive blur overlay
	 */
	public function enqueue_styles() {
		wp_add_inline_style(
			'elementor-frontend',
			'.vlt-progressive-blur { position: relative; }
			.vlt-progressive-blur > .vlt-progressive-blur-overlay {
				position: absolute;
				pointer-events: none;
				left: 0;
				width: 100%;
			}
			.vlt-progressive-blur-top > .vlt-progressive-blur-overlay { top: 0; bottom: auto; }
			.vlt-progressive-blur-bottom > .vlt-progressive-blur-overlay { bottom: 0; top: auto; }'
		);
	}

	/**
	 * Register progressive blur controls
	 *
	 * @param Element_Base $element elementor element instance
	 * @param array        $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		$element->start_controls_section(
			'section_progressive_blur',
			[
				'label' => esc_html__( 'Progressive Blur', 'toolkit' ) . \VLT\Toolkit\Modules\Integrations\Elementor\Helpers::get_badge_svg(),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$element->add_control(
			'enable_progressive_blur',
			[
				'label'              => esc_html__( 'Enable Progressive Blur', 'toolkit' ),
				'type'               => Controls_Manager::SWITCHER,
				'label_on'           => esc_html__( 'Yes', 'toolkit' ),
				'label_off'          => esc_html__( 'No', 'toolkit' ),
				'return_value'       => 'yes',
				'default'            => '',
				'frontend_available' => true,
			]
		);

		$element->add_control(
			'progressive_blur_position',
			[
				'label'              => esc_html__( 'Position', 'toolkit' ),
				'type'               => Controls_Manager::SELECT,
				'options'            => [
					'bottom' => esc_html__( 'Bottom', 'toolkit' ),
					'top'    => esc_html__( 'Top', 'toolkit' ),
				],
				'default'            => 'bottom',
				'frontend_available' => true,
				'condition'          => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->add_responsive_control(
			'progressive_blur_size',
			[
				'label'      => esc_html__( 'Size', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', '%', 'vh', 'vw' ],
				'range'      => [
					'px' => [
						'min' => 0,
						'max' => 800,
					],
					'%'  => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'    => [
					'unit' => 'px',
					'size' => 178,
				],
				'selectors'  => [
					'{{WRAPPER}} > .vlt-progressive-blur-overlay' => 'height: {{SIZE}}{{UNIT}};',
				],
				'condition'  => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->add_control(
			'progressive_blur_amount',
			[
				'label'     => esc_html__( 'Blur Amount (px)', 'toolkit' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => [
					'px' => [
						'min' => 0,
						'max' => 100,
					],
				],
				'default'   => [
					'unit' => 'px',
					'size' => 10,
				],
				'selectors' => [
					'{{WRAPPER}} > .vlt-progressive-blur-overlay' => '-webkit-backdrop-filter: blur({{SIZE}}{{UNIT}}); backdrop-filter: blur({{SIZE}}{{UNIT}});',
				],
				'condition' => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->add_control(
			'progressive_blur_start',
			[
				'label'      => esc_html__( 'Start Fade (%)', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 0.5,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 0,
				],
				'condition'  => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->add_control(
			'progressive_blur_end',
			[
				'label'      => esc_html__( 'End Fade (%)', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ '%' ],
				'range'      => [
					'%' => [
						'min'  => 0,
						'max'  => 100,
						'step' => 0.5,
					],
				],
				'default'    => [
					'unit' => '%',
					'size' => 50,
				],
				'selectors'  => [
					'{{WRAPPER}}.vlt-progressive-blur-bottom > .vlt-progressive-blur-overlay' => 'mask-image: linear-gradient(#0000 {{progressive_blur_start.SIZE}}{{progressive_blur_start.UNIT}}, #000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(#0000 {{progressive_blur_start.SIZE}}{{progressive_blur_start.UNIT}}, #000 {{SIZE}}{{UNIT}});',
					'{{WRAPPER}}.vlt-progressive-blur-top > .vlt-progressive-blur-overlay'    => 'mask-image: linear-gradient(#000 {{progressive_blur_start.SIZE}}{{progressive_blur_start.UNIT}}, #0000 {{SIZE}}{{UNIT}}); -webkit-mask-image: linear-gradient(#000 {{progressive_blur_start.SIZE}}{{progressive_blur_start.UNIT}}, #0000 {{SIZE}}{{UNIT}});',
				],
				'condition'  => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->add_control(
			'progressive_blur_zindex',
			[
				'label'     => esc_html__( 'Z-Index', 'toolkit' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 5,
				'selectors' => [
					'{{WRAPPER}} > .vlt-progressive-blur-overlay' => 'z-index: {{VALUE}};',
				],
				'condition' => [
					'enable_progressive_blur' => 'yes',
				],
			]
		);

		$element->end_controls_section();
	}
}
