<?php

namespace VLT\Toolkit\Widgets;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recent Posts Widget
 */
class RecentPosts extends PostsWidget {
	/**
	 * Constructor
	 */
	public function __construct() {
		$widget_options = [
			'classname'   => 'vlt-widget-recent-posts',
			'description' => esc_html__( 'Displays recent blog posts.', 'toolkit' ),
		];

		parent::__construct(
			'vlt_widget_recent_posts',
			esc_html__( 'VLThemes: Recent Posts', 'toolkit' ),
			$widget_options,
		);
	}

	/**
	 * Output widget content
	 *
	 * @param array $args     widget arguments
	 * @param array $instance widget instance
	 */
	public function widget( $args, $instance ) {
		if ( !isset( $args['widget_id'] ) ) {
			$args['widget_id'] = $this->id;
		}

		$title = !empty( $instance['title'] ) ? $instance['title'] : '';
		$title = apply_filters( 'widget_title', $title, $instance, $this->id_base );

		$posts_count = !empty( $instance['posts_count'] ) ? absint( $instance['posts_count'] ) : 5;
		$layout      = !empty( $instance['layout'] ) ? $instance['layout'] : 'list';

		// Query recent posts
		$query_args = [
			'post_type'           => 'post',
			'posts_per_page'      => $posts_count,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
		];

		$query = new \WP_Query( $query_args );

		if ( !$query->have_posts() ) {
			wp_reset_postdata();

			return;
		}

		// Output widget
		echo $args['before_widget'];

		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}

		switch ( $layout ) {
			case 'list':
			default:
				while ( $query->have_posts() ) {
					$query->the_post();
					$this->render_list_item( $query->post );
				}

				break;

			case 'slider':
				?>
<div class="vlt-widget-post-slider swiper-container swiper"
	data-tooltip="<?php esc_attr_e( 'Swipe', 'toolkit' ); ?>">
	<div class="swiper-wrapper">
		<?php
						while ( $query->have_posts() ) {
							$query->the_post();
							$this->render_slider_item( $query->post );
						}
				?>
	</div>
	<div class="vlt-slider-controls">
		<div class="vlt-swiper-pagination vlt-swiper-pagination--style-1"></div>
	</div>
</div>
<?php
				break;
		}

		echo $args['after_widget'];

		wp_reset_postdata();
	}

	/**
	 * Output widget form
	 *
	 * @param array $instance widget instance
	 */
	public function form( $instance ) {
		// Set default values
		$defaults = [
			'title'       => '',
			'posts_count' => 5,
			'layout'      => 'list',
		];

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title       = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$posts_count = isset( $instance['posts_count'] ) ? absint( $instance['posts_count'] ) : 5;
		$layout      = isset( $instance['layout'] ) ? esc_attr( $instance['layout'] ) : 'list';
		?>
<p>
	<label
		for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
		<?php esc_html_e( 'Title:', 'toolkit' ); ?>
	</label>
	<input class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
		type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>

<p>
	<label
		for="<?php echo esc_attr( $this->get_field_id( 'posts_count' ) ); ?>">
		<?php esc_html_e( 'Number of Posts:', 'toolkit' ); ?>
	</label>
	<input class="tiny-text"
		id="<?php echo esc_attr( $this->get_field_id( 'posts_count' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'posts_count' ) ); ?>"
		type="number" min="1" max="20" step="1"
		value="<?php echo esc_attr( $posts_count ); ?>" />
	<br>
	<small><?php esc_html_e( 'Enter the number of posts to display (1-20).', 'toolkit' ); ?></small>
</p>

<p>
	<label
		for="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>">
		<?php esc_html_e( 'Layout:', 'toolkit' ); ?>
	</label>
	<select class="widefat"
		id="<?php echo esc_attr( $this->get_field_id( 'layout' ) ); ?>"
		name="<?php echo esc_attr( $this->get_field_name( 'layout' ) ); ?>">
		<option value="list" <?php selected( $layout, 'list' ); ?>>
			<?php esc_html_e( 'List', 'toolkit' ); ?>
		</option>
		<option value="slider" <?php selected( $layout, 'slider' ); ?>>
			<?php esc_html_e( 'Slider', 'toolkit' ); ?>
		</option>
	</select>
	<br>
	<small><?php esc_html_e( 'Choose how to display the posts.', 'toolkit' ); ?></small>
</p>
<?php
	}

	/**
	 * Update widget instance
	 *
	 * @param array $new_instance new instance
	 * @param array $old_instance old instance
	 *
	 * @return array updated instance
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		// Sanitize and save all fields
		$instance['title']       = sanitize_text_field( $new_instance['title'] );
		$instance['posts_count'] = absint( $new_instance['posts_count'] );
		$instance['layout']      = sanitize_text_field( $new_instance['layout'] );

		// Validate posts count
		if ( $instance['posts_count'] < 1 ) {
			$instance['posts_count'] = 5;
		}

		if ( $instance['posts_count'] > 20 ) {
			$instance['posts_count'] = 20;
		}

		// Validate layout
		if ( !in_array( $instance['layout'], [ 'list', 'slider' ], true ) ) {
			$instance['layout'] = 'list';
		}

		return $instance;
	}
}
?>