/**
 * Group of fields inside a sidebar section, titled like a toolkit dashboard widget head
 */
export default function Group( { title, children } ) {
	return (
		<div className="vlt-grid-editor__group">
			<div className="vlt-grid-editor__group-title">{ title }</div>
			<div className="vlt-grid-editor__group-body">{ children }</div>
		</div>
	);
}
