/**
 * Inline note in the editor UI — the toolkit dashboard's note style (tinted, accent bar on the left)
 */
export default function Hint( { status = 'info', children } ) {
	return (
		<div className={ `vlt-grid-editor__hint is-${ status }` } role={ 'error' === status ? 'alert' : undefined }>
			{ children }
		</div>
	);
}
