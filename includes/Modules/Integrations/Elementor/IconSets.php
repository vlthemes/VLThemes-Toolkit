<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Icon Sets Manager
 *
 * Manages custom icon sets for Elementor
 */
class IconSets {
	/**
	 * Assets URL
	 *
	 * @var string
	 */
	private $assets_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->assets_url = VLT_TOOLKIT_URL . 'assets/';
	}

	/**
	 * Add custom icon tabs
	 *
	 * @param array $settings icon settings
	 *
	 * @return array
	 */
	public function add_icon_tabs( $settings ) {
		$icon_sets = $this->get_icon_sets();

		foreach ( $icon_sets as $key => $icon_set ) {
			// Check if icon set files exist before adding
			$css_path = str_replace( VLT_TOOLKIT_URL, VLT_TOOLKIT_PATH, $icon_set['url'] );

			if ( file_exists( $css_path ) ) {
				$settings[ $key ] = $icon_set;
			}
		}

		return apply_filters( 'vlt_toolkit_elementor_icon_tabs', $settings );
	}

	/**
	 * Get icon sets configuration
	 *
	 * @return array
	 */
	private function get_icon_sets() {
		return [
			// Socicons
			'socicons' => [
				'name'          => 'socicons',
				'label'         => esc_html__( 'Socicons', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/socicons/socicons.css',
				'enqueue'       => false, // CSS loaded globally in SocialIcons module
				'prefix'        => 'socicon-',
				'displayPrefix' => false,
				'labelIcon'     => 'socicon-twitter',
				'fetchJson'     => $this->assets_url . 'fonts/socicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// ET-Line Icons
			'etline' => [
				'name'          => 'etline',
				'label'         => esc_html__( 'ET-Line', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/etline/etl.css',
				'enqueue'       => [ $this->assets_url . 'fonts/etline/etl.css' ],
				'prefix'        => 'etl-',
				'displayPrefix' => false,
				'labelIcon'     => 'etl-desktop',
				'fetchJson'     => $this->assets_url . 'fonts/etline/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// Icomoon
			'icomoon' => [
				'name'          => 'icomoon',
				'label'         => esc_html__( 'Icomoon', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/icomoon/icnm.css',
				'enqueue'       => [ $this->assets_url . 'fonts/icomoon/icnm.css' ],
				'prefix'        => 'icnm-',
				'displayPrefix' => false,
				'labelIcon'     => 'icnm-barcode',
				'fetchJson'     => $this->assets_url . 'fonts/icomoon/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// Iconsmind
			'iconsmind' => [
				'name'          => 'iconsmind',
				'label'         => esc_html__( 'Iconsmind', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/iconsmind/iconsmind.css',
				'enqueue'       => [ $this->assets_url . 'fonts/iconsmind/iconsmind.css' ],
				'prefix'        => 'icnmd-',
				'displayPrefix' => false,
				'labelIcon'     => 'icnmd-ATM',
				'fetchJson'     => $this->assets_url . 'fonts/iconsmind/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// Linearicons
			'linearicons' => [
				'name'          => 'linearicons',
				'label'         => esc_html__( 'Linearicons', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/linearicons/lnr.css',
				'enqueue'       => [ $this->assets_url . 'fonts/linearicons/lnr.css' ],
				'prefix'        => 'lnr-',
				'displayPrefix' => false,
				'labelIcon'     => 'lnr-book',
				'fetchJson'     => $this->assets_url . 'fonts/linearicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// Elusive Icons
			'elusiveicons' => [
				'name'          => 'elusiveicons',
				'label'         => esc_html__( 'Elusive Icons', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/elusiveicons/el.css',
				'enqueue'       => [ $this->assets_url . 'fonts/elusiveicons/el.css' ],
				'prefix'        => 'el-',
				'displayPrefix' => false,
				'labelIcon'     => 'el-address-book',
				'fetchJson'     => $this->assets_url . 'fonts/elusiveicons/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
			// Icofont
			'icofont' => [
				'name'          => 'icofont',
				'label'         => esc_html__( 'Icofont', 'toolkit' ),
				'url'           => $this->assets_url . 'fonts/icofont/icofont.css',
				'enqueue'       => [ $this->assets_url . 'fonts/icofont/icofont.css' ],
				'prefix'        => 'icofont-',
				'displayPrefix' => false,
				'labelIcon'     => 'icofont-cop',
				'fetchJson'     => $this->assets_url . 'fonts/icofont/elementor.json',
				'native'        => false,
				'ver'           => VLT_TOOLKIT_VERSION,
			],
		];
	}
}
