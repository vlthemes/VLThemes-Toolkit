<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Contact Form 7 Widget
 *
 * Renders Contact Form 7 forms in Elementor
 */
class ContactForm7Widget extends Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vlt-contact-form-7';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'Contact Form 7', 'toolkit' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-mail vlthemes-badge';
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
		return [ 'contact', 'form', '7', 'mail' ];
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		if ( !class_exists( 'WPCF7_ContactForm' ) ) {
			$this->start_controls_section(
				'section_warning',
				[
					'label' => esc_html__( 'Warning!', 'toolkit' ),
				]
			);

			$this->add_control(
				'notification_warning',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => '<strong>Contact Form 7</strong> is not installed/activated on your site. Please install and activate <strong>Contact Form 7</strong> first.',
					'content_classes' => 'elementor-panel-alert elementor-panel-alert-info',
					'separator'       => 'after',
				]
			);

			$this->end_controls_section();
		} else {
			$this->start_controls_section(
				'section_contact_form',
				[
					'label' => esc_html__( 'Contact Form 7', 'toolkit' ),
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

			if ( function_exists( 'vlt_toolkit_get_cf7_forms' ) ) {
				$this->add_control(
					'contact_form',
					[
						'label'   => esc_html__( 'Select Form', 'toolkit' ),
						'type'    => Controls_Manager::SELECT2,
						'options' => vlt_toolkit_get_cf7_forms(),
						'default' => 0,
					]
				);
			}

			$this->end_controls_section();
		}
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$this->add_render_attribute( 'contact-form-7', 'class', 'vlt-contact-form-7' );

		?>

		<div <?php $this->print_render_attribute_string( 'contact-form-7' ); ?>>

			<?php

				if ( !empty( $settings['contact_form'] ) && function_exists( 'vlt_toolkit_render_cf7_form' ) ) {
					echo vlt_toolkit_render_cf7_form( $settings['contact_form'] );
				}

			?>

		</div>

		<?php

	}
}