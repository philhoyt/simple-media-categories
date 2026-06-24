/**
 * Root component for the Tag Media workspace.
 *
 * Holds workspace state (terms, media, filters, selection), wires the REST
 * client, and provides drag-and-drop tagging across the sidebar and grid.
 */
import { useState, useEffect, useMemo, useCallback } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';
import {
	DndContext,
	DragOverlay,
	PointerSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';

import { getTerms, createTerm, getMedia, bulkUpdate } from './api';
import useSelection from './hooks/useSelection';
import { indexTerms, buildTree } from './utils/terms';
import TermSidebar from './components/TermSidebar';
import MediaGrid from './components/MediaGrid';
import BulkPanel from './components/BulkPanel';
import DragStackPreview from './components/DragStackPreview';
import Toaster from './components/Toaster';

const PER_PAGE = 40;

export default function App() {
	const [ terms, setTerms ] = useState( [] );
	const [ items, setItems ] = useState( [] );
	const [ total, setTotal ] = useState( 0 );
	const [ totalPages, setTotalPages ] = useState( 0 );
	const [ page, setPage ] = useState( 1 );
	const [ loading, setLoading ] = useState( false );
	const [ busy, setBusy ] = useState( false );
	const [ searchInput, setSearchInput ] = useState( '' );
	const [ activeId, setActiveId ] = useState( null );
	const [ filter, setFilter ] = useState( {
		mode: 'all',
		termId: 0,
		search: '',
	} );

	const selection = useSelection();
	const { createNotice } = useDispatch( noticesStore );

	const termsById = useMemo( () => indexTerms( terms ), [ terms ] );
	const tree = useMemo( () => buildTree( terms ), [ terms ] );

	const toast = useCallback(
		( status, message ) =>
			createNotice( status, message, {
				type: 'snackbar',
				isDismissible: true,
			} ),
		[ createNotice ]
	);

	// Load the term tree once.
	useEffect( () => {
		getTerms()
			.then( setTerms )
			.catch( () =>
				toast(
					'error',
					__(
						'Failed to load categories.',
						'simple-media-categories'
					)
				)
			);
	}, [ toast ] );

	const buildParams = useCallback(
		( pageNum ) => {
			const params = { page: pageNum, per_page: PER_PAGE };
			if ( filter.search ) {
				params.search = filter.search;
			}
			if ( filter.mode === 'untagged' ) {
				params.untagged = true;
			} else if ( filter.mode === 'term' ) {
				params.term = filter.termId;
			}
			return params;
		},
		[ filter ]
	);

	const loadPage = useCallback(
		async ( pageNum, replace ) => {
			setLoading( true );
			try {
				const res = await getMedia( buildParams( pageNum ) );
				setItems( ( prev ) =>
					replace ? res.items : prev.concat( res.items )
				);
				setTotal( res.total );
				setTotalPages( res.totalPages );
				setPage( pageNum );
			} catch {
				toast(
					'error',
					__( 'Failed to load media.', 'simple-media-categories' )
				);
			} finally {
				setLoading( false );
			}
		},
		[ buildParams, toast ]
	);

	// Reload from page 1 whenever the filter changes.
	useEffect( () => {
		loadPage( 1, true );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ filter ] );

	// Debounce the search box into the filter.
	useEffect( () => {
		const handle = window.setTimeout( () => {
			setFilter( ( prev ) =>
				prev.search === searchInput
					? prev
					: { ...prev, search: searchInput }
			);
		}, 300 );
		return () => window.clearTimeout( handle );
	}, [ searchInput ] );

	const handleFilter = useCallback(
		( mode, termId = 0 ) => {
			selection.clear();
			setFilter( ( prev ) => ( { ...prev, mode, termId } ) );
		},
		[ selection ]
	);

	const handleCreate = useCallback(
		async ( name, parent = 0 ) => {
			try {
				const term = await createTerm( name, parent );
				setTerms( ( prev ) => [ ...prev, term ] );
				return term;
			} catch {
				toast(
					'error',
					__(
						'Could not create category.',
						'simple-media-categories'
					)
				);
				return null;
			}
		},
		[ toast ]
	);

	const applyBulk = useCallback(
		async ( action, termIds, idsOverride ) => {
			const ids = idsOverride || Array.from( selection.selected );
			if ( ! ids.length ) {
				return;
			}
			if ( action !== 'set' && ! termIds.length ) {
				return;
			}

			setBusy( true );
			try {
				const res = await bulkUpdate( ids, action, termIds );

				setItems( ( prev ) =>
					prev
						.map( ( item ) =>
							res.updated[ item.id ]
								? { ...item, terms: res.updated[ item.id ] }
								: item
						)
						.filter( ( item ) => {
							if ( filter.mode === 'untagged' ) {
								return ( item.terms || [] ).length === 0;
							}
							if ( filter.mode === 'term' ) {
								return ( item.terms || [] ).includes(
									filter.termId
								);
							}
							return true;
						} )
				);

				setTerms( ( prev ) =>
					prev.map( ( term ) =>
						res.counts[ term.id ] !== undefined
							? { ...term, count: res.counts[ term.id ] }
							: term
					)
				);

				const skipped = res.skipped?.length || 0;
				toast(
					'success',
					skipped
						? sprintf(
								/* translators: 1: number updated, 2: number skipped. */
								__(
									'Updated %1$d, skipped %2$d without permission.',
									'simple-media-categories'
								),
								ids.length - skipped,
								skipped
						  )
						: __( 'Categories updated.', 'simple-media-categories' )
				);
				selection.clear();
			} catch {
				toast(
					'error',
					__( 'Update failed.', 'simple-media-categories' )
				);
			} finally {
				setBusy( false );
			}
		},
		[ selection, filter, toast ]
	);

	const sensors = useSensors(
		useSensor( PointerSensor, { activationConstraint: { distance: 6 } } )
	);

	const itemsById = useMemo( () => {
		const map = {};
		items.forEach( ( item ) => {
			map[ item.id ] = item;
		} );
		return map;
	}, [ items ] );

	// Ids that move together for the current drag: the whole selection when the
	// grabbed card is part of it, otherwise just that card.
	const draggingIds = useMemo( () => {
		if ( activeId === null ) {
			return [];
		}
		return selection.selected.has( activeId )
			? Array.from( selection.selected )
			: [ activeId ];
	}, [ activeId, selection ] );

	const draggingSet = useMemo(
		() => new Set( draggingIds ),
		[ draggingIds ]
	);

	const handleDragStart = useCallback( ( event ) => {
		setActiveId( event.active?.data?.current?.id ?? null );
	}, [] );

	const handleDragCancel = useCallback( () => setActiveId( null ), [] );

	const handleDragEnd = useCallback(
		( event ) => {
			const termId = event.over?.data?.current?.termId;
			const draggedId = event.active?.data?.current?.id;
			setActiveId( null );
			if ( ! termId || ! draggedId ) {
				return;
			}
			const ids = selection.selected.has( draggedId )
				? Array.from( selection.selected )
				: [ draggedId ];
			applyBulk( 'add', [ termId ], ids );
		},
		[ selection, applyBulk ]
	);

	const selectedItems = useMemo(
		() => items.filter( ( item ) => selection.selected.has( item.id ) ),
		[ items, selection ]
	);

	return (
		<DndContext
			sensors={ sensors }
			onDragStart={ handleDragStart }
			onDragEnd={ handleDragEnd }
			onDragCancel={ handleDragCancel }
		>
			<div className="smc-app">
				<h1 className="smc-app__title">
					{ __( 'Tag Media', 'simple-media-categories' ) }
				</h1>
				<div className="smc-app__body">
					<TermSidebar
						tree={ tree }
						filter={ filter }
						onFilter={ handleFilter }
					/>
					<MediaGrid
						items={ items }
						loading={ loading }
						total={ total }
						hasMore={ page < totalPages }
						search={ searchInput }
						onSearch={ setSearchInput }
						selected={ selection.selected }
						onSelect={ {
							toggle: selection.toggle,
							range: selection.range,
						} }
						onSelectAll={ selection.selectAll }
						onClear={ selection.clear }
						termsById={ termsById }
						draggingIds={ draggingSet }
						onLoadMore={ () => loadPage( page + 1, false ) }
					/>
					<BulkPanel
						selectedItems={ selectedItems }
						terms={ terms }
						termsById={ termsById }
						onApply={ applyBulk }
						onCreate={ handleCreate }
						onClearSelection={ selection.clear }
						busy={ busy }
					/>
				</div>
				<Toaster />
			</div>
			<DragOverlay>
				{ activeId !== null ? (
					<DragStackPreview
						items={ draggingIds
							.map( ( id ) => itemsById[ id ] )
							.filter( Boolean ) }
						count={ draggingIds.length }
					/>
				) : null }
			</DragOverlay>
		</DndContext>
	);
}
