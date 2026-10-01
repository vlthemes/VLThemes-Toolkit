<?php

namespace VLT\Toolkit\Admin;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Dashboard class
 */
class Dashboard {
	/**
	 * Theme object
	 *
	 * @var \WP_Theme
	 */
	public $theme;

	/**
	 * Theme name
	 *
	 * @var string
	 */
	public $theme_name;

	/**
	 * Theme version
	 *
	 * @var string
	 */
	public $theme_version;

	/**
	 * Theme slug
	 *
	 * @var string
	 */
	public $theme_slug;

	/**
	 * Theme author
	 *
	 * @var string
	 */
	public $theme_author;

	/**
	 * Site URL
	 *
	 * @var string
	 */
	public $site_url;

	/**
	 * Products URL
	 *
	 * @var string
	 */
	public $products_url;

	/**
	 * Documentation URL
	 *
	 * @var string
	 */
	public $docs_url;

	/**
	 * Knowledge Base URL
	 *
	 * @var string
	 */
	public $knowledge_base_url;

	/**
	 * Knowledge Base article: where to find the purchase code / license key
	 *
	 * @var string
	 */
	public $license_help_url;

	/**
	 * Support URL
	 *
	 * @var string
	 */
	public $support_url;

	/**
	 * Support Policy URL
	 *
	 * @var string
	 */
	public $support_policy_url;

	/**
	 * Support email
	 *
	 * @var string
	 */
	public $support_email;

	/**
	 * Instance
	 *
	 * @var Dashboard
	 */
	private static $instance = null;

	/**
	 * Dashboard slug
	 *
	 * @var string
	 */
	private $dashboard_slug = 'vlt-dashboard';

	/**
	 * Dashboard path
	 *
	 * @var string
	 */
	private $dashboard_path;

	/**
	 * Constructor
	 */
	private function __construct() {
		$this->dashboard_path = VLT_TOOLKIT_PATH . 'includes/Admin/';

		// Set theme properties
		$this->theme         = wp_get_theme();
		$this->theme_name    = $this->theme->get( 'Name' );
		$this->theme_version = $this->theme->get( 'Version' );
		$this->theme_slug    = $this->theme->get_template();
		$this->theme_author  = $this->theme->get( 'Author' );

		// Set helper links with filters for customization
		$this->site_url           = apply_filters( 'vlt_toolkit_site_url', 'https://vlthemes.me/' );
		$this->products_url       = apply_filters( 'vlt_toolkit_products_url', $this->site_url . 'products/' );
		$this->docs_url           = apply_filters( 'vlt_toolkit_docs_url', $this->site_url . 'docs/', $this->theme_slug );
		$this->knowledge_base_url = apply_filters( 'vlt_toolkit_knowledge_base_url', $this->site_url . 'kb/' );
		$this->license_help_url   = apply_filters( 'vlt_toolkit_license_help_url', $this->knowledge_base_url . 'where-to-find-my-purchase-and-download-link/' );
		$this->support_url        = apply_filters( 'vlt_toolkit_support_url', $this->site_url . 'support-ticket/' );
		$this->support_policy_url = apply_filters( 'vlt_toolkit_support_policy_url', $this->site_url . 'terms/#support' );
		$this->support_email      = apply_filters( 'vlt_toolkit_support_email', 'support@vlthemes.me' );

		$this->init_hooks();
	}

	/**
	 * Get instance
	 *
	 * @return Dashboard
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Customize admin footer version
	 *
	 * @return string
	 */
	public function admin_footer_version() {
		$screen = get_current_screen();

		// Only on our dashboard pages
		if ( false === strpos( $screen->id, $this->dashboard_slug ) ) {
			return '';
		}

		/* translators: %s: theme version */
		return sprintf( esc_html__( 'Version %s', 'toolkit' ), esc_html( $this->theme_version ) );
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		// Main menu page
		add_menu_page(
			$this->theme_name,
			$this->theme_name,
			'manage_options',
			$this->dashboard_slug,
			[ $this, 'render_welcome_page' ],
			$this->get_menu_icon(),
			3,
		);

		// Welcome submenu
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Welcome', 'toolkit' ),
			esc_html__( 'Welcome', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug,
			[ $this, 'render_welcome_page' ],
		);

		// Activate theme
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Activate Theme', 'toolkit' ),
			esc_html__( 'Activate Theme', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-activate-theme',
			[ $this, 'render_activate_theme_page' ],
		);

		// Requirements submenu
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Requirements', 'toolkit' ),
			esc_html__( 'Requirements', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-requirements',
			[ $this, 'render_requirements_page' ],
		);

		// Required Plugins
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Required Plugins', 'toolkit' ),
			esc_html__( 'Required Plugins', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-plugins',
			[ $this, 'render_plugins_page' ],
		);

		// Demo Import (redirects to One Click Demo Import before any output is sent)
		$demo_import_hook = add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Demo Import', 'toolkit' ),
			esc_html__( 'Demo Import', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-demo-import',
			[ $this, 'render_demo_import_page' ],
		);
		add_action( 'load-' . $demo_import_hook, [ $this, 'redirect_demo_import_page' ] );

		// Theme Options (redirects to the Customizer before any output is sent)
		$theme_options_hook = add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Theme Options', 'toolkit' ),
			esc_html__( 'Theme Options', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-theme-options',
			[ $this, 'render_theme_options_page' ],
		);
		add_action( 'load-' . $theme_options_hook, [ $this, 'redirect_theme_options_page' ] );

		// System Status
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'System Status', 'toolkit' ),
			esc_html__( 'System Status', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-status',
			[ $this, 'render_status_page' ],
		);

		// Template Parts - only if Elementor and ACF are active
		if ( defined( 'ELEMENTOR_VERSION' ) && function_exists( 'acf_add_local_field_group' ) ) {
			add_submenu_page(
				$this->dashboard_slug,
				esc_html__( 'Template Parts', 'toolkit' ),
				esc_html__( 'Template Parts', 'toolkit' ),
				'manage_options',
				'edit.php?post_type=vlt_tp',
				'',
			);
		}

		// Help Center
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__( 'Help Center', 'toolkit' ),
			esc_html__( 'Help Center', 'toolkit' ),
			'manage_options',
			$this->dashboard_slug . '-helper',
			[ $this, 'render_helper_page' ],
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts( $hook ) {
		// Only load on our dashboard pages
		if ( false === strpos( $hook, $this->dashboard_slug ) ) {
			return;
		}

		// Enqueue dashboard CSS
		wp_enqueue_style(
			'vlt-dashboard',
			VLT_TOOLKIT_URL . 'assets/css/admin-dashboard.css',
			[],
			VLT_TOOLKIT_VERSION,
		);

		wp_enqueue_script( 'imagesloaded' );
		wp_enqueue_script( 'masonry' );

		wp_add_inline_script(
			'masonry',
			'
			document.addEventListener("DOMContentLoaded", function() {
				var grid = document.querySelector(".vlt-masonry-grid");
				if (grid) {
					imagesLoaded(grid, function() {
						new Masonry(grid, {
							itemSelector: ".vlt-masonry-item",
							columnWidth: ".vlt-masonry-sizer",
							percentPosition: true,
							gutter: 20
						});
					});
				}
			});
		',
		);
	}

	/**
	 * Render welcome page
	 */
	public function render_welcome_page() {
		$this->render_template( 'template-welcome' );
	}

	/**
	 * Render activate theme
	 */
	public function render_activate_theme_page() {
		$this->render_template( 'template-activate-theme' );
	}

	/**
	 * Render status page
	 */
	public function render_status_page() {
		$this->render_template( 'template-status' );
	}

	/**
	 * Render requirements page
	 */
	public function render_requirements_page() {
		$this->render_template( 'template-requirements' );
	}

	/**
	 * Render plugins page
	 */
	public function render_plugins_page() {
		$this->render_template( 'template-plugins' );
	}

	/**
	 * Render demo import page
	 *
	 * Unreachable in practice: redirect_demo_import_page() sends the
	 * user to One Click Demo Import on 'load-{hook}', before this ever runs.
	 */
	public function render_demo_import_page() {}

	/**
	 * Redirect the "Demo Import" submenu to One Click Demo Import
	 *
	 * Runs on the page's 'load-{hook}' action, before any admin HTML
	 * has been output, so the redirect headers can still be sent.
	 */
	public function redirect_demo_import_page() {
		wp_safe_redirect( admin_url( 'themes.php?page=one-click-demo-import' ) );
		exit;
	}

	/**
	 * Render helper page
	 */
	public function render_helper_page() {
		$this->render_template( 'template-helper' );
	}

	/**
	 * Render elementor page
	 */
	public function render_elementor_page() {
		$this->render_template( 'template-elementor' );
	}

	/**
	 * Render theme options page
	 *
	 * Unreachable in practice: redirect_theme_options_page() sends the
	 * user to the Customizer on 'load-{hook}', before this ever runs.
	 */
	public function render_theme_options_page() {}

	/**
	 * Redirect the "Theme Options" submenu straight to the Customizer
	 *
	 * Runs on the page's 'load-{hook}' action, before any admin HTML
	 * has been output, so the redirect headers can still be sent.
	 */
	public function redirect_theme_options_page() {
		wp_safe_redirect( admin_url( 'customize.php' ) );
		exit;
	}

	/**
	 * Add UTM tags to a vlthemes.me link, so sales from the dashboard show up in analytics
	 *
	 * @param string $url       link to the site
	 * @param string $placement where the link sits (utm_content)
	 *
	 * @return string
	 */
	public function utm( $url, $placement = '' ) {
		$args = [
			'utm_source'   => 'wp-dashboard',
			'utm_medium'   => 'theme',
			'utm_campaign' => $this->theme_slug,
		];

		if ( $placement ) {
			$args['utm_content'] = $placement;
		}

		return add_query_arg( apply_filters( 'vlt_toolkit_utm_args', $args, $url, $placement ), $url );
	}

	/**
	 * Status value for the requirement tables: colored dot + value
	 *
	 * @param bool   $condition check passed
	 * @param string $value     value to show
	 *
	 * @return string
	 */
	public function status( $condition, $value = '' ) {
		return sprintf(
			'<span class="vlt-check vlt-check--%1$s">%2$s</span>',
			$condition ? 'ok' : 'fail',
			esc_html( $value ),
		);
	}

	/**
	 * Theme screenshot URL (screenshot.png / .jpg / .jpeg / .webp), or empty if the theme has none
	 *
	 * @return string
	 */
	public function get_screenshot_url() {
		foreach ( [ 'png', 'jpg', 'jpeg', 'webp' ] as $ext ) {
			if ( file_exists( get_template_directory() . '/screenshot.' . $ext ) ) {
				return get_template_directory_uri() . '/screenshot.' . $ext;
			}
		}

		return '';
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks() {
		add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		add_filter( 'update_footer', [ $this, 'admin_footer_version' ], 11 );
	}

	/**
	 * Get menu icon SVG
	 *
	 * @return string
	 */
	private function get_menu_icon() {
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 912 1019"><path fill="#aaaaaa" d="M402.516 12.75c29.362-16.993 76.942-17.007 106.328 0l349.352 202.168c29.362 16.992 53.164 58.287 53.164 92.169v404.598c0 33.912-23.778 75.163-53.164 92.169L508.844 1006.02c-29.362 17-76.942 17.01-106.328 0L53.164 803.854C23.802 786.862 0 745.567 0 711.685V307.087c0-33.912 23.778-75.163 53.164-92.169L402.516 12.749Zm40.494 742.748-1.091 2.594-57.07-138.51-.017.041-115.211-279.689h-114.75l172.125 418.158H441.93l1.08-2.594Zm31.538-75.675 172.453-413.794-114.535.137-111.15 266.109 53.233 147.547-.001.001Zm73.75-4.412c4.767 23.66 14.546 41.762 29.337 54.306 21.826 18.511 52.818 27.766 93.233 27.766 40.415 0 77.812-5.517 77.812-5.517l-16.119-76.465s-23.119 3.039-41.457 1.664c-18.338-1.375-30.437-6.419-36.605-11.878-6.168-5.458-11.881-17.691-11.881-30.506V449.094l-94.32 226.317ZM677.86 364.532l-34.054 81.711h88.755v-81.711H677.86Z"/></svg>';

		return 'data:image/svg+xml;base64,' . base64_encode( $svg );
	}

	/**
	 * Render dashboard header
	 */
	private function render_header() {
		global $submenu;
		$menu_items = '';

		if ( isset( $submenu[ $this->dashboard_slug ] ) ) {
			$menu_items = $submenu[ $this->dashboard_slug ];
		}

		if ( !empty( $menu_items ) ) :
			?>

<div class="vlt-theme-dashboard">

	<div class="vlt-theme-dashboard__navigation">

		<div class="nav-tab-wrapper">

			<?php
						foreach ( $menu_items as $item ) :
							// Skip Template Parts from navigation tabs
							if ( false !== strpos( $item[2], 'edit.php?post_type=vlt_tp' ) ) {
								continue;
							}
							$class = isset( $_GET['page'] ) && $_GET['page'] === $item[2] ? ' nav-tab-active' : '';
							?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $item[2] ) ); ?>"
				class="nav-tab<?php echo esc_attr( $class ); ?>">
				<?php echo esc_html( $item[0] ); ?>
			</a>
			<?php endforeach; ?>
		</div>

	</div>

	<?php
		endif;
	}

	/**
	 * Render dashboard footer
	 */
	private function render_footer() {
		echo '</div>';
	}

	/**
	 * Render template
	 *
	 * @param string $template template name
	 */
	private function render_template( $template ) {
		$template_file = $this->dashboard_path . 'templates/' . $template . '.php';

		echo '<div class="wrap">';
		/* translators: %s: theme name */
		echo '<h2>' . sprintf( esc_html__( '%s Dashboard', 'toolkit' ), esc_html( $this->theme_name ) ) . '</h2>';

		$this->render_header();
		echo '<div class="vlt-theme-dashboard__content vlt-theme-dashboard--' . esc_attr( $template ) . '">';

		include $template_file;
		echo '</div>';
		$this->render_footer();
		echo '</div>';
	}
}
?>