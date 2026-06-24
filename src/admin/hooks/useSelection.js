/**
 * Multi-select hook for the media grid.
 *
 * Models GotchaBoard's SelectionManager: toggle, shift-click range select,
 * select-all, and clear, tracking the anchor index for range operations.
 */
import { useState, useRef, useCallback } from '@wordpress/element';

export default function useSelection() {
	const [ selected, setSelected ] = useState( () => new Set() );
	const anchor = useRef( null );

	const toggle = useCallback( ( id, index = null ) => {
		setSelected( ( prev ) => {
			const next = new Set( prev );
			if ( next.has( id ) ) {
				next.delete( id );
			} else {
				next.add( id );
			}
			return next;
		} );
		anchor.current = index;
	}, [] );

	const range = useCallback( ( index, orderedIds ) => {
		// Read the anchor before mutating it — the setSelected updater runs
		// after this function returns, by which point anchor.current would
		// already equal `index`.
		const start = anchor.current === null ? index : anchor.current;
		const lo = Math.min( start, index );
		const hi = Math.max( start, index );
		setSelected( ( prev ) => {
			const next = new Set( prev );
			for ( let i = lo; i <= hi; i++ ) {
				if ( orderedIds[ i ] !== undefined ) {
					next.add( orderedIds[ i ] );
				}
			}
			return next;
		} );
		anchor.current = index;
	}, [] );

	const selectAll = useCallback( ( ids ) => {
		setSelected( new Set( ids ) );
	}, [] );

	const clear = useCallback( () => {
		setSelected( new Set() );
		anchor.current = null;
	}, [] );

	const isSelected = useCallback(
		( id ) => selected.has( id ),
		[ selected ]
	);

	return { selected, toggle, range, selectAll, clear, isSelected };
}
