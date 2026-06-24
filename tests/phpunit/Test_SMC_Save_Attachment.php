<?php
/**
 * Tests for the save_attachment_compat AJAX handler — the nonce + capability
 * gating added in SEC-01.
 *
 * The handler is invoked directly (rather than via _handleAjax) so the nonce
 * and capability branches can be asserted in isolation without core's
 * priority-10 handler terminating the request.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @covers SMC_Taxonomy::save_attachment_compat
 */
class Test_SMC_Save_Attachment extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var SMC_Taxonomy
	 */
	private $taxonomy;

	/**
	 * Attachment created per test.
	 *
	 * @var int
	 */
	private $attachment;

	public function set_up(): void {
		parent::set_up();
		$this->taxonomy = new SMC_Taxonomy();

		self::factory()->term->create(
			array(
				'taxonomy' => 'media_category',
				'slug'     => 'logos',
				'name'     => 'Logos',
			)
		);

		$this->attachment = self::factory()->post->create(
			array(
				'post_type'   => 'attachment',
				'post_status' => 'inherit',
			)
		);

		$_REQUEST['id']                              = (string) $this->attachment;
		$_REQUEST['tax_input']['media_category']     = array( 'logos' => 'logos' );
	}

	public function tear_down(): void {
		unset( $_REQUEST['id'], $_REQUEST['nonce'], $_REQUEST['tax_input'] );
		parent::tear_down();
	}

	public function test_missing_nonce_does_not_assign_terms(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		// No $_REQUEST['nonce'] set.

		$this->taxonomy->save_attachment_compat();

		$this->assertEmpty(
			wp_get_object_terms( $this->attachment, 'media_category' ),
			'Terms must not be saved without a valid nonce.'
		);
	}

	public function test_invalid_nonce_does_not_assign_terms(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$_REQUEST['nonce'] = 'not-a-real-nonce';

		$this->taxonomy->save_attachment_compat();

		$this->assertEmpty( wp_get_object_terms( $this->attachment, 'media_category' ) );
	}

	public function test_user_without_capability_does_not_assign_terms(): void {
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		wp_set_current_user( $subscriber );
		$_REQUEST['nonce'] = wp_create_nonce( 'save-attachment-compat' );

		$this->taxonomy->save_attachment_compat();

		$this->assertEmpty(
			wp_get_object_terms( $this->attachment, 'media_category' ),
			'Terms must not be saved for users without edit_post.'
		);
	}

	public function test_valid_nonce_and_capability_assigns_terms(): void {
		$editor = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor );
		$_REQUEST['nonce'] = wp_create_nonce( 'save-attachment-compat' );

		$this->taxonomy->save_attachment_compat();

		$slugs = wp_get_object_terms( $this->attachment, 'media_category', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'logos', $slugs );
	}
}
