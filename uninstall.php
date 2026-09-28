<?php
/**
 * Uninstall cleanup for MTSUAV Maintenance Mode.
 *
 * Deletes the settings option, the subscriber list, per-user tip-box
 * dismissal meta, and any leftover transients.
 *
 * @package MTSUAV_Maintenance_Mode
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mtsuav_mm_settings' );
delete_option( 'mtsuav_mm_subscribers' );

global $wpdb;

/* Per-user tip-box dismissal meta for this plugin's slug. */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
		'mtsuav_tip_dismissed_mtsuav-maintenance-mode'
	)
);

/* Leftover rate-limit and updater transients. */
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_mtsuav_mm_rl_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_mtsuav_mm_rl_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_mtsuav_upd_mtsuav-maintenance-mode%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_timeout_mtsuav_upd_mtsuav-maintenance-mode%'" );
