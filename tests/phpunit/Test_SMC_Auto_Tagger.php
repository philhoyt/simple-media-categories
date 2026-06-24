<?php
/**
 * Tests for SMC_Auto_Tagger — parent-post and file-type tagging rules.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @covers SMC_Auto_Tagger
 */
class Test_SMC_Auto_Tagger extends WP_UnitTestCase {

	/**
	 * Instance under test.
	 *
	 * @var SMC_Auto_Tagger
	 */
	private $tagger;

	public function set_up(): void {
		parent::set_up();
		$this->tagger = new SMC_Auto_Tagger();
	}

	private function make_attachment( array $args = array() ): int {
		return self::factory()->post->create(
			array_merge(
				array(
					'post_type'   => 'attachment',
					'post_status' => 'inherit',
				),
				$args
			)
		);
	}

	/* Parent-post rule -------------------------------------------------- */

	public function test_tag_by_post_creates_post_type_and_child_terms(): void {
		$parent     = self::factory()->post->create( array( 'post_title' => 'Hello World' ) );
		$attachment = $this->make_attachment( array( 'post_parent' => $parent ) );

		$this->tagger->tag_by_post( $attachment );

		$slugs = wp_get_object_terms( $attachment, 'media_category', array( 'fields' => 'slugs' ) );

		$this->assertContains( 'post', $slugs, 'Post-type term should be assigned.' );
		$this->assertContains( 'post-' . $parent, $slugs, 'Post-specific child term should be assigned.' );

		$child = get_term_by( 'slug', 'post-' . $parent, 'media_category' );
		$type  = get_term_by( 'slug', 'post', 'media_category' );
		$this->assertSame( (int) $type->term_id, (int) $child->parent, 'Child term should nest under the post-type term.' );
	}

	public function test_tag_by_post_skips_attachments_without_parent(): void {
		$attachment = $this->make_attachment();

		$this->tagger->tag_by_post( $attachment );

		$this->assertEmpty( wp_get_object_terms( $attachment, 'media_category' ) );
	}

	/* File-type rule ---------------------------------------------------- */

	public function test_mime_group_maps_broad_types(): void {
		$this->assertSame( 'images', $this->tagger->mime_group( 'image/jpeg' ) );
		$this->assertSame( 'audio', $this->tagger->mime_group( 'audio/mpeg' ) );
		$this->assertSame( 'video', $this->tagger->mime_group( 'video/mp4' ) );
		$this->assertSame( 'documents', $this->tagger->mime_group( 'application/pdf' ) );
		$this->assertSame( 'documents', $this->tagger->mime_group( 'text/plain' ) );
		$this->assertSame( 'other', $this->tagger->mime_group( 'font/woff2' ) );
	}

	public function test_tag_by_mime_creates_file_type_hierarchy(): void {
		$attachment = $this->make_attachment( array( 'post_mime_type' => 'application/pdf' ) );

		$this->tagger->tag_by_mime( $attachment );

		$slugs = wp_get_object_terms( $attachment, 'media_category', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'file-type-documents', $slugs );

		$child  = get_term_by( 'slug', 'file-type-documents', 'media_category' );
		$parent = get_term_by( 'slug', 'file-type', 'media_category' );
		$this->assertInstanceOf( WP_Term::class, $parent );
		$this->assertSame( (int) $parent->term_id, (int) $child->parent );
	}

	public function test_tag_attachment_respects_toggles(): void {
		$parent     = self::factory()->post->create( array( 'post_title' => 'Hello World' ) );
		$attachment = $this->make_attachment(
			array(
				'post_parent'    => $parent,
				'post_mime_type' => 'image/png',
			)
		);

		// Clear any terms applied by the add_attachment hook at creation time
		// so this exercises tag_attachment() in isolation.
		wp_set_object_terms( $attachment, array(), 'media_category' );

		// Only the mime rule enabled.
		update_option(
			SMC_Settings::OPTION,
			array(
				'auto_tag_post' => false,
				'auto_tag_mime' => true,
			)
		);

		$this->tagger->tag_attachment( $attachment );

		$slugs = wp_get_object_terms( $attachment, 'media_category', array( 'fields' => 'slugs' ) );
		$this->assertContains( 'file-type-images', $slugs, 'Mime rule should run.' );
		$this->assertNotContains( 'post', $slugs, 'Post rule should be skipped when disabled.' );

		delete_option( SMC_Settings::OPTION );
	}
}
