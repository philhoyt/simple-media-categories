<?php
/**
 * PHPUnit bootstrap for the WordPress integration test suite.
 *
 * @package SimpleMediaCategories
 */

declare(strict_types=1);

$wp_tests_dir = getenv( 'WP_TESTS_DIR' ) ?: rtrim( sys_get_temp_dir(), '/\\' ) . '/wordpress-tests-lib';

if ( ! file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	return; // WordPress test suite not available — nothing to bootstrap.
}

// Required by the WP Core test bootstrap.
define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', dirname( __DIR__, 2 ) . '/vendor/yoast/phpunit-polyfills' );

require_once $wp_tests_dir . '/includes/functions.php';

/**
 * Load the plugin under test before WordPress finishes booting.
 */
function _manually_load_plugin(): void {
	require dirname( __DIR__, 2 ) . '/simple-media-categories.php';
}
tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

require $wp_tests_dir . '/includes/bootstrap.php';
