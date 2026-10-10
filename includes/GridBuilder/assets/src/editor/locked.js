/**
 * Options that need an activated theme: greyed out and unreachable (inert), with a note linking to the activation page
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef } from '@wordpress/element';
import Hint from './hint.js';

const { activated, activateUrl } = window.vltGridEditor;

export default function Locked( { children } ) {
	const ref = useRef();

	// inert: no clicks, no keyboard focus inside (set directly — React 18 has no boolean inert prop)
	useEffect( () => {
		ref.current?.setAttribute( 'inert', '' );
	}, [] );

	if ( activated ) {
		return children;
	}

	return (
		<>
			<Hint status="warning">
				{ __( 'Activate your theme to use this option.', 'toolkit' ) }{ ' ' }
				<a href={ activateUrl }>{ __( 'Activate theme', 'toolkit' ) }</a>
			</Hint>
			<div ref={ ref } className="vlt-grid-editor__locked" aria-disabled="true">
				{ children }
			</div>
		</>
	);
}
