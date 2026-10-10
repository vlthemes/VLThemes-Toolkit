/**
 * Inserts: Template Parts (promo blocks, banners, forms…) placed between the items as their own grid cells
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, SelectControl, TextControl } from '@wordpress/components';
import Hint from '../hint.js';

const { templates, templatesUrl } = window.vltGridEditor;

const MAX = 10;

const clamp = ( value, min, max, fallback ) => {
	const number = parseInt( value, 10 );

	return Number.isNaN( number ) ? fallback : Math.max( min, Math.min( max, number ) );
};

export default function InsertsPanel( { config, update } ) {
	const inserts = config.inserts || [];
	const set = ( index, changes ) => update( 'inserts', inserts.map( ( insert, i ) => ( i === index ? { ...insert, ...changes } : insert ) ) );

	if ( ! templates.length ) {
		return (
			<Hint status="info">
				{ __( 'Insert promo blocks, banners or forms between the items. Create a Template Part first.', 'toolkit' ) }{ ' ' }
				<a href={ templatesUrl } target="_blank" rel="noreferrer">
					{ __( 'Template Parts', 'toolkit' ) }
				</a>
			</Hint>
		);
	}

	return (
		<>
			{ inserts.map( ( insert, index ) => (
				<div className="vlt-grid-editor__insert" key={ index }>
					<div className="vlt-grid-editor__insert-head">
						{ /* translators: %d: insert number */ }
						<span className="vlt-grid-editor__label">{ sprintf( __( 'Insert %d', 'toolkit' ), index + 1 ) }</span>
						<Button icon="trash" size="small" label={ __( 'Remove insert', 'toolkit' ) } onClick={ () => update( 'inserts', inserts.filter( ( _, i ) => i !== index ) ) } />
					</div>
					<SelectControl
						label={ __( 'Template part', 'toolkit' ) }
						value={ String( insert.template || '' ) }
						options={ [ { value: '', label: __( '— Select —', 'toolkit' ) }, ...templates ] }
						onChange={ ( value ) => set( index, { template: parseInt( value, 10 ) || 0 } ) }
						__nextHasNoMarginBottom
					/>
					<div className="vlt-grid-editor__row is-2">
						<TextControl type="number" min={ 1 } label={ __( 'Before item', 'toolkit' ) } value={ insert.position } onChange={ ( value ) => set( index, { position: clamp( value, 1, 1000, 1 ) } ) } __nextHasNoMarginBottom />
						<TextControl type="number" min={ 0 } label={ __( 'Repeat every', 'toolkit' ) } value={ insert.every } onChange={ ( value ) => set( index, { every: clamp( value, 0, 1000, 0 ) } ) } __nextHasNoMarginBottom />
					</div>
				</div>
			) ) }

			{ inserts.length < MAX && (
				<Button variant="secondary" icon="plus-alt2" onClick={ () => update( 'inserts', [ ...inserts, { template: 0, position: 1, every: 0 } ] ) }>
					{ __( 'Add insert', 'toolkit' ) }
				</Button>
			) }

			<p className="vlt-grid-editor__muted">
				{ __( 'Counted over the whole result, so inserts keep their places across pages and "Load More". Repeat every 0 = once. Unselected templates are not saved.', 'toolkit' ) }{ ' ' }
				<a href={ templatesUrl } target="_blank" rel="noreferrer">
					{ __( 'Template Parts', 'toolkit' ) }
				</a>
			</p>
		</>
	);
}
