/**
 * Pagination panel: on/off switch + style (stored as pagination.type: none | numbers | loadmore)
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl, ToggleControl } from '@wordpress/components';
import { useRef } from '@wordpress/element';
import Hint from '../hint.js';
import StyleSelect from '../style-select.js';
import AlignControl from '../align-control.js';

const { paginationTemplates } = window.vltGridEditor;

export default function PaginationPanel( { config, update } ) {
	const pagination = config.pagination;
	const enabled = pagination.type !== 'none';

	// Switching off and on again brings back the style picked before
	const lastStyle = useRef( enabled ? pagination.type : 'numbers' );

	if ( enabled ) {
		lastStyle.current = pagination.type;
	}

	return (
		<>
			<ToggleControl
				label={ __( 'Show pagination', 'toolkit' ) }
				help={ __( 'Pages are loaded over AJAX and keep the current filter.', 'toolkit' ) }
				checked={ enabled }
				onChange={ ( checked ) => update( 'pagination.type', checked ? lastStyle.current : 'none' ) }
				__nextHasNoMarginBottom
			/>

			{ enabled && -1 === config.query.per_page && (
				<Hint status="warning">{ __( 'Per page is -1 (show all), so there is nothing to paginate.', 'toolkit' ) }</Hint>
			) }

			{ enabled && (
				<SelectControl
					label={ __( 'Type', 'toolkit' ) }
					value={ pagination.type }
					options={ [
						{ value: 'numbers', label: __( 'Page numbers', 'toolkit' ) },
						{ value: 'loadmore', label: __( 'Load More button', 'toolkit' ) },
					] }
					onChange={ ( value ) => update( 'pagination.type', value ) }
					__nextHasNoMarginBottom
				/>
			) }

			{ enabled && (
				<StyleSelect
					value={ pagination.template }
					templates={ paginationTemplates }
					help={ __( 'Theme styles: pagination/{Name}/pagination.php in the theme.', 'toolkit' ) }
					onChange={ ( value ) => update( 'pagination.template', value ) }
				/>
			) }

			{ enabled && ! pagination.template && <AlignControl value={ pagination.align } onChange={ ( value ) => update( 'pagination.align', value ) } /> }

			{ pagination.type === 'loadmore' && (
				<>
					<TextControl label={ __( 'Button label', 'toolkit' ) } placeholder={ __( 'Load More', 'toolkit' ) } value={ pagination.load_more_label } onChange={ ( value ) => update( 'pagination.load_more_label', value ) } __nextHasNoMarginBottom />
					<div className="vlt-grid-editor__row is-2">
						<TextControl label={ __( 'While loading', 'toolkit' ) } placeholder={ __( 'Loading…', 'toolkit' ) } value={ pagination.loading_label } onChange={ ( value ) => update( 'pagination.loading_label', value ) } __nextHasNoMarginBottom />
						<TextControl label={ __( 'No more items', 'toolkit' ) } placeholder={ __( 'No more items', 'toolkit' ) } value={ pagination.no_more_label } onChange={ ( value ) => update( 'pagination.no_more_label', value ) } __nextHasNoMarginBottom />
					</div>
				</>
			) }
		</>
	);
}
