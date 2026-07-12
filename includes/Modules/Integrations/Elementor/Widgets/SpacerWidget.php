<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Spacer Widget
 *
 * Adds vertical spacing between elements
 */
class SpacerWidget extends Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vlt-spacer';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Spacer', 'toolkit' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-spacer vlthemes-badge';
	}

	/**
	 * Get widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'vlt-elements' ];
	}

	/**
	 * Get widget keywords
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'space', 'gap', 'height' ];
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_spacer',
			[
				'label' => esc_html__( 'Spacer', 'toolkit' ),
			]
		);

		if ( function_exists( 'vlt_is_theme_activated' ) && !vlt_is_theme_activated() ) {
			$this->add_control(
				'notification_activation',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'              => '<strong>' . esc_html__( 'Theme not activated!', 'toolkit' ) . '</strong><br>' .
						sprintf(
							/* translators: %s: Dashboard URL */
							__( 'Go to the <a href="%s" target="_blank">Dashboard</a> to activate.', 'toolkit' ),
							admin_url( 'admin.php?page=vlt-dashboard-activate-theme' )
						),
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
					'separator'       => 'after',
				]
			);
		}

		$this->add_responsive_control(
			'space',
			[
				'label'      => esc_html__( 'Space', 'toolkit' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => [ 'px', 'em', 'rem', 'custom' ],
				'default'    => [
					'unit' => 'rem',
					'size' => 2,
				],
				'selectors' => [
					'{{WRAPPER}} .vlt-spacer' => 'height: {{SIZE}}{{UNIT}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		?>

		<div class="vlt-spacer"></div>

		<?php
	}
}
