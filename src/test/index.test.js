/**
 * Unit tests for the grid-view toolbar filter.
 *
 * The module is an IIFE that runs on import and wires itself into the global
 * `wp.media.view` namespace, so each test seeds the required globals, imports
 * the module in isolation, then asserts the side effects.
 */

/**
 * Build a minimal mock of the wp.media.view objects the module extends.
 *
 * @return {Object} Mock wp object plus spies for assertions.
 */
function setupWpMock() {
	const attachmentFiltersExtend = jest.fn( ( proto ) => {
		function TaxFilter( options ) {
			Object.assign( this, options, proto );
			this.$el = { val: jest.fn() };
			this.render = jest.fn( () => this );
		}
		TaxFilter.prototype = proto;
		return TaxFilter;
	} );

	const AttachmentFilters = {
		extend: attachmentFiltersExtend,
		prototype: { select: jest.fn() },
	};

	function AttachmentsBrowser() {}
	AttachmentsBrowser.prototype.createToolbar = jest.fn();
	AttachmentsBrowser.extend = jest.fn( ( proto ) => {
		function Extended() {}
		Extended.prototype = Object.create( AttachmentsBrowser.prototype );
		Object.assign( Extended.prototype, proto );
		return Extended;
	} );

	const wp = {
		media: {
			view: { AttachmentFilters, AttachmentsBrowser },
		},
	};

	return {
		wp,
		attachmentFiltersExtend,
		attachmentsBrowserExtend: AttachmentsBrowser.extend,
	};
}

/**
 * Import the module fresh with the given globals applied.
 *
 * @param {Object|undefined} taxonomies Value for the smcTaxonomies global.
 * @param {Object|undefined} wp         Value for the wp global.
 */
function loadModule( taxonomies, wp ) {
	window.smcTaxonomies = taxonomies;
	window.wp = wp;
	jest.isolateModules( () => {
		require( '../index.js' );
	} );
}

afterEach( () => {
	delete window.smcTaxonomies;
	delete window.wp;
	jest.resetModules();
} );

describe( 'grid-view taxonomy filter', () => {
	const taxonomies = {
		media_category: {
			list_title: 'All categories',
			filter_label: 'Filter by Category',
			term_list: [ { term_id: '5', term_name: 'Logos' } ],
		},
	};

	it( 'does nothing when smcTaxonomies is undefined', () => {
		const { wp, attachmentFiltersExtend } = setupWpMock();
		loadModule( undefined, wp );
		expect( attachmentFiltersExtend ).not.toHaveBeenCalled();
	} );

	it( 'does nothing when wp.media is unavailable', () => {
		loadModule( taxonomies, {} );
		// No throw, nothing to assert beyond surviving the guard clause.
		expect( window.wp.media ).toBeUndefined();
	} );

	it( 'extends AttachmentFilters and AttachmentsBrowser when data is present', () => {
		const { wp, attachmentFiltersExtend, attachmentsBrowserExtend } =
			setupWpMock();
		loadModule( taxonomies, wp );

		expect( attachmentFiltersExtend ).toHaveBeenCalledTimes( 1 );
		// The module reassigns wp.media.view.AttachmentsBrowser, so assert on
		// the original extend spy captured before the module ran.
		expect( attachmentsBrowserExtend ).toHaveBeenCalledTimes( 1 );
	} );

	it( 'builds an "all" filter plus one filter per term', () => {
		const { wp, attachmentFiltersExtend } = setupWpMock();
		loadModule( taxonomies, wp );

		const proto = attachmentFiltersExtend.mock.calls[ 0 ][ 0 ];
		const ctx = { filters: null };
		proto.createFilters.call( ctx );

		// Note: integer-like keys ('5') are ordered before '' by JS engines,
		// so assert on membership rather than order.
		expect( Object.keys( ctx.filters ).sort() ).toEqual( [ '', '5' ] );
		expect( ctx.filters[ '' ].text ).toBe( 'All categories' );
		expect( ctx.filters[ '5' ].props ).toEqual( { media_category: '5' } );
	} );

	it( 'select() resets to "All" when the model value is falsy', () => {
		const { wp, attachmentFiltersExtend } = setupWpMock();
		loadModule( taxonomies, wp );

		const proto = attachmentFiltersExtend.mock.calls[ 0 ][ 0 ];
		const $el = { val: jest.fn() };
		proto.select.call( { model: { get: () => '' }, $el } );

		expect( $el.val ).toHaveBeenCalledWith( '' );
	} );
} );
