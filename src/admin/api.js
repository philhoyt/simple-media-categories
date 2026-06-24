/**
 * REST client for the simple-media-categories/v1 namespace.
 */
import apiFetch from '@wordpress/api-fetch';
import { addQueryArgs } from '@wordpress/url';

const NS = '/simple-media-categories/v1';

/**
 * Fetch the full term tree (flat list with parent/count/path).
 *
 * @return {Promise<Array>} Terms.
 */
export function getTerms() {
	return apiFetch( { path: `${ NS }/terms` } );
}

/**
 * Create a term under an optional parent.
 *
 * @param {string} name   Term name.
 * @param {number} parent Parent term ID (0 for top level).
 * @return {Promise<Object>} The created term.
 */
export function createTerm( name, parent = 0 ) {
	return apiFetch( {
		path: `${ NS }/terms`,
		method: 'POST',
		data: { name, parent },
	} );
}

/**
 * Fetch a page of media with filters, returning items plus pagination totals.
 *
 * @param {Object} filters Query params (term, untagged, search, mime, page, per_page).
 * @return {Promise<{items: Array, total: number, totalPages: number}>} Result.
 */
export async function getMedia( filters = {} ) {
	const response = await apiFetch( {
		path: addQueryArgs( `${ NS }/media`, filters ),
		parse: false,
	} );

	const items = await response.json();

	return {
		items,
		total: parseInt( response.headers.get( 'X-WP-Total' ), 10 ) || 0,
		totalPages:
			parseInt( response.headers.get( 'X-WP-TotalPages' ), 10 ) || 0,
	};
}

/**
 * Apply a bulk term operation to attachments.
 *
 * @param {number[]} ids    Attachment IDs.
 * @param {string}   action One of 'add', 'remove', 'set'.
 * @param {number[]} terms  Term IDs.
 * @return {Promise<{updated: Object, skipped: number[], counts: Object}>} Result.
 */
export function bulkUpdate( ids, action, terms = [] ) {
	return apiFetch( {
		path: `${ NS }/media/bulk`,
		method: 'POST',
		data: { ids, action, terms },
	} );
}
