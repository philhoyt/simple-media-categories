/**
 * Grid-view toolbar filter for the media_category taxonomy.
 *
 * Extends wp.media.view.AttachmentFilters to add a category dropdown to the
 * media library grid toolbar and the block editor media modal. Reads term data
 * from the smcTaxonomies global printed by SMC_Taxonomy::enqueue_assets().
 */
( function () {
	'use strict';

	if ( typeof smcTaxonomies === 'undefined' || ! window.wp || ! wp.media ) {
		return;
	}

	Object.entries( smcTaxonomies ).forEach( ( [ slug, taxonomy ] ) => {
		const filterId = `media-attachment-${ slug }-filter`;

		/**
		 * Renders as <div class="media-filter-container"> so the label and
		 * select sit inside one wrapper, matching the built-in type/date filters.
		 * All event/model wiring stays inside this single view — no wrapper view needed.
		 */
		const TaxFilter = wp.media.view.AttachmentFilters.extend( {
			tagName:   'div',
			className: 'media-filter-container',

			// Delegate change events from the inner <select>, not the outer div.
			events: {
				'change select': 'change',
			},

			createFilters() {
				const filters = {
					'': {
						text:     taxonomy.list_title,
						props:    { [ slug ]: undefined },
						priority: 1,
					},
				};

				taxonomy.term_list.forEach( ( term ) => {
					filters[ term.term_id ] = {
						text:     term.term_name,
						props:    { [ slug ]: term.term_id },
						priority: 10,
					};
				} );

				this.filters = filters;
			},

			render() {
				// Build label + select once; subsequent render() calls just repopulate options.
				if ( ! this.$( 'select' ).length ) {
					this.$el
						.append( `<label for="${ filterId }">${ taxonomy.filter_label }</label>` )
						.append( `<select id="${ filterId }" class="attachment-filters"></select>` );
				}

				const options = _.chain( this.filters )
					.keys()
					.sortBy( ( key ) => this.filters[ key ].priority || 10 )
					.map( ( key ) => jQuery( '<option>' ).val( key ).html( this.filters[ key ].text )[ 0 ] )
					.value();

				this.$( 'select' ).html( options );
				this.select();
				return this;
			},

			select() {
				const val = this.model.get( slug );

				// Treat any falsy value (undefined, false, null, '') as "show all".
				if ( ! val ) {
					this.$( 'select' ).val( '' );
					return;
				}

				const match = _.find( _.keys( this.filters ), ( id ) => {
					return _.all( this.filters[ id ].props, ( prop, key ) => {
						return this.model.get( key ) === prop;
					} );
				} );

				this.$( 'select' ).val( match || '' );
			},

			change() {
				const value  = this.$( 'select' ).val();
				const filter = this.filters[ value ];

				if ( filter ) {
					this.model.set( filter.props );
				}
			},
		} );

		const WrappedBrowser = wp.media.view.AttachmentsBrowser;

		wp.media.view.AttachmentsBrowser = WrappedBrowser.extend( {
			createToolbar() {
				WrappedBrowser.prototype.createToolbar.apply( this, arguments );

				this.toolbar.set(
					`${ slug }Filter`,
					new TaxFilter( {
						controller: this.controller,
						model:      this.collection.props,
						priority:   -75,
					} ).render()
				);
			},
		} );
	} );
} )();
