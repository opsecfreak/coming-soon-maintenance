<?php
/**
 * Admin settings page for MTSUAV Maintenance Mode.
 *
 * Settings API, single option array, full sanitization. Also hosts the
 * notify-me subscriber list with CSV export and delete-all.
 *
 * @package MTSUAV_Maintenance_Mode
 */

defined( 'ABSPATH' ) || exit;

class MTSUAV_MM_Settings {

	const PAGE_SLUG = 'mtsuav-maintenance-mode';

	/**
	 * Social networks allowed in the repeatable social links field.
	 *
	 * @return array slug => label
	 */
	public static function social_networks() {
		return array(
			'x'         => __( 'X', 'mtsuav-maintenance-mode' ),
			'facebook'  => __( 'Facebook', 'mtsuav-maintenance-mode' ),
			'instagram' => __( 'Instagram', 'mtsuav-maintenance-mode' ),
			'linkedin'  => __( 'LinkedIn', 'mtsuav-maintenance-mode' ),
			'youtube'   => __( 'YouTube', 'mtsuav-maintenance-mode' ),
		);
	}

	/**
	 * Wire up admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'admin_post_mtsuav_mm_regenerate_key', array( __CLASS__, 'handle_regenerate_key' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public static function add_menu() {
		add_options_page(
			__( 'MTSUAV Maintenance Mode', 'mtsuav-maintenance-mode' ),
			__( 'Maintenance Mode', 'mtsuav-maintenance-mode' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Enqueue media, color picker, and our admin script on our screen only.
	 *
	 * @param string $hook Current admin screen hook suffix.
	 * @return void
	 */
	public static function enqueue( $hook ) {
		if ( 'settings_page_' . self::PAGE_SLUG !== $hook ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script(
			'mtsuav-mm-admin',
			MTSUAV_MM_URL . 'assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			MTSUAV_MM_VERSION,
			true
		);
		wp_enqueue_style(
			'mtsuav-mm-admin',
			MTSUAV_MM_URL . 'assets/admin.css',
			array( 'wp-color-picker' ),
			MTSUAV_MM_VERSION
		);
	}

	/**
	 * Register the single option array and all sections/fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			'mtsuav_mm_settings_group',
			MTSUAV_MM_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => mtsuav_mm_defaults(),
			)
		);

		add_settings_section(
			'mtsuav_mm_section_general',
			__( 'General', 'mtsuav-maintenance-mode' ),
			'__return_false',
			self::PAGE_SLUG
		);
		add_settings_field( 'enabled', __( 'Enable maintenance mode', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_enabled' ), self::PAGE_SLUG, 'mtsuav_mm_section_general' );
		add_settings_field( 'retry_after', __( 'Retry-After (seconds)', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_retry_after' ), self::PAGE_SLUG, 'mtsuav_mm_section_general' );

		add_settings_section(
			'mtsuav_mm_section_bypass',
			__( 'Bypass Access', 'mtsuav-maintenance-mode' ),
			array( __CLASS__, 'section_bypass_desc' ),
			self::PAGE_SLUG
		);
		add_settings_field( 'bypass_roles', __( 'Roles that bypass', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_bypass_roles' ), self::PAGE_SLUG, 'mtsuav_mm_section_bypass' );
		add_settings_field( 'bypass_key', __( 'Secret bypass link', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_bypass_key' ), self::PAGE_SLUG, 'mtsuav_mm_section_bypass' );

		add_settings_section(
			'mtsuav_mm_section_exclusions',
			__( 'Exclusions', 'mtsuav-maintenance-mode' ),
			array( __CLASS__, 'section_exclusions_desc' ),
			self::PAGE_SLUG
		);
		add_settings_field( 'exclude_rest_api', __( 'Exclude the WP REST API', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_exclude_rest_api' ), self::PAGE_SLUG, 'mtsuav_mm_section_exclusions' );

		add_settings_section(
			'mtsuav_mm_section_content',
			__( 'Page Content', 'mtsuav-maintenance-mode' ),
			'__return_false',
			self::PAGE_SLUG
		);
		add_settings_field( 'headline', __( 'Headline', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_headline' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'message', __( 'Message', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_message' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'logo_id', __( 'Logo', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_logo' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'bg_color', __( 'Background color', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_bg_color' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'bg_image_id', __( 'Background image', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_bg_image' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'overlay_opacity', __( 'Image overlay opacity', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_overlay_opacity' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );
		add_settings_field( 'custom_css', __( 'Custom CSS', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_custom_css' ), self::PAGE_SLUG, 'mtsuav_mm_section_content' );

		add_settings_section(
			'mtsuav_mm_section_countdown',
			__( 'Countdown Timer', 'mtsuav-maintenance-mode' ),
			'__return_false',
			self::PAGE_SLUG
		);
		add_settings_field( 'countdown_enabled', __( 'Show countdown', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_countdown_enabled' ), self::PAGE_SLUG, 'mtsuav_mm_section_countdown' );
		add_settings_field( 'countdown_target', __( 'Target date and time', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_countdown_target' ), self::PAGE_SLUG, 'mtsuav_mm_section_countdown' );

		add_settings_section(
			'mtsuav_mm_section_social',
			__( 'Social Links', 'mtsuav-maintenance-mode' ),
			'__return_false',
			self::PAGE_SLUG
		);
		add_settings_field( 'social_links', __( 'Links', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_social_links' ), self::PAGE_SLUG, 'mtsuav_mm_section_social' );

		add_settings_section(
			'mtsuav_mm_section_contact',
			__( 'Contact Line', 'mtsuav-maintenance-mode' ),
			'__return_false',
			self::PAGE_SLUG
		);
		add_settings_field( 'contact_email', __( 'Email', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_contact_email' ), self::PAGE_SLUG, 'mtsuav_mm_section_contact' );
		add_settings_field( 'contact_phone', __( 'Phone', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_contact_phone' ), self::PAGE_SLUG, 'mtsuav_mm_section_contact' );

		add_settings_section(
			'mtsuav_mm_section_notify',
			__( 'Notify Me Capture', 'mtsuav-maintenance-mode' ),
			array( __CLASS__, 'section_notify_desc' ),
			self::PAGE_SLUG
		);
		add_settings_field( 'notify_enabled', __( 'Show email capture form', 'mtsuav-maintenance-mode' ), array( __CLASS__, 'field_notify_enabled' ), self::PAGE_SLUG, 'mtsuav_mm_section_notify' );
	}

	/**
	 * Sanitize the whole option array.
	 *
	 * @param array $input Raw submitted values.
	 * @return array Clean values.
	 */
	public static function sanitize( $input ) {
		$clean = mtsuav_mm_defaults();
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$clean['enabled']          = ! empty( $input['enabled'] ) ? 1 : 0;
		$clean['exclude_rest_api'] = ! empty( $input['exclude_rest_api'] ) ? 1 : 0;
		$clean['countdown_enabled'] = ! empty( $input['countdown_enabled'] ) ? 1 : 0;
		$clean['notify_enabled']  = ! empty( $input['notify_enabled'] ) ? 1 : 0;

		/* Roles: keep only real editable roles. */
		$editable = array_keys( get_editable_roles() );
		$roles    = array();
		if ( ! empty( $input['bypass_roles'] ) && is_array( $input['bypass_roles'] ) ) {
			foreach ( $input['bypass_roles'] as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $editable, true ) ) {
					$roles[] = $role;
				}
			}
		}
		$clean['bypass_roles'] = $roles;

		/* Bypass key is managed by the regenerate action, never by the form. */
		$existing = mtsuav_mm_get_settings();
		$clean['bypass_key'] = isset( $existing['bypass_key'] ) ? (string) $existing['bypass_key'] : '';
		if ( '' === $clean['bypass_key'] ) {
			$clean['bypass_key'] = mtsuav_mm_generate_bypass_key();
		}

		$clean['headline']      = isset( $input['headline'] ) ? sanitize_text_field( wp_unslash( $input['headline'] ) ) : '';
		$clean['message']       = isset( $input['message'] ) ? wp_kses_post( wp_unslash( $input['message'] ) ) : '';
		$clean['logo_id']       = isset( $input['logo_id'] ) ? absint( $input['logo_id'] ) : 0;
		$clean['bg_image_id']   = isset( $input['bg_image_id'] ) ? absint( $input['bg_image_id'] ) : 0;
		$clean['custom_css']    = isset( $input['custom_css'] ) ? wp_strip_all_tags( wp_unslash( $input['custom_css'] ) ) : '';
		$clean['contact_email'] = isset( $input['contact_email'] ) ? sanitize_email( wp_unslash( $input['contact_email'] ) ) : '';
		$clean['contact_phone'] = isset( $input['contact_phone'] ) ? sanitize_text_field( wp_unslash( $input['contact_phone'] ) ) : '';

		$color = isset( $input['bg_color'] ) ? sanitize_hex_color( wp_unslash( $input['bg_color'] ) ) : '';
		$clean['bg_color'] = $color ? $color : '#111827';

		$clean['overlay_opacity'] = isset( $input['overlay_opacity'] ) ? absint( $input['overlay_opacity'] ) : 60;
		$clean['overlay_opacity'] = min( 90, max( 0, $clean['overlay_opacity'] ) );

		$clean['retry_after'] = isset( $input['retry_after'] ) ? absint( $input['retry_after'] ) : 3600;
		if ( $clean['retry_after'] < 60 ) {
			$clean['retry_after'] = 60;
		}

		/* Countdown target: validate as a real date/time. */
		$clean['countdown_target'] = '';
		if ( ! empty( $input['countdown_target'] ) ) {
			$raw = sanitize_text_field( wp_unslash( $input['countdown_target'] ) );
			$dt  = date_create( $raw, wp_timezone() );
			if ( $dt instanceof DateTime ) {
				$clean['countdown_target'] = $dt->format( 'Y-m-d H:i' );
			}
		}

		/* Social links: whitelist networks, require valid URLs. */
		$networks = array_keys( self::social_networks() );
		$links    = array();
		if ( ! empty( $input['social_links'] ) && is_array( $input['social_links'] ) ) {
			foreach ( $input['social_links'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$network = isset( $row['network'] ) ? sanitize_key( $row['network'] ) : '';
				$url     = isset( $row['url'] ) ? esc_url_raw( trim( wp_unslash( $row['url'] ) ) ) : '';
				if ( in_array( $network, $networks, true ) && '' !== $url ) {
					$links[] = array(
						'network' => $network,
						'url'     => $url,
					);
				}
			}
		}
		$clean['social_links'] = array_slice( $links, 0, 10 );

		return $clean;
	}

	/* Section descriptions. */

	public static function section_bypass_desc() {
		echo '<p>' . esc_html__( 'Logged-in users with a selected role skip the maintenance page. The secret bypass link sets a cookie so logged-out visitors (for example, a client) can view the site.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function section_exclusions_desc() {
		echo '<p>' . esc_html__( 'The login page, wp-admin, admin-ajax.php, and wp-cron.php are always excluded so you never lock yourself out.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function section_notify_desc() {
		echo '<p>' . esc_html__( 'Shows an email field on the maintenance page. Submissions are stored on this site only; no emails are sent anywhere.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	/* Field renderers. Each reads from mtsuav_mm_get_settings(). */

	public static function field_enabled() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<label><input type="checkbox" name="%s[enabled]" value="1" %s /> %s</label>',
			esc_attr( MTSUAV_MM_OPTION ),
			checked( 1, $s['enabled'], false ),
			esc_html__( 'Put the site into maintenance mode for visitors', 'mtsuav-maintenance-mode' )
		);
	}

	public static function field_retry_after() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="number" name="%s[retry_after]" value="%d" min="60" step="60" class="small-text" />',
			esc_attr( MTSUAV_MM_OPTION ),
			absint( $s['retry_after'] )
		);
		echo '<p class="description">' . esc_html__( 'Sent in the Retry-After header with the 503 response. Tells search engines when to come back.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_bypass_roles() {
		$s     = mtsuav_mm_get_settings();
		$roles = get_editable_roles();
		echo '<select name="' . esc_attr( MTSUAV_MM_OPTION ) . '[bypass_roles][]" multiple="multiple" style="min-width:220px;min-height:120px;">';
		foreach ( $roles as $slug => $role ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( in_array( $slug, (array) $s['bypass_roles'], true ), true, false ),
				esc_html( translate_user_role( $role['name'] ) )
			);
		}
		echo '</select>';
		echo '<p class="description">' . esc_html__( 'Hold Ctrl (or Cmd) to select more than one role.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_bypass_key() {
		$s   = mtsuav_mm_get_settings();
		$url = add_query_arg( 'mtsuav_mm_bypass', $s['bypass_key'], home_url( '/' ) );
		echo '<input type="text" readonly="readonly" value="' . esc_attr( $url ) . '" class="large-text code mtsuav-mm-select-all" />';
		$regen = wp_nonce_url(
			admin_url( 'admin-post.php?action=mtsuav_mm_regenerate_key' ),
			'mtsuav_mm_regenerate_key'
		);
		echo '<p><a class="button" href="' . esc_url( $regen ) . '">' . esc_html__( 'Generate new key', 'mtsuav-maintenance-mode' ) . '</a></p>';
		echo '<p class="description">' . esc_html__( 'Anyone with this link can view the site while maintenance mode is on. Generating a new key invalidates the old link.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	/**
	 * Regenerate the secret bypass key (admin-post action).
	 *
	 * @return void
	 */
	public static function handle_regenerate_key() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to do this.', 'mtsuav-maintenance-mode' ), 403 );
		}
		check_admin_referer( 'mtsuav_mm_regenerate_key' );
		$settings               = mtsuav_mm_get_settings();
		$settings['bypass_key'] = mtsuav_mm_generate_bypass_key();
		update_option( MTSUAV_MM_OPTION, $settings );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'              => self::PAGE_SLUG,
					'mtsuav_mm_notice'  => 'key-regenerated',
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	public static function field_exclude_rest_api() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<label><input type="checkbox" name="%s[exclude_rest_api]" value="1" %s /> %s</label>',
			esc_attr( MTSUAV_MM_OPTION ),
			checked( 1, $s['exclude_rest_api'], false ),
			esc_html__( 'Do not block REST API requests', 'mtsuav-maintenance-mode' )
		);
		echo '<p class="description">' . esc_html__( 'Recommended if mobile apps or external services use your REST API.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_headline() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="text" name="%s[headline]" value="%s" class="large-text" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $s['headline'] )
		);
	}

	public static function field_message() {
		$s = mtsuav_mm_get_settings();
		wp_editor(
			wp_kses_post( $s['message'] ),
			'mtsuav_mm_message',
			array(
				'textarea_name' => MTSUAV_MM_OPTION . '[message]',
				'textarea_rows' => 6,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);
	}

	/**
	 * Shared media-upload field (logo and background image).
	 *
	 * @param string $field Option key holding the attachment ID.
	 * @param string $label Button label.
	 * @return void
	 */
	protected static function media_field( $field, $label ) {
		$s   = mtsuav_mm_get_settings();
		$id  = absint( $s[ $field ] );
		$src = $id ? wp_get_attachment_image_url( $id, 'medium' ) : '';
		echo '<div class="mtsuav-mm-media-field" data-field="' . esc_attr( $field ) . '">';
		echo '<div class="mtsuav-mm-media-preview">' . ( $src ? '<img src="' . esc_url( $src ) . '" alt="" style="max-width:200px;height:auto;" />' : '' ) . '</div>';
		printf(
			'<input type="hidden" class="mtsuav-mm-media-id" name="%s[%s]" value="%d" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $field ),
			$id
		);
		echo '<p><button type="button" class="button mtsuav-mm-media-select">' . esc_html( $label ) . '</button> ';
		echo '<button type="button" class="button mtsuav-mm-media-remove"' . ( $id ? '' : ' style="display:none;"' ) . '>' . esc_html__( 'Remove', 'mtsuav-maintenance-mode' ) . '</button></p>';
		echo '</div>';
	}

	public static function field_logo() {
		self::media_field( 'logo_id', __( 'Choose logo', 'mtsuav-maintenance-mode' ) );
	}

	public static function field_bg_color() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="text" name="%s[bg_color]" value="%s" class="mtsuav-mm-color" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $s['bg_color'] )
		);
	}

	public static function field_bg_image() {
		self::media_field( 'bg_image_id', __( 'Choose background image', 'mtsuav-maintenance-mode' ) );
		echo '<p class="description">' . esc_html__( 'Shown behind the page with the overlay opacity below.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_overlay_opacity() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="range" name="%s[overlay_opacity]" value="%d" min="0" max="90" step="5" class="mtsuav-mm-range" /> <span class="mtsuav-mm-range-value">%d%%</span>',
			esc_attr( MTSUAV_MM_OPTION ),
			absint( $s['overlay_opacity'] ),
			absint( $s['overlay_opacity'] )
		);
		echo '<p class="description">' . esc_html__( 'How dark the overlay over the background image is. 0 means no overlay.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_custom_css() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<textarea name="%s[custom_css]" rows="6" cols="60" class="large-text code" spellcheck="false">%s</textarea>',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_textarea( $s['custom_css'] )
		);
		echo '<p class="description">' . esc_html__( 'Extra CSS for the maintenance page. Do not include style tags.', 'mtsuav-maintenance-mode' ) . '</p>';
	}

	public static function field_countdown_enabled() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<label><input type="checkbox" name="%s[countdown_enabled]" value="1" %s /> %s</label>',
			esc_attr( MTSUAV_MM_OPTION ),
			checked( 1, $s['countdown_enabled'], false ),
			esc_html__( 'Show a live countdown to the target date', 'mtsuav-maintenance-mode' )
		);
	}

	public static function field_countdown_target() {
		$s     = mtsuav_mm_get_settings();
		$value = '';
		if ( '' !== $s['countdown_target'] ) {
			$dt = date_create( $s['countdown_target'], wp_timezone() );
			if ( $dt instanceof DateTime ) {
				$value = $dt->format( 'Y-m-d\TH:i' );
			}
		}
		printf(
			'<input type="datetime-local" name="%s[countdown_target]" value="%s" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $value )
		);
		/* translators: %s: site timezone name. */
		echo '<p class="description">' . esc_html( sprintf( __( 'Site timezone: %s. The countdown hides itself after the date passes.', 'mtsuav-maintenance-mode' ), wp_timezone_string() ) ) . '</p>';
	}

	public static function field_social_links() {
		$s        = mtsuav_mm_get_settings();
		$networks = self::social_networks();
		$rows     = is_array( $s['social_links'] ) ? $s['social_links'] : array();
		echo '<div id="mtsuav-mm-social-rows">';
		$i = 0;
		foreach ( $rows as $row ) {
			self::social_row( $i, $row['network'], $row['url'], $networks );
			$i++;
		}
		echo '</div>';
		echo '<p><button type="button" class="button" id="mtsuav-mm-social-add">' . esc_html__( 'Add social link', 'mtsuav-maintenance-mode' ) . '</button></p>';
		echo '<script type="text/html" id="mtsuav-mm-social-template">';
		self::social_row( '__INDEX__', 'x', '', $networks );
		echo '</script>';
	}

	/**
	 * Render one social-link row.
	 *
	 * @param int|string $index Row index.
	 * @param string     $network Selected network slug.
	 * @param string     $url     URL value.
	 * @param array      $networks Network slug => label map.
	 * @return void
	 */
	protected static function social_row( $index, $network, $url, $networks ) {
		echo '<div class="mtsuav-mm-social-row" style="margin-bottom:8px;">';
		echo '<select name="' . esc_attr( MTSUAV_MM_OPTION ) . '[social_links][' . esc_attr( (string) $index ) . '][network]">';
		foreach ( $networks as $slug => $label ) {
			printf(
				'<option value="%s" %s>%s</option>',
				esc_attr( $slug ),
				selected( $slug, $network, false ),
				esc_html( $label )
			);
		}
		echo '</select> ';
		printf(
			'<input type="url" name="%s[social_links][%s][url]" value="%s" placeholder="https://" class="regular-text" /> ',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( (string) $index ),
			esc_attr( $url )
		);
		echo '<button type="button" class="button mtsuav-mm-social-remove">' . esc_html__( 'Remove', 'mtsuav-maintenance-mode' ) . '</button>';
		echo '</div>';
	}

	public static function field_contact_email() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="email" name="%s[contact_email]" value="%s" class="regular-text" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $s['contact_email'] )
		);
	}

	public static function field_contact_phone() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<input type="tel" name="%s[contact_phone]" value="%s" class="regular-text" />',
			esc_attr( MTSUAV_MM_OPTION ),
			esc_attr( $s['contact_phone'] )
		);
	}

	public static function field_notify_enabled() {
		$s = mtsuav_mm_get_settings();
		printf(
			'<label><input type="checkbox" name="%s[notify_enabled]" value="1" %s /> %s</label>',
			esc_attr( MTSUAV_MM_OPTION ),
			checked( 1, $s['notify_enabled'], false ),
			esc_html__( 'Show a "notify me" email field on the maintenance page', 'mtsuav-maintenance-mode' )
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mtsuav-maintenance-mode' ), 403 );
		}

		/* Action notices. */
		if ( isset( $_GET['mtsuav_mm_notice'] ) ) {
			$notice = sanitize_key( wp_unslash( $_GET['mtsuav_mm_notice'] ) );
			if ( 'key-regenerated' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Bypass key regenerated. The old bypass link no longer works.', 'mtsuav-maintenance-mode' ) . '</p></div>';
			} elseif ( 'subscribers-deleted' === $notice ) {
				echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'All subscribers deleted.', 'mtsuav-maintenance-mode' ) . '</p></div>';
			}
		}

		$preview_url = add_query_arg( 'mtsuav_mm_preview', '1', home_url( '/' ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MTSUAV Maintenance Mode', 'mtsuav-maintenance-mode' ); ?></h1>
			<?php
			if ( function_exists( 'mtsuav_tip_box' ) ) {
				mtsuav_tip_box( 'mtsuav-maintenance-mode', 'MTSUAV Maintenance Mode' );
			}
			?>
			<p>
				<a class="button" href="<?php echo esc_url( $preview_url ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Preview maintenance page', 'mtsuav-maintenance-mode' ); ?></a>
				<span class="description"><?php esc_html_e( 'Opens the page in a new tab without turning maintenance mode on.', 'mtsuav-maintenance-mode' ); ?></span>
			</p>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'mtsuav_mm_settings_group' );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
			<?php self::render_subscribers(); ?>
		</div>
		<?php
	}

	/**
	 * Render the subscriber list section (outside the settings form).
	 *
	 * @return void
	 */
	protected static function render_subscribers() {
		$subscribers = MTSUAV_MM_Notify::get_subscribers();
		$count       = count( $subscribers );
		?>
		<hr />
		<h2><?php esc_html_e( 'Notify Me Subscribers', 'mtsuav-maintenance-mode' ); ?></h2>
		<p class="description">
			<?php
			/* translators: %d: number of subscribers. */
			printf( esc_html__( '%d address(es) collected.', 'mtsuav-maintenance-mode' ), absint( $count ) );
			?>
		</p>
		<?php if ( $count > 0 ) : ?>
			<p>
				<a class="button" href="<?php echo esc_url( MTSUAV_MM_Notify::export_url() ); ?>"><?php esc_html_e( 'Export CSV', 'mtsuav-maintenance-mode' ); ?></a>
				<a class="button button-link-delete mtsuav-mm-delete-all" href="<?php echo esc_url( MTSUAV_MM_Notify::delete_url() ); ?>" data-confirm="<?php echo esc_attr__( 'Delete all subscribers? This cannot be undone.', 'mtsuav-maintenance-mode' ); ?>"><?php esc_html_e( 'Delete all', 'mtsuav-maintenance-mode' ); ?></a>
			</p>
			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Email', 'mtsuav-maintenance-mode' ); ?></th>
						<th><?php esc_html_e( 'Date', 'mtsuav-maintenance-mode' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $subscribers as $sub ) : ?>
						<tr>
							<td><?php echo esc_html( $sub['email'] ); ?></td>
							<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $sub['time'] ) ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		<?php else : ?>
			<p><?php esc_html_e( 'No subscribers yet.', 'mtsuav-maintenance-mode' ); ?></p>
		<?php endif; ?>
		<?php
	}
}
