/**
 * Skin panel — the card template: the plugin's neutral built-in one, or the theme's skins
 */
import { __, sprintf } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';
import Hint from '../hint.js';

const { skins, skinsPath } = window.vltGridEditor;

export default function SkinPanel( { config, update } ) {
	if ( ! skins.length ) {
		return (
			<Hint status="warning">
				{ /* translators: %s: skin path inside the theme */ }
				{ sprintf( __( 'No skins found. Add %s to your (child) theme.', 'toolkit' ), skinsPath ) }
			</Hint>
		);
	}

	return (
		<SelectControl
			label={ __( 'Skin', 'toolkit' ) }
			hideLabelFromVision
			/* translators: %s: skin path inside the theme */
			help={ sprintf( __( 'Default is built in. The theme adds its own skins in %s (a "default" folder there replaces the built-in one).', 'toolkit' ), skinsPath ) }
			value={ config.skin }
			options={ skins }
			onChange={ ( value ) => update( 'skin', value ) }
			__nextHasNoMarginBottom
		/>
	);
}
