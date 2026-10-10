/**
 * Layout config + custom CSS bound to the post meta (saved with the post, the WordPress way)
 */
import { useEntityProp } from '@wordpress/core-data';
import { useSelect } from '@wordpress/data';
import { useCallback, useMemo } from '@wordpress/element';

const { metaConfig, metaCss, defaults, canEditCss } = window.vltGridEditor;

/**
 * Deep merge of plain objects (arrays and scalars from `value` win)
 */
function merge( base, value ) {
	if ( ! value || typeof value !== 'object' || Array.isArray( value ) || ! base || typeof base !== 'object' || Array.isArray( base ) ) {
		return value === undefined ? base : value;
	}

	const out = { ...base };

	Object.keys( value ).forEach( ( key ) => {
		out[ key ] = merge( base[ key ], value[ key ] );
	} );

	return out;
}

/**
 * Immutable set by "a.b.c" path
 */
function setIn( object, path, value ) {
	const [ key, ...rest ] = path.split( '.' );

	return { ...object, [ key ]: rest.length ? setIn( object[ key ] || {}, rest.join( '.' ), value ) : value };
}

export default function useLayout() {
	const postType = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostType(), [] );
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const stored = meta?.[ metaConfig ] || '';

	const config = useMemo( () => {
		let saved = {};

		try {
			saved = stored ? JSON.parse( stored ) : {};
		} catch {}

		return merge( defaults, saved );
	}, [ stored ] );

	const write = useCallback(
		( changes ) => {
			const next = { ...meta, ...changes };

			// Users without edit_css never send the CSS key: on a new layout it has no stored value yet and the REST API would reject it
			if ( ! canEditCss ) {
				delete next[ metaCss ];
			}

			setMeta( next );
		},
		[ meta, setMeta ]
	);

	const update = useCallback( ( path, value ) => write( { [ metaConfig ]: JSON.stringify( setIn( config, path, value ) ) } ), [ config, write ] );
	const replace = useCallback( ( next ) => write( { [ metaConfig ]: JSON.stringify( next ) } ), [ write ] );
	const setCss = useCallback( ( css ) => write( { [ metaCss ]: css } ), [ write ] );

	return { config, update, replace, css: meta?.[ metaCss ] || '', setCss };
}
