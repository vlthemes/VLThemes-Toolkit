<?php

namespace VLT\Toolkit\Widgets;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract Posts Widget Base.
 */
abstract class PostsWidget extends \WP_Widget {
	protected $attrs = [];

	/**
	 * Render post in list layout.
	 *
	 * @param \WP_Post $post post object
	 */
	protected function render_list_item( $post ) {
		do_action( 'vlt_toolkit_widget_render_list_item', $post, $this->attrs, $this );
	}

	protected function render_before_slider() {
		do_action( 'vlt_toolkit_widget_before_slider', $this->attrs, $this );
	}

	protected function render_after_slider() {
		do_action( 'vlt_toolkit_widget_after_slider', $this->attrs, $this );
	}

	/**
	 * Render post in slider layout (swiper slide).
	 *
	 * @param \WP_Post $post post object
	 */
	protected function render_slider_item( $post ) {
		do_action( 'vlt_toolkit_widget_render_slider_item', $post, $this->attrs, $this );
	}
}
?>
