<?php
/**
 * Plugin Name:       MTSUAV Maintenance Mode
 * Plugin URI:        https://mtsuav.com/
 * Description:       Put your site into maintenance mode with a clean holding page, countdown timer, secret bypass links, and email capture.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MTSUAV
 * Author URI:        https://mtsuav.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       mtsuav-maintenance-mode
 * Update URI:        https://github.com/opsecfreak/mtsuav-maintenance-mode
 *
 * @package MTSUAV_Maintenance_Mode
 */

defined( 'ABSPATH' ) || exit;

/* Plugin constants. */
define( 'MTSUAV_MM_VERSION', '1.0.0' );
define( 'MTSUAV_MM_OPTION', 'mtsuav_mm_settings' );
define( 'MTSUAV_MM_SUBSCRIBERS_OPTION', 'mtsuav_mm_subscribers' );
define( 'MTSUAV_MM_DIR', plugin_dir_path( __FILE__ ) );
define( 'MTSUAV_MM_URL', plugin_dir_url( __FILE__ ) );

/* Shared GitHub releases updater (drop-in, do not modify). */
define( 'MTSUAV_UPDATER_SLUG', 'mtsuav-maintenance-mode' );
define( 'MTSUAV_UPDATER_REPO', 'opsecfreak/mtsuav-maintenance-mode' );
define( 'MTSUAV_UPDATER_VERSION', MTSUAV_MM_VERSION );
define( 'MTSUAV_UPDATER_FILE', __FILE__ );
require_once MTSUAV_MM_DIR . 'includes/class-mtsuav-updater.php';
MTSUAV_Updater::init();

/* Shared quiet tip box (drop-in, do not modify). */
require_once MTSUAV_MM_DIR . 'includes/class-mtsuav-tip-box.php';
mtsuav_tip_box_init();

/* Plugin classes. */
require_once MTSUAV_MM_DIR . 'includes/class-mtsuav-mm-settings.php';
require_once MTSUAV_MM_DIR . 'includes/class-mtsuav-mm-frontend.php';
require_once MTSUAV_MM_DIR . 'includes/class-mtsuav-mm-notify.php';

MTSUAV_MM_Settings::init();
MTSUAV_MM_Frontend::init();
MTSUAV_MM_Notify::init();

/**
 * Default settings for a fresh install.
 *
 * @return array
 */
function mtsuav_mm_defaults() {
	return array(
		'enabled'           => 0,
		'bypass_roles'      => array( 'administrator' ),
		'bypass_key'        => '',
		'exclude_rest_api'  => 1,
		'headline'          => __( 'We will be back soon', 'mtsuav-maintenance-mode' ),
		'message'           => __( '<p>We are performing scheduled maintenance on the site. Please check back shortly.</p>', 'mtsuav-maintenance-mode' ),
		'logo_id'           => 0,
		'bg_color'          => '#111827',
		'bg_image_id'       => 0,
		'overlay_opacity'   => 60,
		'custom_css'        => '',
		'countdown_enabled' => 0,
		'countdown_target'  => '',
		'social_links'      => array(),
		'contact_email'     => '',
		'contact_phone'     => '',
		'notify_enabled'    => 0,
		'retry_after'       => 3600,
	);
}

/**
 * Get the current settings merged over defaults.
 *
 * @return array
 */
function mtsuav_mm_get_settings() {
	$saved = get_option( MTSUAV_MM_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, mtsuav_mm_defaults() );
}

/**
 * Generate a random bypass key.
 *
 * @return string
 */
function mtsuav_mm_generate_bypass_key() {
	return wp_generate_password( 32, false );
}

/**
 * Activation: seed defaults and a bypass key.
 *
 * @return void
 */
function mtsuav_mm_activate() {
	$settings = get_option( MTSUAV_MM_OPTION, null );
	if ( ! is_array( $settings ) ) {
		$settings = mtsuav_mm_defaults();
	}
	if ( empty( $settings['bypass_key'] ) ) {
		$settings['bypass_key'] = mtsuav_mm_generate_bypass_key();
	}
	update_option( MTSUAV_MM_OPTION, $settings );
}
register_activation_hook( __FILE__, 'mtsuav_mm_activate' );

/**
 * Load translations.
 *
 * @return void
 */
function mtsuav_mm_load_textdomain() {
	load_plugin_textdomain( 'mtsuav-maintenance-mode', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'mtsuav_mm_load_textdomain' );

/**
 * Add a Settings link on the plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function mtsuav_mm_plugin_action_links( $links ) {
	$url           = admin_url( 'options-general.php?page=mtsuav-maintenance-mode' );
	$links['settings'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $url ),
		esc_html__( 'Settings', 'mtsuav-maintenance-mode' )
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'mtsuav_mm_plugin_action_links' );
