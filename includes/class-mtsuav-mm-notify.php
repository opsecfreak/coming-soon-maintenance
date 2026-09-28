<?php
/**
 * Notify-me email capture for MTSUAV Maintenance Mode.
 *
 * Submissions are stored in an option list (email + timestamp + IP). Nothing
 * is emailed anywhere. Rate limited to one submission per minute per IP.
 *
 * @package MTSUAV_Maintenance_Mode
 */

defined( 'ABSPATH' ) || exit;

class MTSUAV_MM_Notify {

	const NONCE_ACTION = 'mtsuav_mm_notify';
	const RATE_LIMIT   = 60;   /* seconds between submissions per IP */
	const MAX_STORED   = 10000; /* cap on stored addresses */

	/**
	 * Wire up form handling and admin actions.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_post_nopriv_mtsuav_mm_notify', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_mtsuav_mm_notify', array( __CLASS__, 'handle_submit' ) );
		add_action( 'admin_post_mtsuav_mm_export_csv', array( __CLASS__, 'handle_export_csv' ) );
		add_action( 'admin_post_mtsuav_mm_delete_subscribers', array( __CLASS__, 'handle_delete_all' ) );
	}

	/**
	 * Get all stored subscribers, newest first.
	 *
	 * @return array List of arrays with email, time, ip keys.
	 */
	public static function get_subscribers() {
		$subs = get_option( MTSUAV_MM_SUBSCRIBERS_OPTION, array() );
		if ( ! is_array( $subs ) ) {
			return array();
		}
		return $subs;
	}

	/**
	 * Visitor IP address. Uses REMOTE_ADDR only, no proxy headers.
	 *
	 * @return string
	 */
	protected static function visitor_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) : '';
		$ip = trim( $ip );
		return '' !== $ip ? substr( $ip, 0, 45 ) : 'unknown';
	}

	/**
	 * Handle the notify-me form submission from the maintenance page.
	 *
	 * @return void
	 */
	public static function handle_submit() {
		$settings = mtsuav_mm_get_settings();
		$redirect = home_url( '/' );

		$referer = wp_get_referer();
		if ( $referer ) {
			$redirect = $referer;
		}

		$fail = function ( $code ) use ( $redirect ) {
			wp_safe_redirect( add_query_arg( 'mtsuav_mm_notify', $code, $redirect ) );
			exit;
		};

		if ( empty( $settings['notify_enabled'] ) ) {
			$fail( 'disabled' );
		}

		if ( ! isset( $_POST['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), self::NONCE_ACTION ) ) {
			$fail( 'invalid' );
		}

		/* Rate limit: one submission per minute per IP. */
		$rl_key = 'mtsuav_mm_rl_' . md5( self::visitor_ip() );
		if ( get_transient( $rl_key ) ) {
			$fail( 'limited' );
		}

		$email = isset( $_POST['mtsuav_mm_email'] ) ? sanitize_email( wp_unslash( $_POST['mtsuav_mm_email'] ) ) : '';
		if ( '' === $email || ! is_email( $email ) ) {
			$fail( 'invalid-email' );
		}

		$subs = self::get_subscribers();

		/* Skip duplicates (case-insensitive). */
		foreach ( $subs as $sub ) {
			if ( isset( $sub['email'] ) && 0 === strcasecmp( $sub['email'], $email ) ) {
				set_transient( $rl_key, time(), self::RATE_LIMIT );
				wp_safe_redirect( add_query_arg( 'mtsuav_mm_notify', 'subscribed', $redirect ) );
				exit;
			}
		}

		array_unshift(
			$subs,
			array(
				'email' => $email,
				'time'  => time(),
				'ip'    => self::visitor_ip(),
			)
		);
		$subs = array_slice( $subs, 0, self::MAX_STORED );
		update_option( MTSUAV_MM_SUBSCRIBERS_OPTION, $subs, false );

		set_transient( $rl_key, time(), self::RATE_LIMIT );

		wp_safe_redirect( add_query_arg( 'mtsuav_mm_notify', 'subscribed', $redirect ) );
		exit;
	}

	/**
	 * Nonced admin-post URL for CSV export.
	 *
	 * @return string
	 */
	public static function export_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=mtsuav_mm_export_csv' ),
			'mtsuav_mm_export_csv'
		);
	}

	/**
	 * Nonced admin-post URL for delete-all.
	 *
	 * @return string
	 */
	public static function delete_url() {
		return wp_nonce_url(
			admin_url( 'admin-post.php?action=mtsuav_mm_delete_subscribers' ),
			'mtsuav_mm_delete_subscribers'
		);
	}

	/**
	 * Stream the subscriber list as a CSV download.
	 *
	 * @return void
	 */
	public static function handle_export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'mtsuav-maintenance-mode' ), 403 );
		}
		check_admin_referer( 'mtsuav_mm_export_csv' );

		$subs     = self::get_subscribers();
		$filename = 'mtsuav-maintenance-mode-subscribers-' . gmdate( 'Y-m-d' ) . '.csv';

		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'email', 'subscribed_at' ) );
		foreach ( $subs as $sub ) {
			fputcsv(
				$out,
				array(
					isset( $sub['email'] ) ? $sub['email'] : '',
					isset( $sub['time'] ) ? gmdate( 'Y-m-d H:i:s', (int) $sub['time'] ) : '',
				)
			);
		}
		fclose( $out );
		exit;
	}

	/**
	 * Delete all stored subscribers.
	 *
	 * @return void
	 */
	public static function handle_delete_all() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'mtsuav-maintenance-mode' ), 403 );
		}
		check_admin_referer( 'mtsuav_mm_delete_subscribers' );

		delete_option( MTSUAV_MM_SUBSCRIBERS_OPTION );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'             => MTSUAV_MM_Settings::PAGE_SLUG,
					'mtsuav_mm_notice' => 'subscribers-deleted',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}
}
