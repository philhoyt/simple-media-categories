<?php
/**
 * Constant definitions for static analysis.
 *
 * These are defined at runtime in the main plugin file; declaring them here as
 * a PHPStan bootstrap file lets analysis resolve them without executing the
 * plugin. Not loaded by WordPress.
 *
 * @package SimpleMediaCategories
 */

define( 'SMC_VERSION', '0.0.0' );
define( 'SMC_DIR', '/' );
define( 'SMC_URL', 'https://example.test/' );
