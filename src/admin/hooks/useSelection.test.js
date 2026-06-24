/**
 * Unit tests for the useSelection hook.
 */
import { renderHook, act } from '@testing-library/react';

import useSelection from './useSelection';

describe( 'useSelection', () => {
	it( 'toggles an id on and off', () => {
		const { result } = renderHook( () => useSelection() );

		act( () => result.current.toggle( 5, 0 ) );
		expect( result.current.selected.has( 5 ) ).toBe( true );

		act( () => result.current.toggle( 5, 0 ) );
		expect( result.current.selected.has( 5 ) ).toBe( false );
	} );

	it( 'range-selects between the anchor and the new index', () => {
		const ids = [ 10, 20, 30, 40 ];
		const { result } = renderHook( () => useSelection() );

		act( () => result.current.toggle( 10, 0 ) );
		act( () => result.current.range( 2, ids ) );

		expect(
			[ ...result.current.selected ].sort( ( a, b ) => a - b )
		).toEqual( [ 10, 20, 30 ] );
	} );

	it( 'selects all and clears', () => {
		const { result } = renderHook( () => useSelection() );

		act( () => result.current.selectAll( [ 1, 2, 3 ] ) );
		expect( result.current.selected.size ).toBe( 3 );

		act( () => result.current.clear() );
		expect( result.current.selected.size ).toBe( 0 );
	} );
} );
