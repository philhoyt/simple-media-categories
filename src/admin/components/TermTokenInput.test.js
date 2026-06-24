/**
 * Unit tests for the TermTokenInput autocomplete + create flow.
 */
import { render, screen, fireEvent, waitFor } from '@testing-library/react';

import TermTokenInput from './TermTokenInput';

const terms = [
	{ id: 1, name: 'Hello', path: 'Posts / Hello' },
	{ id: 2, name: 'Logos', path: 'Logos' },
];
const termsById = { 1: terms[ 0 ], 2: terms[ 1 ] };

function setup( props = {} ) {
	const onChange = jest.fn();
	const onCreate = jest.fn();
	render(
		<TermTokenInput
			terms={ terms }
			termsById={ termsById }
			value={ [] }
			onChange={ onChange }
			onCreate={ onCreate }
			{ ...props }
		/>
	);
	return { onChange, onCreate };
}

it( 'shows path-aware suggestions and selects one', () => {
	const { onChange } = setup();

	fireEvent.change( screen.getByPlaceholderText( /add category/i ), {
		target: { value: 'hel' },
	} );

	fireEvent.click( screen.getByText( 'Posts / Hello' ) );
	expect( onChange ).toHaveBeenCalledWith( [ 1 ] );
} );

it( 'offers an inline "Create" option and adds the new term', async () => {
	const onCreate = jest.fn().mockResolvedValue( { id: 9, name: 'Brand' } );
	const onChange = jest.fn();
	render(
		<TermTokenInput
			terms={ terms }
			termsById={ termsById }
			value={ [] }
			onChange={ onChange }
			onCreate={ onCreate }
		/>
	);

	fireEvent.change( screen.getByPlaceholderText( /add category/i ), {
		target: { value: 'Brand' },
	} );

	fireEvent.click( screen.getByText( /Create/ ) );

	expect( onCreate ).toHaveBeenCalledWith( 'Brand' );
	await waitFor( () => expect( onChange ).toHaveBeenCalledWith( [ 9 ] ) );
} );

it( 'renders existing tokens as chips', () => {
	setup( { value: [ 2 ] } );
	expect( screen.getByText( 'Logos' ) ).toBeTruthy();
} );
