<?php

/**
 * @author: VLThemes
 * @version: 1.0
 */

if (! class_exists('TGM_Plugin_Activation')) {
	return false;
}

$plugin_table = new TGMPA_List_Table;

// Return early if processing a plugin installation action.
if ((('tgmpa-bulk-install' === $plugin_table->current_action() || 'tgmpa-bulk-update' === $plugin_table->current_action()))) {
	return;
}

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
	<?php $plugin_table->views(); ?>

	<form id="tgmpa-plugins" action="" method="post">
		<input type="hidden" name="tgmpa-page" value="<?php echo esc_attr($plugin_table->menu); ?>">
		<input type="hidden" name="plugin_status" value="<?php echo esc_attr($plugin_table->view_context); ?>">
		<?php $plugin_table->display(); ?>
	</form>

</div>