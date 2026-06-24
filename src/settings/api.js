/**
 * REST client for the settings screen.
 *
 * Settings ride core's /wp/v2/settings (the option is registered with
 * show_in_rest); the retroactive runner uses the plugin's maintenance route.
 */
import apiFetch from '@wordpress/api-fetch';

const SETTINGS_KEY = 'smc_settings';
const DEFAULTS = { auto_tag_post: true, auto_tag_mime: false };

/**
 * Load the plugin settings.
 *
 * @return {Promise<{auto_tag_post: boolean, auto_tag_mime: boolean}>} Settings.
 */
export async function getSettings() {
	const all = await apiFetch( { path: '/wp/v2/settings' } );
	return { ...DEFAULTS, ...( all[ SETTINGS_KEY ] || {} ) };
}

/**
 * Persist the plugin settings.
 *
 * @param {Object} settings Settings to save.
 * @return {Promise<{auto_tag_post: boolean, auto_tag_mime: boolean}>} Saved settings.
 */
export async function saveSettings( settings ) {
	const all = await apiFetch( {
		path: '/wp/v2/settings',
		method: 'POST',
		data: { [ SETTINGS_KEY ]: settings },
	} );
	return { ...DEFAULTS, ...( all[ SETTINGS_KEY ] || settings ) };
}

/**
 * Process one batch of the retroactive tagging run.
 *
 * @param {number} offset    Starting offset.
 * @param {number} batchSize Items per batch.
 * @return {Promise<{total: number, processed: number, offset: number, done: boolean}>} Progress.
 */
export function retag( offset = 0, batchSize = 50 ) {
	return apiFetch( {
		path: '/simple-media-categories/v1/maintenance/retag',
		method: 'POST',
		data: { offset, batch_size: batchSize },
	} );
}

/**
 * Run the full retroactive tagging loop, batch by batch, until done.
 *
 * @param {Function} onProgress Called with { processed, total } after each batch.
 * @param {number}   batchSize  Items per batch.
 * @return {Promise<void>} Resolves when every batch is processed.
 */
export async function runRetag( onProgress, batchSize = 50 ) {
	let offset = 0;
	let done = false;

	while ( ! done ) {
		const res = await retag( offset, batchSize );
		if ( onProgress ) {
			onProgress( { processed: res.processed, total: res.total } );
		}
		// Stop on the server's done flag, or if the offset fails to advance.
		done = res.done || res.offset === offset;
		offset = res.offset;
	}
}
