<?php
/**
 * Widget List Control
 *
 * Extended Select2 control for widget selection
 *
 * @package VLT\Toolkit
 */

namespace VLT\Toolkit\Modules\Integrations\Elementor\Controls;

use Elementor\Control_Select2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Widget List Control
 *
 * Custom control for selecting widgets
 */
class WidgetList extends Control_Select2 {

	/**
	 * Control type
	 */
	const TYPE = 'vlt-widget-list';

	/**
	 * Get control type
	 *
	 * @return string Control type
	 */
	public function get_type() {
		return self::TYPE;
	}

	/**
	 * Get default settings
	 *
	 * @return array Default settings
	 */
	protected function get_default_settings() {
		return array_merge(
			parent::get_default_settings(),
			[
				'multiple' => true,
				'options'  => [],
			]
		);
	}
}
