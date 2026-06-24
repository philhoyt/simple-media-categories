/**
 * ESLint flat config.
 *
 * ESLint 9 (bundled with @wordpress/scripts 32) ignores legacy .eslintrc.*
 * files. This extends the default WordPress flat config and declares the
 * plugin's runtime globals.
 */
const wordpress = require( '@wordpress/scripts/config/eslint.config.cjs' );

module.exports = [
	...wordpress,
	{
		languageOptions: {
			globals: {
				smcTaxonomies: 'readonly',
				wp: 'readonly',
			},
		},
	},
];
