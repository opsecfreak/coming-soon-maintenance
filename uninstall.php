<?php
/**
 * Uninstall cleanup for Coming Soon & Maintenance Mode.
 *
 * Deletes the settings option, the subscriber list, per-user tip-box
 * dismissal meta, and any leftover transients.
 *
 * @package CSM
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'csm_settings' );
delete_option( 'csm_subscribers' );

global $wpdb;

/* Per-user tip-box dismissal meta for this plugin's slug. */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
		'mtsuav_tip_dismissed_coming-soon-maintenance'
	)
);

/* Leftover rate-limit and updater transients. */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_csm_rl_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_csm_rl_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_mtsuav_upd_coming-soon-maintenance%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_mtsuav_upd_coming-soon-maintenance%'" );
