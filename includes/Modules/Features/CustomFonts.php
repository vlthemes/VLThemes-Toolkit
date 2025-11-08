<?php

namespace VLT\Helper\Modules\Features;

use VLT\Helper\Modules\BaseModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom Fonts Module
 *
 * Integrates custom fonts with Kirki Customizer Framework and Elementor
 * Adds support for Custom Fonts, TypeKit fonts and theme fonts
 */
class CustomFonts extends BaseModule {

	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'custom_fonts';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register() {
		add_action( 'init', [ $this, 'prepare_custom_fonts' ] );

		// Fonts list filters
		add_filter( 'vlt_helper_fonts_list', [ $this, 'add_custom_fonts' ], 20 );
		add_filter( 'vlt_helper_fonts_list', [ $this, 'add_typekit_fonts' ], 20 );
		add_filter( 'vlt_helper_fonts_list', [ $this, 'add_theme_fonts' ], 20 );

		// Kirki support
		add_filter( 'kirki/fonts/standard_fonts', [ $this, 'add_fonts_to_kirki' ], 20 );

		// Elementor support
		add_filter( 'elementor/fonts/groups', [ $this, 'add_elementor_font_groups' ] );
		add_filter( 'elementor/fonts/additional_fonts', [ $this, 'add_elementor_fonts' ] );
	}

	/**
	 * Prepare custom fonts from Bsf Custom Fonts plugin
	 */
	public function prepare_custom_fonts() {
		// Check if Bsf Custom Fonts plugin is active
		if ( ! class_exists( 'Bsf_Custom_Fonts_Render' ) ) {
			return;
		}

		$fonts        = \Bsf_Custom_Fonts_Render::get_instance()->get_existing_font_posts();
		$custom_fonts = [];

		if ( ! empty( $fonts ) ) {
			foreach ( $fonts as $post_id ) {
				$font_family_name                   = get_the_title( $post_id );
				$custom_fonts[ $font_family_name ] = $font_family_name;
			}
		}

		update_option( 'vlt-helper-custom-fonts', $custom_fonts );
	}

	/**
	 * Normalize font variants for Kirki
	 * Converts '400' to 'regular', adds italic variants if needed
	 *
	 * @param array $variants Font variants.
	 * @return array Normalized variants.
	 */
	protected function normalize_variants( $variants ) {
		if ( empty( $variants ) ) {
			return [ 'regular' ];
		}

		$normalized = [];

		foreach ( $variants as $variant ) {
			// Convert 400 to regular
			if ( $variant === '400' || $variant === 400 ) {
				$normalized[] = 'regular';
			} else {
				$normalized[] = (string) $variant;
			}
		}

		return array_unique( $normalized );
	}

	/**
	 * Add custom fonts to fonts list
	 *
	 * @param array $fonts Existing fonts.
	 * @return array Modified fonts list.
	 */
	public function add_custom_fonts( $fonts ) {
		$custom_fonts = get_option( 'vlt-helper-custom-fonts', [] );

		if ( empty( $custom_fonts ) ) {
			return $fonts;
		}

		// Initialize arrays if not exists
		if ( ! isset( $fonts['families'] ) ) {
			$fonts['families'] = [];
		}

		if ( ! isset( $fonts['variants'] ) ) {
			$fonts['variants'] = [];
		}

		// Add custom fonts group
		$fonts['families']['custom_fonts'] = [
			'text'     => esc_html__( 'Custom Fonts', 'vlt-helper' ),
			'children' => [],
		];

		// Add each custom font
		foreach ( $custom_fonts as $font => $key ) {
			$fonts['families']['custom_fonts']['children'][] = [
				'id'   => $font,
				'text' => $font,
			];

			// Add all font weights
			$fonts['variants'][ $font ] = $this->normalize_variants( [ '100', '200', '300', '400', '500', '600', '700', '800', '900' ] );
		}

		return $fonts;
	}

	/**
	 * Add TypeKit fonts to fonts list
	 *
	 * @param array $fonts Existing fonts.
	 * @return array Modified fonts list.
	 */
	public function add_typekit_fonts( $fonts ) {
		$typekit_option = get_option( 'custom-typekit-fonts', [] );
		$typekit_fonts  = isset( $typekit_option['custom-typekit-font-details'] ) ? $typekit_option['custom-typekit-font-details'] : [];

		if ( empty( $typekit_fonts ) ) {
			return $fonts;
		}

		// Initialize arrays if not exists
		if ( ! isset( $fonts['families'] ) ) {
			$fonts['families'] = [];
		}
		if ( ! isset( $fonts['variants'] ) ) {
			$fonts['variants'] = [];
		}

		// Add TypeKit fonts group
		$fonts['families']['typekit_fonts'] = [
			'text'     => esc_html__( 'Adobe Fonts', 'vlt-helper' ),
			'children' => [],
		];

		// Add each TypeKit font
		foreach ( $typekit_fonts as $font ) {
			$font_id = $font['slug'];

			$fonts['families']['typekit_fonts']['children'][] = [
				'id'   => $font['slug'],
				'text' => $font['family'],
			];

			// Add font weights
			$weights = isset( $font['weights'] ) ? $font['weights'] : [ 'regular' ];
			$fonts['variants'][ $font_id ] = $this->normalize_variants( $weights );
		}

		return $fonts;
	}

	/**
	 * Add theme fonts via filter
	 *
	 * @param array $fonts Existing fonts.
	 * @return array Modified fonts list.
	 */
	public function add_theme_fonts( $fonts ) {
		// Get theme fonts from filter
		$theme_fonts = apply_filters( 'vlt_helper_register_custom_fonts', [] );

		if ( empty( $theme_fonts ) ) {
			return $fonts;
		}

		// Initialize arrays if not exists
		if ( ! isset( $fonts['families'] ) ) {
			$fonts['families'] = [];
		}
		if ( ! isset( $fonts['variants'] ) ) {
			$fonts['variants'] = [];
		}

		// Group fonts by category
		$categories = [];
		foreach ( $theme_fonts as $font_id => $font_data ) {
			$category = isset( $font_data['category'] ) ? $font_data['category'] : 'theme_fonts';
			$category_label = isset( $font_data['category_label'] ) ? $font_data['category_label'] : esc_html__( 'Theme Fonts', 'vlt-helper' );

			if ( ! isset( $categories[ $category ] ) ) {
				$categories[ $category ] = [
					'label' => $category_label,
					'fonts' => []
				];
			}

			$categories[ $category ]['fonts'][ $font_id ] = $font_data;
		}

		// Add each category
		foreach ( $categories as $category_id => $category_data ) {
			$fonts['families'][ $category_id ] = [
				'text'     => $category_data['label'],
				'children' => [],
			];

			foreach ( $category_data['fonts'] as $font_id => $font_data ) {
				$fonts['families'][ $category_id ]['children'][] = [
					'id'   => $font_id,
					'text' => isset( $font_data['label'] ) ? $font_data['label'] : $font_id,
				];

				// Add font variants with normalization
				$variants = isset( $font_data['variants'] ) ? $font_data['variants'] : [ '400', '700' ];
				$fonts['variants'][ $font_id ] = $this->normalize_variants( $variants );
			}
		}

		return $fonts;
	}

	/**
	 * Add fonts to Kirki standard fonts
	 *
	 * @param array $kirki_fonts Existing Kirki fonts.
	 * @return array Modified fonts list.
	 */
	public function add_fonts_to_kirki( $kirki_fonts ) {
		$fonts_list = apply_filters( 'vlt_helper_fonts_list', [] );

		if ( empty( $fonts_list['variants'] ) ) {
			return $kirki_fonts;
		}

		// Add all custom fonts to Kirki
		foreach ( $fonts_list['variants'] as $font_id => $variants ) {
			$kirki_fonts[ $font_id ] = [
				'label'    => $font_id,
				'variants' => $variants,
				'stack'    => '"' . $font_id . '", sans-serif',
			];
		}

		return $kirki_fonts;
	}

	/**
	 * Add font groups to Elementor
	 *
	 * @param array $font_groups Existing font groups.
	 * @return array Modified font groups.
	 */
	public function add_elementor_font_groups( $font_groups ) {
		$fonts_list = apply_filters( 'vlt_helper_fonts_list', [] );

		if ( empty( $fonts_list['families'] ) ) {
			return $font_groups;
		}

		// Add each font category as Elementor group
		foreach ( $fonts_list['families'] as $category_id => $category_data ) {
			$font_groups[ $category_id ] = $category_data['text'];
		}

		return $font_groups;
	}

	/**
	 * Add fonts to Elementor
	 *
	 * @param array $additional_fonts Existing fonts.
	 * @return array Modified fonts.
	 */
	public function add_elementor_fonts( $additional_fonts ) {
		$fonts_list = apply_filters( 'vlt_helper_fonts_list', [] );

		if ( empty( $fonts_list['families'] ) ) {
			return $additional_fonts;
		}

		// Add fonts to their categories
		foreach ( $fonts_list['families'] as $category_id => $category_data ) {
			if ( ! empty( $category_data['children'] ) ) {
				foreach ( $category_data['children'] as $font ) {
					$additional_fonts[ $font['id'] ] = $category_id;
				}
			}
		}

		return $additional_fonts;
	}
}