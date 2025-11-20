<?php

namespace VLT\Toolkit\Widgets;

if (! defined('ABSPATH')) {
	exit;
}

/**
 * Abstract Posts Widget Base
 */
abstract class PostsWidget extends \WP_Widget
{
	/**
	 * Render post thumbnail
	 *
	 * @param int    $post_id Post ID.
	 * @param string $size    Image size.
	 * @param string $ratio   Aspect ratio class.
	 */
	protected function render_thumbnail($post_id, $size = 'thumbnail', $ratio = '1x1'): void
	{
		if (! has_post_thumbnail($post_id)) {
			return;
		}
		?>
		<div class="vlt-widget-post__thumbnail">
			<a href="<?php echo esc_url(get_permalink($post_id)); ?>">
				<div class="vlt-aspect-ratio vlt-aspect-ratio--<?php echo esc_attr($ratio); ?>">
					<?php echo get_the_post_thumbnail($post_id, $size, [ 'loading' => 'lazy' ]); ?>
				</div>
			</a>
		</div>
		<?php
	}

	/**
	 * Render post title
	 *
	 * @param int $post_id Post ID.
	 */
	protected function render_title($post_id): void
	{
		?>
		<h6>
			<a href="<?php echo esc_url(get_permalink($post_id)); ?>">
				<?php echo esc_html(get_the_title($post_id)); ?>
			</a>
		</h6>
		<?php
	}

	/**
	 * Render post meta
	 *
	 * @param int  $post_id       Post ID.
	 * @param bool $show_date     Show date.
	 * @param bool $show_comments Show comments count.
	 */
	protected function render_meta($post_id, $show_date = true, $show_comments = false): void
	{
		?>
		<div class="vlt-widget-post__meta">
			<?php if ($show_date) : ?>
				<div class="vlt-widget-post__meta-date">
					<?php echo esc_html(get_the_date('', $post_id)); ?>
				</div>
			<?php endif; ?>

			<?php if ($show_comments && comments_open($post_id)) : ?>
				<div class="vlt-widget-post__meta-comments">
					<?php
					printf(
						esc_html(_n('%s Comment', '%s Comments', get_comments_number($post_id), 'toolkit')),
						number_format_i18n(get_comments_number($post_id)),
					);
				?>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render post excerpt
	 *
	 * @param int $post_id Post ID.
	 * @param int $length  Excerpt length.
	 */
	protected function render_excerpt($post_id, $length = 20): void
	{
		$excerpt = get_the_excerpt($post_id);

		if (empty($excerpt)) {
			return;
		}

		$excerpt = wp_trim_words($excerpt, $length, '...');
		?>
		<div class="vlt-widget-post__excerpt">
			<?php echo esc_html($excerpt); ?>
		</div>
		<?php
	}

	/**
	 * Render post in list layout
	 *
	 * @param \WP_Post $post Post object.
	 */
	protected function render_list_item($post): void
	{
		?>
		<div class="vlt-widget-post">
			<?php $this->render_thumbnail($post->ID, 'thumbnail', '1x1'); ?>
			<div class="vlt-widget-post__content">
				<?php $this->render_title($post->ID); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render post in slider layout (swiper slide)
	 *
	 * @param \WP_Post $post Post object.
	 */
	protected function render_slider_item($post): void
	{
		?>
		<div class="swiper-slide">
			<article class="vlt-widget-post">
				<?php $this->render_thumbnail($post->ID, 'medium', '4x3'); ?>
				<div class="vlt-widget-post__content">
					<?php $this->render_title($post->ID); ?>
				</div>
			</article>
		</div>
		<?php
	}
}
