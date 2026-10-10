/**
 * Content area of a Grid Layout: live preview (the only, locked block of a vlt_grid post)
 */
import { useBlockProps } from '@wordpress/block-editor';
import useLayout from './use-layout.js';
import Preview from './preview.js';

export default function Canvas() {
	const { config, css } = useLayout();

	return (
		<div { ...useBlockProps( { className: 'vlt-grid-editor' } ) }>
			<Preview config={ config } css={ css } />
		</div>
	);
}
