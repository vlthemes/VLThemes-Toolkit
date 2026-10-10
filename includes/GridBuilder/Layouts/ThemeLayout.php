<?php

namespace VLT\Toolkit\GridBuilder\Layouts;

use VLT\Toolkit\GridBuilder\GridBuilder;
use VLT\Toolkit\GridBuilder\SkinManager;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Layout type defined by the theme — a folder like the skins:
 *
 *     your-theme/vlthemes-toolkit/grid-builder/layouts/{Name}/layout.php   (required)
 *     your-theme/vlthemes-toolkit/grid-builder/layouts/{Name}/style.css    (optional)
 *     your-theme/vlthemes-toolkit/grid-builder/layouts/{Name}/script.js    (optional)
 *
 * layout.php has a "Name:" / "Description:" header and returns its definition:
 *
 *     return [
 *         'settings' => [
 *             'row_height' => [ 'type' => 'number', 'label' => 'Row height', 'default' => 240, 'min' => 80, 'max' => 600, 'unit' => 'px' ],
 *             'last_row'   => [ 'type' => 'select', 'label' => 'Last row', 'default' => 'left', 'options' => [ 'left' => 'Left', 'justify' => 'Justify' ] ],
 *             'captions'   => [ 'type' => 'toggle', 'label' => 'Captions on hover', 'default' => true ],
 *         ],
 *         'columns'      => false,                                   // optional: the type doesn't use columns (default true)
 *         'item_classes' => fn( $index, $config ) => [],           // optional
 *         'css'          => fn( $config, $scope, $breakpoints ) => '', // optional, extra CSS
 *     ];
 *
 * Setting types: number (min, max, step, unit), select (options), toggle, text. "help" adds a note.
 * 'responsive' => true stores one value per device (desktop / tablet / mobile), edited under the device switch;
 * the default may be one value or [ 'desktop' => …, 'tablet' => …, 'mobile' => … ]. Like columns and gaps,
 * tablet and mobile need an activated theme — without it every device uses the desktop value.
 * The editor shows the settings under Layout; columns and gaps per device stay shared (--vlt-grid-cols, --vlt-grid-gap-x/y).
 * A type that places items by something else (Justified: row height and image ratio) sets 'columns' => false:
 * the editor then offers gaps only.
 *
 * Each setting reaches the CSS as --vlt-gb-{key} on the grid (number with its unit, toggle 1 / 0, select and
 * text as the value) and the class .vlt-gb--layout-{id} scopes style.css. script.js gets the values from the
 * root's data-vlt-grid JSON (layoutSettings) and re-runs on the "vlt-grid:init" / "vlt-grid:updated" events.
 */
class ThemeLayout implements LayoutInterface {
	const TYPES   = [ 'number', 'select', 'toggle', 'text' ];
	const DEVICES = [ 'desktop', 'tablet', 'mobile' ];

	/**
	 * ID
	 *
	 * @var string
	 */
	private $id;

	/**
	 * Label
	 *
	 * @var string
	 */
	private $label;

	/**
	 * Description
	 *
	 * @var string
	 */
	private $description;

	/**
	 * Definition returned by layout.php
	 *
	 * @var array
	 */
	private $definition;

	/**
	 * Constructor
	 *
	 * @param array $entry SkinManager entry of the "layouts" kind
	 */
	public function __construct( array $entry ) {
		$this->id          = $entry['id'];
		$this->label       = $entry['label'];
		$this->description = $entry['description'];

		$file       = SkinManager::locate( $this->id, 'template', 'layouts' );
		$definition = $file ? ( static fn() => include $file[0] )() : [];

		$this->definition = is_array( $definition ) ? $definition : [];
	}

	/**
	 * Get ID
	 *
	 * @return string
	 */
	public function get_id() {
		return $this->id;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Get description
	 *
	 * @return string
	 */
	public function get_description() {
		return $this->description;
	}

	/**
	 * Whether the type lays items out in columns (the editor's Columns field)
	 *
	 * @return bool
	 */
	public function uses_columns() {
		return false !== ( $this->definition['columns'] ?? true );
	}

	/**
	 * Valid settings of the definition
	 *
	 * @return array [ key => normalised setting ]
	 */
	public function settings() {
		$settings = [];

		foreach ( (array) ( $this->definition['settings'] ?? [] ) as $key => $setting ) {
			$key = sanitize_key( $key );

			if ( !$key || !is_array( $setting ) || !in_array( $setting['type'] ?? '', self::TYPES, true ) ) {
				continue;
			}

			$type    = $setting['type'];
			$options = 'select' === $type ? array_map( 'strval', (array) ( $setting['options'] ?? [] ) ) : [];

			if ( 'select' === $type && !$options ) {
				continue;
			}

			$normalised = [
				'type'       => $type,
				'label'      => (string) ( $setting['label'] ?? $key ),
				'help'       => (string) ( $setting['help'] ?? '' ),
				'responsive' => !empty( $setting['responsive'] ),
				'min'        => isset( $setting['min'] ) ? (float) $setting['min'] : null,
				'max'        => isset( $setting['max'] ) ? (float) $setting['max'] : null,
				'step'       => isset( $setting['step'] ) ? (float) $setting['step'] : 1,
				'unit'       => preg_replace( '/[^a-z%]/', '', strtolower( (string) ( $setting['unit'] ?? '' ) ) ),
				'options'    => $options,
			];

			// The type's own fallback: number → min (or 0), select → first option
			$fallback = match ( $type ) {
				'number' => $normalised['min'] ?? 0.0,
				'toggle' => false,
				'select' => (string) array_key_first( $options ),
				default  => '',
			};

			$default = $setting['default'] ?? null;

			if ( $normalised['responsive'] ) {
				$desktop = self::clean( $normalised, is_array( $default ) ? ( $default['desktop'] ?? null ) : $default, $fallback );
				$default = [];

				foreach ( self::DEVICES as $device ) {
					$default[ $device ] = self::clean( $normalised, is_array( $setting['default'] ?? null ) ? ( $setting['default'][ $device ] ?? null ) : null, $desktop );
				}
			} else {
				$default = self::clean( $normalised, $default, $fallback );
			}

			$settings[ $key ] = [ 'default' => $default ] + $normalised;
		}

		return $settings;
	}

	/**
	 * Default settings
	 *
	 * @return array
	 */
	public function defaults() {
		return wp_list_pluck( $this->settings(), 'default' );
	}

	/**
	 * Validate settings
	 *
	 * @param array $settings Raw settings
	 *
	 * @return array
	 */
	public function sanitize( array $settings ) {
		$clean = [];

		foreach ( $this->settings() as $key => $setting ) {
			$value = $settings[ $key ] ?? null;

			if ( !$setting['responsive'] ) {
				$clean[ $key ] = self::clean( $setting, $value, $setting['default'] );
				continue;
			}

			// One value (older configs, or a type that became responsive) applies to every device
			foreach ( self::DEVICES as $device ) {
				$clean[ $key ][ $device ] = self::clean( $setting, is_array( $value ) ? ( $value[ $device ] ?? null ) : $value, $setting['default'][ $device ] );
			}
		}

		return $clean;
	}

	/**
	 * One validated value of a setting
	 *
	 * @param array $setting  Normalised setting
	 * @param mixed $value    Raw value (null = missing)
	 * @param mixed $fallback Used when the value is missing or invalid
	 *
	 * @return mixed
	 */
	private static function clean( array $setting, $value, $fallback ) {
		if ( null === $value ) {
			return $fallback;
		}

		return match ( $setting['type'] ) {
			'number' => is_numeric( $value ) ? min( $setting['max'] ?? PHP_FLOAT_MAX, max( $setting['min'] ?? -PHP_FLOAT_MAX, (float) $value ) ) : $fallback,
			'toggle' => (bool) $value,
			'select' => isset( $setting['options'][ (string) $value ] ) ? (string) $value : $fallback,
			default  => mb_substr( sanitize_text_field( (string) $value ), 0, 200 ),
		};
	}

	/**
	 * Values actually used: responsive settings per device, tablet / mobile = desktop without an activated theme
	 *
	 * @param array $config Layout config
	 *
	 * @return array [ key => value | [ desktop, tablet, mobile ] ]
	 */
	public function values( array $config ) {
		$values    = $this->sanitize( (array) ( $config['layout'][ $this->id ] ?? [] ) );
		$activated = GridBuilder::is_activated();

		foreach ( $this->settings() as $key => $setting ) {
			if ( $setting['responsive'] && !$activated ) {
				$values[ $key ]['tablet'] = $values[ $key ]['desktop'];
				$values[ $key ]['mobile'] = $values[ $key ]['desktop'];
			}
		}

		return $values;
	}

	/**
	 * Extra classes of the item at a position
	 *
	 * @param int   $index  Position
	 * @param array $config Layout config
	 *
	 * @return array
	 */
	public function item_classes( $index, array $config ) {
		$callback = $this->definition['item_classes'] ?? null;

		return is_callable( $callback ) ? array_map( 'sanitize_html_class', (array) $callback( $index, $config ) ) : [];
	}

	/**
	 * Settings as CSS variables, then the definition's own CSS
	 *
	 * @param array  $config      Layout config
	 * @param string $scope       Scope selector
	 * @param array  $breakpoints Breakpoints
	 *
	 * @return string
	 */
	public function css( array $config, $scope, array $breakpoints ) {
		$values = $this->values( $config );
		$rules  = array_fill_keys( self::DEVICES, '' );

		foreach ( $this->settings() as $key => $setting ) {
			$name = '--vlt-gb-' . str_replace( '_', '-', $key ) . ':';

			if ( !$setting['responsive'] ) {
				$rules['desktop'] .= $name . self::css_value( $setting, $values[ $key ] ) . ';';
				continue;
			}

			// Tablet / mobile only where they differ from the wider device
			$wider = null;

			foreach ( self::DEVICES as $device ) {
				$value = self::css_value( $setting, $values[ $key ][ $device ] );

				if ( $value !== $wider ) {
					$rules[ $device ] .= $name . $value . ';';
				}

				$wider = $value;
			}
		}

		$css = $rules['desktop'] ? $scope . '{' . $rules['desktop'] . '}' : '';

		foreach ( [ 'tablet', 'mobile' ] as $device ) {
			if ( $rules[ $device ] && !empty( $breakpoints[ $device ] ) ) {
				$css .= '@media (max-width:' . (int) $breakpoints[ $device ] . 'px){' . $scope . '{' . $rules[ $device ] . '}}';
			}
		}

		$callback = $this->definition['css'] ?? null;

		return $css . ( is_callable( $callback ) ? (string) $callback( $config, $scope, $breakpoints ) : '' );
	}

	/**
	 * A value as CSS: number with its unit, toggle 1 / 0, text that can't break out of the declaration
	 *
	 * @param array $setting Normalised setting
	 * @param mixed $value   Value
	 *
	 * @return string
	 */
	private static function css_value( array $setting, $value ) {
		return match ( $setting['type'] ) {
			'number' => ( floor( $value ) == $value ? (int) $value : $value ) . $setting['unit'], // phpcs:ignore Universal.Operators.StrictComparisons
			'toggle' => $value ? '1' : '0',
			default  => preg_replace( '/[^\w\s#%.,()+-]/u', '', (string) $value ),
		};
	}
}
