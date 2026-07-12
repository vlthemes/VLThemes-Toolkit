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

	/**
	 * Populate Elementor template types for dropdowns
	 *
	 * Returns an array of Elementor library template types suitable for populating select fields.
	 *
	 * @return array Array of template types in format [slug => name]. Empty array if none found.
	 */
	public static function populate_elementor_template_types() {
		$terms = get_terms(
			[
				'taxonomy'   => 'elementor_library_type',
				'hide_empty' => false,
			],
		);

		$options = [];

		if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
			foreach ( $terms as $term ) {
				$options[ $term->slug ] = ucfirst( $term->name );
			}
		}

		return $options;
	}

	/**
	 * Get VLThemes badge SVG
	 *
	 * Returns the VLThemes logo SVG for use in Elementor control labels
	 *
	 * @return string SVG markup
	 */
	public static function get_badge_svg() {
		return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 912 1019" style="margin-left: 5px; height: 1em; float: right;"><path fill="currentColor" d="M402.516 12.75c29.362-16.993 76.942-17.007 106.328 0l349.352 202.168c29.362 16.992 53.164 58.287 53.164 92.169v404.598c0 33.912-23.778 75.163-53.164 92.169L508.844 1006.02c-29.362 17-76.942 17.01-106.328 0L53.164 803.854C23.802 786.862 0 745.567 0 711.685V307.087c0-33.912 23.778-75.163 53.164-92.169L402.516 12.749Zm40.494 742.748-1.091 2.594-57.07-138.51-.017.041-115.211-279.689h-114.75l172.125 418.158H441.93l1.08-2.594Zm31.538-75.675 172.453-413.794-114.535.137-111.15 266.109 53.233 147.547-.001.001Zm73.75-4.412c4.767 23.66 14.546 41.762 29.337 54.306 21.826 18.511 52.818 27.766 93.233 27.766 40.415 0 77.812-5.517 77.812-5.517l-16.119-76.465s-23.119 3.039-41.457 1.664c-18.338-1.375-30.437-6.419-36.605-11.878-6.168-5.458-11.881-17.691-11.881-30.506V449.094l-94.32 226.317ZM677.86 364.532l-34.054 81.711h88.755v-81.711H677.86Z"/></svg>';
	}
}
