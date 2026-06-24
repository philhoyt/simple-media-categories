/**
 * Unit tests for the settings REST client.
 */
import apiFetch from '@wordpress/api-fetch';

import { getSettings, saveSettings, retag, runRetag } from './api';

jest.mock( '@wordpress/api-fetch' );

afterEach( () => jest.clearAllMocks() );

describe( 'getSettings', () => {
	it( 'reads /wp/v2/settings and merges defaults', async () => {
		apiFetch.mockResolvedValue( { smc_settings: { auto_tag_mime: true } } );

		const settings = await getSettings();

		expect( apiFetch ).toHaveBeenCalledWith( { path: '/wp/v2/settings' } );
		expect( settings ).toEqual( {
			auto_tag_post: true,
			auto_tag_mime: true,
		} );
	} );
} );

describe( 'saveSettings', () => {
	it( 'POSTs under the smc_settings key', async () => {
		apiFetch.mockResolvedValue( {
			smc_settings: { auto_tag_post: false, auto_tag_mime: true },
		} );

		const result = await saveSettings( {
			auto_tag_post: false,
			auto_tag_mime: true,
		} );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/wp/v2/settings',
			method: 'POST',
			data: {
				smc_settings: { auto_tag_post: false, auto_tag_mime: true },
			},
		} );
		expect( result.auto_tag_post ).toBe( false );
	} );
} );

describe( 'retag', () => {
	it( 'POSTs the offset and batch size', async () => {
		apiFetch.mockResolvedValue( {
			total: 3,
			processed: 2,
			offset: 2,
			done: false,
		} );

		const result = await retag( 0, 50 );

		expect( apiFetch ).toHaveBeenCalledWith( {
			path: '/simple-media-categories/v1/maintenance/retag',
			method: 'POST',
			data: { offset: 0, batch_size: 50 },
		} );
		expect( result.total ).toBe( 3 );
	} );
} );

describe( 'runRetag', () => {
	it( 'loops with advancing offset until done and reports progress', async () => {
		apiFetch
			.mockResolvedValueOnce( {
				total: 3,
				processed: 2,
				offset: 2,
				done: false,
			} )
			.mockResolvedValueOnce( {
				total: 3,
				processed: 3,
				offset: 3,
				done: true,
			} );

		const progress = [];
		await runRetag( ( p ) => progress.push( p ), 2 );

		expect( apiFetch ).toHaveBeenCalledTimes( 2 );
		expect( apiFetch.mock.calls[ 0 ][ 0 ].data ).toEqual( {
			offset: 0,
			batch_size: 2,
		} );
		expect( apiFetch.mock.calls[ 1 ][ 0 ].data ).toEqual( {
			offset: 2,
			batch_size: 2,
		} );
		expect( progress ).toEqual( [
			{ processed: 2, total: 3 },
			{ processed: 3, total: 3 },
		] );
	} );

	it( 'stops if the offset fails to advance', async () => {
		apiFetch.mockResolvedValue( {
			total: 5,
			processed: 0,
			offset: 0,
			done: false,
		} );

		await runRetag( () => {}, 2 );

		expect( apiFetch ).toHaveBeenCalledTimes( 1 );
	} );
} );
