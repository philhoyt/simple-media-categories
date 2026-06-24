/**
 * Drag overlay shown while dragging media: a stack of up to three thumbnails
 * with a count badge, mirroring GotchaBoard's multi-drag stack.
 *
 * @param {Object} root0       Component props.
 * @param {Array}  root0.items Items being dragged (first three are shown).
 * @param {number} root0.count Total number of items being dragged.
 */
export default function DragStackPreview( { items, count } ) {
	const preview = items.slice( 0, 3 );

	return (
		<div className="smc-dragstack">
			{ preview.map( ( item, index ) => (
				<div
					key={ item.id }
					className="smc-dragstack__card"
					style={ {
						transform: `translate(${ index * 6 }px, ${
							index * 6
						}px) rotate(${ ( index - 1 ) * 3 }deg)`,
						zIndex: preview.length - index,
					} }
				>
					{ item.thumbnail ? (
						<img src={ item.thumbnail } alt="" />
					) : (
						<span className="smc-dragstack__placeholder" />
					) }
				</div>
			) ) }
			{ count > 1 && (
				<span className="smc-dragstack__badge">{ count }</span>
			) }
		</div>
	);
}
