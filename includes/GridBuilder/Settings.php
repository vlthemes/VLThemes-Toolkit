<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Grid Builder settings (Portfolio → Settings, or Grid Layouts → Settings without the portfolio) and the Documentation link
 *
 * The page has tabs. Every section of sections() is a tab of number fields saved in one option (read with
 * Settings::get( 'section' ), validated by the field limits); a tab saves only its own section. Other tabs
 * come from modules, with their own content and forms:
 *
 *     add_filter( 'vlt_toolkit_grid_settings_tabs', fn( $tabs ) => $tabs + [ 'my' => [ 'label' => 'My', 'render' => 'my_render' ] ] );
 *
 * For now: Breakpoints, and Migration (Portfolio module). The vlt_toolkit_grid_breakpoints filter still has the last word.
 */
class Settings {
	const OPTION = 'vlt_toolkit_grid_settings';
	const PAGE   = 'vlt-grid-settings';
	const GROUP  = 'vlt_toolkit_grid_settings_group';

	/**
	 * Init
	 */
	public static function init() {
		add_action( 'admin_init', [ __CLASS__, 'register' ] );
		add_action( 'admin_menu', [ __CLASS__, 'menu' ], 30 );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue' ] );
	}

	/**
	 * Sections and their fields
	 *
	 * @return array [ section => [ 'tab', 'title', 'description', 'fields' => [ key => [ label, icon (Dashicon, optional), help, default, min, max, unit ] ] ] ]
	 */
	public static function sections() {
		return [
			'breakpoints' => [
				'tab'         => __( 'Breakpoints', 'toolkit' ),
				'title'       => __( 'Responsive Breakpoints', 'toolkit' ),
				'description' => __( 'Screen widths where grids switch to their tablet and mobile columns, gaps and tiles patterns.', 'toolkit' ),
				'fields'      => [
					'tablet' => [
						'label'   => __( 'Tablet', 'toolkit' ),
						'icon'    => 'tablet',
						'help'    => __( 'Tablet settings apply up to this width.', 'toolkit' ),
						'default' => 1024,
						'min'     => 480,
						'max'     => 2560,
						'unit'    => 'px',
					],
					'mobile' => [
						'label'   => __( 'Mobile', 'toolkit' ),
						'icon'    => 'smartphone',
						'help'    => __( 'Mobile settings apply up to this width; must be below the tablet one.', 'toolkit' ),
						'default' => 767,
						'min'     => 320,
						'max'     => 2559,
						'unit'    => 'px',
					],
				],
			],
		];
	}

	/**
	 * Values of a section (saved, or the defaults)
	 *
	 * @param string $section Section
	 *
	 * @return array
	 */
	public static function get( $section ) {
		$saved = get_option( self::OPTION, [] );

		return self::sanitize( is_array( $saved ) ? $saved : [] )[ $section ] ?? [];
	}

	/**
	 * Validate the whole option
	 *
	 * @param mixed $input Raw
	 *
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : [];
		$clean = [];
		$saved = null;

		foreach ( self::sections() as $section => $definition ) {
			// A tab sends only its own section: the others keep their saved values
			if ( !isset( $input[ $section ] ) ) {
				$saved ??= (array) get_option( self::OPTION, [] );
				$input[ $section ] = $saved[ $section ] ?? [];
			}

			foreach ( $definition['fields'] as $key => $field ) {
				$value = $input[ $section ][ $key ] ?? null;

				$clean[ $section ][ $key ] = is_numeric( $value ) ? max( $field['min'], min( $field['max'], (int) $value ) ) : $field['default'];
			}
		}

		// Mobile must stay below tablet
		if ( $clean['breakpoints']['mobile'] >= $clean['breakpoints']['tablet'] ) {
			$clean['breakpoints']['mobile'] = $clean['breakpoints']['tablet'] - 1;
		}

		return $clean;
	}

	/**
	 * Register the option
	 */
	public static function register() {
		register_setting( self::GROUP, self::OPTION, [
			'type'              => 'array',
			'sanitize_callback' => [ __CLASS__, 'sanitize' ],
			'default'           => [],
		] );
	}

	/**
	 * Documentation URL: the toolkit docs (https://vlthemes.me/docs/vlthemes-toolkit/)
	 *
	 * @return string
	 */
	public static function docs_url() {
		$site = apply_filters( 'vlt_toolkit_site_url', 'https://vlthemes.me/' );
		$docs = apply_filters( 'vlt_toolkit_docs_url', $site . 'docs/', get_template() );

		return (string) apply_filters( 'vlt_toolkit_grid_docs_url', trailingslashit( $docs ) . 'vlthemes-toolkit/' );
	}

	/**
	 * Menu: Settings and Documentation (external) under Portfolio
	 */
	public static function menu() {
		$parent = GridBuilder::menu_parent();

		add_submenu_page( $parent, __( 'Grid Builder Settings', 'toolkit' ), __( 'Settings', 'toolkit' ), 'manage_options', self::PAGE, [ __CLASS__, 'page' ] );

		// A full URL as the slug: WordPress prints it as the link
		add_submenu_page( $parent, __( 'Documentation', 'toolkit' ), __( 'Documentation', 'toolkit' ), 'edit_posts', self::docs_url() );
	}

	/**
	 * Dashboard styles on the settings page; the Documentation link opens in a new tab everywhere
	 *
	 * @param string $hook Admin page hook
	 */
	public static function enqueue( $hook ) {
		// The menu prints the URL without its trailing slash
		$url = untrailingslashit( self::docs_url() );

		wp_add_inline_script( 'common', 'document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("#adminmenu a[href^=\'' . esc_js( $url ) . '\']").forEach(function(a){a.target="_blank";a.rel="noopener";});});' );

		if ( false === strpos( $hook, self::PAGE ) ) {
			return;
		}

		$css = VLT_TOOLKIT_PATH . 'assets/css/admin-dashboard.css';

		if ( is_file( $css ) ) {
			wp_enqueue_style( 'vlt-dashboard', VLT_TOOLKIT_URL . 'assets/css/admin-dashboard.css', [], VLT_TOOLKIT_VERSION . '.' . filemtime( $css ) );
			wp_enqueue_style( 'vlt-admin-settings', VLT_TOOLKIT_URL . 'assets/css/admin-settings.css', [ 'vlt-dashboard' ], VLT_TOOLKIT_VERSION . '.' . filemtime( VLT_TOOLKIT_PATH . 'assets/css/admin-settings.css' ) );
		}
	}

	/**
	 * Tabs: the settings sections, then the ones modules add
	 *
	 * @return array [ id => [ 'label', 'render' => callable|null ] ] — null renders the section's fields
	 */
	public static function tabs() {
		$tabs = [];

		foreach ( self::sections() as $section => $definition ) {
			$tabs[ $section ] = [
				'label'  => $definition['tab'],
				'render' => null,
			];
		}

		$extra = (array) apply_filters( 'vlt_toolkit_grid_settings_tabs', [] );

		foreach ( $extra as $id => $tab ) {
			$id = sanitize_key( $id );

			if ( $id && !isset( $tabs[ $id ] ) && !empty( $tab['label'] ) && is_callable( $tab['render'] ?? null ) ) {
				$tabs[ $id ] = [
					'label'  => (string) $tab['label'],
					'render' => $tab['render'],
				];
			}
		}

		return $tabs;
	}

	/**
	 * Settings page (dashboard look: .vlt-theme-dashboard navigation tabs, cards and .vlt-form-group fields)
	 */
	public static function page() {
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		$tabs    = self::tabs();
		$current = sanitize_key( wp_unslash( $_GET['tab'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $tabs[ $current ] ) ? $current : (string) array_key_first( $tabs );
		// menu_page_url() returns an escaped URL (&#038;): decode before adding the tab
		$url     = html_entity_decode( menu_page_url( self::PAGE, false ) );
		?>
		<div class="wrap">
			<h2><?php esc_html_e( 'Grid Builder Settings', 'toolkit' ); ?></h2>
			<?php settings_errors(); ?>

			<div class="vlt-theme-dashboard vlt-grid-settings-page">
				<div class="vlt-theme-dashboard__navigation">
					<div class="nav-tab-wrapper">
						<?php foreach ( $tabs as $id => $tab ) : ?>
							<a href="<?php echo esc_url( add_query_arg( 'tab', $id, $url ) ); ?>" class="nav-tab<?php echo $id === $current ? ' nav-tab-active' : ''; ?>"<?php echo $id === $current ? ' aria-current="page"' : ''; ?>>
								<?php echo esc_html( $tab['label'] ); ?>
							</a>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="vlt-theme-dashboard__content vlt-grid-settings">
					<?php
					if ( $tabs[ $current ]['render'] ) {
						call_user_func( $tabs[ $current ]['render'] );
					} else {
						self::render_section( $current );
					}
					?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * A settings section: its fields and Save
	 *
	 * @param string $section Section
	 */
	private static function render_section( $section ) {
		$definition = self::sections()[ $section ];
		$values     = self::get( $section );
		?>
		<form method="post" action="options.php">
			<?php settings_fields( self::GROUP ); ?>

			<div class="vlt-widget">
				<div class="vlt-widget__title">
					<mark><?php echo esc_html( $definition['title'] ); ?></mark>
				</div>

				<div class="vlt-widget__content">
					<p><?php echo esc_html( $definition['description'] ); ?></p>

					<div class="vlt-grid-settings__fields">
						<?php
						foreach ( $definition['fields'] as $key => $field ) :
							$id = 'vlt-grid-' . $section . '-' . $key;
							?>
							<div class="vlt-form-group">
								<label class="vlt-grid-settings__label" for="<?php echo esc_attr( $id ); ?>">
									<?php if ( !empty( $field['icon'] ) ) : ?>
										<span class="dashicons dashicons-<?php echo esc_attr( $field['icon'] ); ?>" aria-hidden="true"></span>
									<?php endif; ?>
									<?php echo esc_html( $field['label'] . ( $field['unit'] ? ' (' . $field['unit'] . ')' : '' ) ); ?>
								</label>
								<input
									type="number"
									id="<?php echo esc_attr( $id ); ?>"
									name="<?php echo esc_attr( self::OPTION . '[' . $section . '][' . $key . ']' ); ?>"
									value="<?php echo esc_attr( $values[ $key ] ); ?>"
									min="<?php echo esc_attr( $field['min'] ); ?>"
									max="<?php echo esc_attr( $field['max'] ); ?>"
									placeholder="<?php echo esc_attr( $field['default'] ); ?>"
								>
								<p class="small"><?php echo esc_html( $field['help'] ); ?></p>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
			</div>

			<div class="vlt-grid-settings__actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'toolkit' ); ?></button>
				<a class="button" href="<?php echo esc_url( self::docs_url() ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Documentation', 'toolkit' ); ?></a>
			</div>
		</form>
		<?php
	}
}
