/**
 * Tag Media admin app entry point.
 *
 * Mounts the React workspace into the page root rendered by SMC_Admin_Page.
 */
import { createRoot } from '@wordpress/element';

import App from './App';
import './style.scss';

const root = document.getElementById( 'smc-tag-media-root' );

if ( root ) {
	createRoot( root ).render( <App /> );
}
