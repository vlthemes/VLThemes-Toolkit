/**
 * Shortcodes of the layout (top of the Grid Settings sidebar): read-only field with "Copy" inside, like the dashboard
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useSelect } from '@wordpress/data';
import { useState } from '@wordpress/element';

const { shortcodes } = window.vltGridEditor;

const ICON_COPY = (
	<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
		<rect x="9" y="9" width="11" height="11" rx="2" />
		<path d="M5 15H4a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v1" />
	</svg>
);

const ICON_DONE = (
	<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" aria-hidden="true">
		<path d="M5 12.5l4.5 4.5L19 7.5" />
	</svg>
);

function ShortcodeField( { label, code } ) {
	const [ copied, setCopied ] = useState( false );
	const ref = useCopyToClipboard( code, () => {
		setCopied( true );
		window.setTimeout( () => setCopied( false ), 1500 );
	} );

	return (
		<div>
			<span className="vlt-grid-editor__label">{ label }</span>
			<div className="vlt-grid-editor__copy">
				<code title={ code }>{ code }</code>
				<button type="button" ref={ ref } className={ copied ? 'is-copied' : undefined } aria-label={ copied ? __( 'Copied', 'toolkit' ) : __( 'Copy shortcode', 'toolkit' ) } title={ copied ? __( 'Copied', 'toolkit' ) : __( 'Copy', 'toolkit' ) }>
					{ copied ? ICON_DONE : ICON_COPY }
				</button>
			</div>
		</div>
	);
}

export default function Shortcodes( { config, update } ) {
	const postId = useSelect( ( select ) => select( 'core/editor' ).getCurrentPostId(), [] );
	const code = ( tag ) => `[${ tag } id="${ postId }"]`;
	const attached = config.display.attach_controls;

	return (
		<div className="vlt-grid-editor__shortcodes">
			<p className="vlt-grid-editor__muted">{ __( 'To output this layout and its parts, use these shortcodes:', 'toolkit' ) }</p>
			<ShortcodeField label={ __( 'Grid', 'toolkit' ) } code={ code( shortcodes.grid ) } />
			{ ! attached && config.filters.enabled && <ShortcodeField label={ __( 'Filter', 'toolkit' ) } code={ code( shortcodes.filter ) } /> }
			{ ! attached && config.pagination.type !== 'none' && <ShortcodeField label={ __( 'Pagination', 'toolkit' ) } code={ code( shortcodes.pagination ) } /> }
			<ToggleControl
				label={ __( 'Output filter and pagination with the grid', 'toolkit' ) }
				help={ attached ? __( 'The grid shortcode prints the filter above and the pagination below it.', 'toolkit' ) : __( 'Handy for demos: no separate shortcodes needed.', 'toolkit' ) }
				checked={ attached }
				onChange={ ( value ) => update( 'display.attach_controls', value ) }
				__nextHasNoMarginBottom
			/>
		</div>
	);
}
