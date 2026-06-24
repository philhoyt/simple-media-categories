/**
 * Root component for the Media Category Settings screen.
 */
import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { ToggleControl, Card, CardBody, Spinner } from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

import { getSettings, saveSettings } from './api';
import RetroRunner from './components/RetroRunner';
import Toaster from '../admin/components/Toaster';

export default function App() {
	const [ settings, setSettings ] = useState( null );
	const [ saving, setSaving ] = useState( false );
	const { createNotice } = useDispatch( noticesStore );

	useEffect( () => {
		getSettings()
			.then( setSettings )
			.catch( () =>
				setSettings( { auto_tag_post: true, auto_tag_mime: false } )
			);
	}, [] );

	const update = useCallback(
		async ( key, value ) => {
			const previous = settings;
			const next = { ...settings, [ key ]: value };
			setSettings( next );
			setSaving( true );
			try {
				setSettings( await saveSettings( next ) );
				createNotice(
					'success',
					__( 'Settings saved.', 'simple-media-categories' ),
					{ type: 'snackbar', isDismissible: true }
				);
			} catch {
				setSettings( previous );
				createNotice(
					'error',
					__( 'Could not save settings.', 'simple-media-categories' ),
					{ type: 'snackbar', isDismissible: true }
				);
			} finally {
				setSaving( false );
			}
		},
		[ settings, createNotice ]
	);

	if ( ! settings ) {
		return (
			<div className="smc-settings smc-settings--loading">
				<Spinner />
			</div>
		);
	}

	return (
		<div className="smc-settings">
			<h1 className="smc-settings__title">
				{ __( 'Media Category Settings', 'simple-media-categories' ) }
			</h1>

			<Card>
				<CardBody>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Auto-tag by attached post',
							'simple-media-categories'
						) }
						help={ __(
							'When media is uploaded to a post, tag it with the post type and the specific post.',
							'simple-media-categories'
						) }
						checked={ !! settings.auto_tag_post }
						disabled={ saving }
						onChange={ ( value ) =>
							update( 'auto_tag_post', value )
						}
					/>
					<ToggleControl
						__nextHasNoMarginBottom
						label={ __(
							'Auto-tag by file type',
							'simple-media-categories'
						) }
						help={ __(
							'Tag media by broad type — Images, Documents, Audio, Video, Other — under a File Type category.',
							'simple-media-categories'
						) }
						checked={ !! settings.auto_tag_mime }
						disabled={ saving }
						onChange={ ( value ) =>
							update( 'auto_tag_mime', value )
						}
					/>
				</CardBody>
			</Card>

			<RetroRunner
				enabled={ settings.auto_tag_post || settings.auto_tag_mime }
			/>

			<Toaster />
		</div>
	);
}
