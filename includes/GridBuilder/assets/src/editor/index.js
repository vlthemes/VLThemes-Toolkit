/**
 * Grid Layout editor entry — built to assets/build/editor.js (npm run grid:build)
 *
 * Settings: the Grid Layout sidebar, shortcodes on top (sidebar.js). Content area: one locked block
 * showing a live preview (canvas.js). The block saves nothing to the content: all settings are
 * post meta (_vlt_grid_config, _vlt_grid_css).
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { registerPlugin } from '@wordpress/plugins';
import Canvas from './canvas.js';
import Sidebar, { PLUGIN } from './sidebar.js';
import './editor.scss';

registerPlugin( PLUGIN, { render: Sidebar } );

registerBlockType( 'vlt-toolkit/grid-settings', {
	apiVersion: 3,
	title: __( 'Grid Layout Preview', 'toolkit' ),
	category: 'widgets',
	icon: 'grid-view',
	// Full canvas width: the preview shows the real desktop / tablet / mobile grid of the editor's device preview
	attributes: {
		align: { type: 'string', default: 'full' },
	},
	supports: {
		align: [ 'full' ],
		inserter: false,
		html: false,
		reusable: false,
		multiple: false,
		lock: false,
		className: false,
		customClassName: false,
	},
	edit: Canvas,
	save: () => null,
} );
