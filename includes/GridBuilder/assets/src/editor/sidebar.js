/**
 * Grid Layout sidebar — shortcodes on top, then every settings section
 *
 * A dedicated sidebar (not panels in the document sidebar) so the order is ours: the document sidebar
 * always lists its own panels first. It is pinned to the header and opened once the editor is ready
 * (opening earlier is overridden by the editor's own default); status and publishing stay on the document tab.
 */
import { __ } from '@wordpress/i18n';
import { Icon, PanelBody } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useEffect, useRef } from '@wordpress/element';
import useLayout from './use-layout.js';
import Shortcodes from './shortcodes.js';
import { ContentPanel, OrderPanel } from './panels/query.js';
import LayoutPanel from './panels/layout.js';
import SkinPanel from './panels/skin.js';
import FiltersPanel from './panels/filters.js';
import PaginationPanel from './panels/pagination.js';
import CssPanel from './panels/css.js';
import AnimationPanel from './panels/animation.js';
import InsertsPanel from './panels/inserts.js';
import Locked from './locked.js';
import * as icons from './icons.js';

export const SIDEBAR = 'vlt-grid-sidebar';
export const PLUGIN = 'vlt-grid-settings';

const AREA = `${ PLUGIN }/${ SIDEBAR }`;

const { sources, activated } = window.vltGridEditor;

// Section title with its icon (icons.js)
const Title = ( { icon, children } ) => (
	<span className="vlt-grid-editor__panel-title">
		<Icon icon={ icon } size={ 18 } />
		{ children }
	</span>
);

const PANELS = [
	{ name: 'content', icon: icons.content, title: __( 'Content', 'toolkit' ), Panel: ContentPanel },
	{ name: 'order', icon: icons.order, title: __( 'Order & limit', 'toolkit' ), Panel: OrderPanel },
	{ name: 'layout', icon: icons.layout, title: __( 'Layout', 'toolkit' ), Panel: LayoutPanel },
	{ name: 'skin', icon: icons.skin, title: __( 'Skin', 'toolkit' ), Panel: SkinPanel },
	{ name: 'filters', icon: icons.filter, title: __( 'Filter', 'toolkit' ), Panel: FiltersPanel },
	{ name: 'pagination', icon: icons.pagination, title: __( 'Pagination', 'toolkit' ), Panel: PaginationPanel },
	{ name: 'animation', icon: icons.animation, title: __( 'Animation', 'toolkit' ), Panel: AnimationPanel, locked: true },
	{ name: 'inserts', icon: icons.inserts, title: __( 'Inserts', 'toolkit' ), Panel: InsertsPanel, locked: true },
	{ name: 'css', icon: icons.css, title: __( 'Custom CSS', 'toolkit' ), Panel: CssPanel, locked: true },
];

function useOpenOnReady() {
	const { ready, active } = useSelect(
		( select ) => ( {
			ready: select( 'core/editor' ).__unstableIsEditorReady?.() ?? true,
			active: select( 'core/interface' )?.getActiveComplementaryArea( 'core' ),
		} ),
		[]
	);
	const { enableComplementaryArea, pinItem } = useDispatch( 'core/interface' ) || {};
	const done = useRef( false );

	// The editor switches to its document tab while it boots: keep asking until ours is shown once,
	// then leave the choice to the user
	useEffect( () => {
		if ( ! ready || done.current ) {
			return;
		}

		if ( active === AREA ) {
			done.current = true;
			return;
		}

		pinItem?.( 'core', AREA );
		enableComplementaryArea?.( 'core', AREA );
	}, [ ready, active ] ); // eslint-disable-line react-hooks/exhaustive-deps
}

/**
 * Keep the WordPress admin menu visible on Grid Layout screens
 *
 * The editor's fullscreen mode is a per-user preference shared by every post type; flipping it would change
 * posts and pages too. Instead the fullscreen body class is kept off here only, which is what lays the menu out.
 */
function useAdminMenu() {
	useEffect( () => {
		const body = document.body;
		const off = () => body.classList.contains( 'is-fullscreen-mode' ) && body.classList.remove( 'is-fullscreen-mode' );

		off();

		const observer = new window.MutationObserver( off );
		observer.observe( body, { attributes: true, attributeFilter: [ 'class' ] } );

		return () => observer.disconnect();
	}, [] );
}

export default function Sidebar() {
	const layout = useLayout();
	const source = sources.find( ( item ) => item.value === layout.config.query.source );

	useOpenOnReady();
	useAdminMenu();

	return (
		<>
			<PluginSidebarMoreMenuItem target={ SIDEBAR } icon="grid-view">
				{ __( 'Grid Settings', 'toolkit' ) }
			</PluginSidebarMoreMenuItem>
			<PluginSidebar name={ SIDEBAR } title={ __( 'Grid Settings', 'toolkit' ) } icon="grid-view" className="vlt-grid-editor__sidebar">
				<PanelBody title={ <Title icon={ icons.shortcodes }>{ __( 'Shortcodes', 'toolkit' ) }</Title> } className="vlt-grid-editor__panel">
					<Shortcodes config={ layout.config } update={ layout.update } />
				</PanelBody>
				{ PANELS.map( ( { name, icon, title, Panel, locked } ) => {
					const panel = <Panel { ...layout } source={ source } sources={ sources } />;

					return (
						<PanelBody
							key={ name }
							title={
								<Title icon={ icon }>
									{ title }
									{ /* Locked until the theme is activated — visible without opening the section */ }
									{ locked && ! activated && (
										<span className="vlt-grid-editor__lock" title={ __( 'Activate your theme to use this option', 'toolkit' ) }>
											{ __( 'Locked', 'toolkit' ) }
										</span>
									) }
								</Title>
							}
							initialOpen={ 'content' === name }
							className="vlt-grid-editor__panel"
						>
							{ locked ? <Locked>{ panel }</Locked> : panel }
						</PanelBody>
					);
				} ) }
			</PluginSidebar>
		</>
	);
}
