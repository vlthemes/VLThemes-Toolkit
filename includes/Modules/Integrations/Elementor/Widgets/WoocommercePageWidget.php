<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Plugin;
use Elementor\Widget_Base;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce Page Widget
 *
 * Renders WooCommerce cart, checkout or my account pages
 */
class WoocommercePageWidget extends Widget_Base {
	/**
	 * Get widget name
	 *
	 * @return string
	 */
	public function get_name() {
		return 'vlt-wc-page';
	}

	/**
	 * Get widget title
	 *
	 * @return string
	 */
	public function get_title() {
		return esc_html__( 'WC Page', 'toolkit' );
	}

	/**
	 * Get widget icon
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-woocommerce vlthemes-badge';
	}

	/**
	 * Get widget categories
	 *
	 * @return array
	 */
	public function get_categories() {
		return [ 'vlt-woocommerce' ];
	}

	/**
	 * Get widget keywords
	 *
	 * @return array
	 */
	public function get_keywords() {
		return [ 'woocommerce', 'cart', 'checkout', 'account', 'my account', 'wc' ];
	}

	/**
	 * Register widget controls
	 */
	protected function register_controls() {
		$this->start_controls_section(
			'section_wc_page',
			[
				'label' => esc_html__( 'WooCommerce Page', 'toolkit' ),
			]
		);

		if ( function_exists( 'vlt_is_theme_activated' ) && !vlt_is_theme_activated() ) {
			$this->add_control(
				'notification_activation',
				[
					'type'            => Controls_Manager::RAW_HTML,
					'raw'             => '<strong>' . esc_html__( 'Theme not activated!', 'toolkit' ) . '</strong><br>' .
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
			'wc_page',
			[
				'label'   => esc_html__( 'Page to render', 'toolkit' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'cart',
				'options' => [
					'cart'     => esc_html__( 'Cart', 'toolkit' ),
					'checkout' => esc_html__( 'Checkout', 'toolkit' ),
					'account'  => esc_html__( 'My Account', 'toolkit' ),
				],
			]
		);

		$this->add_control(
			'wrapper_class',
			[
				'label'       => esc_html__( 'Wrapper CSS class', 'toolkit' ),
				'type'        => Controls_Manager::TEXT,
				'label_block' => true,
				'placeholder' => 'my-custom-class',
			]
		);

		$this->end_controls_section();
	}

	/**
	 * Render widget output
	 */
	protected function render() {
		if ( !class_exists( 'WooCommerce' ) ) {
			echo '<div class="we-wc-notice">' . esc_html__( 'WooCommerce is not active. Please activate WooCommerce to display this content.', 'toolkit' ) . '</div>';

			return;
		}

		$is_editor = Plugin::$instance->editor->is_edit_mode() || ( method_exists( Plugin::$instance, 'preview' ) && Plugin::$instance->preview->is_preview_mode() );

		// Simulate a logged out user so that all WooCommerce sections will render in the Editor
		if ( $is_editor ) {
			// Ensure Woo session + cart are live on FIRST preview load
			if ( function_exists( 'wc' ) && wc() ) {
				// Session
				if ( !WC()->session ) {
					wc()->initialize_session();
				}

				// Cart
				if ( !WC()->cart ) {
					if ( function_exists( 'wc_load_cart' ) ) {
						wc_load_cart(); // WC 8+
					}

					if ( !WC()->cart && class_exists( 'WC_Cart' ) ) {
						WC()->cart = new \WC_Cart(); // Fallback
					}
				}

				// Make sure common frontend scripts/styles are available in the iframe
				if ( class_exists( '\WC_Frontend_Scripts' ) && method_exists( '\WC_Frontend_Scripts', 'load_scripts' ) ) {
					\WC_Frontend_Scripts::load_scripts();
				}
			}
		}

		$settings = $this->get_settings_for_display();

		$page = isset( $settings['wc_page'] ) ? $settings['wc_page'] : 'cart';

		$this->add_render_attribute(
			'wc-page',
			[
				'class' => [
					'vlt-wc-page',
					$settings['wrapper_class'],
				]
			]
		);

		switch ( $page ) {
			case 'checkout':
				$shortcode = '[woocommerce_checkout]';

				break;

			case 'account':
				$shortcode = '[woocommerce_my_account]';

				break;

			case 'cart':
			default:
				$shortcode = '[woocommerce_cart]';

				break;
		}

		?>

		<div <?php $this->print_render_attribute_string( 'wc-page' ); ?>>
			<?php echo do_shortcode( $shortcode ); ?>
		</div>

		<?php

	}
}
