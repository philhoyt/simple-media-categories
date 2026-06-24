<?php
/**
 * Integration tests for taxonomy registration and query/data behaviour.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @covers SMC_Taxonomy
 */
class Test_SMC_Taxonomy extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var SMC_Taxonomy
	 */
	private $taxonomy;

	public function set_up(): void {
		parent::set_up();
		$this->taxonomy = new SMC_Taxonomy();
	}

	public function tear_down(): void {
		unset( $_REQUEST['query'] );
		parent::tear_down();
	}

	public function test_taxonomy_is_registered_on_attachments(): void {
		$this->assertTrue( taxonomy_exists( 'media_category' ) );
		$this->assertContains(
			'media_category',
			get_object_taxonomies( 'attachment' )
		);
	}

	public function test_taxonomy_is_exposed_to_rest(): void {
		$tax = get_taxonomy( 'media_category' );
		$this->assertTrue( $tax->hierarchical );
		$this->assertTrue( $tax->show_in_rest );
		$this->assertFalse( $tax->public );
	}

	public function test_filter_ajax_query_adds_tax_query(): void {
		$_REQUEST['query']['media_category'] = '7';

		$result = $this->taxonomy->filter_ajax_query( array() );

		$this->assertArrayHasKey( 'tax_query', $result );
		$this->assertSame( 7, $result['tax_query'][0]['terms'] );
		$this->assertSame( 'term_id', $result['tax_query'][0]['field'] );
	}

	public function test_filter_ajax_query_ignores_empty_request(): void {
		$result = $this->taxonomy->filter_ajax_query( array( 'foo' => 'bar' ) );

		$this->assertArrayNotHasKey( 'tax_query', $result );
		$this->assertSame( array( 'foo' => 'bar' ), $result );
	}

	public function test_register_bulk_action_adds_edit_categories(): void {
		$actions = $this->taxonomy->register_bulk_action( array() );

		$this->assertArrayHasKey( 'smc_edit_categories', $actions );
	}

	public function test_handle_bulk_action_passes_through_other_actions(): void {
		$url = $this->taxonomy->handle_bulk_action( 'http://example.test', 'delete', array( 1 ) );

		$this->assertSame( 'http://example.test', $url );
	}

	public function test_handle_bulk_action_builds_edit_url(): void {
		$url = $this->taxonomy->handle_bulk_action( 'http://example.test', 'smc_edit_categories', array( 5, 9 ) );

		$this->assertStringContainsString( 'smc_bulk_cats=1', $url );
		$this->assertStringContainsString( 'upload.php', $url );
	}

	public function test_term_count_callback_counts_only_attachments(): void {
		$term       = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$attachment = self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
			)
		);

		// Triggers update_attachment_term_count via update_count_callback.
		wp_set_object_terms( $attachment, array( $term ), 'media_category' );

		$this->assertSame( 1, (int) get_term( $term, 'media_category' )->count );
	}
}
