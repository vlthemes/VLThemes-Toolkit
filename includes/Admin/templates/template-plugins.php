<?php

/**
 * @author: VLThemes
 * @version: 1.0
 */

if (! class_exists('TGM_Plugin_Activation')) {
	return false;
}

$plugin_table = new TGMPA_List_Table;
$instance     = TGM_Plugin_Activation::$instance;

// Force refresh of available plugin information so we'll know about manual updates/deletes.
wp_clean_plugins_cache(false);

?>

<div class="notice notice-info">
	<p><?php
		// translators: %s - theme name.
		printf(esc_html__('These plugins comes with %s theme. If you want full functionality from demo page, you should activate all of these plugins.', 'vlt-helper'), esc_html($this->theme_name));
		?></p>
</div>

<div class="tgmpa">

	<?php $plugin_table->prepare_items(); ?>

	<?php
	if (! empty($instance->message) && is_string($instance->message)) {
		echo wp_kses_post($instance->message);
	}
	?>

	<?php $plugin_table->views(); ?>

	<form id="tgmpa-plugins" action="<?php echo esc_url($instance->get_tgmpa_url()); ?>" method="post">
		<input type="hidden" name="tgmpa-page" value="<?php echo esc_attr($plugin_table->menu); ?>">
		<input type="hidden" name="plugin_status" value="<?php echo esc_attr($plugin_table->view_context); ?>">
		<?php $plugin_table->display(); ?>
	</form>

</div>