<?php

namespace VLT\Toolkit\Modules\Features;

use VLT\Toolkit\Modules\BaseModule;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Template Parts Extension
 *
 * Provides template parts system for headers, footers, and 404 pages
 * with conditional display rules.
 */
class TemplateParts extends BaseModule {
	protected $name = 'template_parts';

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
		// Register custom post type
		add_action( 'init', [ $this, 'register_post_type' ] );

		// Register ACF field groups
		add_action( 'acf/init', [ $this, 'register_acf_fields' ] );

		// Populate ACF field choices
		add_filter( 'acf/load_field/key=field_tp_rule', [ $this, 'populate_rule_choices' ] );
		add_filter( 'acf/load_field/key=field_tp_exclude_rule', [ $this, 'populate_rule_choices' ] );

		// Add shortcode meta box
		add_action( 'add_meta_boxes', [ $this, 'add_shortcode_meta_box' ] );

		// Add template content filters
		add_filter( 'vlt_toolkit_tp_header', [ $this, 'get_header_content' ] );
		add_filter( 'vlt_toolkit_tp_footer', [ $this, 'get_footer_content' ] );
		add_filter( 'vlt_toolkit_tp_above_footer', [ $this, 'get_above_footer_content' ] );
		add_filter( 'vlt_toolkit_tp_404', [ $this, 'get_404_content' ] );

		// Admin columns
		add_filter( 'manage_vlt_tp_posts_columns', [ $this, 'add_admin_columns' ] );
		add_action( 'manage_vlt_tp_posts_custom_column', [ $this, 'render_admin_columns' ], 10, 2 );
		add_filter( 'manage_edit-vlt_tp_sortable_columns', [ $this, 'make_columns_sortable' ] );

		// Add post states
		add_filter( 'display_post_states', [ $this, 'add_template_type_state' ], 10, 2 );

		// Admin filters
		add_action( 'restrict_manage_posts', [ $this, 'add_template_type_filter' ] );
		add_filter( 'parse_query', [ $this, 'filter_by_template_type' ] );

		// Admin scripts
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

		// Register shortcode
		add_shortcode( 'vlt_template_part', [ $this, 'render_shortcode' ] );

		add_filter( 'single_template', [ $this, 'load_canvas_template' ] );
		add_action( 'template_redirect', [ $this, 'block_template_frontend' ] );
	}

	/**
	 * Register vlt_tp custom post type
	 */
	public function register_post_type() {
		$labels = [
			'name'               => esc_html__( 'Template Parts', 'toolkit' ),
			'singular_name'      => esc_html__( 'Template Part', 'toolkit' ),
			'menu_name'          => esc_html__( 'Template Parts', 'toolkit' ),
			'add_new'            => esc_html__( 'Add New', 'toolkit' ),
			'add_new_item'       => esc_html__( 'Add New Template Part', 'toolkit' ),
			'edit_item'          => esc_html__( 'Edit Template Part', 'toolkit' ),
			'new_item'           => esc_html__( 'New Template Part', 'toolkit' ),
			'view_item'          => esc_html__( 'View Template Part', 'toolkit' ),
			'search_items'       => esc_html__( 'Search Template Parts', 'toolkit' ),
			'not_found'          => esc_html__( 'No template parts found', 'toolkit' ),
			'not_found_in_trash' => esc_html__( 'No template parts found in trash', 'toolkit' ),
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
			'supports'            => [ 'title', 'elementor' ],
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

		register_post_type( 'vlt_tp', $args );
	}

	/**
	 * Single template function which will choose our template
	 */
	public function load_canvas_template( $single_template ) {
		global $post;

		if ( 'vlt_tp' == $post->post_type ) {
			$elementor_canvas = ELEMENTOR_PATH . '/modules/page-templates/templates/canvas.php';

			if ( file_exists( $elementor_canvas ) ) {
				return $elementor_canvas;
			}

			return;
		}

		return $single_template;
	}

	/**
	 * Don't display the elementor Elementor Header & Footer Builder templates on the frontend for non edit_posts capable users.
	 */
	public function block_template_frontend() {
		if ( is_singular( 'vlt_tp' ) && !current_user_can( 'edit_posts' ) ) {
			wp_redirect( site_url(), 301 );

			die;
		}
	}

	/**
	 * Render shortcode [hfb_template]
	 *
	 * Usage: [hfb_template id="123"]
	 *
	 * @param array $atts shortcode attributes
	 *
	 * @return string
	 */
	public function render_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'id' => '',
			],
			$atts,
			'vlt_template_part',
		);

		if ( empty( $atts['id'] ) ) {
			return '';
		}

		if ( !class_exists( '\Elementor\Plugin' ) ) {
			return '';
		}

		$template_id = intval( $atts['id'] );

		// Verify it's a vlt_tp post type
		if ( 'vlt_tp' !== get_post_type( $template_id ) ) {
			return '';
		}

		// Get Elementor content
		$content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );

		if ( empty( $content ) ) {
			return '';
		}

		return sprintf(
			'<div class="vlt-tp-template" data-template-id="%d">%s</div>',
			$template_id,
			$content,
		);
	}

	/**
	 * Enqueue admin scripts and localize data
	 */
	public function enqueue_admin_scripts() {
		// Only load on single vlt_tp post edit pages
		global $pagenow;
		$screen = get_current_screen();

		// Check if we're on post.php or post-new.php for vlt_tp post type
		if ( !$screen || 'vlt_tp' !== $screen->post_type || !in_array( $pagenow, [ 'post.php', 'post-new.php' ] ) ) {
			return;
		}

		// Register and enqueue Template Parts admin script
		wp_enqueue_script(
			'vlt-tp-admin',
			VLT_TOOLKIT_URL . 'assets/js/tp-admin.js',
			[],
			VLT_TOOLKIT_VERSION,
			true,
		);

		// Localize script with admin data
		wp_localize_script(
			'vlt-tp-admin',
			'tp_admin_data',
			[
				'tp_edit_url'      => admin_url( 'edit.php?post_type=vlt_tp' ),
				'tp_view_all_text' => esc_html__( 'View All', 'toolkit' ),
			],
		);
	}

	/**
	 * Register ACF field groups for template parts
	 */
	public function register_acf_fields() {
		if ( !function_exists( 'acf_add_local_field_group' ) ) {
			return;
		}

		acf_add_local_field_group(
			[
				'key'    => 'group_vlt_tp_settings',
				'title'  => esc_html__( 'Template Settings', 'toolkit' ),
				'fields' => [
					[
						'key'      => 'field_template_type',
						'label'    => esc_html__( 'Template Type', 'toolkit' ),
						'name'     => 'template_type',
						'type'     => 'select',
						'required' => 1,
						'choices'  => [
							'header'       => esc_html__( 'Header', 'toolkit' ),
							'footer'       => esc_html__( 'Footer', 'toolkit' ),
							'above_footer' => esc_html__( 'Above Footer', 'toolkit' ),
							'404'          => esc_html__( '404 Page', 'toolkit' ),
							'submenu'      => esc_html__( 'Submenu', 'toolkit' ),
							'custom'       => esc_html__( 'Custom', 'toolkit' ),
						],
						'default_value' => 'header',
					],
					[
						'key'               => 'field_display_rules',
						'label'             => esc_html__( 'Display Rules', 'toolkit' ),
						'instructions'      => esc_html__( 'Add locations for where this template should appear.', 'toolkit' ),
						'name'              => 'display_rules',
						'type'              => 'repeater',
						'layout'            => 'block',
						'button_label'      => esc_html__( 'Add Rule', 'toolkit' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => '404',
								],
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => 'custom',
								],
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => 'submenu',
								],
							],
						],
						'sub_fields' => [
							[
								'key'     => 'field_tp_rule',
								'label'   => esc_html__( 'Rule', 'toolkit' ),
								'name'    => 'rule',
								'type'    => 'select',
								'choices' => [], // Populated dynamically
							],
							[
								'key'               => 'field_tp_specifics',
								'label'             => esc_html__( 'Specific Target', 'toolkit' ),
								'name'              => 'specifics',
								'type'              => 'post_object',
								'post_type'         => [], // All post types
								'taxonomy'          => [], // All taxonomies
								'allow_null'        => 0,
								'multiple'          => 1,
								'return_format'     => 'object',
								'conditional_logic' => [
									[
										[
											'field'    => 'field_tp_rule',
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
						'label'             => esc_html__( 'Exclude Rules', 'toolkit' ),
						'instructions'      => esc_html__( 'Add locations for where this template should not appear.', 'toolkit' ),
						'name'              => 'exclude_rules',
						'type'              => 'repeater',
						'layout'            => 'block',
						'button_label'      => esc_html__( 'Add Exclusion', 'toolkit' ),
						'conditional_logic' => [
							[
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => '404',
								],
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => 'custom',
								],
								[
									'field'    => 'field_template_type',
									'operator' => '!=',
									'value'    => 'submenu',
								],
							],
						],
						'sub_fields' => [
							[
								'key'     => 'field_tp_exclude_rule',
								'label'   => esc_html__( 'Rule', 'toolkit' ),
								'name'    => 'rule',
								'type'    => 'select',
								'choices' => [], // Populated dynamically
							],
							[
								'key'               => 'field_tp_exclude_specifics',
								'label'             => esc_html__( 'Specific Target', 'toolkit' ),
								'name'              => 'specifics',
								'type'              => 'post_object',
								'post_type'         => [], // All post types
								'taxonomy'          => [], // All taxonomies
								'allow_null'        => 0,
								'multiple'          => 1,
								'return_format'     => 'object',
								'conditional_logic' => [
									[
										[
											'field'    => 'field_tp_exclude_rule',
											'operator' => '==',
											'value'    => 'specifics',
										],
									],
								],
							],
						],
					],
					[
						'key'          => 'field_vlt_tp_note',
						'label'        => esc_html__( 'Note', 'toolkit' ),
						'name'         => 'note',
						'type'         => 'textarea',
						'instructions' => esc_html__( 'This note is only visible in the admin area.', 'toolkit' ),
						'required'     => 0,
						'rows'         => 4,
						'placeholder'  => esc_html__( 'Add a note for this template...', 'toolkit' ),
					],
				],
				'location' => [
					[
						[
							'param'    => 'post_type',
							'operator' => '==',
							'value'    => 'vlt_tp',
						],
					],
				],
			],
		);
	}

	/**
	 * Add shortcode meta box
	 */
	public function add_shortcode_meta_box() {
		add_meta_box(
			'vlt_tp_shortcode',
			esc_html__( 'Shortcode', 'toolkit' ),
			[ $this, 'render_shortcode_meta_box' ],
			'vlt_tp',
			'side',
			'high',
		);
	}

	/**
	 * Render shortcode meta box
	 *
	 * @param WP_Post $post current post object
	 */
	public function render_shortcode_meta_box( $post ) {
		$shortcode = '[vlt_template_part id="' . $post->ID . '"]';
		?>
<input type="text" readonly
    value="<?php echo esc_attr( $shortcode ); ?>"
    style="width: 100%; font-family: monospace; font-size: 12px; padding: 6px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 3px; cursor: pointer;"
    onclick="this.select(); document.execCommand('copy'); this.style.background='#d4edda'; setTimeout(() => this.style.background='#f0f0f1', 1000);"
    title="<?php esc_attr_e( 'Click to copy', 'toolkit' ); ?>" />
<?php
	}

	/**
	 * Populate rule choices dynamically
	 *
	 * @param array $field ACF field array
	 *
	 * @return array
	 */
	public function populate_rule_choices( $field ) {
		$field['choices'] = $this->prepare_rule_choices();

		return $field;
	}

	/**
	 * Get header content
	 *
	 * @return string
	 */
	public function get_header_content() {
		return $this->get_template_content( 'header' );
	}

	/**
	 * Get footer content
	 *
	 * @return string
	 */
	public function get_footer_content() {
		return $this->get_template_content( 'footer' );
	}

	/**
	 * Get above footer content
	 *
	 * @return string
	 */
	public function get_above_footer_content() {
		return $this->get_template_content( 'above_footer' );
	}

	/**
	 * Get 404 content
	 *
	 * @return string
	 */
	public function get_404_content() {
		return $this->get_template_content( '404' );
	}

	/**
	 * Add admin columns
	 *
	 * @param array $columns columns array
	 *
	 * @return array
	 */
	public function add_admin_columns( $columns ) {
		$new_columns = [];

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;

			if ( 'title' === $key ) {
				$new_columns['display_rules'] = esc_html__( 'Display Rules', 'toolkit' );
				$new_columns['note']          = esc_html__( 'Note', 'toolkit' );
				$new_columns['shortcode']     = esc_html__( 'Shortcode', 'toolkit' );
			}
		}

		return $new_columns;
	}

	/**
	 * Render admin columns
	 *
	 * @param string $column  column name
	 * @param int    $post_id post ID
	 */
	public function render_admin_columns( $column, $post_id ) {
		switch ( $column ) {
			case 'display_rules':
				// Get both display and exclude rules
				$display_rules = get_field( 'display_rules', $post_id );
				$exclude_rules = get_field( 'exclude_rules', $post_id );
				$choices       = $this->prepare_rule_choices();

				$output = [];

				// Process Display Rules
				if ( $display_rules && is_array( $display_rules ) ) {
					$rule_labels = [];
					foreach ( $display_rules as $rule ) {
						$rule_value = $rule['rule'] ?? '';

						if ( empty( $rule_value ) ) {
							continue;
						}

						$label = '';
						foreach ( $choices as $options ) {
							if ( isset( $options[ $rule_value ] ) ) {
								$label = $options[ $rule_value ];

								break;
							}
						}

						if ( 'specifics' === $rule_value && !empty( $rule['specifics'] ) ) {
							$specifics       = $rule['specifics'];
							$specifics_array = is_array( $specifics ) ? $specifics : [ $specifics ];
							$linked_names    = [];

							foreach ( $specifics_array as $specific_item ) {
								$specific_id    = is_object( $specific_item ) ? $specific_item->ID : $specific_item;
								$queried_object = get_post( $specific_id );
								$is_term        = false;

								if ( !$queried_object ) {
									$queried_object = get_term( $specific_id );
									$is_term        = true;
								}

								if ( $queried_object ) {
									$name = isset( $queried_object->post_title ) ? $queried_object->post_title : $queried_object->name;

									// Create permalink
									if ( $is_term ) {
										$permalink = get_term_link( $specific_id, $queried_object->taxonomy );
									} else {
										$permalink = get_permalink( $specific_id );
									}

									if ( $permalink && !is_wp_error( $permalink ) ) {
										$linked_names[] = sprintf(
											'<a href="%s" target="_blank" title="%s">%s</a>',
											esc_url( $permalink ),
											esc_attr__( 'View', 'toolkit' ) . ': ' . esc_attr( $name ),
											esc_html( $name ),
										);
									} else {
										$linked_names[] = esc_html( $name );
									}
								}
							}

							if ( !empty( $linked_names ) ) {
								$label .= ': ' . implode( ', ', $linked_names );
							}
						}

						if ( $label ) {
							$rule_labels[] = $label;
						}
					}

					if ( !empty( $rule_labels ) ) {
						$output[] = '<strong>' . esc_html__( 'Display:', 'toolkit' ) . '</strong> ' . implode( ', ', $rule_labels );
					}
				}

				// Process Exclude Rules
				if ( $exclude_rules && is_array( $exclude_rules ) ) {
					$rule_labels = [];
					foreach ( $exclude_rules as $rule ) {
						$rule_value = $rule['rule'] ?? '';

						if ( empty( $rule_value ) ) {
							continue;
						}

						$label = '';
						foreach ( $choices as $options ) {
							if ( isset( $options[ $rule_value ] ) ) {
								$label = $options[ $rule_value ];

								break;
							}
						}

						if ( 'specifics' === $rule_value && !empty( $rule['specifics'] ) ) {
							$specifics       = $rule['specifics'];
							$specifics_array = is_array( $specifics ) ? $specifics : [ $specifics ];
							$linked_names    = [];

							foreach ( $specifics_array as $specific_item ) {
								$specific_id    = is_object( $specific_item ) ? $specific_item->ID : $specific_item;
								$queried_object = get_post( $specific_id );
								$is_term        = false;

								if ( !$queried_object ) {
									$queried_object = get_term( $specific_id );
									$is_term        = true;
								}

								if ( $queried_object ) {
									$name = isset( $queried_object->post_title ) ? $queried_object->post_title : $queried_object->name;

									// Create permalink
									if ( $is_term ) {
										$permalink = get_term_link( $specific_id, $queried_object->taxonomy );
									} else {
										$permalink = get_permalink( $specific_id );
									}

									if ( $permalink && !is_wp_error( $permalink ) ) {
										$linked_names[] = sprintf(
											'<a href="%s" target="_blank" title="%s">%s</a>',
											esc_url( $permalink ),
											esc_attr__( 'View', 'toolkit' ) . ': ' . esc_attr( $name ),
											esc_html( $name ),
										);
									} else {
										$linked_names[] = esc_html( $name );
									}
								}
							}

							if ( !empty( $linked_names ) ) {
								$label .= ': ' . implode( ', ', $linked_names );
							}
						}

						if ( $label ) {
							$rule_labels[] = $label;
						}
					}

					if ( !empty( $rule_labels ) ) {
						$output[] = '<strong>' . esc_html__( 'Exclusion:', 'toolkit' ) . '</strong> ' . implode( ', ', $rule_labels );
					}
				}

				if ( !empty( $output ) ) {
					echo implode( '<br>', $output );
				} else {
					echo '—';
				}

				break;

			case 'note':
				$note = get_field( 'note', $post_id );

				if ( $note ) {
					echo wp_kses_post( nl2br( $note ) );
				} else {
					echo '—';
				}

				break;

			case 'shortcode':
				$shortcode = '[vlt_template_part id="' . $post_id . '"]';
				echo '<input type="text" readonly value="' . esc_attr( $shortcode ) . '" style="width: 100%; font-family: monospace; font-size: 12px; padding: 4px; background: #f0f0f1; border: 1px solid #dcdcde; border-radius: 2px;" onclick="this.select(); document.execCommand(\'copy\'); this.style.background=\'#d4edda\'; setTimeout(() => this.style.background=\'#f0f0f1\', 1000);" title="' . esc_attr__( 'Click to copy', 'toolkit' ) . '" />';

				break;
		}
	}

	/**
	 * Make columns sortable
	 *
	 * @param array $columns sortable columns
	 *
	 * @return array
	 */
	public function make_columns_sortable( $columns ) {
		return $columns;
	}

	/**
	 * Add template type to post states
	 *
	 * @param array   $post_states post states
	 * @param WP_Post $post        post object
	 *
	 * @return array
	 */
	public function add_template_type_state( $post_states, $post ) {
		if ( 'vlt_tp' !== $post->post_type ) {
			return $post_states;
		}

		$type = get_field( 'template_type', $post->ID );

		if ( $type ) {
			$types = [
				'header'       => esc_html__( 'Header', 'toolkit' ),
				'footer'       => esc_html__( 'Footer', 'toolkit' ),
				'above_footer' => esc_html__( 'Above Footer', 'toolkit' ),
				'404'          => esc_html__( '404 Page', 'toolkit' ),
				'submenu'      => esc_html__( 'Submenu', 'toolkit' ),
				'custom'       => esc_html__( 'Custom', 'toolkit' ),
			];
			$post_states['vlt_tp_type'] = $types[ $type ] ?? $type;
		}

		return $post_states;
	}

	/**
	 * Add template type filter dropdown
	 */
	public function add_template_type_filter() {
		global $typenow;

		if ( 'vlt_tp' !== $typenow ) {
			return;
		}

		$current_type = isset( $_GET['template_type_filter'] ) ? sanitize_text_field( $_GET['template_type_filter'] ) : '';

		$types = [
			''             => esc_html__( 'All Types', 'toolkit' ),
			'header'       => esc_html__( 'Header', 'toolkit' ),
			'footer'       => esc_html__( 'Footer', 'toolkit' ),
			'above_footer' => esc_html__( 'Above Footer', 'toolkit' ),
			'404'          => esc_html__( '404 Page', 'toolkit' ),
			'submenu'      => esc_html__( 'Submenu', 'toolkit' ),
			'custom'       => esc_html__( 'Custom', 'toolkit' ),
		];

		echo '<select name="template_type_filter" id="template_type_filter">';
		foreach ( $types as $value => $label ) {
			printf(
				'<option value="%s"%s>%s</option>',
				esc_attr( $value ),
				selected( $current_type, $value, false ),
				esc_html( $label ),
			);
		}
		echo '</select>';
	}

	/**
	 * Filter posts by template type
	 *
	 * @param WP_Query $query the WP_Query instance
	 */
	public function filter_by_template_type( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' !== $pagenow || 'vlt_tp' !== $typenow || !is_admin() ) {
			return;
		}

		if ( !isset( $_GET['template_type_filter'] ) || empty( $_GET['template_type_filter'] ) ) {
			return;
		}

		$template_type = sanitize_text_field( $_GET['template_type_filter'] );

		$query->set(
			'meta_query',
			[
				[
					'key'     => 'template_type',
					'value'   => $template_type,
					'compare' => '=',
				],
			],
		);
	}

	/**
	 * Register extension controls
	 *
	 * This extension doesn't add controls to Elementor elements
	 *
	 * @param object $element elementor element instance
	 * @param array  $args    element arguments
	 */
	public function register_controls( $element, $args ) {
		// This extension works with custom post types, not element controls
	}

	/**
	 * Render extension attributes
	 *
	 * This extension doesn't render attributes on Elementor elements
	 *
	 * @param object $element elementor element instance
	 */
	public function render_attributes( $element ) {
		// This extension works with custom post types, not element attributes
	}

	/**
	 * Get post types rules for ACF choices
	 *
	 * @return array
	 */
	private function get_post_types_rules() {
		$types = get_post_types( [ 'public' => true ], 'objects' );
		$rules = [];

		foreach ( $types as $type ) {
			if ( in_array( $type->name, [ 'attachment', 'vlt_tp' ] ) ) {
				continue;
			}

			$rules[ "post_type|{$type->name}" ]         = "All {$type->label}";
			$rules[ "post_type|{$type->name}|archive" ] = "All {$type->label} Archive";

			$taxonomies = get_object_taxonomies( $type->name, 'objects' );
			foreach ( $taxonomies as $tax ) {
				if ( !$tax->public ) {
					continue;
				}
				$rules[ "post_type|{$type->name}|taxarchive|{$tax->name}" ] = "All {$tax->labels->name} Archive";
			}
		}

		return $rules;
	}

	/**
	 * Prepare rule choices with optgroups
	 *
	 * @return array
	 */
	private function prepare_rule_choices() {
		return [
			''      => '- Select Rule -',
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
			'Post Types'      => $this->get_post_types_rules(),
			'Specific Target' => [
				'specifics' => 'Specific Pages',
			],
		];
	}

	/**
	 * Check if template should display on current page
	 *
	 * @param int $template_id template post ID
	 *
	 * @return bool
	 */
	private function should_display_template( $template_id ) {
		$template_type = get_field( 'template_type', $template_id );

		// For 404 templates, only display on 404 pages (no rules needed)
		if ( '404' === $template_type ) {
			return is_404();
		}

		$display_rules = get_field( 'display_rules', $template_id );
		$exclude_rules = get_field( 'exclude_rules', $template_id );

		// Check exclusion rules first (only if not empty)
		if ( $exclude_rules && is_array( $exclude_rules ) && count( $exclude_rules ) > 0 ) {
			foreach ( $exclude_rules as $rule ) {
				if ( $this->check_rule( $rule ) ) {
					return false;
				}
			}
		}

		// Check display rules (only if not empty)
		if ( !$display_rules || !is_array( $display_rules ) || 0 === count( $display_rules ) ) {
			return false;
		}

		foreach ( $display_rules as $rule ) {
			if ( $this->check_rule( $rule ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a rule matches the current page
	 *
	 * @param array|null $rule rule array with 'rule' and optional 'specifics'
	 *
	 * @return bool
	 */
	private function check_rule( $rule ) {
		// Handle null or empty rule
		if ( !$rule || !is_array( $rule ) || empty( $rule['rule'] ) ) {
			return false;
		}

		$rule_value = $rule['rule'];
		$specifics  = $rule['specifics'] ?? null;

		// Handle specific target
		if ( 'specifics' === $rule_value && $specifics ) {
			$queried_object = get_queried_object();

			if ( !$queried_object ) {
				return false;
			}

			// Ensure specifics is an array
			$specifics_array = is_array( $specifics ) ? $specifics : [ $specifics ];

			foreach ( $specifics_array as $specific_item ) {
				$specific_id = is_object( $specific_item ) ? $specific_item->ID : $specific_item;

				// Check if it's a post/page
				if ( isset( $queried_object->ID ) && $queried_object->ID == $specific_id ) {
					return true;
				}

				// Check if it's a term
				if ( isset( $queried_object->term_id ) && $queried_object->term_id == $specific_id ) {
					return true;
				}
			}

			return false;
		}

		// Handle other rules
		return match ( true ) {
			// Basic rules
			'basic-global' === $rule_value    => true,
			'basic-singulars' === $rule_value => is_singular(),
			'basic-archives' === $rule_value  => is_archive(),

			// Special pages
			'special-404' === $rule_value      => is_404(),
			'special-search' === $rule_value   => is_search(),
			'special-blog' === $rule_value     => is_home(),
			'special-front' === $rule_value    => is_front_page(),
			'special-date' === $rule_value     => is_date(),
			'special-author' === $rule_value   => is_author(),
			'special-woo-shop' === $rule_value => function_exists( 'is_shop' ) && is_shop(),

			// Post type rules (pipe-delimited)
			str_starts_with( $rule_value, 'post_type|' ) => $this->check_post_type_rule( $rule_value ),

			default => false,
		};
	}

	/**
	 * Check post type rule
	 *
	 * @param string $rule_value Pipe-delimited rule (e.g., 'post_type|post|archive').
	 *
	 * @return bool
	 */
	private function check_post_type_rule( $rule_value ) {
		$parts = explode( '|', $rule_value );

		if ( count( $parts ) < 2 ) {
			return false;
		}

		$post_type = $parts[1];

		// post_type|{type}
		if ( 2 === count( $parts ) ) {
			return is_singular( $post_type );
		}

		// post_type|{type}|archive
		if ( 3 === count( $parts ) && 'archive' === $parts[2] ) {
			return is_post_type_archive( $post_type );
		}

		// post_type|{type}|taxarchive|{taxonomy}
		if ( 4 === count( $parts ) && 'taxarchive' === $parts[2] ) {
			$taxonomy = $parts[3];

			return is_tax( $taxonomy );
		}

		return false;
	}

	/**
	 * Get templates by type (public static method)
	 *
	 * @param string|null $type template type (header, footer, above_footer, 404, submenu, custom) or null for all
	 *
	 * @return array Array of template posts [ID => title]
	 */
	public static function get_templates_by_type( $type = null ) {
		$options = [];

		$args = [
			'post_type'      => 'vlt_tp',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
		];

		// Add meta query if type is specified
		if ( $type ) {
			$args['meta_query'] = [
				[
					'key'     => 'template_type',
					'value'   => $type,
					'compare' => '=',
				],
			];
		}

		$templates = get_posts( $args );

		if ( !empty( $templates ) && !is_wp_error( $templates ) ) {
			foreach ( $templates as $post ) {
				$options[ $post->ID ] = $post->post_title;
			}
		}

		return $options;
	}

	/**
	 * Get template by type
	 *
	 * @param string $type template type (header, footer, 404)
	 *
	 * @return int|null template post ID or null if not found
	 */
	private function get_template_by_type( $type ) {
		$args = [
			'post_type'      => 'vlt_tp',
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

		$templates = get_posts( $args );

		$matching_templates = [];

		// Collect all matching templates with their priority
		foreach ( $templates as $template ) {
			if ( $this->should_display_template( $template->ID ) ) {
				$priority             = $this->get_template_priority( $template->ID );
				$matching_templates[] = [
					'id'       => $template->ID,
					'priority' => $priority,
				];
			}
		}

		if ( empty( $matching_templates ) ) {
			return null;
		}

		// Sort by priority (highest first) - more specific rules win
		usort(
			$matching_templates,
			function ( $a, $b ) {
				return $b['priority'] - $a['priority'];
			},
		);

		// Return the template with highest priority
		return $matching_templates[0]['id'];
	}

	/**
	 * Get template priority based on rule specificity
	 *
	 * @param int $template_id template post ID
	 *
	 * @return int Priority value (higher = more specific)
	 */
	private function get_template_priority( $template_id ) {
		$display_rules = get_field( 'display_rules', $template_id );

		if ( !$display_rules || !is_array( $display_rules ) ) {
			return 0;
		}

		$max_priority = 0;

		// Get the highest priority from all display rules
		foreach ( $display_rules as $rule ) {
			$rule_value = $rule['rule'] ?? '';
			$priority   = $this->get_rule_priority( $rule_value );

			if ( $priority > $max_priority ) {
				$max_priority = $priority;
			}
		}

		return $max_priority;
	}

	/**
	 * Get priority for a specific rule
	 *
	 * @param string $rule_value rule value
	 *
	 * @return int priority value (higher = more specific)
	 */
	private function get_rule_priority( $rule_value ) {
		// Specific pages have highest priority
		if ( 'specifics' === $rule_value ) {
			return 100;
		}

		// Special pages (404, search, shop, etc.)
		if ( str_starts_with( $rule_value, 'special-' ) ) {
			return 50;
		}

		// Post type specific rules
		if ( str_starts_with( $rule_value, 'post_type|' ) ) {
			$parts = explode( '|', $rule_value );

			// Taxonomy archives (most specific)
			if ( 4 === count( $parts ) ) {
				return 40;
			}

			// Post type archives
			if ( 3 === count( $parts ) ) {
				return 30;
			}

			// All posts of a type
			return 20;
		}

		// Basic rules (singulars, archives)
		if ( 'basic-singulars' === $rule_value || 'basic-archives' === $rule_value ) {
			return 10;
		}

		// Global (lowest priority)
		if ( 'basic-global' === $rule_value ) {
			return 1;
		}

		return 0;
	}

	/**
	 * Get template content by type
	 *
	 * @param string $type template type (header, footer, 404)
	 *
	 * @return string
	 */
	private function get_template_content( $type ) {
		$template_id = $this->get_template_by_type( $type );

		if ( !$template_id ) {
			return '';
		}

		if ( !class_exists( '\Elementor\Plugin' ) ) {
			return '';
		}

		if ( class_exists( '\Elementor\Core\Files\CSS\Post' ) ) {
			$css_file = new \Elementor\Core\Files\CSS\Post( $template_id );
			$css_file->enqueue();
		}

		$content = \Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $template_id );

		if ( empty( $content ) ) {
			return '';
		}

		return sprintf(
			'<div class="vlt-tp vlt-tp--%s" data-template-id="%d">%s</div>',
			esc_attr( $type ),
			$template_id,
			$content,
		);
	}
}
?>