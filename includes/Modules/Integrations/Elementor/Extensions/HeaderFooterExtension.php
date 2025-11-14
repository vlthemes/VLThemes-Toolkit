<?php

namespace VLT\Helper\Modules\Integrations\Elementor\Extensions;

use VLT\Helper\Modules\Integrations\Elementor\BaseExtension;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Header/Footer Builder Extension
 *
 * Provides template parts system for headers, footers, and 404 pages
 * with conditional display rules.
 *
 * @package VLT Helper
 */
class HeaderFooterExtension extends BaseExtension
{

	/**
	 * Extension name
	 *
	 * @var string
	 */
	protected $name = 'header_footer_builder';

	/**
	 * Initialize extension
	 */
	protected function init()
	{
		// No initialization needed for this extension
	}

	/**
	 * Register extension hooks
	 */
	protected function register_hooks()
	{
		// Register custom post type
		add_action('init', [$this, 'register_post_type']);

		// Register ACF field groups
		add_action('acf/init', [$this, 'register_acf_fields']);

		// Populate ACF field choices
		add_filter('acf/load_field/key=field_hfb_rule', [$this, 'populate_rule_choices']);
		add_filter('acf/load_field/key=field_hfb_exclude_rule', [$this, 'populate_rule_choices']);

		// Add template content filters
		add_filter('vlt_hfb_header', [$this, 'get_header_content']);
		add_filter('vlt_hfb_footer', [$this, 'get_footer_content']);
		add_filter('vlt_hfb_404', [$this, 'get_404_content']);

		// Admin columns
		add_filter('manage_vlt_hfb_posts_columns', [$this, 'add_admin_columns']);
		add_action('manage_vlt_hfb_posts_custom_column', [$this, 'render_admin_columns'], 10, 2);
		add_filter('manage_edit-vlt_hfb_sortable_columns', [$this, 'make_columns_sortable']);

		// Add post states
		add_filter('display_post_states', [$this, 'add_template_type_state'], 10, 2);

		// Admin filters
		add_action('restrict_manage_posts', [$this, 'add_template_type_filter']);
		add_filter('parse_query', [$this, 'filter_by_template_type']);

		// Admin scripts
		add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);

		// Register shortcode
		add_shortcode('hfb_template', [$this, 'render_shortcode']);

		add_filter('single_template', [$this, 'load_canvas_template']);
		add_action('template_redirect', [$this, 'block_template_frontend']);
	}

	/**
	 * Register vlt_hfb custom post type
	 */
	public function register_post_type()
	{
		$labels = [
			'name'               => esc_html__('Header/Footer', 'vlt-helper'),
			'singular_name'      => esc_html__('Header/Footer', 'vlt-helper'),
			'menu_name'          => esc_html__('Header/Footer', 'vlt-helper'),
			'add_new'            => esc_html__('Add New', 'vlt-helper'),
			'add_new_item'       => esc_html__('Add New Template', 'vlt-helper'),
			'edit_item'          => esc_html__('Edit Template', 'vlt-helper'),
			'new_item'           => esc_html__('New Template', 'vlt-helper'),
			'view_item'          => esc_html__('View Template', 'vlt-helper'),
			'search_items'       => esc_html__('Search Templates', 'vlt-helper'),
			'not_found'          => esc_html__('No templates found', 'vlt-helper'),
			'not_found_in_trash' => esc_html__('No templates found in trash', 'vlt-helper'),
		];

		$args = [
			'labels'              => $labels,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'capability_type'     => 'post',
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-editor-kitchensink',
			'supports'            => ['title', 'thumbnail', 'elementor'],
			'menu_position'       => 5,
			'capabilities'        => [
				'edit_post'              => 'manage_options',
				'read_post'              => 'read',
				'delete_post'            => 'manage_options',
				'edit_posts'             => 'manage_options',
				'edit_others_posts'      => 'manage_options',
				'publish_posts'          => 'manage_options',
				'read_private_posts'     => 'manage_options',
				'delete_posts'           => 'manage_options',
				'delete_others_posts'    => 'manage_options',
				'delete_private_posts'   => 'manage_options',
				'delete_published_posts' => 'manage_options',
				'create_posts'           => 'manage_options',
			],
		];

		register_post_type('vlt_hfb', $args);
	}

	/**
	 * Single template function which will choose our template
	 */
	public function load_canvas_template($single_template)
	{
		global $post;

		if ('vlt_hfb' == $post->post_type) {
			$elementor_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';

			if (file_exists($elementor_canvas)) {
				return $elementor_canvas;
			}

			return;
		}

		return $single_template;
	}

	/**
	 * Don't display the elementor Elementor Header & Footer Builder templates on the frontend for non edit_posts capable users.
	 */
	public function block_template_frontend()
	{
		if (is_singular('vlt_hfb') && ! current_user_can('edit_posts')) {
			wp_redirect(site_url(), 301);
			die;
		}
	}

	/**
	 * Render shortcode [hfb_template]
	 *
	 * Usage: [hfb_template id="123"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function render_shortcode($atts)
	{
		$atts = shortcode_atts([
			'id' => '',
		], $atts, 'hfb_template');

		if (empty($atts['id'])) {
			return '';
		}

		if (!class_exists('\Elementor\Plugin')) {
			return '';
		}

		$template_id = intval($atts['id']);

		// Verify it's a vlt_hfb post type
		if (get_post_type($template_id) !== 'vlt_hfb') {
			return '';
		}

		// Get Elementor content
		$content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);

		if (empty($content)) {
			return '';
		}

		return sprintf(
			'<div class="vlt-hfb-template" data-template-id="%d">%s</div>',
			$template_id,
			$content
		);
	}

	/**
	 * Enqueue admin scripts and localize data
	 */
	public function enqueue_admin_scripts()
	{
		// Only load on single vlt_hfb post edit pages
		global $pagenow;
		$screen = get_current_screen();

		// Check if we're on post.php or post-new.php for vlt_hfb post type
		if (!$screen || $screen->post_type !== 'vlt_hfb' || !in_array($pagenow, ['post.php', 'post-new.php'])) {
			return;
		}

		// Register and enqueue HFB admin script
		wp_enqueue_script(
			'vlt-hfb-admin',
			VLT_HELPER_URL . 'assets/js/hfb-admin.js',
			[],
			VLT_HELPER_VERSION,
			true
		);

		// Localize script with admin data
		wp_localize_script(
			'vlt-hfb-admin',
			'hfb_admin_data',
			[
				'hfb_edit_url'      => admin_url('edit.php?post_type=vlt_hfb'),
				'hfb_view_all_text' => esc_html__('View All', 'vlt-helper'),
			]
		);
	}

	/**
	 * Register ACF field groups for template parts
	 */
	public function register_acf_fields()
	{
		if (! function_exists('acf_add_local_field_group')) {
			return;
		}

		acf_add_local_field_group([
			'key'      => 'group_vlt_hfb_settings',
			'title'    => esc_html__('Template Settings', 'vlt-helper'),
			'fields'   => [
				[
					'key'           => 'field_template_type',
					'label'         => esc_html__('Template Type', 'vlt-helper'),
					'name'          => 'template_type',
					'type'          => 'select',
					'required'      => 1,
					'choices'       => [
						'header'  => esc_html__('Header', 'vlt-helper'),
						'footer'  => esc_html__('Footer', 'vlt-helper'),
						'404'     => esc_html__('404 Page', 'vlt-helper'),
						'submenu' => esc_html__('Submenu', 'vlt-helper'),
						'custom'  => esc_html__('Custom', 'vlt-helper'),
					],
					'default_value' => 'header',
				],
				[
					'key'               => 'field_display_rules',
					'label'             => esc_html__('Display Rules', 'vlt-helper'),
					'instructions'      => esc_html__('Add locations for where this template should appear.', 'vlt-helper'),
					'name'              => 'display_rules',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => esc_html__('Add Rule', 'vlt-helper'),
					'conditional_logic' => [
						[
							[
								'field'    => 'field_template_type',
								'operator' => '!=',
								'value'    => '404',
							],
						],
					],
					'sub_fields'        => [
						[
							'key'     => 'field_hfb_rule',
							'label'   => esc_html__('Rule', 'vlt-helper'),
							'name'    => 'rule',
							'type'    => 'select',
							'choices' => [], // Populated dynamically
						],
						[
							'key'               => 'field_hfb_specifics',
							'label'             => esc_html__('Specific Target', 'vlt-helper'),
							'name'              => 'specifics',
							'type'              => 'post_object',
							'post_type'         => [], // All post types
							'taxonomy'          => [], // All taxonomies
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'id',
							'conditional_logic' => [
								[
									[
										'field'    => 'field_hfb_rule',
										'operator' => '==',
										'value'    => 'specifics',
									],
								],
							],
						],
					],
				],
				[
					'key'               => 'field_exclude_rules',
					'label'             => esc_html__('Exclude Rules', 'vlt-helper'),
					'instructions'      => esc_html__('Add locations for where this template should not appear.', 'vlt-helper'),
					'name'              => 'exclude_rules',
					'type'              => 'repeater',
					'layout'            => 'block',
					'button_label'      => esc_html__('Add Exclusion', 'vlt-helper'),
					'conditional_logic' => [
						[
							[
								'field'    => 'field_template_type',
								'operator' => '!=',
								'value'    => '404',
							],
						],
					],
					'sub_fields'        => [
						[
							'key'     => 'field_hfb_exclude_rule',
							'label'   => esc_html__('Rule', 'vlt-helper'),
							'name'    => 'rule',
							'type'    => 'select',
							'choices' => [], // Populated dynamically
						],
						[
							'key'               => 'field_hfb_exclude_specifics',
							'label'             => esc_html__('Specific Target', 'vlt-helper'),
							'name'              => 'specifics',
							'type'              => 'post_object',
							'post_type'         => [], // All post types
							'taxonomy'          => [], // All taxonomies
							'allow_null'        => 0,
							'multiple'          => 0,
							'return_format'     => 'id',
							'conditional_logic' => [
								[
									[
										'field'    => 'field_hfb_exclude_rule',
										'operator' => '==',
										'value'    => 'specifics',
									],
								],
							],
						],
					],
				],
				[
					'key'          => 'field_vlt_hfb_note',
					'label'        => esc_html__('Note', 'vlt-helper'),
					'name'         => 'note',
					'type'         => 'textarea',
					'instructions' => esc_html__('This note is only visible in the admin area.', 'vlt-helper'),
					'required'     => 0,
					'rows'         => 4,
					'placeholder'  => esc_html__('Add a note for this template...', 'vlt-helper'),
				],
			],
			'location' => [
				[
					[
						'param'    => 'post_type',
						'operator' => '==',
						'value'    => 'vlt_hfb',
					],
				],
			],
		]);
	}

	/**
	 * Get post types rules for ACF choices
	 *
	 * @return array
	 */
	private function get_post_types_rules()
	{
		$types = get_post_types(['public' => true], 'objects');
		$rules = [];

		foreach ($types as $type) {
			if (in_array($type->name, ['attachment', 'vlt_hfb'])) {
				continue;
			}

			$rules["post_type|{$type->name}"] = "All {$type->label}";
			$rules["post_type|{$type->name}|archive"] = "All {$type->label} Archive";

			$taxonomies = get_object_taxonomies($type->name, 'objects');
			foreach ($taxonomies as $tax) {
				if (! $tax->public) {
					continue;
				}
				$rules["post_type|{$type->name}|taxarchive|{$tax->name}"] = "All {$tax->labels->name} Archive";
			}
		}

		return $rules;
	}

	/**
	 * Prepare rule choices with optgroups
	 *
	 * @return array
	 */
	private function prepare_rule_choices()
	{
		return [
			'' => '- Select Rule -',
			'Basic' => [
				'basic-global'    => 'Entire Website',
				'basic-singulars' => 'All Singulars',
				'basic-archives'  => 'All Archives',
			],
			'Special Pages' => [
				'special-404'      => '404 Page',
				'special-search'   => 'Search Page',
				'special-blog'     => 'Blog / Posts Page',
				'special-front'    => 'Front Page',
				'special-date'     => 'Date Archive',
				'special-author'   => 'Author Archive',
				'special-woo-shop' => 'WooCommerce Shop Page',
			],
			'Post Types' => $this->get_post_types_rules(),
			'Specific Target' => [
				'specifics' => 'Specific Pages',
			],
		];
	}

	/**
	 * Populate rule choices dynamically
	 *
	 * @param array $field ACF field array.
	 * @return array
	 */
	public function populate_rule_choices($field)
	{
		$field['choices'] = $this->prepare_rule_choices();
		return $field;
	}

	/**
	 * Check if template should display on current page
	 *
	 * @param int $template_id Template post ID.
	 * @return bool
	 */
	private function should_display_template($template_id)
	{
		$template_type = get_field('template_type', $template_id);

		// For 404 templates, only display on 404 pages (no rules needed)
		if ($template_type === '404') {
			return is_404();
		}

		$display_rules = get_field('display_rules', $template_id);
		$exclude_rules = get_field('exclude_rules', $template_id);

		// Check exclusion rules first (only if not empty)
		if ($exclude_rules && is_array($exclude_rules) && count($exclude_rules) > 0) {
			foreach ($exclude_rules as $rule) {
				if ($this->check_rule($rule)) {
					return false;
				}
			}
		}

		// Check display rules (only if not empty)
		if (! $display_rules || ! is_array($display_rules) || count($display_rules) === 0) {
			return false;
		}

		foreach ($display_rules as $rule) {
			if ($this->check_rule($rule)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a rule matches the current page
	 *
	 * @param array|null $rule Rule array with 'rule' and optional 'specifics'.
	 * @return bool
	 */
	private function check_rule($rule)
	{
		// Handle null or empty rule
		if (! $rule || ! is_array($rule) || empty($rule['rule'])) {
			return false;
		}

		$rule_value = $rule['rule'];
		$specifics  = $rule['specifics'] ?? null;

		// Handle specific target
		if ($rule_value === 'specifics' && $specifics) {
			$queried_object = get_queried_object();
			if (! $queried_object) {
				return false;
			}

			// Check if it's a post/page
			if (isset($queried_object->ID) && $queried_object->ID == $specifics) {
				return true;
			}

			// Check if it's a term
			if (isset($queried_object->term_id) && $queried_object->term_id == $specifics) {
				return true;
			}

			return false;
		}

		// Handle other rules
		return match (true) {
			// Basic rules
			$rule_value === 'basic-global'    => true,
			$rule_value === 'basic-singulars' => is_singular(),
			$rule_value === 'basic-archives'  => is_archive(),

			// Special pages
			$rule_value === 'special-404'    => is_404(),
			$rule_value === 'special-search' => is_search(),
			$rule_value === 'special-blog'   => is_home(),
			$rule_value === 'special-front'  => is_front_page(),
			$rule_value === 'special-date'   => is_date(),
			$rule_value === 'special-author' => is_author(),
			$rule_value === 'special-woo-shop' => function_exists('is_shop') && is_shop(),

			// Post type rules (pipe-delimited)
			str_starts_with($rule_value, 'post_type|') => $this->check_post_type_rule($rule_value),

			default => false,
		};
	}

	/**
	 * Check post type rule
	 *
	 * @param string $rule_value Pipe-delimited rule (e.g., 'post_type|post|archive').
	 * @return bool
	 */
	private function check_post_type_rule($rule_value)
	{
		$parts = explode('|', $rule_value);

		if (count($parts) < 2) {
			return false;
		}

		$post_type = $parts[1];

		// post_type|{type}
		if (count($parts) === 2) {
			return is_singular($post_type);
		}

		// post_type|{type}|archive
		if (count($parts) === 3 && $parts[2] === 'archive') {
			return is_post_type_archive($post_type);
		}

		// post_type|{type}|taxarchive|{taxonomy}
		if (count($parts) === 4 && $parts[2] === 'taxarchive') {
			$taxonomy = $parts[3];
			return is_tax($taxonomy);
		}

		return false;
	}

	/**
	 * Get template by type
	 *
	 * @param string $type Template type (header, footer, 404).
	 * @return int|null Template post ID or null if not found.
	 */
	private function get_template_by_type($type)
	{
		$args = [
			'post_type'      => 'vlt_hfb',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'meta_query'     => [
				[
					'key'     => 'template_type',
					'value'   => $type,
					'compare' => '=',
				],
			],
		];

		$templates = get_posts($args);

		foreach ($templates as $template) {
			if ($this->should_display_template($template->ID)) {
				return $template->ID;
			}
		}

		return null;
	}

	/**
	 * Get header content
	 *
	 * @return string
	 */
	public function get_header_content()
	{
		return $this->get_template_content('header');
	}

	/**
	 * Get footer content
	 *
	 * @return string
	 */
	public function get_footer_content()
	{
		return $this->get_template_content('footer');
	}

	/**
	 * Get 404 content
	 *
	 * @return string
	 */
	public function get_404_content()
	{
		return $this->get_template_content('404');
	}

	/**
	 * Get template content by type
	 *
	 * @param string $type Template type (header, footer, 404).
	 * @return string
	 */
	private function get_template_content($type)
	{

		$template_id = $this->get_template_by_type($type);

		if (! $template_id) {
			return '';
		}

		if (! class_exists('\Elementor\Plugin')) {
			return '';
		}

		$content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display($template_id);

		if (empty($content)) {
			return '';
		}

		return sprintf(
			'<div class="vlt-template vlt-template--%s" data-template-id="%d">%s</div>',
			esc_attr($type),
			$template_id,
			$content
		);
	}

	/**
	 * Add admin columns
	 *
	 * @param array $columns Columns array.
	 * @return array
	 */
	public function add_admin_columns($columns)
	{
		$new_columns = [];

		foreach ($columns as $key => $value) {
			$new_columns[$key] = $value;

			if ($key === 'title') {
				$new_columns['display_rules'] = esc_html__('Display Rules', 'vlt-helper');
				$new_columns['note']          = esc_html__('Note', 'vlt-helper');
				$new_columns['shortcode']     = esc_html__('Shortcode', 'vlt-helper');
			}
		}

		return $new_columns;
	}

	/**
	 * Render admin columns
	 *
	 * @param string $column Column name.
	 * @param int    $post_id Post ID.
	 */
	public function render_admin_columns($column, $post_id)
	{
		switch ($column) {
			case 'display_rules':
				// Get both display and exclude rules
				$display_rules = get_field('display_rules', $post_id);
				$exclude_rules = get_field('exclude_rules', $post_id);
				$choices = $this->prepare_rule_choices();

				$output = [];

				// Process Display Rules
				if ($display_rules && is_array($display_rules)) {
					$rule_labels = [];
					foreach ($display_rules as $rule) {
						$rule_value = $rule['rule'] ?? '';
						if (empty($rule_value)) {
							continue;
						}

						$label = '';
						foreach ($choices as $options) {
							if (isset($options[$rule_value])) {
								$label = $options[$rule_value];
								break;
							}
						}

						if ($rule_value === 'specifics' && !empty($rule['specifics'])) {
							$specific_id = $rule['specifics'];
							$queried_object = get_post($specific_id);
							if (!$queried_object) {
								$queried_object = get_term($specific_id);
							}
							if ($queried_object) {
								$name = isset($queried_object->post_title) ? $queried_object->post_title : $queried_object->name;
								$label .= ': ' . $name;
							}
						}

						if ($label) {
							$rule_labels[] = $label;
						}
					}

					if (!empty($rule_labels)) {
						$output[] = '<strong>' . esc_html__('Display:', 'vlt-helper') . '</strong> ' . esc_html(implode(', ', $rule_labels));
					}
				}

				// Process Exclude Rules
				if ($exclude_rules && is_array($exclude_rules)) {
					$rule_labels = [];
					foreach ($exclude_rules as $rule) {
						$rule_value = $rule['rule'] ?? '';
						if (empty($rule_value)) {
							continue;
						}

						$label = '';
						foreach ($choices as $options) {
							if (isset($options[$rule_value])) {
								$label = $options[$rule_value];
								break;
							}
						}

						if ($rule_value === 'specifics' && !empty($rule['specifics'])) {
							$specific_id = $rule['specifics'];
							$queried_object = get_post($specific_id);
							if (!$queried_object) {
								$queried_object = get_term($specific_id);
							}
							if ($queried_object) {
								$name = isset($queried_object->post_title) ? $queried_object->post_title : $queried_object->name;
								$label .= ': ' . $name;
							}
						}

						if ($label) {
							$rule_labels[] = $label;
						}
					}

					if (!empty($rule_labels)) {
						$output[] = '<strong>' . esc_html__('Exclusion:', 'vlt-helper') . '</strong> ' . esc_html(implode(', ', $rule_labels));
					}
				}

				if (!empty($output)) {
					echo implode('<br>', $output);
				} else {
					echo '—';
				}
				break;

			case 'note':
				$note = get_field('note', $post_id);
				if ($note) {
					echo esc_html($note);
				} else {
					echo '—';
				}
				break;

			case 'shortcode':
				$shortcode = '[hfb_template id="' . $post_id . '"]';
				echo '<input type="text" readonly value="' . esc_attr($shortcode) . '" style="width: 100%; font-family: monospace; font-size: 12px; padding: 4px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 2px;" onclick="this.select(); document.execCommand(\'copy\'); this.style.background=\'#d4edda\'; setTimeout(() => this.style.background=\'#f0f0f1\', 1000);" title="' . esc_attr__('Click to copy', 'vlt-helper') . '" />';
				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function make_columns_sortable($columns)
	{
		return $columns;
	}

	/**
	 * Add template type to post states
	 *
	 * @param array   $post_states Post states.
	 * @param WP_Post $post        Post object.
	 * @return array
	 */
	public function add_template_type_state($post_states, $post)
	{
		if ($post->post_type !== 'vlt_hfb') {
			return $post_states;
		}

		$type = get_field('template_type', $post->ID);
		if ($type) {
			$types = [
				'header'  => esc_html__('Header', 'vlt-helper'),
				'footer'  => esc_html__('Footer', 'vlt-helper'),
				'404'     => esc_html__('404 Page', 'vlt-helper'),
				'submenu' => esc_html__('Submenu', 'vlt-helper'),
				'custom'  => esc_html__('Custom', 'vlt-helper'),
			];
			$post_states['vlt_hfb_type'] = $types[$type] ?? $type;
		}

		return $post_states;
	}

	/**
	 * Add template type filter dropdown
	 */
	public function add_template_type_filter()
	{
		global $typenow;

		if ($typenow !== 'vlt_hfb') {
			return;
		}

		$current_type = isset($_GET['template_type_filter']) ? sanitize_text_field($_GET['template_type_filter']) : '';

		$types = [
			''        => esc_html__('All Types', 'vlt-helper'),
			'header'  => esc_html__('Header', 'vlt-helper'),
			'footer'  => esc_html__('Footer', 'vlt-helper'),
			'404'     => esc_html__('404 Page', 'vlt-helper'),
			'submenu' => esc_html__('Submenu', 'vlt-helper'),
			'custom'  => esc_html__('Custom', 'vlt-helper'),
		];

		echo '<select name="template_type_filter" id="template_type_filter">';
		foreach ($types as $value => $label) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr($value),
				selected($current_type, $value, false),
				esc_html($label)
			);
		}
		echo '</select>';
	}

	/**
	 * Filter posts by template type
	 *
	 * @param WP_Query $query The WP_Query instance.
	 */
	public function filter_by_template_type($query)
	{
		global $pagenow, $typenow;

		if ($pagenow !== 'edit.php' || $typenow !== 'vlt_hfb' || !is_admin()) {
			return;
		}

		if (!isset($_GET['template_type_filter']) || empty($_GET['template_type_filter'])) {
			return;
		}

		$template_type = sanitize_text_field($_GET['template_type_filter']);

		$query->set('meta_query', [
			[
				'key'     => 'template_type',
				'value'   => $template_type,
				'compare' => '=',
			],
		]);
	}

	/**
	 * Register extension controls
	 *
	 * This extension doesn't add controls to Elementor elements
	 *
	 * @param object $element Elementor element instance.
	 * @param array  $args    Element arguments.
	 */
	public function register_controls($element, $args)
	{
		// This extension works with custom post types, not element controls
	}

	/**
	 * Render extension attributes
	 *
	 * This extension doesn't render attributes on Elementor elements
	 *
	 * @param object $element Elementor element instance.
	 */
	public function render_attributes($element)
	{
		// This extension works with custom post types, not element attributes
	}
}
