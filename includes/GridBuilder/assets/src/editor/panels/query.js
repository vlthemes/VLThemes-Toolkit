/**
 * Query Builder panel
 */
import { __ } from '@wordpress/i18n';
import { Button, CheckboxControl, FormTokenField, SelectControl, TextareaControl, TextControl } from '@wordpress/components';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { useSelect } from '@wordpress/data';
import { useEffect, useMemo, useState } from '@wordpress/element';
import { request } from '../api.js';
import Hint from '../hint.js';
import Group from '../group.js';

const termsCache = new Map();

/**
 * Terms of several taxonomies: { taxonomy: [ terms ] } (missing key = still loading), cached per page view
 */
function useTermsMap( taxonomies ) {
	const key = taxonomies.join( ',' );
	const [ map, setMap ] = useState( {} );

	useEffect( () => {
		let active = true;

		taxonomies.forEach( ( taxonomy ) => {
			const done = ( list ) => active && setMap( ( current ) => ( { ...current, [ taxonomy ]: list } ) );

			if ( termsCache.has( taxonomy ) ) {
				done( termsCache.get( taxonomy ) );
				return;
			}

			request( 'vlt_toolkit_grid_terms', { taxonomy } )
				.then( ( list ) => {
					termsCache.set( taxonomy, list );
					done( list );
				} )
				// A taxonomy that can't be loaded behaves like an empty one: hidden
				.catch( () => done( [] ) );
		} );

		return () => {
			active = false;
		};
	}, [ key ] ); // eslint-disable-line react-hooks/exhaustive-deps

	return map;
}

function TermsControl( { label, terms, selected, onChange } ) {
	return (
		<fieldset className="vlt-grid-editor__terms">
			<legend>{ label }</legend>
			{ terms.map( ( term ) => (
				<CheckboxControl
					key={ term.id }
					label={ term.name }
					checked={ selected.includes( term.id ) }
					onChange={ ( checked ) => onChange( checked ? [ ...selected, term.id ] : selected.filter( ( id ) => id !== term.id ) ) }
					__nextHasNoMarginBottom
				/>
			) ) }
		</fieldset>
	);
}

/**
 * Pick posts of the selected post type: searchable multi-select (tokens "Title (#ID)"), used for
 * "Specific items" and "Exclude items"; suggestions come from a search as you type
 */
function PostsControl( { label, help, source, value, onChange } ) {
	const [ titles, setTitles ] = useState( {} );
	const [ suggestions, setSuggestions ] = useState( [] );

	// Resolve stored IDs to titles once
	useEffect( () => {
		const missing = value.filter( ( id ) => ! titles[ id ] );

		if ( missing.length ) {
			request( 'vlt_toolkit_grid_posts', { source, include: missing.join( ',' ) } )
				.then( ( posts ) => setTitles( ( current ) => ( { ...current, ...Object.fromEntries( posts.map( ( post ) => [ post.id, post.title ] ) ) } ) ) )
				.catch( () => {} );
		}
	}, [ source, value.join( ',' ) ] ); // eslint-disable-line react-hooks/exhaustive-deps

	const tokenLabel = ( id ) => `${ titles[ id ] || __( 'Post', 'toolkit' ) } (#${ id })`;

	const search = ( text ) => {
		if ( text.length < 2 ) {
			return;
		}

		request( 'vlt_toolkit_grid_posts', { source, search: text } )
			.then( ( posts ) => {
				setTitles( ( current ) => ( { ...current, ...Object.fromEntries( posts.map( ( post ) => [ post.id, post.title ] ) ) } ) );
				setSuggestions( posts.map( ( post ) => `${ post.title } (#${ post.id })` ) );
			} )
			.catch( () => {} );
	};

	return (
		<FormTokenField
			label={ label }
			value={ value.map( tokenLabel ) }
			suggestions={ suggestions }
			onInputChange={ search }
			onChange={ ( tokens ) => onChange( [ ...new Set( tokens.map( ( token ) => Number( String( token ).match( /\(#(\d+)\)$/ )?.[ 1 ] ) ).filter( Boolean ) ) ] ) }
			__experimentalShowHowTo={ false }
			__experimentalExpandOnFocus
			placeholder={ __( 'Type to search…', 'toolkit' ) }
			help={ help }
			__nextHasNoMarginBottom
		/>
	);
}

/**
 * Plain text of rendered HTML (REST "rendered" fields), parsed inertly
 */
const plain = ( html ) => new window.DOMParser().parseFromString( html || '', 'text/html' ).body.textContent.trim();

/**
 * Overrides of one image inside this layout; empty fields fall back to the Media Library
 */
function ImageDetails( { media, value, labels, onChange, onClose } ) {
	const set = ( key, next ) => onChange( { ...value, [ key ]: next } );

	return (
		<div className="vlt-grid-editor__image">
			<div className="vlt-grid-editor__image-head">
				<img src={ media.media_details?.sizes?.thumbnail?.source_url || media.source_url } alt="" />
				<span>{ plain( media.title?.rendered ) || `#${ media.id }` }</span>
				<Button icon="no-alt" size="small" label={ __( 'Close', 'toolkit' ) } onClick={ onClose } />
			</div>
			<TextControl label={ __( 'Title', 'toolkit' ) } value={ value.title || '' } placeholder={ plain( media.title?.rendered ) } onChange={ ( next ) => set( 'title', next ) } __nextHasNoMarginBottom />
			<TextareaControl label={ __( 'Description', 'toolkit' ) } rows={ 2 } value={ value.caption || '' } placeholder={ plain( media.caption?.rendered ) } onChange={ ( next ) => set( 'caption', next ) } __nextHasNoMarginBottom />
			<TextControl type="url" label={ __( 'Link', 'toolkit' ) } value={ value.link || '' } placeholder={ __( 'Full-size image', 'toolkit' ) } onChange={ ( next ) => set( 'link', next ) } __nextHasNoMarginBottom />
			<FormTokenField
				label={ __( 'Categories', 'toolkit' ) }
				value={ value.labels || [] }
				suggestions={ labels }
				onChange={ ( tokens ) => set( 'labels', tokens.map( ( token ) => String( token?.value ?? token ).trim() ).filter( Boolean ) ) }
				__experimentalShowHowTo={ false }
				__experimentalExpandOnFocus
				__nextHasNoMarginBottom
			/>
			<p className="vlt-grid-editor__muted">{ __( 'Only for this layout; empty fields use the Media Library. Categories work as filter terms.', 'toolkit' ) }</p>
		</div>
	);
}

function ImagesControl( { value, data, onChange } ) {
	const [ current, setCurrent ] = useState( 0 );
	const media = useSelect( ( select ) => value.map( ( id ) => select( 'core' ).getMedia( id, { context: 'view' } ) ).filter( Boolean ), [ value.join( ',' ) ] ); // eslint-disable-line react-hooks/exhaustive-deps
	const labels = useMemo( () => [ ...new Set( Object.values( data ).flatMap( ( entry ) => entry.labels || [] ) ) ], [ data ] );
	const active = media.find( ( item ) => item.id === current );

	// Overrides of images that left the selection are dropped with them
	const select = ( items ) => {
		const ids = items.map( ( item ) => item.id );
		onChange( ids, Object.fromEntries( Object.entries( data ).filter( ( [ id ] ) => ids.includes( Number( id ) ) ) ) );
	};

	const edited = ( id ) => Object.values( data[ id ] || {} ).some( ( field ) => ( Array.isArray( field ) ? field.length : field ) );

	return (
		<div className="vlt-grid-editor__images">
			<MediaUploadCheck fallback={ <Hint status="warning">{ __( 'You are not allowed to choose media.', 'toolkit' ) }</Hint> }>
				<MediaUpload
					multiple
					gallery
					allowedTypes={ [ 'image' ] }
					value={ value }
					onSelect={ select }
					render={ ( { open } ) => (
						<Button variant="secondary" onClick={ open }>
							{ value.length ? __( 'Edit images', 'toolkit' ) : __( 'Choose images', 'toolkit' ) }
						</Button>
					) }
				/>
			</MediaUploadCheck>
			{ !! value.length && (
				<>
					<ul className="vlt-grid-editor__thumbs">
						{ media.map( ( item ) => (
							<li key={ item.id }>
								<button
									type="button"
									className={ [ current === item.id && 'is-active', edited( item.id ) && 'is-edited' ].filter( Boolean ).join( ' ' ) || undefined }
									aria-pressed={ current === item.id }
									aria-label={ plain( item.title?.rendered ) || `#${ item.id }` }
									onClick={ () => setCurrent( current === item.id ? 0 : item.id ) }
								>
									<img src={ item.media_details?.sizes?.thumbnail?.source_url || item.source_url } alt="" />
								</button>
							</li>
						) ) }
					</ul>
					{ active ? (
						<ImageDetails
							key={ active.id }
							media={ active }
							value={ data[ active.id ] || {} }
							labels={ labels }
							onChange={ ( entry ) => onChange( value, { ...data, [ active.id ]: entry } ) }
							onClose={ () => setCurrent( 0 ) }
						/>
					) : (
						<p className="vlt-grid-editor__muted">{ __( 'Click an image to set its title, description, link and categories.', 'toolkit' ) }</p>
					) }
				</>
			) }
		</div>
	);
}

/**
 * Content: where items come from and which of them
 */
export function ContentPanel( { config, update, replace, source, sources } ) {
	const query = config.query;
	const taxonomies = source?.taxonomies || [];
	const selected = useMemo( () => query.taxonomies || {}, [ query.taxonomies ] );
	const termsMap = useTermsMap( source?.isPostType ? taxonomies.map( ( taxonomy ) => taxonomy.value ) : [] );

	// Taxonomies without terms are hidden; the AND/OR switch only matters with two or more left
	const withTerms = taxonomies.filter( ( taxonomy ) => termsMap[ taxonomy.value ]?.length );

	// Switching source drops what belongs to the old one (terms, picked posts, filter taxonomy)
	const changeSource = ( value ) => {
		const next = sources.find( ( item ) => item.value === value );
		const orderby = next.orderby.some( ( option ) => option.value === query.orderby ) ? query.orderby : next.orderby[ 0 ]?.value;

		replace( {
			...config,
			query: { ...query, source: value, orderby, taxonomies: {}, include: [], exclude: [] },
			filters: { ...config.filters, enabled: false, taxonomy: '' },
		} );
	};

	return (
		<>
			<Group title={ __( 'Source', 'toolkit' ) }>
				<SelectControl label={ __( 'Post type', 'toolkit' ) } value={ query.source } options={ sources.map( ( { value, label } ) => ( { value, label } ) ) } onChange={ changeSource } __nextHasNoMarginBottom />
				{ source?.isMedia && (
					<ImagesControl
						value={ query.images }
						// PHP encodes an empty map as []
						data={ Array.isArray( query.image_data ) ? {} : query.image_data || {} }
						onChange={ ( images, data ) => replace( { ...config, query: { ...query, images, image_data: data } } ) }
					/>
				) }
			</Group>

			{ source?.isPostType && (
				<Group title={ __( 'Items', 'toolkit' ) }>
					<PostsControl
						label={ __( 'Specific items', 'toolkit' ) }
						help={ __( 'Show only these. Sort by "Selection order" to keep the order you picked them in.', 'toolkit' ) }
						source={ query.source }
						value={ query.include }
						onChange={ ( value ) => update( 'query.include', value ) }
					/>
					<PostsControl label={ __( 'Exclude items', 'toolkit' ) } source={ query.source } value={ query.exclude } onChange={ ( value ) => update( 'query.exclude', value ) } />
					<TextControl label={ __( 'Search', 'toolkit' ) } help={ __( 'Only items matching these words.', 'toolkit' ) } value={ query.search } onChange={ ( value ) => update( 'query.search', value ) } __nextHasNoMarginBottom />
				</Group>
			) }

			{ source?.isPostType && !! withTerms.length && (
				<Group title={ __( 'Terms', 'toolkit' ) }>
					{ withTerms.map( ( taxonomy ) => (
						<TermsControl
							key={ taxonomy.value }
							label={ taxonomy.label }
							terms={ termsMap[ taxonomy.value ] }
							selected={ selected[ taxonomy.value ] || [] }
							onChange={ ( ids ) => update( `query.taxonomies.${ taxonomy.value }`, ids ) }
						/>
					) ) }

					{ withTerms.length > 1 && (
						<SelectControl
							label={ __( 'Match terms of', 'toolkit' ) }
							value={ query.tax_relation }
							options={ [
								{ value: 'OR', label: __( 'Any taxonomy (OR)', 'toolkit' ) },
								{ value: 'AND', label: __( 'Every taxonomy (AND)', 'toolkit' ) },
							] }
							onChange={ ( value ) => update( 'query.tax_relation', value ) }
							__nextHasNoMarginBottom
						/>
					) }
				</Group>
			) }
		</>
	);
}

/**
 * Order & limit: sorting, how many, offset
 */
export function OrderPanel( { config, update, source } ) {
	const query = config.query;

	return (
		<>
			<Group title={ __( 'Sorting', 'toolkit' ) }>
			<div className="vlt-grid-editor__row is-2">
				<SelectControl label={ __( 'Order by', 'toolkit' ) } value={ query.orderby } options={ source?.orderby || [] } onChange={ ( value ) => update( 'query.orderby', value ) } __nextHasNoMarginBottom />
				<SelectControl
					label={ __( 'Order', 'toolkit' ) }
					value={ query.order }
					options={ [
						{ value: 'DESC', label: __( 'Descending', 'toolkit' ) },
						{ value: 'ASC', label: __( 'Ascending', 'toolkit' ) },
					] }
					onChange={ ( value ) => update( 'query.order', value ) }
					__nextHasNoMarginBottom
				/>
			</div>
			</Group>

			<Group title={ __( 'Limit', 'toolkit' ) }>
			<div className="vlt-grid-editor__row is-2">
				<TextControl
					type="number"
					min={ -1 }
					max={ 100 }
					label={ __( 'Per page', 'toolkit' ) }
					value={ query.per_page }
					onChange={ ( value ) => {
						const number = parseInt( value, 10 );
						update( 'query.per_page', -1 === number ? -1 : Math.max( 1, Math.min( 100, number || 1 ) ) );
					} }
					__nextHasNoMarginBottom
				/>
				<TextControl
					type="number"
					min={ 0 }
					max={ 1000 }
					label={ __( 'Offset', 'toolkit' ) }
					value={ query.offset }
					onChange={ ( value ) => update( 'query.offset', Math.max( 0, Math.min( 1000, parseInt( value, 10 ) || 0 ) ) ) }
					__nextHasNoMarginBottom
				/>
			</div>

			<p className="vlt-grid-editor__muted">
				{ -1 === query.per_page ? __( 'Showing all items — pagination is off.', 'toolkit' ) : __( 'Per page: 1–100, or -1 for all items.', 'toolkit' ) }{ ' ' }
				{ __( 'Offset skips the first items of the whole result once; pages start after them.', 'toolkit' ) }
			</p>
			</Group>
		</>
	);
}
