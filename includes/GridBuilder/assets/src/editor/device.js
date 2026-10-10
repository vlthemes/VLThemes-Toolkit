/**
 * Device being edited (desktop / tablet / mobile), shared by the Layout panel switch and the preview
 *
 * Not the editor's own device preview: its canvas widths (780 / 360px minus padding) don't match the grid
 * breakpoints, so its "Tablet" lands on the mobile rules. The preview renders at our device widths instead.
 */
import { useSyncExternalStore } from '@wordpress/element';

const { breakpoints } = window.vltGridEditor;

let current = 'desktop';
const listeners = new Set();

const subscribe = ( listener ) => {
	listeners.add( listener );

	return () => listeners.delete( listener );
};

export function setDevice( device ) {
	current = device;
	listeners.forEach( ( listener ) => listener() );
}

export function useDevice() {
	return [ useSyncExternalStore( subscribe, () => current ), setDevice ];
}

/**
 * Width a device's preview is rendered at, in px — always inside that device's range, at real size (no scaling;
 * wider than the canvas scrolls): desktop = the canvas, but never narrower than just past the tablet breakpoint;
 * tablet = the tablet breakpoint; mobile = a phone width inside the mobile range
 *
 * @param {string} device    Device
 * @param {number} available Canvas width for the preview
 * @return {number} Width
 */
export function previewWidth( device, available = 0 ) {
	if ( 'tablet' === device ) {
		return breakpoints.tablet;
	}

	if ( 'mobile' === device ) {
		return Math.min( 375, breakpoints.mobile );
	}

	return Math.max( available, breakpoints.tablet + 1 );
}
