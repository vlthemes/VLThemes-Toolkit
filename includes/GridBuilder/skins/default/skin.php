<?php
/**
 * Skin Name: Default (built-in)
 * Description: Neutral card shipped with the plugin: image, terms and title, in the theme's font and colours.
 *
 * Used until the theme has skins of its own. A theme replaces it with a folder of the same name:
 * your-theme/vlthemes-toolkit/grid-builder/skins/default/skin.php (and style.css), or adds others next to it.
 *
 * Variables: $item (normalised item, see Sources\SourceInterface), $index, $config (layout config), $source.
 *
 * @package VLT\Toolkit\GridBuilder
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Terms of the filter taxonomy, or of the source's first one
$vlt_taxonomy = $config['filters']['taxonomy'] ?: (string) array_key_first( $source ? $source->get_taxonomies() : [] );
$vlt_terms    = $source && $vlt_taxonomy ? wp_list_pluck( $source->get_item_terms( $item, $vlt_taxonomy ), 'name' ) : [];
$vlt_tag      = $item['url'] ? 'a' : 'div';
?>
<<?php echo esc_attr( $vlt_tag ); ?> class="vlt-gb-card"<?php echo $item['url'] ? ' href="' . esc_url( $item['url'] ) . '"' : ''; ?>>
	<span class="vlt-gb__media vlt-gb-card__media<?php echo $item['image_id'] ? '' : ' is-empty'; ?>">
		<?php
		if ( $item['image_id'] ) {
			echo wp_get_attachment_image( $item['image_id'], 'large', false, [ 'loading' => 'lazy' ] );
		}
		?>
	</span>
	<span class="vlt-gb-card__body">
		<?php if ( $vlt_terms ) : ?>
			<span class="vlt-gb-card__terms"><?php echo esc_html( implode( ', ', $vlt_terms ) ); ?></span>
		<?php endif; ?>
		<span class="vlt-gb-card__title"><?php echo esc_html( $item['title'] ); ?></span>
	</span>
</<?php echo esc_attr( $vlt_tag ); ?>>
