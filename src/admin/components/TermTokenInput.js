/**
 * Chip/token input with hierarchy-path autocomplete and inline term creation.
 *
 * Built bespoke because no single @wordpress/components control combines
 * hierarchical, path-aware suggestions with create-on-the-fly.
 */
import { useState, useRef } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';

export default function TermTokenInput( {
	terms,
	termsById,
	value,
	onChange,
	onCreate,
} ) {
	const [ query, setQuery ] = useState( '' );
	const [ open, setOpen ] = useState( false );
	const [ activeIndex, setActiveIndex ] = useState( 0 );
	const [ creating, setCreating ] = useState( false );
	const inputRef = useRef( null );

	const trimmed = query.trim();
	const available = terms.filter( ( term ) => ! value.includes( term.id ) );
	const matches = (
		trimmed
			? available.filter( ( term ) =>
					( term.path || term.name )
						.toLowerCase()
						.includes( trimmed.toLowerCase() )
			  )
			: available
	).slice( 0, 8 );

	const exactMatch = terms.find(
		( term ) => term.name.toLowerCase() === trimmed.toLowerCase()
	);
	const showCreate = !! trimmed && ! exactMatch;

	// Flattened option list for keyboard navigation.
	const options = [
		...matches.map( ( term ) => ( { type: 'term', term } ) ),
		...( showCreate ? [ { type: 'create' } ] : [] ),
	];

	const addToken = ( id ) => {
		onChange( [ ...value, id ] );
		setQuery( '' );
		setActiveIndex( 0 );
	};

	const removeToken = ( id ) => onChange( value.filter( ( v ) => v !== id ) );

	const handleCreate = async () => {
		if ( ! trimmed || creating ) {
			return;
		}
		setCreating( true );
		try {
			const term = await onCreate( trimmed );
			if ( term && term.id ) {
				addToken( term.id );
			}
		} finally {
			setCreating( false );
		}
	};

	const commit = ( option ) => {
		if ( ! option ) {
			return;
		}
		if ( option.type === 'create' ) {
			handleCreate();
		} else {
			addToken( option.term.id );
		}
	};

	const handleKeyDown = ( event ) => {
		if ( event.key === 'ArrowDown' ) {
			event.preventDefault();
			setOpen( true );
			setActiveIndex( ( i ) => Math.min( i + 1, options.length - 1 ) );
		} else if ( event.key === 'ArrowUp' ) {
			event.preventDefault();
			setActiveIndex( ( i ) => Math.max( i - 1, 0 ) );
		} else if ( event.key === 'Enter' ) {
			event.preventDefault();
			commit( options[ activeIndex ] );
		} else if ( event.key === 'Escape' ) {
			setOpen( false );
		} else if (
			event.key === 'Backspace' &&
			query === '' &&
			value.length
		) {
			removeToken( value[ value.length - 1 ] );
		}
	};

	return (
		<div className="smc-token">
			<div
				className="smc-token__field"
				onClick={ () => inputRef.current?.focus() }
				role="presentation"
			>
				{ value.map( ( id ) => (
					<span key={ id } className="smc-chip">
						{ termsById[ id ]?.name || `#${ id }` }
						<button
							type="button"
							className="smc-chip__remove"
							aria-label={ sprintf(
								/* translators: %s: term name. */
								__( 'Remove %s', 'simple-media-categories' ),
								termsById[ id ]?.name || id
							) }
							onClick={ ( event ) => {
								event.stopPropagation();
								removeToken( id );
							} }
						>
							×
						</button>
					</span>
				) ) }
				<input
					ref={ inputRef }
					type="text"
					className="smc-token__input"
					value={ query }
					placeholder={ __(
						'Add category…',
						'simple-media-categories'
					) }
					onChange={ ( event ) => {
						setQuery( event.target.value );
						setOpen( true );
						setActiveIndex( 0 );
					} }
					onFocus={ () => setOpen( true ) }
					onBlur={ () =>
						// Delay so option mousedown can register first.
						window.setTimeout( () => setOpen( false ), 120 )
					}
					onKeyDown={ handleKeyDown }
				/>
			</div>

			{ open && options.length > 0 && (
				<ul className="smc-token__menu">
					{ options.map( ( option, index ) => {
						const isActive = index === activeIndex;
						if ( option.type === 'create' ) {
							return (
								<li key="__create">
									<button
										type="button"
										className={ `smc-token__option smc-token__option--create${
											isActive ? ' is-active' : ''
										}` }
										onMouseDown={ ( event ) =>
											event.preventDefault()
										}
										onClick={ handleCreate }
									>
										{ sprintf(
											/* translators: %s: new term name. */
											__(
												'Create “%s”',
												'simple-media-categories'
											),
											trimmed
										) }
									</button>
								</li>
							);
						}
						return (
							<li key={ option.term.id }>
								<button
									type="button"
									className={ `smc-token__option${
										isActive ? ' is-active' : ''
									}` }
									onMouseDown={ ( event ) =>
										event.preventDefault()
									}
									onClick={ () => addToken( option.term.id ) }
								>
									{ option.term.path || option.term.name }
								</button>
							</li>
						);
					} ) }
				</ul>
			) }
		</div>
	);
}
