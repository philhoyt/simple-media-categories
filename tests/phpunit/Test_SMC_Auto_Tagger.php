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
}
