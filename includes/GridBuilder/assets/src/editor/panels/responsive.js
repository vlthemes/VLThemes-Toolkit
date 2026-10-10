/**
 * Columns & spacing: columns per device and gaps, compact number fields in rows
 */
import { __, sprintf } from '@wordpress/i18n';
import { Icon, TextControl } from '@wordpress/components';
import Group from '../group.js';

const { breakpoints, tilesPatterns, layoutSettings } = window.vltGridEditor;

const clamp = ( value, min, max, fallback ) => {
	const number = parseInt( value, 10 );

	return Number.isNaN( number ) ? fallback : Math.max( min, Math.min( max, number ) );
};

// Justified sizes by row height; theme layout types can do without columns too ('columns' => false)
const usesColumns = ( type ) => 'justified' !== type && false !== layoutSettings[ type ]?.columns;

// Field label with the device icon (same icons as the Tiles device switch)
const deviceLabel = ( icon, text ) => (
	<span className="vlt-grid-editor__device-label">
		<Icon icon={ icon } size={ 16 } />
		{ text }
	</span>
);

/**
 * Which screens a device covers — for layout types without the Columns field (it carries this note otherwise)
 */
export function DeviceHint( { config, device } ) {
	if ( usesColumns( config.layout.type ) ) {
		return null;
	}

	const label = { desktop: __( 'Desktop', 'toolkit' ), tablet: __( 'Tablet', 'toolkit' ), mobile: __( 'Mobile', 'toolkit' ) }[ device ];
	const icon = { desktop: 'desktop', tablet: 'tablet', mobile: 'smartphone' }[ device ];
	const help = {
		/* translators: %d: breakpoint width in px */
		tablet: sprintf( __( 'Screens up to %dpx.', 'toolkit' ), breakpoints.tablet ),
		/* translators: %d: breakpoint width in px */
		mobile: sprintf( __( 'Screens up to %dpx.', 'toolkit' ), breakpoints.mobile ),
		desktop: __( 'Wider screens.', 'toolkit' ),
	}[ device ];

	return (
		<p className="vlt-grid-editor__muted vlt-grid-editor__device-hint">
			{ deviceLabel( icon, label ) } { help }
		</p>
	);
}

export default function ResponsivePanel( { config, update, device } ) {
	const { columns } = config.responsive;
	// Older layouts store one gap number for all devices: read it as per-device and save the whole set on change
	const perDevice = ( value ) => ( value && 'object' === typeof value ? value : { desktop: value, tablet: value, mobile: value } );
	const gapX = perDevice( config.responsive.gap_x );
	const gapY = perDevice( config.responsive.gap_y );
	const isTiles = config.layout.type === 'tiles';
	// Theme layout types can do without columns (Justified: row height and image ratio decide); gaps stay
	const hasColumns = usesColumns( config.layout.type );
	const key = { desktop: 'pattern', tablet: 'pattern_tablet', mobile: 'pattern_mobile' }[ device ];

	// Tiles: a device with its own pattern (desktop always has one) uses that pattern's columns — shown, locked
	const patternColumns = isTiles ? tilesPatterns.find( ( pattern ) => pattern.value === config.layout.tiles[ key ] )?.columns : null;
	const label = { desktop: __( 'Desktop', 'toolkit' ), tablet: __( 'Tablet', 'toolkit' ), mobile: __( 'Mobile', 'toolkit' ) }[ device ];
	const icon = { desktop: 'desktop', tablet: 'tablet', mobile: 'smartphone' }[ device ];
	const fallback = { desktop: 3, tablet: 2, mobile: 1 }[ device ];

	const help = {
		/* translators: %d: breakpoint width in px */
		tablet: sprintf( __( 'Screens up to %dpx.', 'toolkit' ), breakpoints.tablet ),
		/* translators: %d: breakpoint width in px */
		mobile: sprintf( __( 'Screens up to %dpx.', 'toolkit' ), breakpoints.mobile ),
		desktop: __( 'Wider screens.', 'toolkit' ),
	}[ device ];

	return (
		<>
			{ hasColumns && (
				<Group title={ __( 'Columns', 'toolkit' ) }>
					<TextControl
						type="number"
						min={ 1 }
						max={ 12 }
						label={ deviceLabel( icon, label ) }
						help={ patternColumns ? __( 'Set by the tiles pattern.', 'toolkit' ) : help }
						value={ patternColumns || columns[ device ] }
						disabled={ !! patternColumns }
						onChange={ ( value ) => update( `responsive.columns.${ device }`, clamp( value, 1, 12, fallback ) ) }
						__nextHasNoMarginBottom
					/>
				</Group>
			) }

			<Group title={ __( 'Gaps (px)', 'toolkit' ) }>
				<div className="vlt-grid-editor__row is-2">
					<TextControl type="number" min={ 0 } max={ 200 } label={ __( 'Horizontal', 'toolkit' ) } value={ gapX[ device ] } onChange={ ( value ) => update( 'responsive.gap_x', { ...gapX, [ device ]: clamp( value, 0, 200, 24 ) } ) } __nextHasNoMarginBottom />
					<TextControl type="number" min={ 0 } max={ 200 } label={ __( 'Vertical', 'toolkit' ) } value={ gapY[ device ] } onChange={ ( value ) => update( 'responsive.gap_y', { ...gapY, [ device ]: clamp( value, 0, 200, 24 ) } ) } __nextHasNoMarginBottom />
				</div>
			</Group>
		</>
	);
}
