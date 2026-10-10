<?php

namespace VLT\Toolkit\GridBuilder;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Entrance animations of grid items — pure CSS
 *
 * Every item gets a CSS animation; the browser runs it whenever the item is inserted, so it plays on page
 * load, after a filter (items are replaced) and for items added by "Load More" / pagination — no script.
 * The stagger uses the item's position in its own batch (--vlt-i), so loaded items don't wait behind earlier ones.
 * Reduced-motion users get no animation.
 *
 * Presets come from the 'vlt_toolkit_grid_animations' filter. A theme adds one by its start state
 * (keyframes are generated) or by the name of @keyframes from its own CSS:
 *
 *     add_filter( 'vlt_toolkit_grid_animations', fn( $presets ) => $presets + [
 *         'flip' => [ 'label' => 'Flip', 'from' => 'opacity:0;transform:perspective(600px) rotateX(-30deg)' ],
 *         'pop'  => [ 'label' => 'Pop', 'keyframes' => 'my-theme-pop' ],
 *     ] );
 */
class Animations {
	/**
	 * Presets cache
	 *
	 * @var array|null
	 */
	private static $presets = null;

	/**
	 * Available presets
	 *
	 * @return array [ key => [ 'label', 'from' ] | [ 'label', 'keyframes' ] ]
	 */
	public static function presets() {
		if ( null !== self::$presets ) {
			return self::$presets;
		}

		$presets = (array) apply_filters( 'vlt_toolkit_grid_animations', [
			'fade'        => [
				'label' => __( 'Fade', 'toolkit' ),
				'from'  => 'opacity:0',
			],
			'fade-up'     => [
				'label' => __( 'Fade up', 'toolkit' ),
				'from'  => 'opacity:0;transform:translateY(24px)',
			],
			'fade-down'   => [
				'label' => __( 'Fade down', 'toolkit' ),
				'from'  => 'opacity:0;transform:translateY(-24px)',
			],
			'slide-left'  => [
				'label' => __( 'Slide from right', 'toolkit' ),
				'from'  => 'opacity:0;transform:translateX(32px)',
			],
			'slide-right' => [
				'label' => __( 'Slide from left', 'toolkit' ),
				'from'  => 'opacity:0;transform:translateX(-32px)',
			],
			'zoom-in'     => [
				'label' => __( 'Zoom in', 'toolkit' ),
				'from'  => 'opacity:0;transform:scale(.92)',
			],
			'blur'        => [
				'label' => __( 'Blur in', 'toolkit' ),
				'from'  => 'opacity:0;filter:blur(8px)',
			],
		] );

		self::$presets = [];

		foreach ( $presets as $key => $preset ) {
			$key = sanitize_key( $key );

			if ( !$key || 'none' === $key ) {
				continue;
			}

			// Declarations / names only: braces and markup can't break out of the generated rules
			if ( !empty( $preset['keyframes'] ) ) {
				$entry = [ 'keyframes' => preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $preset['keyframes'] ) ];
			} elseif ( !empty( $preset['from'] ) ) {
				$entry = [ 'from' => str_replace( [ '{', '}', '<', '>' ], '', (string) $preset['from'] ) ];
			} else {
				continue;
			}

			self::$presets[ $key ] = [ 'label' => (string) ( $preset['label'] ?? $key ) ] + $entry;
		}

		return self::$presets;
	}

	/**
	 * Animation CSS of a layout
	 *
	 * @param array  $config Layout config
	 * @param string $scope  Scope selector
	 *
	 * @return string
	 */
	public static function css( array $config, $scope ) {
		$animation = $config['animation'];
		$preset    = self::presets()[ $animation['effect'] ] ?? null;

		// Needs an activated theme; the setting is kept and applies once it is
		if ( !$preset || !GridBuilder::is_activated() ) {
			return '';
		}

		$name = $preset['keyframes'] ?? 'vlt-grid-' . $animation['effect'];
		$css  = isset( $preset['from'] ) ? sprintf( '@keyframes %s{from{%s}}', $name, $preset['from'] ) : '';

		$css .= sprintf(
			'%1$s .vlt-gb__item{animation:%2$s %3$dms cubic-bezier(.2,.8,.2,1) both;animation-delay:calc(var(--vlt-i,0) * %4$dms)}'
			. '@media (prefers-reduced-motion:reduce){%1$s .vlt-gb__item{animation:none}}',
			$scope,
			$name,
			$animation['duration'],
			$animation['stagger'],
		);

		return $css;
	}
}
