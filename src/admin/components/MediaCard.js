/**
 * A single selectable, draggable media tile.
 */
import { useDraggable } from '@dnd-kit/core';
import { __, sprintf, _n } from '@wordpress/i18n';

export default function MediaCard( {
	item,
	index,
	selected,
	onSelect,
	termNames,
} ) {
	const { attributes, listeners, setNodeRef, transform, isDragging } =
		useDraggable( {
			id: `card-${ item.id }`,
			data: { id: item.id },
		} );

	const style = transform
		? {
				transform: `translate3d(${ transform.x }px, ${ transform.y }px, 0)`,
		  }
		: undefined;

	const classes = [
		'smc-card',
		selected ? 'is-selected' : '',
		isDragging ? 'is-dragging' : '',
	]
		.filter( Boolean )
		.join( ' ' );

	const handleSelect = ( event ) => onSelect( item.id, index, event );

	const handleKeyDown = ( event ) => {
		if ( event.key === 'Enter' || event.key === ' ' ) {
			event.preventDefault();
			onSelect( item.id, index, event );
		}
	};

	return (
		<div
			ref={ setNodeRef }
			style={ style }
			className={ classes }
			role="button"
			tabIndex={ 0 }
			aria-pressed={ selected }
			onClick={ handleSelect }
			onKeyDown={ handleKeyDown }
			{ ...listeners }
			{ ...attributes }
		>
			<span className="smc-card__check" aria-hidden="true" />
			<div className="smc-card__thumb">
				{ item.thumbnail ? (
					<img src={ item.thumbnail } alt="" />
				) : (
					<span className="smc-card__placeholder">{ item.mime }</span>
				) }
			</div>
			<div className="smc-card__title">
				{ item.title || __( '(no title)', 'simple-media-categories' ) }
			</div>
			{ termNames.length > 0 && (
				<div className="smc-card__terms">
					{ termNames.map( ( name ) => (
						<span key={ name } className="smc-chip smc-chip--mini">
							{ name }
						</span>
					) ) }
				</div>
			) }
			<div className="smc-card__count">
				{ sprintf(
					/* translators: %d: number of categories on this item. */
					_n(
						'%d category',
						'%d categories',
						termNames.length,
						'simple-media-categories'
					),
					termNames.length
				) }
			</div>
		</div>
	);
}
