<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copyright Widget
 *
 * Displays copyright text with dynamic year
 */
class CopyrightWidget extends Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vlt-copyright';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Copyright', 'toolkit' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-t-letter vlthemes-badge';
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
		return [ 'copyright', 'text' ];
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_copyright',
			[
				'label' => esc_html__( 'Copyright', 'toolkit' ),
			]
		);

		if ( function_exists( 'vlt_is_theme_activated' ) && !vlt_is_theme_activated() ) {
			$this->add_control(
				'notification_activation',
				[
					'type' => Controls_Manager::RAW_HTML,
					'raw'  => '<strong>' . esc_html__( 'Theme not activated!', 'toolkit' ) . '</strong><br>' .
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

		$this->add_control(
			'text',
			[
				'label'   => esc_html__( 'Text', 'toolkit' ),
				'type'    => Controls_Manager::TEXTAREA,
				'default' => esc_html__( '© {{YEAR}}. All rights reserved.', 'toolkit' ),
			]
		);

		$this->add_responsive_control(
			'alignment',
			[
				'label'   => esc_html__( 'Alignment', 'toolkit' ),
				'type'    => Controls_Manager::CHOOSE,
				'options' => [
					'left' => [
						'title' => esc_html__( 'Left', 'toolkit' ),
						'icon'  => 'eicon-text-align-left',
					],
					'center' => [
						'title' => esc_html__( 'Center', 'toolkit' ),
						'icon'  => 'eicon-text-align-center',
					],
					'right' => [
						'title' => esc_html__( 'Right', 'toolkit' ),
						'icon'  => 'eicon-text-align-right',
					],
				],
				'default'   => 'center',
				'selectors' => [
					'{{WRAPPER}}' => 'text-align: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();

		$this->start_controls_section(
			'section_style',
			[
				'label' => esc_html__( 'Style', 'toolkit' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			]
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			[
				'name'     => 'text_typography',
				'selector' => '{{WRAPPER}} .vlt-copyright',
			]
		);

		$this->add_control(
			'text_color',
			[
				'label'     => esc_html__( 'Text Color', 'toolkit' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => [
					'{{WRAPPER}} .vlt-copyright' => 'color: {{VALUE}};',
				],
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( 'copyright', 'class', 'vlt-copyright' );

		?>

		<div <?php $this->print_render_attribute_string( 'copyright' ); ?>>

			<?php if ( $settings['text'] ) : ?>

			<p>
				<?php echo wp_kses_post( vlt_toolkit_parse_dynamic_content( $settings['text'] ) ); ?>
			</p>

			<?php endif; ?>

		</div>

		<?php

	}
}