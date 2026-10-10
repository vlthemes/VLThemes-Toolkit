/**
 * Live preview of the unsaved settings: a real front-end page (Preview.php) with the theme's styles and scripts
 *
 * A moment after every change the settings are POSTed into the iframe (the Visual Portfolio approach), so the
 * grid looks and behaves as on the site. The page reports its height and state with postMessage. Each device
 * renders at its own width at real size (device.js: desktop = the canvas, tablet / mobile at the breakpoints),
 * so its rules always match; a device wider than the canvas scrolls sideways instead of being scaled. Filter / pagination requests inside carry the unsaved settings (Ajax::load accepts
 * them only from editors of the layout, with the nonce).
 */
import { useSelect } from '@wordpress/data';
import { useEffect, useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import Hint from './hint.js';
import { previewWidth, useDevice } from './device.js';

const DELAY = 600;

const STATES = {
	empty: __( 'No items match this query.', 'toolkit' ),
	'no-skin': __( 'The theme has no skin yet — showing the layout.', 'toolkit' ),
};

const { previewUrl, nonce } = window.vltGridEditor;

let frames = 0;

export default function Preview( { config, css } ) {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const [ state, setState ] = useState( { loading: true, error: false, status: 'ok', height: 0 } );
	const [ device ] = useDevice();
	const frame = useRef();
	const stage = useRef();
	const [ available, setAvailable ] = useState( 0 );
	const name = useRef( `vlt-grid-preview-${ ++frames }` );
	const configJson = JSON.stringify( config );

	// POST the settings into the frame: a hidden form targeting it (a page, not fetched HTML)
	useEffect( () => {
		const timer = window.setTimeout( () => {
			// The frame lives in the editor canvas (its own document): the form must be in that document too
			const owner = frame.current?.ownerDocument || document;
			const form = owner.createElement( 'form' );

			form.method = 'post';
			form.action = previewUrl;
			form.target = name.current;
			form.style.display = 'none';

			// Cross-origin isolated editor (WordPress 7.1+): the page has to join its agent cluster (Preview.php)
			const isolated = owner.defaultView?.crossOriginIsolated ? '1' : '';

			Object.entries( { post_id: postId, config: configJson, css, nonce, isolated } ).forEach( ( [ key, value ] ) => {
				const input = owner.createElement( 'input' );
				input.type = 'hidden';
				input.name = key;
				input.value = value ?? '';
				form.append( input );
			} );

			owner.body.append( form );
			setState( ( current ) => ( { ...current, loading: true } ) );
			form.submit();
			form.remove();
		}, DELAY );

		return () => window.clearTimeout( timer );
	}, [ postId, configJson, css ] );

	// Height and state from the page — only messages of our own frame count. The page posts to the window that
	// holds the frame, and the editor may move the block into its canvas iframe after the first render: listen
	// on the frame's current window, re-bound on every load.
	const listening = useRef( null );

	const onMessage = ( event ) => {
		if ( event.source !== frame.current?.contentWindow || ! event.data?.vltGridPreview ) {
			return;
		}

		setState( { loading: false, error: false, status: event.data.state || 'ok', height: event.data.height || 0 } );
	};

	const listen = () => {
		const view = frame.current?.ownerDocument?.defaultView;

		if ( view && listening.current !== view ) {
			listening.current?.removeEventListener( 'message', onMessage );
			view.addEventListener( 'message', onMessage );
			listening.current = view;
		}
	};

	useEffect( () => {
		listen();

		return () => listening.current?.removeEventListener( 'message', onMessage );
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Each load: bind the listener, then read the page directly (same origin). The empty frame's own about:blank
	// load is skipped; a page without the preview marker (an error page, a 403) is an error.
	const onLoad = () => {
		listen();

		let doc;

		try {
			doc = frame.current?.contentDocument;
		} catch {
			// Another origin (admin and site on different hosts): its messages report the state
			return;
		}

		if ( ! doc || 'about:blank' === doc.location?.href ) {
			return;
		}

		const marker = doc.body?.getAttribute( 'data-vlt-grid-state' );

		setState( marker
			? { loading: false, error: false, status: marker, height: Math.ceil( doc.body.getBoundingClientRect().height ) }
			: ( current ) => ( { ...current, loading: false, error: true } ) );
	};

	// Width the canvas gives the preview (the stage lives in the canvas document: its own ResizeObserver)
	useEffect( () => {
		const element = stage.current;
		const Observer = element?.ownerDocument?.defaultView?.ResizeObserver;

		if ( ! Observer ) {
			return;
		}

		const observer = new Observer( ( [ entry ] ) => setAvailable( Math.floor( entry.contentRect.width ) ) );
		observer.observe( element );

		return () => observer.disconnect();
	}, [] );

	const empty = 'empty' === state.status;
	const hidden = empty || state.error;
	const width = previewWidth( device, available );
	const overflows = available > 0 && width > available;

	return (
		<div className={ 'vlt-grid-editor__preview' + ( state.loading ? ' is-loading' : '' ) }>
			{ state.error && <Hint status="error">{ __( 'Could not render the preview.', 'toolkit' ) }</Hint> }
			{ STATES[ state.status ] && <p className="vlt-grid-editor__preview-note">{ STATES[ state.status ] }</p> }
			<div ref={ stage } className={ 'vlt-grid-editor__stage' + ( overflows ? ' is-overflowing' : '' ) }>
				{ /* Always mounted (the form targets it); nothing to show → hidden, only the note */ }
				<div
					className={ 'vlt-grid-editor__viewport is-' + device }
					style={ { width, height: hidden ? 0 : state.height, visibility: hidden ? 'hidden' : undefined } }
				>
					<iframe
						ref={ frame }
						name={ name.current }
						title={ __( 'Grid preview', 'toolkit' ) }
						// Theme scripts run inside: same origin for its cookies and AJAX, but no navigating the editor or popups
						sandbox="allow-same-origin allow-scripts"
						onLoad={ onLoad }
						className="vlt-grid-editor__frame"
						style={ { width, height: hidden ? 0 : state.height } }
					/>
				</div>
			</div>
			{ ! hidden && (
				<p className="vlt-grid-editor__viewport-info">
					{ overflows
						/* translators: %d: preview width in px */
						? sprintf( __( '%dpx — real size, wider than the canvas: scroll sideways', 'toolkit' ), width )
						/* translators: %d: preview width in px */
						: sprintf( __( '%dpx — real size', 'toolkit' ), width ) }
				</p>
			) }
		</div>
	);
}
