/**
 * Left sidebar: filter shortcuts plus the droppable term tree.
 */
import { useDroppable } from '@dnd-kit/core';
import { __ } from '@wordpress/i18n';

function TermRow( { node, depth, activeTermId, onFilter } ) {
	const { setNodeRef, isOver } = useDroppable( {
		id: `term-${ node.id }`,
		data: { termId: node.id },
	} );

	const classes = [
		'smc-term',
		activeTermId === node.id ? 'is-active' : '',
		isOver ? 'is-over' : '',
	]
		.filter( Boolean )
		.join( ' ' );

	return (
		<>
			<button
				type="button"
				ref={ setNodeRef }
				className={ classes }
				style={ { paddingLeft: `${ 12 + depth * 16 }px` } }
				onClick={ () => onFilter( 'term', node.id ) }
			>
				<span className="smc-term__name">{ node.name }</span>
				<span className="smc-term__count">{ node.count }</span>
			</button>
			{ node.children.map( ( child ) => (
				<TermRow
					key={ child.id }
					node={ child }
					depth={ depth + 1 }
					activeTermId={ activeTermId }
					onFilter={ onFilter }
				/>
			) ) }
		</>
	);
}

export default function TermSidebar( { tree, filter, onFilter } ) {
	return (
		<nav
			className="smc-sidebar"
			aria-label={ __( 'Filters', 'simple-media-categories' ) }
		>
			<button
				type="button"
				className={ `smc-term smc-term--all${
					filter.mode === 'all' ? ' is-active' : ''
				}` }
				onClick={ () => onFilter( 'all' ) }
			>
				{ __( 'All media', 'simple-media-categories' ) }
			</button>
			<button
				type="button"
				className={ `smc-term smc-term--untagged${
					filter.mode === 'untagged' ? ' is-active' : ''
				}` }
				onClick={ () => onFilter( 'untagged' ) }
			>
				{ __( 'Untagged', 'simple-media-categories' ) }
			</button>

			<div className="smc-sidebar__heading">
				{ __( 'Categories', 'simple-media-categories' ) }
			</div>

			{ tree.length ? (
				tree.map( ( node ) => (
					<TermRow
						key={ node.id }
						node={ node }
						depth={ 0 }
						activeTermId={
							filter.mode === 'term' ? filter.termId : 0
						}
						onFilter={ onFilter }
					/>
				) )
			) : (
				<p className="smc-sidebar__empty">
					{ __( 'No categories yet.', 'simple-media-categories' ) }
				</p>
			) }
		</nav>
	);
}
