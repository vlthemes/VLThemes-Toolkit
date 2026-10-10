/**
 * Filters panel
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import Hint from '../hint.js';
import StyleSelect from '../style-select.js';
import AlignControl from '../align-control.js';

const { filterTemplates } = window.vltGridEditor;

export default function FiltersPanel( { config, update, replace, source } ) {
	const filters = config.filters;
	const taxonomies = source?.taxonomies || [];

	if ( ! taxonomies.length ) {
		return <Hint status="info">{ __( 'The selected source has no taxonomies, so filters are not available.', 'toolkit' ) }</Hint>;
	}

	const toggle = ( enabled ) => replace( { ...config, filters: { ...filters, enabled, taxonomy: filters.taxonomy || taxonomies[ 0 ].value } } );

	return (
		<>
			<ToggleControl label={ __( 'Show filters', 'toolkit' ) } checked={ filters.enabled } onChange={ toggle } __nextHasNoMarginBottom />

			{ filters.enabled && (
				<>
					<SelectControl
						label={ __( 'Filter by', 'toolkit' ) }
						help={ __( 'If the query is limited to some terms of this taxonomy, only those (and their children) are offered.', 'toolkit' ) }
						value={ filters.taxonomy }
						options={ taxonomies }
						onChange={ ( value ) => update( 'filters.taxonomy', value ) }
						__nextHasNoMarginBottom
					/>
					<StyleSelect
						value={ filters.template }
						templates={ filterTemplates }
						help={ __( 'Theme styles: filters/{Name}/filter.php in the theme.', 'toolkit' ) }
						onChange={ ( value ) => update( 'filters.template', value ) }
					/>
					{ ! filters.template && <AlignControl value={ filters.align } onChange={ ( value ) => update( 'filters.align', value ) } /> }
					<TextControl label={ __( '"All" label', 'toolkit' ) } placeholder={ __( 'All', 'toolkit' ) } value={ filters.all_label } onChange={ ( value ) => update( 'filters.all_label', value ) } __nextHasNoMarginBottom />
					<ToggleControl label={ __( 'Show item counts', 'toolkit' ) } checked={ filters.show_count } onChange={ ( value ) => update( 'filters.show_count', value ) } __nextHasNoMarginBottom />
				</>
			) }
		</>
	);
}
