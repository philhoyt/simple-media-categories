/**
 * Snackbar toasts bound to the core notices store.
 */
import { SnackbarList } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

export default function Toaster() {
	const notices = useSelect(
		( select ) =>
			select( noticesStore )
				.getNotices()
				.filter( ( notice ) => notice.type === 'snackbar' ),
		[]
	);
	const { removeNotice } = useDispatch( noticesStore );

	if ( ! notices.length ) {
		return null;
	}

	return (
		<SnackbarList
			className="smc-toaster"
			notices={ notices }
			onRemove={ removeNotice }
		/>
	);
}
