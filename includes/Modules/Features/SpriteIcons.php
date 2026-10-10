<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SVG Sprite Icons Module
 *
 * The theme supplies icons through the 'vlt_toolkit_sprite_icons' filter as [ id => '<svg viewBox="…">…</svg>' ]
 * and prints the sprite where it wants it with do_action( 'vlt_toolkit_render_sprite_icons' ), usually right after <body>.
 * Icons are then referenced as <svg><use href="#i-{id}"/></svg> — see get_icon().
 */
class SpriteIcons extends BaseModule {
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'sprite_icons';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Symbol id prefix
	 *
	 * @var string
	 */
	const PREFIX = 'i-';

	/**
	 * Parsed icons cache [ id => [ attrs, inner ] ]
	 *
	 * @var array|null
	 */
	private static $icons = null;

	/**
	 * Register module
	 */
	public function register() {
		add_action( 'vlt_toolkit_render_sprite_icons', [ __CLASS__, 'render_sprite' ] );
	}

	/**
	 * Parse icons supplied by the theme
	 *
	 * Read lazily: modules register on plugins_loaded, before the theme adds its filter.
	 *
	 * @return array [ id => [ 'attrs' => string, 'inner' => string ] ]
	 */
	private static function parse_icons() {
		if ( null !== self::$icons ) {
			return self::$icons;
		}

		self::$icons = [];

		foreach ( (array) apply_filters( 'vlt_toolkit_sprite_icons', [] ) as $id => $svg ) {
			if ( !preg_match( '/<svg\b([^>]*)>(.*)<\/svg>/s', (string) $svg, $match ) ) {
				continue;
			}

			self::$icons[ sanitize_key( $id ) ] = [
				// xmlns belongs to the outer <svg>, not to <symbol>
				'attrs' => trim( preg_replace( '/\s+xmlns(:\w+)?="[^"]*"/', '', $match[1] ) ),
				'inner' => trim( $match[2] ),
			];
		}

		return self::$icons;
	}

	/**
	 * Get all icons as standalone SVG markup (for icon pickers, previews)
	 *
	 * @return array [ id => '<svg …>…</svg>' ]
	 */
	public static function get_icons() {
		$icons = [];

		foreach ( self::parse_icons() as $id => $icon ) {
			$icons[ $id ] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $icon['attrs'] . '>' . $icon['inner'] . '</svg>';
		}

		return $icons;
	}

	/**
	 * Check if icon exists
	 *
	 * @param string $id Icon id without prefix
	 *
	 * @return bool
	 */
	public static function has_icon( $id ) {
		return isset( self::parse_icons()[ $id ] );
	}

	/**
	 * Get icon markup referencing the sprite
	 *
	 * @param string $id   Icon id without prefix
	 * @param array  $args {
	 *     @type string $class Extra CSS classes
	 *     @type string $label Accessible label; without it the icon is aria-hidden
	 * }
	 *
	 * @return string
	 */
	public static function get_icon( $id, $args = [] ) {
		$args = wp_parse_args( $args, [
			'class' => '',
			'label' => '',
		] );

		$a11y = $args['label']
			? 'role="img" aria-label="' . esc_attr( $args['label'] ) . '"'
			: 'aria-hidden="true"';

		return sprintf(
			'<svg class="%s" %s><use href="#%s"/></svg>',
			esc_attr( trim( 'vlt-icon ' . $args['class'] ) ),
			$a11y,
			esc_attr( self::PREFIX . $id )
		);
	}

	/**
	 * Print the sprite
	 */
	public static function render_sprite() {
		$icons = self::parse_icons();

		if ( !$icons ) {
			return;
		}

		echo '<svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" style="position: absolute; width: 0; height: 0; overflow: hidden">';

		foreach ( $icons as $id => $icon ) {
			// Markup comes from the theme's own filter (trusted code, like a template part), so it is printed as-is
			echo '<symbol id="' . esc_attr( self::PREFIX . $id ) . '" ' . $icon['attrs'] . '>' . $icon['inner'] . '</symbol>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '</svg>';
	}
}
