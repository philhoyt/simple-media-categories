/**
 * Bulk term editor for the current selection.
 *
 * Shows current terms across the selection with on-all vs on-some state,
 * and lets the user add, set (replace), or remove terms in one action.
 */
import { useState } from '@wordpress/element';
import { __, sprintf, _n } from '@wordpress/i18n';
import { Button } from '@wordpress/components';

import TermTokenInput from './TermTokenInput';
import { termDistribution } from '../utils/terms';

export default function BulkPanel( {
	selectedItems,
	terms,
	termsById,
	onApply,
	onCreate,
	onClearSelection,
	busy,
} ) {
	const [ pending, setPending ] = useState( [] );

	// Always render the panel so it never reflows the grid when a selection
	// appears or clears — show a placeholder when nothing is selected.
	if ( ! selectedItems.length ) {
		return (
			<aside className="smc-bulk smc-bulk--empty">
				<p className="smc-bulk__placeholder">
					{ __(
						'Select media to add or remove categories.',
						'simple-media-categories'
					) }
				</p>
			</aside>
		);
	}

	const distribution = termDistribution( selectedItems );
	const currentTermIds = Object.keys( distribution ).map( ( id ) =>
		parseInt( id, 10 )
	);

	const apply = async ( action ) => {
		await onApply( action, pending );
		setPending( [] );
	};

	return (
		<aside className="smc-bulk">
			<div className="smc-bulk__head">
				<strong>
					{ sprintf(
						/* translators: %d: number of selected items. */
						_n(
							'%d item selected',
							'%d items selected',
							selectedItems.length,
							'simple-media-categories'
						),
						selectedItems.length
					) }
				</strong>
				<Button variant="link" onClick={ onClearSelection }>
					{ __( 'Clear', 'simple-media-categories' ) }
				</Button>
			</div>

			<div className="smc-bulk__section">
				<h3 className="smc-bulk__label">
					{ __( 'Current categories', 'simple-media-categories' ) }
				</h3>
				{ currentTermIds.length ? (
					<div className="smc-bulk__chips">
						{ currentTermIds.map( ( id ) => {
							const info = distribution[ id ];
							return (
								<button
									key={ id }
									type="button"
									className={ `smc-chip smc-chip--removable${
										info.all ? '' : ' smc-chip--partial'
									}` }
									title={ __(
										'Click to remove from selection',
										'simple-media-categories'
									) }
									onClick={ () =>
										onApply( 'remove', [ id ] )
									}
								>
									{ termsById[ id ]?.name || `#${ id }` }
									{ ! info.all && (
										<span className="smc-chip__frac">
											{ ` ${ info.count }/${ selectedItems.length }` }
										</span>
									) }
									<span className="smc-chip__remove">×</span>
								</button>
							);
						} ) }
					</div>
				) : (
					<p className="smc-bulk__empty">
						{ __(
							'No categories on the selection.',
							'simple-media-categories'
						) }
					</p>
				) }
			</div>

			<div className="smc-bulk__section">
				<h3 className="smc-bulk__label">
					{ __( 'Add or replace', 'simple-media-categories' ) }
				</h3>
				<TermTokenInput
					terms={ terms }
					termsById={ termsById }
					value={ pending }
					onChange={ setPending }
					onCreate={ onCreate }
				/>
				<div className="smc-bulk__actions">
					<Button
						variant="primary"
						disabled={ ! pending.length || busy }
						isBusy={ busy }
						onClick={ () => apply( 'add' ) }
					>
						{ __( 'Add to selected', 'simple-media-categories' ) }
					</Button>
					<Button
						variant="secondary"
						disabled={ busy }
						onClick={ () => apply( 'set' ) }
					>
						{ __( 'Replace all', 'simple-media-categories' ) }
					</Button>
				</div>
				<p className="smc-bulk__hint">
					{ __(
						'Tip: drag selected media onto a category to tag it.',
						'simple-media-categories'
					) }
				</p>
			</div>
		</aside>
	);
}
