<?php

namespace VLT\Helper\Modules\Integrations\Elementor;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Elementor Helpers
 *
 * Static helper methods for Elementor widgets
 */
class Helpers
{

	/**
	 * Get post names by post type
	 *
	 * @param string $post_type Post type.
	 * @return array Posts list.
	 */
	public static function get_post_name($post_type = 'post')
	{
		$options = [];

		$all_post = [
			'posts_per_page' => -1,
			'post_type'      => $post_type,
		];

		$post_terms = get_posts($all_post);

		if (! empty($post_terms) && ! is_wp_error($post_terms)) {
			foreach ($post_terms as $term) {
				$options[$term->ID] = $term->post_title;
			}
		}

		return $options;
	}

	/**
	 * Get post types
	 *
	 * @param array $args Arguments.
	 * @return array Post types list.
	 */
	public static function get_post_types($args = [])
	{
		$post_type_args = [
			'show_in_nav_menus' => true,
		];

		if (! empty($args['post_type'])) {
			$post_type_args['name'] = $args['post_type'];
		}

		$_post_types = get_post_types($post_type_args, 'objects');

		$post_types = [];
		foreach ($_post_types as $post_type => $object) {
			$post_types[$post_type] = $object->label;
		}

		return $post_types;
	}

	/**
	 * Get all sidebars
	 *
	 * @return array Sidebars list.
	 */
	public static function get_all_sidebars()
	{
		global $wp_registered_sidebars;

		$options = [];

		if (! $wp_registered_sidebars) {
			$options[''] = esc_html__('No sidebars were found', 'vlthemes-toolkit');
		} else {
			$options[''] = esc_html__('Choose Sidebar', 'vlthemes-toolkit');

			foreach ($wp_registered_sidebars as $sidebar_id => $sidebar) {
				$options[$sidebar_id] = $sidebar['name'];
			}
		}

		return $options;
	}

	/**
	 * Get all types of posts
	 *
	 * @return array Posts list.
	 */
	public static function get_all_types_post()
	{
		$posts = get_posts([
			'post_type'      => 'any',
			'post_style'     => 'all_types',
			'post_status'    => 'publish',
			'posts_per_page' => '-1',
		]);

		if (! empty($posts)) {
			return wp_list_pluck($posts, 'post_title', 'ID');
		}

		return [];
	}

	/**
	 * Get post type categories
	 *
	 * @param string $type Type of value to return (term_id, slug, etc).
	 * @return array Categories list.
	 */
	public static function get_post_type_categories($type = 'term_id')
	{
		$options = [];

		$terms = get_terms([
			'taxonomy'   => 'category',
			'hide_empty' => true,
		]);

		if (! empty($terms) && ! is_wp_error($terms)) {
			foreach ($terms as $term) {
				$options[$term->{$type}] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get taxonomies
	 *
	 * @param string $taxonomy Taxonomy name.
	 * @return array Taxonomies list.
	 */
	public static function get_taxonomies($taxonomy = 'category')
	{
		$options = [];

		$terms = get_terms([
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		]);

		if (! empty($terms) && ! is_wp_error($terms)) {
			foreach ($terms as $term) {
				$options[$term->slug] = $term->name;
			}
		}

		return $options;
	}

	/**
	 * Get available menus
	 *
	 * @return array Menus list.
	 */
	public static function get_available_menus()
	{
		$options = [];
		$menus   = wp_get_nav_menus();

		foreach ($menus as $menu) {
			$options[$menu->slug] = $menu->name;
		}

		return $options;
	}

	/**
	 * Get Elementor templates
	 *
	 * @param string|null $type Template type.
	 * @return array Templates list.
	 */
	public static function get_elementor_templates($type = null)
	{
		$args = [
			'post_type'      => 'elementor_library',
			'posts_per_page' => -1,
		];

		if ($type) {
			$args['tax_query'] = [
				[
					'taxonomy' => 'elementor_library_type',
					'field'    => 'slug',
					'terms'    => $type,
				],
			];
		}

		$page_templates = get_posts($args);

		$options[0] = esc_html__('Select a Template', 'vlthemes-toolkit');

		if (! empty($page_templates) && ! is_wp_error($page_templates)) {
			foreach ($page_templates as $post) {
				$options[$post->ID] = $post->post_title;
			}
		} else {
			$options[0] = esc_html__('Create a Template First', 'vlthemes-toolkit');
		}

		return $options;
	}
}
