/**
 * admin-ajax requests of the Grid Layout editor (nonce + capability checked in Ajax.php)
 */
const { ajaxUrl, nonce } = window.vltGridEditor;

export function request( action, params = {} ) {
	const body = new window.FormData();
	body.append( 'action', action );
	body.append( 'nonce', nonce );

	Object.entries( params ).forEach( ( [ key, value ] ) => body.append( key, value ) );

	return window
		.fetch( ajaxUrl, { method: 'POST', body, credentials: 'same-origin' } )
		.then( ( response ) => response.json() )
		.then( ( response ) => {
			if ( ! response || ! response.success ) {
				throw new Error( 'vlt-grid: request failed' );
			}

			return response.data;
		} );
}
