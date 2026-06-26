<?php
/**
 * Uninstall routine.
 *
 * Removes the plugin's own option. The media_category terms and their
 * assignments are user content and are intentionally left in place, so
 * categorization survives a reinstall.
 *
 * @package SimpleMediaCategories
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Delete the plugin option for the current site.
 */
function smc_uninstall_site(): void {
	delete_option( 'smc_settings' );
}

if ( is_multisite() ) {
	$smc_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $smc_site_ids as $smc_site_id ) {
		switch_to_blog( (int) $smc_site_id );
		smc_uninstall_site();
		restore_current_blog();
	}
} else {
	smc_uninstall_site();
}
