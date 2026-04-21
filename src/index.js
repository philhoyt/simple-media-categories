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

		const TaxFilter = wp.media.view.AttachmentFilters.extend( {
			id:       filterId,
			label:    taxonomy.filter_label,
			priority: -75,

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

			/**
			 * Override select() so any falsy model value (undefined, false, null, '')
			 * correctly highlights "All categories".
			 */
			select() {
				const val = this.model.get( slug );
				if ( ! val ) {
					this.$el.val( '' );
					return;
				}
				wp.media.view.AttachmentFilters.prototype.select.apply( this, arguments );
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
