/**
 * Style picker of the filter / pagination: the built-in neutral style, then the theme's templates
 */
import { __ } from '@wordpress/i18n';
import { SelectControl } from '@wordpress/components';

export default function StyleSelect( { value, templates, help, onChange } ) {
	return (
		<SelectControl label={ __( 'Style', 'toolkit' ) } help={ help } value={ value } onChange={ onChange } __nextHasNoMarginBottom>
			<optgroup label={ __( 'Built-in', 'toolkit' ) }>
				<option value="">{ __( 'Default', 'toolkit' ) }</option>
			</optgroup>
			{ !! templates.length && (
				<optgroup label={ __( 'Theme', 'toolkit' ) }>
					{ templates.map( ( template ) => (
						<option key={ template.value } value={ template.value }>
							{ template.label }
						</option>
					) ) }
				</optgroup>
			) }
		</SelectControl>
	);
}
