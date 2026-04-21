<?php
/**
 * Plugin Name:       Simple Media Categories
 * Plugin URI:        https://github.com/ph/simple-media-categories
 * Description:       Registers a hierarchical media category taxonomy on attachments. No settings. No front-end output.
 * Version:           1.0.0
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * Author:            Phil Hoyt
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       simple-media-categories
 *
 * @package SimpleMediaCategories
 */

defined( 'ABSPATH' ) || exit;

define( 'SMC_VERSION', '1.0.0' );
define( 'SMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMC_URL', plugin_dir_url( __FILE__ ) );

require_once SMC_DIR . 'includes/class-walkers.php';
require_once SMC_DIR . 'includes/class-taxonomy.php';

add_action(
	'plugins_loaded',
	function () {
		( new SMC_Taxonomy() )->register();
	}
);
