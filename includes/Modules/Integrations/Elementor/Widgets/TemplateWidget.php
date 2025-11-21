<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use VLT\Toolkit\Modules\Integrations\Elementor;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Widget
 *
 * Renders Elementor library templates
 */
class TemplateWidget extends Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vlt-template';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Template', 'toolkit' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-document-file vlthemes-badge';
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
		return [ 'template', 'library', 'block', 'page', 'section', 'element' ];
	}

	/**
	 * Check if reload preview is required
	 *
	 * @return bool
	 */
	public function is_reload_preview_required() {
		return true;
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_template',
			[
				'label' => esc_html__( 'Template', 'toolkit' ),
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

		if ( function_exists( 'vlt_toolkit_populate_elementor_template_types' ) && function_exists( 'vlt_toolkit_populate_elementor_templates' ) ) {
			$template_types = [ '' => esc_html__( 'All', 'toolkit' ) ] + vlt_toolkit_populate_elementor_template_types();

			$this->add_control(
				'template_type',
				[
					'label'   => esc_html__( 'Template Type', 'toolkit' ),
					'type'    => Controls_Manager::SELECT,
					'default' => '',
					'options' => $template_types,
				]
			);

			// Control for all templates (when type is empty)
			$this->add_control(
				'template_all',
				[
					'label'       => esc_html__( 'Choose Template', 'toolkit' ),
					'type'        => Controls_Manager::SELECT2,
					'options'     => vlt_toolkit_populate_elementor_templates(),
					'label_block' => true,
					'condition'   => [
						'template_type' => '',
					],
				]
			);

			foreach ( $template_types as $type_key => $type_label ) {
				if ( empty( $type_key ) ) {
					continue;
				}

				$this->add_control(
					'template_' . $type_key,
					[
						'label'       => esc_html__( 'Choose Template', 'toolkit' ),
						'type'        => Controls_Manager::SELECT2,
						'options'     => vlt_toolkit_populate_elementor_templates( $type_key ),
						'label_block' => true,
						'condition'   => [
							'template_type' => $type_key,
						],
					]
				);
			}
		}

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings      = $this->get_settings_for_display();
		$template_type = $settings['template_type'];
		$template_key  = empty( $template_type ) ? 'all' : $template_type;
		$template_id   = $settings[ 'template_' . $template_key ] ?? 0;

		?>

		<div class="vlt-elementor-template">
			<?php echo Elementor::render_template( $template_id ); ?>
		</div>

		<?php
	}
}