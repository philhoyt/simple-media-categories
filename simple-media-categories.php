<?php
/**
 * Plugin Name:       Simple Media Categories
 * Plugin URI:        https://github.com/philhoyt/simple-media-categories
 * Description:       Adds a hierarchical category taxonomy to the media library, with filters in the list and grid views, bulk tagging, and auto-tagging on upload.
 * Version:           1.3.2
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

define( 'SMC_VERSION', '1.3.2' );
define( 'SMC_DIR', plugin_dir_path( __FILE__ ) );
define( 'SMC_URL', plugin_dir_url( __FILE__ ) );

require_once SMC_DIR . 'includes/class-walkers.php';
require_once SMC_DIR . 'includes/class-settings.php';
require_once SMC_DIR . 'includes/class-auto-tagger.php';
require_once SMC_DIR . 'includes/class-taxonomy.php';
require_once SMC_DIR . 'includes/class-rest-controller.php';
require_once SMC_DIR . 'includes/class-admin-page.php';
require_once SMC_DIR . 'includes/class-settings-page.php';

require_once SMC_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

$smc_update_checker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/philhoyt/simple-media-categories/',
	__FILE__,
	'simple-media-categories'
);

// Serve the zip attached to each GitHub release (GitHub VCS API only).
// Without a name filter the first asset wins, and GitHub lists assets by
// name, so readme.txt would come ahead of simple-media-categories.zip and
// WordPress would fail with PCLZIP_ERR_BAD_FORMAT. Only a .zip asset counts,
// and a release without one is skipped rather than falling back to GitHub's
// source archive, which installs as <Repo>-<tag>/ and breaks the directory.
$smc_vcs_api = $smc_update_checker->getVcsApi();
if ( method_exists( $smc_vcs_api, 'enableReleaseAssets' ) ) {
	$smc_vcs_api->enableReleaseAssets( '/\.zip$/', $smc_vcs_api::REQUIRE_RELEASE_ASSETS );
}

add_action(
	'plugins_loaded',
	function () {
		( new SMC_Settings() )->register();
		( new SMC_Auto_Tagger() )->register();
		( new SMC_Taxonomy() )->register();
		( new SMC_REST_Controller() )->register();
		( new SMC_Admin_Page() )->register();
		( new SMC_Settings_Page() )->register();
	}
);
