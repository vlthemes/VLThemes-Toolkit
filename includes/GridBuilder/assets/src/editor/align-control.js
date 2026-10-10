/**
 * Alignment of the built-in filter / pagination style
 */
import { __ } from '@wordpress/i18n';
import {
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
} from '@wordpress/components';

export default function AlignControl( { value, onChange } ) {
	return (
		<ToggleGroupControl label={ __( 'Alignment', 'toolkit' ) } value={ value } onChange={ onChange } isBlock __nextHasNoMarginBottom __next40pxDefaultSize>
			<ToggleGroupControlOptionIcon value="left" icon="editor-alignleft" label={ __( 'Left', 'toolkit' ) } />
			<ToggleGroupControlOptionIcon value="center" icon="editor-aligncenter" label={ __( 'Center', 'toolkit' ) } />
			<ToggleGroupControlOptionIcon value="right" icon="editor-alignright" label={ __( 'Right', 'toolkit' ) } />
		</ToggleGroupControl>
	);
}
