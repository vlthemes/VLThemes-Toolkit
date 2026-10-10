/**
 * Layout panel: type + per-type settings (Tiles: pick a predefined pattern; the theme can add more)
 */
import { __ } from '@wordpress/i18n';
import {
	SelectControl,
	TextControl,
	ToggleControl,
	__experimentalToggleGroupControl as ToggleGroupControl,
	__experimentalToggleGroupControlOptionIcon as ToggleGroupControlOptionIcon,
} from '@wordpress/components';
import Locked from '../locked.js';
import ResponsivePanel, { DeviceHint } from './responsive.js';
import Group from '../group.js';
import { useDevice } from '../device.js';

const { layouts, layoutSettings, ratios, tilesPatterns } = window.vltGridEditor;

/**
 * Settings of a theme layout type, from its layout.php schema (number / select / toggle / text)
 *
 * Without a device: the settings shared by all devices. With one: the responsive ones (one value per device,
 * shown under the device switch next to columns and gaps).
 */
function ThemeSettings( { type, values, update, device } ) {
	const schema = layoutSettings[ type ];
	const entries = Object.entries( schema.settings ).filter( ( [ , setting ] ) => !! device === !! setting.responsive );

	if ( ! entries.length ) {
		return null;
	}

	// A responsive value saved before it became per-device (one number) applies to every device
	const perDevice = ( value, fallback ) => ( value && 'object' === typeof value ? value : { desktop: value ?? fallback.desktop, tablet: value ?? fallback.tablet, mobile: value ?? fallback.mobile } );

	return (
		<Group title={ device ? __( 'Per device', 'toolkit' ) : __( 'Settings', 'toolkit' ) }>
			{ entries.map( ( [ key, setting ] ) => {
				const all = device ? perDevice( values?.[ key ], setting.default ) : null;
				const fallback = device ? setting.default[ device ] : setting.default;
				const value = device ? all[ device ] ?? fallback : values?.[ key ] ?? fallback;
				const set = ( next ) => update( `layout.${ type }.${ key }`, device ? { ...all, [ device ]: next } : next );
				const common = { label: setting.label, help: setting.help || undefined, __nextHasNoMarginBottom: true };

				switch ( setting.type ) {
					case 'number':
						return (
							<TextControl
								key={ key }
								{ ...common }
								type="number"
								label={ setting.unit ? `${ setting.label } (${ setting.unit })` : setting.label }
								min={ setting.min ?? undefined }
								max={ setting.max ?? undefined }
								step={ setting.step }
								value={ value }
								onChange={ ( next ) => set( '' === next ? fallback : Number( next ) ) }
							/>
						);
					case 'select':
						return <SelectControl key={ key } { ...common } value={ value } options={ setting.options } onChange={ set } />;
					case 'toggle':
						return <ToggleControl key={ key } { ...common } checked={ !! value } onChange={ set } />;
					default:
						return <TextControl key={ key } { ...common } value={ value } onChange={ set } />;
				}
			} ) }
		</Group>
	);
}

/**
 * Sketch of a pattern at its real proportions: rows are 1/100 of a column width of the card (container
 * query units, as on the site); repeated until at least two rows of every column are filled, cut at the card height
 */
function TilesSketch( { pattern } ) {
	const repeat = Math.max( 2, Math.ceil( ( pattern.columns * 2 ) / pattern.tiles.length ) );
	const tiles = Array.from( { length: repeat }, () => pattern.tiles ).flat();
	const columns = pattern.columns;

	return (
		<span
			className="vlt-grid-editor__sketch"
			style={ {
				gridTemplateColumns: `repeat(${ columns }, 1fr)`,
				gridAutoRows: `calc((100cqw - ${ columns - 1 } * 2px) / ${ columns } / 100)`,
			} }
			aria-hidden="true"
		>
			{ tiles.map( ( tile, i ) => (
				<span key={ i } style={ { gridColumn: `span ${ tile.width }`, gridRow: `span ${ tile.rows }` } } />
			) ) }
		</span>
	);
}

const DEVICES = [
	{ value: 'desktop', icon: 'desktop', label: __( 'Desktop', 'toolkit' ), key: 'pattern' },
	{ value: 'tablet', icon: 'tablet', label: __( 'Tablet', 'toolkit' ), key: 'pattern_tablet', inherit: __( 'As desktop', 'toolkit' ) },
	{ value: 'mobile', icon: 'smartphone', label: __( 'Mobile', 'toolkit' ), key: 'pattern_mobile', inherit: __( 'As tablet', 'toolkit' ) },
];

function TilesPicker( { value, inherit, onChange } ) {
	return (
		<div className="vlt-grid-editor__patterns" role="radiogroup" aria-label={ __( 'Tiles pattern', 'toolkit' ) }>
			{ inherit && (
				<button
					type="button"
					role="radio"
					aria-checked={ '' === value }
					className={ 'vlt-grid-editor__pattern is-inherit' + ( '' === value ? ' is-selected' : '' ) }
					onClick={ () => onChange( '' ) }
				>
					<span className="vlt-grid-editor__pattern-inherit">{ inherit }</span>
				</button>
			) }
			{ tilesPatterns.map( ( pattern ) => (
				<button
					key={ pattern.value }
					type="button"
					role="radio"
					aria-checked={ value === pattern.value }
					aria-label={ pattern.label }
					title={ pattern.label }
					className={ 'vlt-grid-editor__pattern' + ( value === pattern.value ? ' is-selected' : '' ) }
					onClick={ () => onChange( pattern.value ) }
				>
					<TilesSketch pattern={ pattern } />
				</button>
			) ) }
		</div>
	);
}

/**
 * Pattern of one device: Desktop sets the base; Tablet / Mobile inherit it (narrowed to their columns) or use their own
 */
function TilesPatterns( { tiles, device, update } ) {
	const current = DEVICES.find( ( item ) => item.value === device );

	return (
		<Group title={ __( 'Pattern', 'toolkit' ) }>
			<TilesPicker value={ tiles[ current.key ] || '' } inherit={ current.inherit } onChange={ ( value ) => update( `layout.tiles.${ current.key }`, value ) } />
			{ current.inherit && ! tiles[ current.key ] && (
				<p className="vlt-grid-editor__muted">
					{ 'tablet' === device
						? __( 'Uses the desktop pattern, narrowed to the tablet columns below.', 'toolkit' )
						: __( 'Uses the tablet pattern, narrowed to the mobile columns below.', 'toolkit' ) }
				</p>
			) }
		</Group>
	);
}

/**
 * Justified row height of one device (one number saved by older configs applies to every device)
 */
function JustifiedRowHeight( { justified, device, update } ) {
	const fallback = { desktop: 260, tablet: 200, mobile: 150 };
	const raw = justified?.row_height;
	const heights = raw && 'object' === typeof raw ? { ...fallback, ...raw } : { desktop: raw ?? fallback.desktop, tablet: raw ?? fallback.tablet, mobile: raw ?? fallback.mobile };

	return (
		<Group title={ __( 'Row height', 'toolkit' ) }>
			<TextControl
				type="number"
				min={ 60 }
				max={ 800 }
				step={ 10 }
				label={ __( 'Height (px)', 'toolkit' ) }
				help={ __( 'Every row has this height; items keep their image proportions.', 'toolkit' ) }
				value={ heights[ device ] }
				onChange={ ( value ) => update( 'layout.justified.row_height', { ...heights, [ device ]: Math.max( 60, Math.min( 800, parseInt( value, 10 ) || fallback[ device ] ) ) } ) }
				__nextHasNoMarginBottom
			/>
		</Group>
	);
}

export default function LayoutPanel( props ) {
	const { config, update } = props;
	const layout = config.layout;
	const [ device, setDevice ] = useDevice();

	const deviceGroups = (
		<>
			<DeviceHint config={ config } device={ device } />
			{ layout.type === 'tiles' && <TilesPatterns tiles={ layout.tiles } device={ device } update={ update } /> }
			{ layout.type === 'justified' && <JustifiedRowHeight justified={ layout.justified } device={ device } update={ update } /> }
			{ layoutSettings[ layout.type ] && <ThemeSettings type={ layout.type } values={ layout[ layout.type ] } update={ update } device={ device } /> }
			<ResponsivePanel { ...props } device={ device } />
		</>
	);

	return (
		<>
			<Group title={ __( 'Type', 'toolkit' ) }>
				<SelectControl label={ __( 'Layout type', 'toolkit' ) } hideLabelFromVision value={ layout.type } options={ layouts } onChange={ ( value ) => update( 'layout.type', value ) } __nextHasNoMarginBottom />
				{ layoutSettings[ layout.type ]?.description && <p className="vlt-grid-editor__muted">{ layoutSettings[ layout.type ].description }</p> }
				{ layout.type === 'masonry' && (
					<p className="vlt-grid-editor__muted">
						{ __( 'Masonry uses CSS columns: items fill each column from top to bottom, so the reading order goes down a column, not across a row. Columns and gaps are set per device below.', 'toolkit' ) }
					</p>
				) }
			</Group>

			{ layoutSettings[ layout.type ] && <ThemeSettings type={ layout.type } values={ layout[ layout.type ] } update={ update } /> }

			{ layout.type === 'justified' && (
				<Group title={ __( 'Settings', 'toolkit' ) }>
					<ToggleControl
						label={ __( 'Stretch the last row', 'toolkit' ) }
						help={ __( 'Off: the last row keeps the row height and starts on the left.', 'toolkit' ) }
						checked={ !! layout.justified?.stretch_last }
						onChange={ ( value ) => update( 'layout.justified.stretch_last', value ) }
						__nextHasNoMarginBottom
					/>
				</Group>
			) }

			{ layout.type === 'grid' && (
				<Group title={ __( 'Image', 'toolkit' ) }>
					<div className="vlt-grid-editor__row is-2">
						<SelectControl
							label={ __( 'Ratio', 'toolkit' ) }
							value={ layout.grid.ratio }
							options={ ratios.map( ( ratio ) => ( { ...ratio, label: ratio.value === 'auto' ? __( 'Original', 'toolkit' ) : ratio.label } ) ) }
							onChange={ ( value ) => update( 'layout.grid.ratio', value ) }
							__nextHasNoMarginBottom
						/>
						<SelectControl
							label={ __( 'Fit', 'toolkit' ) }
							value={ layout.grid.fit }
							options={ [
								{ value: 'cover', label: __( 'Cover', 'toolkit' ) },
								{ value: 'contain', label: __( 'Contain', 'toolkit' ) },
							] }
							onChange={ ( value ) => update( 'layout.grid.fit', value ) }
							__nextHasNoMarginBottom
						/>
					</div>
					<p className="vlt-grid-editor__muted">{ __( 'Applied to .vlt-gb__media in the skin.', 'toolkit' ) }</p>
				</Group>
			) }

			{ /* One device switch for everything below: pattern (Tiles), columns, gaps */ }
			<ToggleGroupControl label={ __( 'Device', 'toolkit' ) } hideLabelFromVision value={ device } onChange={ setDevice } isBlock __nextHasNoMarginBottom __next40pxDefaultSize>
				{ DEVICES.map( ( item ) => (
					<ToggleGroupControlOptionIcon key={ item.value } value={ item.value } icon={ item.icon } label={ item.label } />
				) ) }
			</ToggleGroupControl>

			{ /* Desktop is always editable; tablet and mobile need an activated theme */ }
			{ 'desktop' === device ? (
				deviceGroups
			) : (
				<Locked>{ deviceGroups }</Locked>
			) }
		</>
	);
}
