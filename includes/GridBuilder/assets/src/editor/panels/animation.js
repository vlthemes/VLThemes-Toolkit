/**
 * Animation: entrance effect of items (pure CSS, see Animations.php) — on load, after filtering and for loaded items
 */
import { __ } from '@wordpress/i18n';
import { SelectControl, TextControl } from '@wordpress/components';
import Group from '../group.js';

const { animations } = window.vltGridEditor;

const clamp = ( value, min, max, fallback ) => {
	const number = parseInt( value, 10 );

	return Number.isNaN( number ) ? fallback : Math.max( min, Math.min( max, number ) );
};

export default function AnimationPanel( { config, update } ) {
	const animation = config.animation;

	return (
		<>
			<Group title={ __( 'Effect', 'toolkit' ) }>
				<SelectControl
					label={ __( 'Entrance effect', 'toolkit' ) }
					hideLabelFromVision
					help={ __( 'Plays when items appear: on load, after filtering and for items loaded later. Off for visitors who prefer reduced motion.', 'toolkit' ) }
					value={ animation.effect }
					options={ animations }
					onChange={ ( value ) => update( 'animation.effect', value ) }
					__nextHasNoMarginBottom
				/>
			</Group>

			{ animation.effect !== 'none' && (
				<Group title={ __( 'Timing', 'toolkit' ) }>
					<div className="vlt-grid-editor__row is-2">
						<TextControl type="number" min={ 100 } max={ 2000 } step={ 50 } label={ __( 'Duration (ms)', 'toolkit' ) } value={ animation.duration } onChange={ ( value ) => update( 'animation.duration', clamp( value, 100, 2000, 500 ) ) } __nextHasNoMarginBottom />
						<TextControl type="number" min={ 0 } max={ 300 } step={ 10 } label={ __( 'Stagger (ms)', 'toolkit' ) } value={ animation.stagger } onChange={ ( value ) => update( 'animation.stagger', clamp( value, 0, 300, 60 ) ) } __nextHasNoMarginBottom />
					</div>
					<p className="vlt-grid-editor__muted">{ __( 'Stagger delays each next item a little, so they appear one after another.', 'toolkit' ) }</p>
				</Group>
			) }
		</>
	);
}
