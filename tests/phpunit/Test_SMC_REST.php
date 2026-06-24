<?php
/**
 * Tests for the simple-media-categories/v1 REST API.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @covers SMC_REST_Controller
 */
class Test_SMC_REST extends WP_Test_REST_TestCase {

	/**
	 * REST server instance.
	 *
	 * @var WP_REST_Server
	 */
	private $server;

	/**
	 * Editor user ID (has upload_files + manage_categories + edit_others_posts).
	 *
	 * @var int
	 */
	private $editor;

	/**
	 * Author user ID (has upload_files, but not edit_others_posts).
	 *
	 * @var int
	 */
	private $author;

	/**
	 * Subscriber user ID (no upload_files).
	 *
	 * @var int
	 */
	private $subscriber;

	public function set_up(): void {
		parent::set_up();

		global $wp_rest_server;
		$this->server = new WP_REST_Server();
		$wp_rest_server = $this->server;
		do_action( 'rest_api_init' );

		$this->editor     = self::factory()->user->create( array( 'role' => 'editor' ) );
		$this->author     = self::factory()->user->create( array( 'role' => 'author' ) );
		$this->subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
	}

	public function tear_down(): void {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/**
	 * Dispatch a REST request as a given user.
	 *
	 * @param string $method HTTP method.
	 * @param string $route  Route path.
	 * @param array  $params Request params.
	 * @param int    $user   User ID (0 for logged out).
	 * @return WP_REST_Response
	 */
	private function dispatch( string $method, string $route, array $params = array(), int $user = 0 ) {
		wp_set_current_user( $user );
		$request = new WP_REST_Request( $method, $route );
		foreach ( $params as $key => $value ) {
			$request->set_param( $key, $value );
		}
		return $this->server->dispatch( $request );
	}

	private function make_attachment( int $author = 0 ): int {
		return self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
				'post_author' => $author,
			)
		);
	}

	/* Terms ------------------------------------------------------------ */

	public function test_terms_list_rejects_users_without_upload_files(): void {
		$response = $this->dispatch( 'GET', '/simple-media-categories/v1/terms', array(), $this->subscriber );
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_terms_list_returns_tree_with_path(): void {
		$parent = self::factory()->term->create(
			array(
				'taxonomy' => 'media_category',
				'name'     => 'Posts',
			)
		);
		self::factory()->term->create(
			array(
				'taxonomy' => 'media_category',
				'name'     => 'Hello',
				'parent'   => $parent,
			)
		);

		$response = $this->dispatch( 'GET', '/simple-media-categories/v1/terms', array(), $this->editor );
		$this->assertSame( 200, $response->get_status() );

		$paths = wp_list_pluck( $response->get_data(), 'path' );
		$this->assertContains( 'Posts / Hello', $paths );
	}

	public function test_create_term_requires_manage_categories(): void {
		$response = $this->dispatch(
			'POST',
			'/simple-media-categories/v1/terms',
			array( 'name' => 'Brand' ),
			$this->author
		);
		$this->assertSame( 403, $response->get_status() );
	}

	public function test_create_term_succeeds_for_editor(): void {
		$response = $this->dispatch(
			'POST',
			'/simple-media-categories/v1/terms',
			array( 'name' => 'Brand' ),
			$this->editor
		);

		$this->assertSame( 201, $response->get_status() );
		$this->assertSame( 'Brand', $response->get_data()['name'] );
		$this->assertInstanceOf( WP_Term::class, get_term_by( 'name', 'Brand', 'media_category' ) );
	}

	/* Media listing ---------------------------------------------------- */

	public function test_media_list_returns_attachments(): void {
		$this->make_attachment();
		$this->make_attachment();

		$response = $this->dispatch( 'GET', '/simple-media-categories/v1/media', array(), $this->editor );

		$this->assertSame( 200, $response->get_status() );
		$this->assertCount( 2, $response->get_data() );
		$this->assertSame( '2', $response->get_headers()['X-WP-Total'] );
	}

	public function test_media_untagged_filter(): void {
		$term   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$tagged = $this->make_attachment();
		$this->make_attachment();
		wp_set_object_terms( $tagged, array( $term ), 'media_category' );

		$response = $this->dispatch(
			'GET',
			'/simple-media-categories/v1/media',
			array( 'untagged' => true ),
			$this->editor
		);

		$ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertNotContains( $tagged, $ids );
		$this->assertCount( 1, $ids );
	}

	public function test_media_term_filter(): void {
		$term   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$tagged = $this->make_attachment();
		$this->make_attachment();
		wp_set_object_terms( $tagged, array( $term ), 'media_category' );

		$response = $this->dispatch(
			'GET',
			'/simple-media-categories/v1/media',
			array( 'term' => $term ),
			$this->editor
		);

		$ids = wp_list_pluck( $response->get_data(), 'id' );
		$this->assertSame( array( $tagged ), $ids );
	}

	/* Bulk updates ----------------------------------------------------- */

	public function test_bulk_add_assigns_terms(): void {
		$term = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$att  = $this->make_attachment();

		$response = $this->dispatch(
			'POST',
			'/simple-media-categories/v1/media/bulk',
			array(
				'ids'    => array( $att ),
				'action' => 'add',
				'terms'  => array( $term ),
			),
			$this->editor
		);

		$this->assertSame( 200, $response->get_status() );
		$this->assertContains( $term, wp_get_object_terms( $att, 'media_category', array( 'fields' => 'ids' ) ) );
	}

	public function test_bulk_set_replaces_terms(): void {
		$a   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$b   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$att = $this->make_attachment();
		wp_set_object_terms( $att, array( $a ), 'media_category' );

		$this->dispatch(
			'POST',
			'/simple-media-categories/v1/media/bulk',
			array(
				'ids'    => array( $att ),
				'action' => 'set',
				'terms'  => array( $b ),
			),
			$this->editor
		);

		$ids = wp_get_object_terms( $att, 'media_category', array( 'fields' => 'ids' ) );
		$this->assertSame( array( $b ), $ids );
	}

	public function test_bulk_remove_drops_terms(): void {
		$a   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$b   = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		$att = $this->make_attachment();
		wp_set_object_terms( $att, array( $a, $b ), 'media_category' );

		$this->dispatch(
			'POST',
			'/simple-media-categories/v1/media/bulk',
			array(
				'ids'    => array( $att ),
				'action' => 'remove',
				'terms'  => array( $a ),
			),
			$this->editor
		);

		$ids = wp_get_object_terms( $att, 'media_category', array( 'fields' => 'ids' ) );
		$this->assertSame( array( $b ), $ids );
	}

	public function test_bulk_skips_items_without_edit_capability(): void {
		$term = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );
		// Attachment owned by the editor; the author cannot edit others' posts.
		$att = $this->make_attachment( $this->editor );

		$response = $this->dispatch(
			'POST',
			'/simple-media-categories/v1/media/bulk',
			array(
				'ids'    => array( $att ),
				'action' => 'add',
				'terms'  => array( $term ),
			),
			$this->author
		);

		$this->assertContains( $att, $response->get_data()['skipped'] );
		$this->assertEmpty( wp_get_object_terms( $att, 'media_category', array( 'fields' => 'ids' ) ) );
	}

	public function test_bulk_rejects_users_without_upload_files(): void {
		$response = $this->dispatch(
			'POST',
			'/simple-media-categories/v1/media/bulk',
			array(
				'ids'    => array( 1 ),
				'action' => 'add',
				'terms'  => array( 1 ),
			),
			$this->subscriber
		);
		$this->assertSame( 403, $response->get_status() );
	}
}
