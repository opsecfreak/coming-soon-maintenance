=== Coming Soon & Maintenance Mode ===
Contributors: mobiletechspecialists
Tags: maintenance mode, coming soon, under construction, countdown, bypass
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Put your site into maintenance mode with a clean holding page, countdown timer, secret bypass links, and email capture.

== Description ==

Coming Soon & Maintenance Mode shows a clean, standalone holding page to visitors while you work on your site. No theme dependency, no page builder needed.

Features:

* Enable or disable maintenance mode with one toggle
* Visitors get a proper HTTP 503 response with a Retry-After header, so search engines know to come back later
* Bypass access for selected user roles (administrators by default)
* Secret bypass link (?csm_bypass=KEY) that sets a cookie, so a client or teammate can view the site without logging in; regenerate the key any time
* Always excluded: wp-login.php, wp-admin, admin-ajax.php, wp-cron.php, and optionally the WP REST API
* Customizable page: headline, rich-text message, logo upload, background color, background image with overlay opacity, custom CSS
* Live countdown timer to a target date and time; hides itself automatically when the date passes
* Social links with inline SVG icons (X, Facebook, Instagram, LinkedIn, YouTube)
* Optional contact email and phone line
* Optional "notify me" email capture; addresses are stored on your site only and can be exported to CSV
* Preview the maintenance page without enabling it

Free forever. No upsells, no tracking, no license keys.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/coming-soon-maintenance` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Go to Settings, then Maintenance Mode, to configure the page.
4. Use the "Preview maintenance page" button to check your design, then flip the enable toggle when you are ready.

== Frequently Asked Questions ==

= Will I lock myself out of wp-admin? =

No. wp-login.php, wp-admin, admin-ajax.php, and wp-cron.php are always excluded. Logged-in users with a bypass role (administrators by default) also skip the maintenance page.

= How does the secret bypass link work? =

The settings page shows a link like `?csm_bypass=KEY`. Visiting it sets a cookie for 30 days so that browser can view the site. Use "Generate new key" to invalidate the old link at any time.

= What happens to search engines? =

Blocked visitors receive HTTP 503 with a Retry-After header, which tells search engines the outage is temporary. The holding page itself carries noindex, nofollow.

= Where do "notify me" emails go? =

They are stored in your WordPress database only. Nothing is emailed anywhere. Export them as CSV from the settings page.

= Does the countdown keep showing after the date passes? =

No. The countdown hides itself automatically once the target date and time pass.

== Screenshots ==

1. Settings page with all maintenance page options.
2. The maintenance holding page with countdown timer.
3. Secret bypass link and role bypass settings.

== Changelog ==

= 1.0.0 =
* Initial release.

== Privacy ==

This plugin does not collect or transmit any personal data.

The plugin checks for updates by polling the public GitHub releases API (api.github.com) for the repository opsecfreak/coming-soon-maintenance, at most once every 12 hours. The request contains no personal data, no cookies, and no site identifiers; it uses a generic updater user-agent string.

"Notify me" email addresses entered by visitors are stored in your own WordPress database and are never sent anywhere by this plugin. Delete them at any time from the settings page, or uninstall the plugin to remove all stored data.
