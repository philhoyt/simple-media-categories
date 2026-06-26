<?php
/**
 * Tests for the uninstall routine.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @coversNothing
 */
class Test_SMC_Uninstall extends WP_UnitTestCase {

	public function test_uninstall_deletes_the_settings_option_but_keeps_terms(): void {
		update_option( 'smc_settings', array( 'auto_tag_mime' => true ) );
		$term = self::factory()->term->create( array( 'taxonomy' => 'media_category' ) );

		$this->assertNotFalse( get_option( 'smc_settings' ) );

		if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			define( 'WP_UNINSTALL_PLUGIN', 'simple-media-categories/simple-media-categories.php' );
		}
		require dirname( __DIR__, 2 ) . '/uninstall.php';

		// register_setting() registers a default, so get_option() synthesizes
		// it even when the row is gone; a sentinel proves the row was deleted.
		$this->assertSame(
			'gone',
			get_option( 'smc_settings', 'gone' ),
			'Option row should be removed.'
		);
		$this->assertInstanceOf(
			WP_Term::class,
			get_term( $term, 'media_category' ),
			'Terms are user content and must survive uninstall.'
		);
	}
}
