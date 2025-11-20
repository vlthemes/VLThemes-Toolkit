<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Breadcrumbs Module
 *
 * Provides breadcrumb navigation with Schema.org structured data
 */
class Breadcrumbs extends BaseModule
{
	/**
	 * Module name
	 *
	 * @var string
	 */
	protected $name = 'breadcrumbs';

	/**
	 * Module version
	 *
	 * @var string
	 */
	protected $version = '1.0.0';

	/**
	 * Register module
	 */
	public function register(): void
	{
	}

	/**
	 * Render breadcrumbs (HTML + optional JSON-LD)
	 *
	 * @param array $args Optional arguments to override defaults.
	 * @param bool  $echo Whether to echo or return the output.
	 *
	 * @return string|void Breadcrumbs HTML if $echo is false.
	 */
	public static function render($args = [], $echo = true)
	{
		$defaults = [
			'show_home'       => true,
			'show_on_home'    => false,
			'home_label'      => __('Home', 'toolkit'),
			'separator'       => '<span class="sep">-</span>',
			'active_class'    => 'current',
			'container_tag'   => 'nav',
			'container_class' => 'vlt-breadcrumbs',
			'show_schema'     => true,
		];

		$args = wp_parse_args($args, $defaults);

		/**
		 * Allow third-parties to change args.
		 *
		 * @param array $args Breadcrumb arguments.
		 */
		$args = apply_filters('vlt_toolkit_breadcrumbs_args', $args);

		// Don't show on front page unless explicitly allowed.
		if (is_front_page() && ! $args['show_on_home']) {
			return $echo ? '' : null;
		}

		$breadcrumbs  = [];
		$schema_items = [];
		$position     = 1;

		// Home
		if ($args['show_home']) {
			$home_label = (string) $args['home_label'];
			$home_url   = home_url('/');

			$breadcrumbs[]  = '<a href="' . esc_url($home_url) . '">' . esc_html($home_label) . '</a>';
			$schema_items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => (string) $home_label,
				'item'     => (string) $home_url,
			];
		}

		// Posts page (blog index) when not front page
		if (is_home() && ! is_front_page()) {
			$posts_page_id  = get_option('page_for_posts');
			$title          = $posts_page_id ? get_the_title($posts_page_id) : get_bloginfo('name');
			$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($title) . '</span>';
			$schema_items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => (string) $title,
			];
		} elseif (is_singular()) {
			// Singular items (posts, pages, CPTs)
			$post = get_queried_object();

			if ($post && isset($post->post_type)) {
				$post_type = $post->post_type;

				// Custom post type archive link for CPTs (not standard 'post')
				if ($post_type && $post_type !== 'post') {
					$post_type_obj = get_post_type_object($post_type);

					if ($post_type_obj && ! empty($post_type_obj->has_archive)) {
						$link = get_post_type_archive_link($post_type);

						if ($link) {
							$name           = ! empty($post_type_obj->labels->name) ? $post_type_obj->labels->name : ucfirst($post_type);
							$breadcrumbs[]  = '<a href="' . esc_url($link) . '">' . esc_html($name) . '</a>';
							$schema_items[] = [
								'@type'    => 'ListItem',
								'position' => $position++,
								'name'     => (string) $name,
								'item'     => (string) $link,
							];
						}
					}
				}

				// If the post has parents (pages), list them
				if (isset($post->post_parent) && $post->post_parent) {
					$parent_id          = (int) $post->post_parent;
					$parent_breadcrumbs = [];
					$parent_schema      = [];

					while ($parent_id) {
						$page = get_post($parent_id);

						if (! $page) {
							break;
						}
						$link                 = get_permalink($page->ID);
						$title                = get_the_title($page->ID);
						$parent_breadcrumbs[] = '<a href="' . esc_url($link) . '">' . esc_html($title) . '</a>';
						$parent_schema[]      = [
							'@type'    => 'ListItem',
							'position' => $position++,
							'name'     => (string) $title,
							'item'     => (string) $link,
						];
						$parent_id = (int) $page->post_parent;
					}

					if (! empty($parent_breadcrumbs)) {
						$breadcrumbs  = array_merge($breadcrumbs, array_reverse($parent_breadcrumbs));
						$schema_items = array_merge($schema_items, array_reverse($parent_schema));
					}
				}

				// Current singular title
				$title          = get_the_title($post);
				$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($title) . '</span>';
				$schema_items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => (string) $title,
				];
			}
		} elseif (is_category() || is_tag() || is_tax()) {
			// Categories / Tags / Taxonomies
			$term = get_queried_object();

			if ($term && isset($term->name)) {
				$name           = $term->name;
				$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($name) . '</span>';
				$schema_items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => (string) $name,
				];
			}
		} elseif (is_post_type_archive()) {
			// Post type archive (e.g., /products/)
			$obj   = get_queried_object();
			$title = (isset($obj->labels->name) ? $obj->labels->name : (isset($obj->name) ? $obj->name : ''));

			if ($title) {
				$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($title) . '</span>';
				$schema_items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => (string) $title,
				];
			}
		} elseif (is_author()) {
			// Author archive
			$author = get_queried_object();

			if ($author && isset($author->display_name)) {
				$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($author->display_name) . '</span>';
				$schema_items[] = [
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => (string) $author->display_name,
				];
			}
		} elseif (is_search()) {
			// Search results
			$q              = get_search_query();
			$label          = sprintf( /* translators: %s: search term */__('Search results for: %s', 'toolkit'), $q);
			$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($label) . '</span>';
			$schema_items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => (string) $q,
			];
		} elseif (is_404()) {
			// 404
			$label          = __('404 Not Found', 'toolkit');
			$breadcrumbs[]  = '<span class="' . esc_attr($args['active_class']) . '">' . esc_html($label) . '</span>';
			$schema_items[] = [
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => '404',
			];
		}

		/**
		 * Allow modification of breadcrumbs and schema items before output.
		 *
		 * @param array $breadcrumbs  HTML pieces for breadcrumbs.
		 * @param array $schema_items Schema item array suitable for JSON-LD.
		 * @param array $args         The resolved args.
		 */
		$modified = apply_filters('vlt_toolkit_breadcrumbs_items', [ $breadcrumbs, $schema_items, $args ]);

		// Respect filter return structure if modified
		if (is_array($modified) && isset($modified[0]) && isset($modified[1])) {
			$breadcrumbs  = (array) $modified[0];
			$schema_items = (array) $modified[1];

			if (isset($modified[2])) {
				$args = wp_parse_args($modified[2], $args);
			}
		}

		// Build output if we have breadcrumbs
		$output = '';

		if (! empty($breadcrumbs)) {

			// Ensure allowed container tag (basic whitelist)
			$allowed_tags  = [ 'nav', 'div', 'section' ];
			$container_tag = in_array($args['container_tag'], $allowed_tags, true) ? $args['container_tag'] : 'nav';

			$output = sprintf(
				'<%1$s class="%2$s" aria-label="%3$s">%4$s</%1$s>',
				esc_html($container_tag),
				esc_attr($args['container_class']),
				esc_attr__('Breadcrumb', 'toolkit'),
				implode(wp_kses_post($args['separator']), $breadcrumbs),
			);

			// JSON-LD structured data
			if ($args['show_schema'] && ! empty($schema_items)) {
				$output .= '<script type="application/ld+json">' .
					wp_json_encode(
						[
							'@context'        => 'https://schema.org',
							'@type'           => 'BreadcrumbList',
							'itemListElement' => $schema_items,
						],
						JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
					) .
					'</script>';
			}
		}

		if ($echo) {
			echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		} else {
			return $output;
		}
	}
}
