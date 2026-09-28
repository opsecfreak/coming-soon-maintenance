<?php
/**
 * Standalone maintenance page template.
 *
 * No theme dependency. All CSS and JS are inline; no external assets load.
 * Included from CSM_Frontend::render_page() with $settings and
 * $is_preview in scope.
 *
 * @package CSM
 */

defined( 'ABSPATH' ) || exit;

/* Countdown target as a Unix timestamp (site timezone). */
$countdown_ts = 0;
if ( ! empty( $settings['countdown_enabled'] ) && '' !== $settings['countdown_target'] ) {
	$dt = date_create( $settings['countdown_target'], wp_timezone() );
	if ( $dt instanceof DateTime && $dt->getTimestamp() > time() ) {
		$countdown_ts = $dt->getTimestamp();
	}
}

/* Background image. */
$bg_image_url = '';
if ( ! empty( $settings['bg_image_id'] ) ) {
	$bg_image_url = wp_get_attachment_image_url( absint( $settings['bg_image_id'] ), 'full' );
}
$overlay_opacity = min( 90, max( 0, absint( $settings['overlay_opacity'] ) ) ) / 100;

/* Logo. */
$logo_html = '';
if ( ! empty( $settings['logo_id'] ) ) {
	$logo_html = wp_get_attachment_image( absint( $settings['logo_id'] ), 'medium', false, array( 'class' => 'csm-logo', 'alt' => get_bloginfo( 'name' ) ) );
}

/* Social links. */
$social_links = array();
if ( ! empty( $settings['social_links'] ) && is_array( $settings['social_links'] ) ) {
	$networks = CSM_Settings::social_networks();
	foreach ( $settings['social_links'] as $row ) {
		if ( empty( $row['network'] ) || empty( $row['url'] ) ) {
			continue;
		}
		$path = CSM_Frontend::social_icon_path( $row['network'] );
		if ( '' === $path ) {
			continue;
		}
		$social_links[] = array(
			'label' => isset( $networks[ $row['network'] ] ) ? $networks[ $row['network'] ] : $row['network'],
			'url'   => $row['url'],
			'path'  => $path,
		);
	}
}

/* Notify-me form status. */
$notify_status = isset( $_GET['csm_notify'] ) ? sanitize_key( wp_unslash( $_GET['csm_notify'] ) ) : '';
$notify_messages = array(
	'subscribed'    => __( 'Thanks, you are on the list. We will let you know when we are back.', 'coming-soon-maintenance' ),
	'limited'       => __( 'Please wait a minute before trying again.', 'coming-soon-maintenance' ),
	'invalid-email' => __( 'Please enter a valid email address.', 'coming-soon-maintenance' ),
	'invalid'       => __( 'Something went wrong. Please try again.', 'coming-soon-maintenance' ),
	'disabled'      => __( 'Something went wrong. Please try again.', 'coming-soon-maintenance' ),
);
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<meta name="robots" content="noindex, nofollow" />
<title><?php echo esc_html( $settings['headline'] !== '' ? $settings['headline'] : get_bloginfo( 'name' ) ); ?></title>
<style>
* { box-sizing: border-box; }
html, body { margin: 0; padding: 0; min-height: 100%; }
body {
	background: <?php echo esc_html( $settings['bg_color'] ); ?>;
	color: #f3f4f6;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
	display: flex;
	align-items: center;
	justify-content: center;
	min-height: 100vh;
	padding: 24px;
	position: relative;
}
.csm-bg {
	position: fixed; inset: 0;
	background-size: cover;
	background-position: center;
	z-index: 0;
}
.csm-overlay {
	position: fixed; inset: 0;
	background: #000;
	z-index: 1;
}
.csm-card {
	position: relative;
	z-index: 2;
	max-width: 640px;
	width: 100%;
	text-align: center;
	background: rgba(17, 24, 39, 0.72);
	border: 1px solid rgba(255, 255, 255, 0.12);
	border-radius: 12px;
	padding: 48px 40px;
}
.csm-logo { max-width: 220px; height: auto; margin-bottom: 24px; }
.csm-card h1 { font-size: 2rem; margin: 0 0 16px; line-height: 1.25; }
.csm-message { font-size: 1.05rem; line-height: 1.6; color: #d1d5db; margin: 0 0 24px; }
.csm-message p { margin: 0 0 1em; }
.csm-countdown { display: flex; gap: 12px; justify-content: center; margin: 0 0 24px; flex-wrap: wrap; }
.csm-count-block { background: rgba(255,255,255,0.08); border-radius: 8px; padding: 12px 16px; min-width: 72px; }
.csm-count-num { display: block; font-size: 1.75rem; font-weight: 700; }
.csm-count-label { display: block; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #9ca3af; }
.csm-social { margin: 0 0 24px; display: flex; gap: 12px; justify-content: center; }
.csm-social a { display: inline-flex; width: 40px; height: 40px; align-items: center; justify-content: center; border-radius: 50%; background: rgba(255,255,255,0.1); }
.csm-social a:hover { background: rgba(255,255,255,0.22); }
.csm-social svg { width: 20px; height: 20px; fill: #f3f4f6; }
.csm-contact { margin: 0 0 24px; color: #d1d5db; font-size: 0.95rem; }
.csm-contact a { color: #f3f4f6; }
.csm-notify { margin: 0 0 8px; }
.csm-notify form { display: flex; gap: 8px; justify-content: center; flex-wrap: wrap; }
.csm-notify input[type="email"] { padding: 10px 14px; border-radius: 6px; border: 1px solid rgba(255,255,255,0.2); background: rgba(255,255,255,0.08); color: #f3f4f6; font-size: 1rem; min-width: 220px; }
.csm-notify input[type="email"]::placeholder { color: #9ca3af; }
.csm-notify button { padding: 10px 20px; border-radius: 6px; border: 0; background: #2563eb; color: #fff; font-size: 1rem; cursor: pointer; }
.csm-notify button:hover { background: #1d4ed8; }
.csm-notify-msg { font-size: 0.9rem; margin: 12px 0 0; }
.csm-notify-msg.ok { color: #6ee7b7; }
.csm-notify-msg.err { color: #fca5a5; }
.screen-reader-text { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }
.csm-preview-bar {
	position: fixed; top: 0; left: 0; right: 0; z-index: 10;
	background: #b45309; color: #fff; text-align: center;
	font-size: 0.85rem; padding: 8px;
}
body.has-preview-bar { padding-top: 60px; }
<?php echo $settings['custom_css']; // phpcs:ignore WordPress.Security.EscapeOutput -- sanitized with wp_strip_all_tags on save. ?>

</style>
</head>
<body class="<?php echo $is_preview ? 'has-preview-bar' : ''; ?>">
<?php if ( $is_preview ) : ?>
	<div class="csm-preview-bar"><?php esc_html_e( 'Preview only: maintenance mode is not enabled. Only administrators see this bar.', 'coming-soon-maintenance' ); ?></div>
<?php endif; ?>
<?php if ( $bg_image_url ) : ?>
	<div class="csm-bg" style="background-image:url('<?php echo esc_url( $bg_image_url ); ?>');"></div>
	<div class="csm-overlay" style="opacity:<?php echo esc_attr( (string) $overlay_opacity ); ?>;"></div>
<?php endif; ?>
<main class="csm-card">
	<?php echo $logo_html; // phpcs:ignore WordPress.Security.EscapeOutput -- built by wp_get_attachment_image. ?>

	<h1><?php echo esc_html( $settings['headline'] ); ?></h1>

	<?php if ( '' !== trim( wp_strip_all_tags( $settings['message'] ) ) ) : ?>
		<div class="csm-message"><?php echo wp_kses_post( $settings['message'] ); ?></div>
	<?php endif; ?>

	<?php if ( $countdown_ts > 0 ) : ?>
		<div class="csm-countdown" id="csm-countdown" data-target="<?php echo esc_attr( (string) $countdown_ts ); ?>">
			<div class="csm-count-block"><span class="csm-count-num" data-part="days">0</span><span class="csm-count-label"><?php esc_html_e( 'Days', 'coming-soon-maintenance' ); ?></span></div>
			<div class="csm-count-block"><span class="csm-count-num" data-part="hours">0</span><span class="csm-count-label"><?php esc_html_e( 'Hours', 'coming-soon-maintenance' ); ?></span></div>
			<div class="csm-count-block"><span class="csm-count-num" data-part="minutes">0</span><span class="csm-count-label"><?php esc_html_e( 'Minutes', 'coming-soon-maintenance' ); ?></span></div>
			<div class="csm-count-block"><span class="csm-count-num" data-part="seconds">0</span><span class="csm-count-label"><?php esc_html_e( 'Seconds', 'coming-soon-maintenance' ); ?></span></div>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $social_links ) ) : ?>
		<div class="csm-social">
			<?php foreach ( $social_links as $link ) : ?>
				<a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( $link['label'] ); ?>" title="<?php echo esc_attr( $link['label'] ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="<?php echo esc_attr( $link['path'] ); ?>"/></svg>
				</a>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $settings['contact_email'] || '' !== $settings['contact_phone'] ) : ?>
		<p class="csm-contact">
			<?php if ( '' !== $settings['contact_email'] ) : ?>
				<a href="mailto:<?php echo esc_attr( $settings['contact_email'] ); ?>"><?php echo esc_html( $settings['contact_email'] ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== $settings['contact_email'] && '' !== $settings['contact_phone'] ) : ?>
				&nbsp;|&nbsp;
			<?php endif; ?>
			<?php if ( '' !== $settings['contact_phone'] ) : ?>
				<a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $settings['contact_phone'] ) ); ?>"><?php echo esc_html( $settings['contact_phone'] ); ?></a>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( ! empty( $settings['notify_enabled'] ) ) : ?>
		<div class="csm-notify">
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="csm_notify" />
				<?php wp_nonce_field( CSM_Notify::NONCE_ACTION ); ?>
				<label class="screen-reader-text" for="csm-email"><?php esc_html_e( 'Email address', 'coming-soon-maintenance' ); ?></label>
				<input type="email" id="csm-email" name="csm_email" placeholder="<?php esc_attr_e( 'Your email address', 'coming-soon-maintenance' ); ?>" required />
				<button type="submit"><?php esc_html_e( 'Notify me', 'coming-soon-maintenance' ); ?></button>
			</form>
			<?php if ( '' !== $notify_status && isset( $notify_messages[ $notify_status ] ) ) : ?>
				<p class="csm-notify-msg <?php echo 'subscribed' === $notify_status ? 'ok' : 'err'; ?>"><?php echo esc_html( $notify_messages[ $notify_status ] ); ?></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</main>
<script>
(function () {
	var el = document.getElementById('csm-countdown');
	if (!el) { return; }
	var target = parseInt(el.getAttribute('data-target'), 10) * 1000;
	var parts = {
		days: el.querySelector('[data-part="days"]'),
		hours: el.querySelector('[data-part="hours"]'),
		minutes: el.querySelector('[data-part="minutes"]'),
		seconds: el.querySelector('[data-part="seconds"]')
	};
	function pad(n) { return (n < 10 ? '0' : '') + n; }
	function tick() {
		var diff = target - Date.now();
		if (diff <= 0) {
			el.style.display = 'none';
			return;
		}
		var s = Math.floor(diff / 1000);
		parts.days.textContent = Math.floor(s / 86400);
		parts.hours.textContent = pad(Math.floor((s % 86400) / 3600));
		parts.minutes.textContent = pad(Math.floor((s % 3600) / 60));
		parts.seconds.textContent = pad(s % 60);
	}
	tick();
	setInterval(tick, 1000);
})();
</script>
</body>
</html>
