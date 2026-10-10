/**
 * Custom CSS panel (CssScoper.php limits it to this layout)
 *
 * WordPress' own code editor (CodeMirror: highlighting, lint, hints; wp_enqueue_code_editor), growing
 * with the content, plus a large editor in a modal and a list of the grid's classes that inserts a rule.
 * A plain textarea when the user turned syntax highlighting off in their profile.
 */
import { __ } from '@wordpress/i18n';
import { Button, Modal } from '@wordpress/components';
import { useEffect, useRef, useState } from '@wordpress/element';
import Hint from '../hint.js';

const { canEditCss, codeEditor, cssClasses } = window.vltGridEditor;

const EXAMPLE = `selector {
    background-color: #f5f5f5;
}

selector .vlt-gb__item {
    border-radius: 12px;
}`;

/**
 * One code editor instance bound to the CSS value
 */
function CssEditor( { value, onChange, minLines, maxLines, editorRef } ) {
	const textarea = useRef();
	const change = useRef( onChange );

	// The editor instance outlives renders: keep the latest setter
	change.current = onChange;

	useEffect( () => {
		if ( ! codeEditor || ! window.wp?.codeEditor || ! textarea.current ) {
			return;
		}

		const instance = window.wp.codeEditor.initialize( textarea.current, codeEditor );
		const cm = instance.codemirror;
		const lineHeight = cm.defaultTextHeight();

		// Grow with the content between minLines and maxLines
		const fit = () => cm.setSize( null, Math.min( maxLines, Math.max( minLines, cm.lineCount() ) ) * lineHeight + 24 );

		fit();
		cm.on( 'change', () => {
			fit();
			change.current( cm.getValue() );
		} );

		if ( editorRef ) {
			editorRef.current = cm;
		}

		// React may already have removed the wrapper: CodeMirror then cleans up a detached tree, harmlessly
		return () => cm.toTextArea();
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps

	// Own wrapper: CodeMirror adds its DOM next to the textarea, and React must only ever remove this div as a whole
	return (
		<div className="vlt-grid-editor__css-wrap">
		<textarea
			ref={ textarea }
			className="vlt-grid-editor__css"
			rows={ minLines }
			defaultValue={ value }
			onChange={ ( event ) => onChange( event.target.value ) }
			spellCheck={ false }
			aria-label={ __( 'Custom CSS', 'toolkit' ) }
		/>
		</div>
	);
}

/**
 * Classes of the grid markup: click inserts a scoped rule at the end
 */
function ClassesTree( { editorRef, onInsert } ) {
	return (
		<div className="vlt-grid-editor__classes">
			<span className="vlt-grid-editor__label">{ __( 'Classes', 'toolkit' ) }</span>
			<ul>
				{ cssClasses.map( ( item ) => (
					<li key={ item.selector } style={ { '--vlt-depth': item.depth } }>
						<button
							type="button"
							title={ item.description }
							onClick={ () => {
								const rule = `\n${ item.selector } {\n\t\n}\n`;
								const cm = editorRef.current;

								if ( cm ) {
									cm.replaceRange( rule, { line: cm.lastLine() } );
									cm.focus();
									cm.setCursor( { line: cm.lastLine() - 1, ch: 1 } );
								} else {
									onInsert( rule );
								}
							} }
						>
							{ item.selector }
						</button>
					</li>
				) ) }
			</ul>
		</div>
	);
}

export default function CssPanel( { css, setCss } ) {
	const [ isOpen, setOpen ] = useState( false );
	const [ version, setVersion ] = useState( 0 );
	const sidebarEditor = useRef();
	const modalEditor = useRef();

	if ( ! canEditCss ) {
		return <Hint status="info">{ __( 'Only users allowed to edit CSS (like Additional CSS) can change this.', 'toolkit' ) }</Hint>;
	}

	const close = () => {
		setOpen( false );
		// Re-create the sidebar editor with what was written in the modal
		setVersion( ( current ) => current + 1 );
	};

	return (
		<>
			{ ! isOpen && (
				<div className="vlt-grid-editor__css-field">
					<CssEditor key={ version } value={ css } onChange={ setCss } minLines={ 5 } maxLines={ 20 } editorRef={ sidebarEditor } />
					{ /* Expand in the corner of the field, like code editors do */ }
					<Button className="vlt-grid-editor__css-expand" icon="fullscreen-alt" size="small" label={ __( 'Expand editor', 'toolkit' ) } onClick={ () => setOpen( true ) } />
				</div>
			) }

			<ClassesTree editorRef={ sidebarEditor } onInsert={ ( rule ) => setCss( css + rule ) } />

			<div className="vlt-grid-editor__help">
				<p>
					{ __( 'Use the', 'toolkit' ) } <code>selector</code> { __( 'rule to style this grid; other selectors stay inside it too.', 'toolkit' ) }
				</p>
				<p>{ __( 'Example:', 'toolkit' ) }</p>
				<pre>{ EXAMPLE }</pre>
			</div>

			{ isOpen && (
				<Modal title={ __( 'Custom CSS', 'toolkit' ) } onRequestClose={ close } size="large" className="vlt-grid-editor__css-modal">
					<div className="vlt-grid-editor__css-modal-body">
						<CssEditor value={ css } onChange={ setCss } minLines={ 24 } maxLines={ 24 } editorRef={ modalEditor } />
						<ClassesTree editorRef={ modalEditor } onInsert={ ( rule ) => setCss( css + rule ) } />
					</div>
				</Modal>
			) }
		</>
	);
}
