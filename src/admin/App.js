/**
 * Root component for the Tag Media workspace.
 *
 * Built out incrementally — this scaffold renders the page shell.
 */
import { __ } from '@wordpress/i18n';

export default function App() {
	return (
		<div className="smc-app">
			<h1 className="smc-app__title">
				{ __( 'Tag Media', 'simple-media-categories' ) }
			</h1>
		</div>
	);
}
