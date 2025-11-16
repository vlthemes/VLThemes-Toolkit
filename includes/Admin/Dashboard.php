<?php

/**
 * VLT Helper Dashboard
 *
 * @package VLT Helper
 */

namespace VLT\Helper\Admin;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Dashboard class
 */
class Dashboard
{

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
	 * Dashboard URL
	 *
	 * @var string
	 */
	private $dashboard_url;

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
	 * Changelog URL
	 *
	 * @var string
	 */
	public $changelog_url;

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
	 * Elementor Partner URL
	 *
	 * @var string
	 */
	public $elementor_partner_url;

	/**
	 * Fornex Partner URL
	 *
	 * @var string
	 */
	public $fornex_partner_url;

	/**
	 * Get instance
	 *
	 * @return Dashboard
	 */
	public static function instance()
	{
		if (null === self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 */
	private function __construct()
	{
		$this->dashboard_path = VLT_HELPER_PATH . 'includes/Admin/';
		$this->dashboard_url  = VLT_HELPER_URL . 'includes/Admin/';

		// Set theme properties
		$this->theme         = wp_get_theme();
		$this->theme_name    = $this->theme->get('Name');
		$this->theme_version = $this->theme->get('Version');
		$this->theme_slug    = $this->theme->get_template();
		$this->theme_author  = $this->theme->get('Author');

		// Set helper links
		$this->docs_url = 'https://docs.vlthemes.me/docs/';
		$this->knowledge_base_url = 'https://docs.vlthemes.me/knowbase/';
		$this->changelog_url = 'https://docs.vlthemes.me/changelog/';
		$this->support_url = 'https://docs.vlthemes.me/support/';
		$this->support_policy_url = 'https://themeforest.net/page/item_support_policy';
		$this->elementor_partner_url = 'https://be.elementor.com/visit/?bta=65732&nci=5352';
		$this->fornex_partner_url = 'https://fornex.com/c/ffg4ni/';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks
	 */
	private function init_hooks()
	{
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
		add_filter('admin_footer_text', array($this, 'admin_footer_text'));
		add_filter('update_footer', array($this, 'admin_footer_version'), 11);
	}

	/**
	 * Customize admin footer text
	 *
	 * @return string
	 */
	public function admin_footer_text()
	{
		$screen = get_current_screen();

		// Only on our dashboard pages
		if (strpos($screen->id, $this->dashboard_slug) === false) {
			return '';
		}

		return sprintf(
			/* translators: 1: theme name, 2: opening link tag, 3: closing link tag */
			esc_html__('Enjoyed %1$s? Please leave us a %2$s★★★★★%3$s rating. We really appreciate your support!', 'vlt-helper'),
			'<strong>' . esc_html($this->theme_name) . '</strong>',
			'<a href="https://themeforest.net/downloads" target="_blank" rel="noopener">',
			'</a>'
		);
	}

	/**
	 * Customize admin footer version
	 *
	 * @return string
	 */
	public function admin_footer_version()
	{
		$screen = get_current_screen();

		// Only on our dashboard pages
		if (strpos($screen->id, $this->dashboard_slug) === false) {
			return '';
		}

		return sprintf(esc_html__('Version %s', 'vlt-helper'), esc_html($this->theme_version));
	}

	/**
	 * Get menu icon SVG
	 *
	 * @return string
	 */
	private function get_menu_icon()
	{
		$svg = '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 912 1019"><path fill="#aaaaaa" d="M402.516 12.75c29.362-16.993 76.942-17.007 106.328 0l349.352 202.168c29.362 16.992 53.164 58.287 53.164 92.169v404.598c0 33.912-23.778 75.163-53.164 92.169L508.844 1006.02c-29.362 17-76.942 17.01-106.328 0L53.164 803.854C23.802 786.862 0 745.567 0 711.685V307.087c0-33.912 23.778-75.163 53.164-92.169L402.516 12.749Zm40.494 742.748-1.091 2.594-57.07-138.51-.017.041-115.211-279.689h-114.75l172.125 418.158H441.93l1.08-2.594Zm31.538-75.675 172.453-413.794-114.535.137-111.15 266.109 53.233 147.547-.001.001Zm73.75-4.412c4.767 23.66 14.546 41.762 29.337 54.306 21.826 18.511 52.818 27.766 93.233 27.766 40.415 0 77.812-5.517 77.812-5.517l-16.119-76.465s-23.119 3.039-41.457 1.664c-18.338-1.375-30.437-6.419-36.605-11.878-6.168-5.458-11.881-17.691-11.881-30.506V449.094l-94.32 226.317ZM677.86 364.532l-34.054 81.711h88.755v-81.711H677.86Z"/></svg>';
		return 'data:image/svg+xml;base64,' . base64_encode($svg);
	}

	/**
	 * Add admin menu
	 */
	public function add_admin_menu()
	{
		// Main menu page
		add_menu_page(
			$this->theme_name,
			$this->theme_name,
			'manage_options',
			$this->dashboard_slug,
			array($this, 'render_welcome_page'),
			$this->get_menu_icon(),
			3
		);

		// Welcome submenu
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Welcome', 'vlt-helper'),
			esc_html__('Welcome', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug,
			array($this, 'render_welcome_page')
		);

		// Requirements submenu
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Requirements', 'vlt-helper'),
			esc_html__('Requirements', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-requirements',
			array($this, 'render_requirements_page')
		);

		// Required Plugins
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Required Plugins', 'vlt-helper'),
			esc_html__('Required Plugins', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-plugins',
			array($this, 'render_plugins_page')
		);

		// Demo Import
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Demo Import', 'vlt-helper'),
			esc_html__('Demo Import', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-demo-import',
			array($this, 'render_demo_import_page')
		);

		// Theme Options
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Theme Options', 'vlt-helper'),
			esc_html__('Theme Options', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-theme-options',
			array($this, 'render_theme_options_page')
		);

		// System Status
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('System Status', 'vlt-helper'),
			esc_html__('System Status', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-status',
			array($this, 'render_status_page')
		);

		// Template Parts - only if Elementor and ACF are active
		if (defined('ELEMENTOR_VERSION') && function_exists('acf_add_local_field_group')) {
			add_submenu_page(
				$this->dashboard_slug,
				esc_html__('Template Parts', 'vlt-helper'),
				esc_html__('Template Parts', 'vlt-helper'),
				'manage_options',
				'edit.php?post_type=vlt_tp',
				'',
			);
		}

		// Help Center
		add_submenu_page(
			$this->dashboard_slug,
			esc_html__('Help Center', 'vlt-helper'),
			esc_html__('Help Center', 'vlt-helper'),
			'manage_options',
			$this->dashboard_slug . '-helper',
			array($this, 'render_helper_page')
		);
	}

	/**
	 * Enqueue admin scripts
	 */
	public function enqueue_admin_scripts($hook)
	{
		// Only load on our dashboard pages
		if (strpos($hook, $this->dashboard_slug) === false) {
			return;
		}

		// Enqueue dashboard CSS
		wp_enqueue_style(
			'vlt-dashboard',
			$this->dashboard_url . 'css/dashboard.css',
			[],
			VLT_HELPER_VERSION
		);

		wp_enqueue_script('imagesloaded');
		wp_enqueue_script('masonry');

		wp_add_inline_script('masonry', '
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
		');
	}

	/**
	 * Render dashboard header
	 */
	private function render_header()
	{
		global $submenu;
		$menu_items = '';
		if (isset($submenu[$this->dashboard_slug])) {
			$menu_items = $submenu[$this->dashboard_slug];
		}
		if (! empty($menu_items)) :
?>

			<div class="vlt-theme-dashboard">

				<div class="vlt-theme-dashboard__navigation">

					<div class="nav-tab-wrapper">

						<?php foreach ($menu_items as $item) :
							// Skip Template Parts from navigation tabs
							if (strpos($item[2], 'edit.php?post_type=vlt_tp') !== false) {
								continue;
							}
							$class = isset($_GET['page']) && $_GET['page'] === $item[2] ? ' nav-tab-active' : '';
						?>
							<a href="<?php echo esc_url(admin_url('admin.php?page=' . $item[2])); ?>" class="nav-tab<?php echo esc_attr($class); ?>">
								<?php echo esc_html($item[0]); ?>
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
	private function render_footer()
	{
		echo '</div>';
	}

	/**
	 * Render template
	 *
	 * @param string $template Template name.
	 */
	private function render_template($template)
	{
		$template_file = $this->dashboard_path . 'templates/' . $template . '.php';

		echo '<div class="wrap">';
		echo '<h2>' . sprintf(esc_html__('%s Dashboard', 'vlt-helper'), esc_html($this->theme_name)) . '</h2>';

		$this->render_header();
		echo '<div class="vlt-theme-dashboard__content vlt-theme-dashboard--' . esc_attr($template) . '">';
		include $template_file;
		echo '</div>';
		$this->render_footer();
		echo '</div>';
	}

	/**
	 * Render welcome page
	 */
	public function render_welcome_page()
	{
		$this->render_template('template-welcome');
	}

	/**
	 * Render status page
	 */
	public function render_status_page()
	{
		$this->render_template('template-status');
	}

	/**
	 * Render requirements page
	 */
	public function render_requirements_page()
	{
		$this->render_template('template-requirements');
	}

	/**
	 * Render plugins page
	 */
	public function render_plugins_page()
	{
		$this->render_template('template-plugins');
	}

	/**
	 * Render demo import page
	 */
	public function render_demo_import_page()
	{
		$this->render_template('template-demo-import');
	}

	/**
	 * Render helper page
	 */
	public function render_helper_page()
	{
		$this->render_template('template-helper');
	}

	/**
	 * Render elementor page
	 */
	public function render_elementor_page()
	{
		$this->render_template('template-elementor');
	}

	/**
	 * Render theme options page
	 */
	public function render_theme_options_page()
	{
		$this->render_template('template-theme-options');
	}
}
