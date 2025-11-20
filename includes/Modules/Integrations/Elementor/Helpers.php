<?php

namespace VLT\Toolkit\Modules\Integrations\Elementor;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Elementor Helpers
 *
 * Static helper methods for Elementor widgets
 */
class Helpers {
	/**
	 * Populate post names by post type for dropdowns
	 *
	 * Returns an array of posts suitable for populating select fields in Elementor widgets,
	 * ACF fields, or theme options. Each post is returned as ID => Title.
	 *
	 * @param string $post_type Post type slug. Default 'post'.
	 *
	 * @return array Array of posts in format [ID => post_title]. Empty array if no posts found.
	 */
	public static function populate_post_name( $post_type = 'post' ) {
		$options = [];

		$all_post = [
			'posts_per_page' => -1,
			'post_type'      => $post_type,
		];

		$post_terms = get_posts( $all_post );

		if ( !empty( $post_terms ) && !is_wp_error( $post_terms ) ) {
			foreach ( $post_terms as $term ) {
				$options[ $term->ID ] = $term->post_title;
			}
		}

		return $options;
	}

	/**
	 * Populate post types for dropdowns
	 *
	 * Returns an array of available post types suitable for populating select fields.
	 * Only includes post types that are shown in navigation menus.
	 *
	 * @param array $args Optional. Arguments for filtering post types. Default empty array.
	 *                    - 'post_type' (string) Filter by specific post type name.
	 *
	 * @return array Array of post types in format [post_type => label]. Empty array if none found.
	 */
	public static function populate_post_types( $args = [] ) {
		$post_type_args = [
			'show_in_nav_menus' => true,
		];

		if ( !empty( $args['post_type'] ) ) {
			$post_type_args['name'] = $args['post_type'];
		}

		$_post_types = get_post_types( $post_type_args, 'objects' );

		$post_types = [];
		foreach ( $_post_types as $post_type => $object ) {
			$post_types[ $post_type ] = $object->label;
		}

		return $post_types;
	}

	/**
	 * Populate all registered sidebars for dropdowns
	 *
	 * Returns an array of all registered WordPress sidebars suitable for populating
	 * select fields. Includes a default "Choose Sidebar" or "No sidebars" option.
	 *
	 * @return array Array of sidebars in format [sidebar_id => name].
	 *               Includes default option at index ''.
	 */
	public static function populate_all_sidebars() {
		global $wp_registered_sidebars;

		$options = [];

		if ( !$wp_registered_sidebars ) {
			$options[''] = esc_html__( 'No sidebars were found', 'toolkit' );
		} else {
			$options[''] = esc_html__( 'Choose Sidebar', 'toolkit' );

			foreach ( $wp_registered_sidebars as $sidebar_id => $sidebar ) {
				$options[ $sidebar_id ] = $sidebar['name'];
			}
		}

		return $options;
	}

	/**
	 * Populate all published posts from any post type for dropdowns
	 *
	 * Returns an array of all published posts regardless of post type,
	 * suitable for populating select fields when you need cross-post-type selection.
	 *
	 * @return array Array of posts in format [ID => post_title]. Empty array if no posts found.
	 */
	public static function populate_all_types_post() {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_style'     => 'all_types',
				'post_status'    => 'publish',
				'posts_per_page' => '-1',
			],
		);

		if ( !empty( $posts ) ) {
			return wp_list_pluck( $posts, 'post_title', 'ID' );
		}

		return [];
	}

	/**
	 * Populate post categories for dropdowns
	 *
	 * Returns an array of category terms from the 'category' taxonomy suitable for
	 * populating select fields. Only includes non-empty categories.
	 *
	 * @param string $type Type of value to use as array key. Default 'term_id'.
	 *                     Accepts 'term_id', 'slug', 'name', or any term object property.
	 *
	 * @return array Array of categories in format [$type => name]. Empty array if none found.
	 */
	public static function populate_post_type_categories( $type = 'term_id' ) {
		$options = [];

		$terms = get_terms(
			[
				'taxonomy'   => 'category',
				'hide_empty' => true,
			],
		);

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->{$type} ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Populate taxonomy terms for dropdowns
	 *
	 * Returns an array of terms from a specified taxonomy suitable for populating
	 * select fields. Only includes non-empty terms. Uses slug as array key.
	 *
	 * @param string $taxonomy Taxonomy slug. Default 'category'.
	 *                         Accepts 'category', 'post_tag', or any registered taxonomy.
	 *
	 * @return array Array of terms in format [slug => name]. Empty array if none found.
	 */
	public static function populate_taxonomies( $taxonomy = 'category' ) {
		$options = [];

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'hide_empty' => true,
			],
		);

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Populate available navigation menus for dropdowns
	 *
	 * Returns an array of all registered WordPress navigation menus suitable for
	 * populating select fields.
	 *
	 * @return array Array of menus in format [slug => name]. Empty array if no menus registered.
	 */
	public static function populate_available_menus() {
		$options = [];
		$menus   = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			$options[ $menu->slug ] = $menu->name;
		}

		return $options;
	}

	/**
	 * Populate Elementor templates for dropdowns
	 *
	 * Returns an array of Elementor library templates suitable for populating select fields.
	 * Can be filtered by template type. Includes default "Select a Template" or
	 * "Create a Template First" option at index 0.
	 *
	 * @param string|null $type Optional. Template type to filter by. Default null (all types).
	 *                          Accepts 'page', 'section', 'widget', 'container', etc.
	 *
	 * @return array Array of templates in format [ID => post_title]. Includes default option at index 0.
	 */
	public static function populate_elementor_templates( $type = null ) {
		$args = [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
		];

		if ( $type ) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => $type,
				],
			];
		}

		$page_templates = get_posts( $args );

		$options = [];

		if ( !empty( $page_templates ) && !is_wp_error( $page_templates ) ) {
			foreach ( $page_templates as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		return $options;
	}
}
