/**
 * Unit tests for term tree helpers.
 */
import { buildTree, indexTerms, termDistribution } from './terms';

describe( 'indexTerms', () => {
	it( 'keys terms by id', () => {
		const byId = indexTerms( [ { id: 3, name: 'A' } ] );
		expect( byId[ 3 ].name ).toBe( 'A' );
	} );
} );

describe( 'buildTree', () => {
	it( 'nests children under their parent', () => {
		const tree = buildTree( [
			{ id: 1, name: 'Parent', parent: 0 },
			{ id: 2, name: 'Child', parent: 1 },
		] );

		expect( tree ).toHaveLength( 1 );
		expect( tree[ 0 ].children ).toHaveLength( 1 );
		expect( tree[ 0 ].children[ 0 ].id ).toBe( 2 );
	} );

	it( 'treats terms with a missing parent as roots', () => {
		const tree = buildTree( [ { id: 5, name: 'Orphan', parent: 99 } ] );
		expect( tree ).toHaveLength( 1 );
		expect( tree[ 0 ].id ).toBe( 5 );
	} );
} );

describe( 'termDistribution', () => {
	it( 'flags terms on every item as "all" and others as partial', () => {
		const dist = termDistribution( [
			{ terms: [ 1, 2 ] },
			{ terms: [ 1 ] },
		] );

		expect( dist[ 1 ] ).toEqual( { count: 2, all: true } );
		expect( dist[ 2 ] ).toEqual( { count: 1, all: false } );
	} );

	it( 'handles items without terms', () => {
		const dist = termDistribution( [ {}, { terms: [ 7 ] } ] );
		expect( dist[ 7 ] ).toEqual( { count: 1, all: false } );
	} );
} );
