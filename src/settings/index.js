/**
 * Settings app entry point.
 *
 * Mounts the React settings screen into the root rendered by SMC_Settings_Page.
 */
import { createRoot } from '@wordpress/element';

import App from './App';
import './style.scss';

const root = document.getElementById( 'smc-settings-root' );

if ( root ) {
	createRoot( root ).render( <App /> );
}
