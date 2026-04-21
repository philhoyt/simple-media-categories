<?php
/**
 * Taxonomy walkers.
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

/**
 * List-view <select> dropdown walker.
 *
 * Used by wp_dropdown_categories() on the upload.php screen. Outputs slug-valued
 * <option> elements with depth-based &nbsp; padding and count support.
 */
class SMC_Walker_Filter extends Walker_CategoryDropdown {

	/**
	 * @param string $output  Passed by reference. Used to append additional content.
	 * @param object $term    The current term object.
	 * @param int    $depth   Depth of the term.
	 * @param array  $args    An array of arguments.
	 * @param int    $id      ID of the current term.
	 */
	public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
		$pad      = str_repeat( '&nbsp;', $depth * 3 );
		$selected = isset( $args['selected'] ) ? (string) $args['selected'] : '';
		$count    = ! empty( $args['show_count'] ) ? '&nbsp;&nbsp;(' . $term->count . ')' : '';

		$output .= sprintf(
			'<option value="%s"%s>%s%s%s</option>',
			esc_attr( $term->slug ),
			selected( $term->slug, $selected, false ),
			$pad,
			esc_html( $term->name ),
			$count
		);
	}
}

/**
 * Grid-view JSON fragment walker.
 *
 * Used to serialize the term tree into the smcTaxonomies JS global. Outputs
 * comma-prefixed JSON objects instead of <option> elements so the result can
 * be assembled into a JSON array after the <select> wrapper is stripped.
 */
class SMC_Walker_Grid_Filter extends Walker_CategoryDropdown {

	/**
	 * @param string $output Passed by reference.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {}

	/**
	 * @param string $output Passed by reference.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {}

	/**
	 * @param string $output Passed by reference.
	 * @param object $term   The current term object.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function end_el( &$output, $term, $depth = 0, $args = array() ) {}

	/**
	 * @param string $output Passed by reference.
	 * @param object $term   The current term object.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 * @param int    $id     ID of the current term.
	 */
	public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
		$prefix    = $depth > 0 ? str_repeat( '  ', $depth ) . '— ' : '';
		$term_name = $prefix . $term->name;

		$output .= ',' . wp_json_encode(
			array(
				'term_id'   => (string) $term->term_id,
				'term_name' => $term_name,
			)
		);
	}
}

/**
 * Attachment details sidebar checklist walker.
 *
 * Renders the hierarchical checkbox list inside the .compat-attachment-fields
 * panel in the grid view and media modal. Term slugs are used as both the
 * array key and value so save_attachment_compat can identify terms by slug.
 */
class SMC_Walker_Checklist extends Walker {

	/** @var string */
	public $tree_type = 'category';

	/** @var array */
	public $db_fields = array(
		'parent' => 'parent',
		'id'     => 'term_id',
	);

	/**
	 * @param string $output Passed by reference.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '<ul class="children">';
	}

	/**
	 * @param string $output Passed by reference.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = array() ) {
		$output .= '</ul>';
	}

	/**
	 * @param string $output Passed by reference.
	 * @param object $term   The current term object.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 * @param int    $id     ID of the current term.
	 */
	public function start_el( &$output, $term, $depth = 0, $args = array(), $id = 0 ) {
		$selected_cats = isset( $args['selected_cats'] ) ? (array) $args['selected_cats'] : array();
		$checked       = in_array( $term->term_id, $selected_cats, true );

		$output .= '<li>';
		$output .= sprintf(
			'<label><input type="checkbox" name="tax_input[media_category][%s]" id="in-media-category-%d" value="%s"%s> %s</label>',
			esc_attr( $term->slug ),
			$term->term_id,
			esc_attr( $term->slug ),
			checked( $checked, true, false ),
			esc_html( $term->name )
		);
	}

	/**
	 * @param string $output Passed by reference.
	 * @param object $term   The current term object.
	 * @param int    $depth  Depth of the term.
	 * @param array  $args   An array of arguments.
	 */
	public function end_el( &$output, $term, $depth = 0, $args = array() ) {
		$output .= '</li>';
	}
}
