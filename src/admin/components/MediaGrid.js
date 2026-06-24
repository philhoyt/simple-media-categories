/**
 * The searchable, paginated media grid with multi-select.
 */
import { __, sprintf } from '@wordpress/i18n';
import { Button, SearchControl, Spinner } from '@wordpress/components';

import MediaCard from './MediaCard';

export default function MediaGrid( {
	items,
	loading,
	total,
	hasMore,
	search,
	onSearch,
	selected,
	onSelect,
	onSelectAll,
	onClear,
	termsById,
	onLoadMore,
} ) {
	const orderedIds = items.map( ( item ) => item.id );

	const handleSelect = ( id, index, event ) => {
		if ( event.shiftKey ) {
			onSelect.range( index, orderedIds );
		} else {
			onSelect.toggle( id, index );
		}
	};

	const termNamesFor = ( item ) =>
		( item.terms || [] )
			.map( ( termId ) => termsById[ termId ]?.name )
			.filter( Boolean );

	return (
		<div className="smc-grid-wrap">
			<div className="smc-grid-toolbar">
				<SearchControl
					__nextHasNoMarginBottom
					value={ search }
					onChange={ onSearch }
					placeholder={ __(
						'Search media…',
						'simple-media-categories'
					) }
				/>
				<div className="smc-grid-toolbar__actions">
					<span className="smc-grid-toolbar__count">
						{ sprintf(
							/* translators: 1: number of selected items, 2: total items. */
							__(
								'%1$d selected of %2$d',
								'simple-media-categories'
							),
							selected.size,
							total
						) }
					</span>
					<Button
						variant="tertiary"
						onClick={ () => onSelectAll( orderedIds ) }
						disabled={ ! items.length }
					>
						{ __( 'Select all', 'simple-media-categories' ) }
					</Button>
					<Button
						variant="tertiary"
						onClick={ onClear }
						disabled={ ! selected.size }
					>
						{ __( 'Clear', 'simple-media-categories' ) }
					</Button>
				</div>
			</div>

			{ loading && ! items.length ? (
				<div className="smc-grid-empty">
					<Spinner />
				</div>
			) : null }

			{ ! loading && ! items.length ? (
				<p className="smc-grid-empty">
					{ __( 'No media found.', 'simple-media-categories' ) }
				</p>
			) : null }

			<div className="smc-grid">
				{ items.map( ( item, index ) => (
					<MediaCard
						key={ item.id }
						item={ item }
						index={ index }
						selected={ selected.has( item.id ) }
						onSelect={ handleSelect }
						termNames={ termNamesFor( item ) }
					/>
				) ) }
			</div>

			{ hasMore && (
				<div className="smc-grid-more">
					<Button
						variant="secondary"
						onClick={ onLoadMore }
						isBusy={ loading }
						disabled={ loading }
					>
						{ __( 'Load more', 'simple-media-categories' ) }
					</Button>
				</div>
			) }
		</div>
	);
}
