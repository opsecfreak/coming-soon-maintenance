<?php
/**
 * Plugin Name:       Coming Soon & Maintenance Mode
 * Plugin URI:        https://mtsuav.com/
 * Description:       Put your site into maintenance mode with a clean holding page, countdown timer, secret bypass links, and email capture.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            MTSUAV
 * Author URI:        https://mtsuav.com/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       coming-soon-maintenance
 * Update URI:        https://github.com/opsecfreak/coming-soon-maintenance
 *
 * @package CSM
 */

defined( 'ABSPATH' ) || exit;

/* Plugin constants. */
define( 'CSM_VERSION', '1.0.0' );
define( 'CSM_OPTION', 'csm_settings' );
define( 'CSM_SUBSCRIBERS_OPTION', 'csm_subscribers' );
define( 'CSM_DIR', plugin_dir_path( __FILE__ ) );
define( 'CSM_URL', plugin_dir_url( __FILE__ ) );

/* Shared GitHub releases updater (drop-in, do not modify). */
require_once CSM_DIR . 'includes/class-mtsuav-updater.php';
MTSUAV_Updater::register( 'coming-soon-maintenance', 'opsecfreak/coming-soon-maintenance', CSM_VERSION, __FILE__ );

/* Shared quiet tip box (drop-in, do not modify). */
require_once CSM_DIR . 'includes/class-mtsuav-tip-box.php';
mtsuav_tip_box_init();

/* Plugin classes. */
require_once CSM_DIR . 'includes/class-csm-settings.php';
require_once CSM_DIR . 'includes/class-csm-frontend.php';
require_once CSM_DIR . 'includes/class-csm-notify.php';

CSM_Settings::init();
CSM_Frontend::init();
CSM_Notify::init();

/**
 * Default settings for a fresh install.
 *
 * @return array
 */
function csm_defaults() {
	return array(
		'enabled'           => 0,
		'bypass_roles'      => array( 'administrator' ),
		'bypass_key'        => '',
		'exclude_rest_api'  => 1,
		'headline'          => __( 'We will be back soon', 'coming-soon-maintenance' ),
		'message'           => __( '<p>We are performing scheduled maintenance on the site. Please check back shortly.</p>', 'coming-soon-maintenance' ),
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
function csm_get_settings() {
	$saved = get_option( CSM_OPTION, array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	return wp_parse_args( $saved, csm_defaults() );
}

/**
 * Generate a random bypass key.
 *
 * @return string
 */
function csm_generate_bypass_key() {
	return wp_generate_password( 32, false );
}

/**
 * Activation: seed defaults and a bypass key.
 *
 * @return void
 */
function csm_activate() {
	$settings = get_option( CSM_OPTION, null );
	if ( ! is_array( $settings ) ) {
		$settings = csm_defaults();
	}
	if ( empty( $settings['bypass_key'] ) ) {
		$settings['bypass_key'] = csm_generate_bypass_key();
	}
	update_option( CSM_OPTION, $settings );
}
register_activation_hook( __FILE__, 'csm_activate' );

/**
 * Load translations.
 *
 * @return void
 */
function csm_load_textdomain() {
	load_plugin_textdomain( 'coming-soon-maintenance', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'csm_load_textdomain' );

/**
 * Add a Settings link on the plugins screen.
 *
 * @param array $links Existing action links.
 * @return array
 */
function csm_plugin_action_links( $links ) {
	$url           = admin_url( 'options-general.php?page=coming-soon-maintenance' );
	$links['settings'] = sprintf(
		'<a href="%s">%s</a>',
		esc_url( $url ),
		esc_html__( 'Settings', 'coming-soon-maintenance' )
	);
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'csm_plugin_action_links' );
