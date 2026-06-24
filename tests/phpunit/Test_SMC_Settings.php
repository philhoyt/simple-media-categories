<?php
/**
 * Tests for SMC_Settings.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

/**
 * @covers SMC_Settings
 */
class Test_SMC_Settings extends WP_UnitTestCase {

	public function tear_down(): void {
		delete_option( SMC_Settings::OPTION );
		parent::tear_down();
	}

	public function test_defaults_preserve_post_tagging(): void {
		$defaults = SMC_Settings::defaults();
		$this->assertTrue( $defaults['auto_tag_post'] );
		$this->assertFalse( $defaults['auto_tag_mime'] );
	}

	public function test_sanitize_coerces_to_booleans(): void {
		$settings = new SMC_Settings();
		$clean    = $settings->sanitize(
			array(
				'auto_tag_post' => '1',
				'auto_tag_mime' => 0,
				'unexpected'    => 'dropped',
			)
		);

		$this->assertSame(
			array(
				'auto_tag_post' => true,
				'auto_tag_mime' => false,
			),
			$clean
		);
	}

	public function test_get_returns_defaults_when_option_absent(): void {
		delete_option( SMC_Settings::OPTION );
		$settings = SMC_Settings::get();

		$this->assertTrue( $settings['auto_tag_post'] );
		$this->assertFalse( $settings['auto_tag_mime'] );
	}

	public function test_is_enabled_reads_the_option(): void {
		update_option(
			SMC_Settings::OPTION,
			array(
				'auto_tag_post' => false,
				'auto_tag_mime' => true,
			)
		);

		$this->assertFalse( SMC_Settings::is_enabled( 'post' ) );
		$this->assertTrue( SMC_Settings::is_enabled( 'mime' ) );
	}
}
