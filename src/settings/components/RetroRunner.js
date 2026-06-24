/**
 * Retroactive "tag existing media" runner with a progress bar.
 */
import { useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { Button, Card, CardBody, ProgressBar } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

import { runRetag } from '../api';

export default function RetroRunner( { enabled } ) {
	const [ running, setRunning ] = useState( false );
	const [ progress, setProgress ] = useState( null );
	const { createNotice } = useDispatch( noticesStore );

	const run = async () => {
		setRunning( true );
		setProgress( { processed: 0, total: 0 } );

		try {
			await runRetag( setProgress );
			createNotice(
				'success',
				__( 'Existing media tagged.', 'simple-media-categories' ),
				{ type: 'snackbar', isDismissible: true }
			);
		} catch {
			createNotice(
				'error',
				__( 'Retagging failed.', 'simple-media-categories' ),
				{ type: 'snackbar', isDismissible: true }
			);
		} finally {
			setRunning( false );
		}
	};

	const percent =
		progress && progress.total
			? Math.round( ( progress.processed / progress.total ) * 100 )
			: 0;

	return (
		<Card className="smc-settings__retro">
			<CardBody>
				<h2 className="smc-settings__subhead">
					{ __( 'Tag existing media', 'simple-media-categories' ) }
				</h2>
				<p>
					{ __(
						'Run the enabled rules across all media already in your library.',
						'simple-media-categories'
					) }
				</p>
				<Button
					variant="primary"
					onClick={ run }
					isBusy={ running }
					disabled={ running || ! enabled }
				>
					{ running
						? __( 'Tagging…', 'simple-media-categories' )
						: __(
								'Tag existing media now',
								'simple-media-categories'
						  ) }
				</Button>
				{ ! enabled && (
					<p className="smc-settings__hint">
						{ __(
							'Enable at least one rule above first.',
							'simple-media-categories'
						) }
					</p>
				) }
				{ progress && (
					<div className="smc-settings__progress">
						<ProgressBar value={ percent } />
						<span className="smc-settings__progress-label">
							{ sprintf(
								/* translators: 1: processed count, 2: total count. */
								__(
									'%1$d of %2$d processed',
									'simple-media-categories'
								),
								progress.processed,
								progress.total
							) }
						</span>
					</div>
				) }
			</CardBody>
		</Card>
	);
}
