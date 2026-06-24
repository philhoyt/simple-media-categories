/**
 * Term tree helpers shared across the workspace.
 */

/**
 * Index a flat term list by ID.
 *
 * @param {Array} terms Flat term list.
 * @return {Object} Map of id -> term.
 */
export function indexTerms( terms ) {
	const byId = {};
	terms.forEach( ( term ) => {
		byId[ term.id ] = term;
	} );
	return byId;
}

/**
 * Build a nested tree from a flat term list.
 *
 * @param {Array} terms Flat term list with `parent` references.
 * @return {Array} Root nodes, each with a `children` array.
 */
export function buildTree( terms ) {
	const nodes = {};
	terms.forEach( ( term ) => {
		nodes[ term.id ] = { ...term, children: [] };
	} );

	const roots = [];
	terms.forEach( ( term ) => {
		if ( term.parent && nodes[ term.parent ] ) {
			nodes[ term.parent ].children.push( nodes[ term.id ] );
		} else {
			roots.push( nodes[ term.id ] );
		}
	} );

	return roots;
}

/**
 * Summarise how a set of term IDs is distributed across selected items.
 *
 * @param {Array} items Selected media items (each with a `terms` array).
 * @return {Object} Map of term id -> { count, all } where `all` means present on every item.
 */
export function termDistribution( items ) {
	const dist = {};
	const total = items.length;

	items.forEach( ( item ) => {
		( item.terms || [] ).forEach( ( termId ) => {
			dist[ termId ] = ( dist[ termId ] || 0 ) + 1;
		} );
	} );

	const result = {};
	Object.keys( dist ).forEach( ( termId ) => {
		result[ termId ] = {
			count: dist[ termId ],
			all: dist[ termId ] === total,
		};
	} );

	return result;
}
