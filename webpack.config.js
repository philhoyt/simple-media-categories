/**
 * Extend the default @wordpress/scripts webpack config with two entry points:
 * the existing media-grid filter (index) and the Tag Media admin app (admin).
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );

module.exports = {
	...defaultConfig,
	entry: {
		index: path.resolve( process.cwd(), 'src', 'index.js' ),
		admin: path.resolve( process.cwd(), 'src', 'admin', 'index.js' ),
		settings: path.resolve( process.cwd(), 'src', 'settings', 'index.js' ),
	},
};
