=== WordPress HTTPS (SSL) ===
Contributors: Mvied
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=N9NFVADLVUR7A
Tags: security, encryption, ssl, shared ssl, private ssl, public ssl, private ssl, http, https
Requires at least: 3.0
Tested up to: 3.5
Stable tag: 3.3.6
License: GPLv3

WordPress HTTPS is intended to be an all-in-one solution to using SSL on WordPress sites.

== Description ==
<p>Read the <a href="http://wordpress.org/extend/plugins/wordpress-https/installation/">Installation Guide</a>. If after setting up the plugin you are experiencing issues, please check the <a href="http://wordpress.org/extend/plugins/wordpress-https/faq/">FAQ</a>.</p>
<p>If you are still unable to resolve your issue, <a href="http://wordpress.org/support/plugin/wordpress-https">start a support topic</a> and I or someone from the community will be able to assist you.</p>
<p>Contribute Code at <a href="https://github.com/Mvied/wordpress-https">https://github.com/Mvied/wordpress-https</a></p>
<p>Contribute Translations at <a href="https://translate.foe-services.de/projects/wordpress-https">https://translate.foe-services.de/projects/wordpress-https</a></p>

== Installation ==
1. Upload the `wordpress-https` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Navigate to the HTTPS settings page in the admin sidebar in the dashboard.
1. If you are using a non-default SSL Host for your HTTPS connection (e.g., a subdomain or shared SSL host) enter the entire secure URL into SSL Host. If your installation is located in a folder, you can choose to include it in the URL or not. If you set this to a domain that is not currently serving your WordPress installation over HTTPS and enable Force SSL Admin, you will lock yourself out of your dashboard. Follow instructions in the FAQ to reset the plugin.
1. If you would like connections to your admin panel to be secure, enable Force SSL Admin. If you are using a non-default SSL Host, do not use WordPress' built-in FORCE_SSL_ADMIN or FORCE_SSL_LOGIN.
1. If you are looking to secure only your admin panel and/or posts and pages you specify, enable Force SSL Exclusively. This will ensure that any content not specified to be secure is always served over HTTP.
1. You can individually secure post and pages when editing them by updating the settings located in the HTTPS box on the right sidebar.
1. You can use simple text match or regular expressions to specify URL's that should be secure using URL Filters in the WordPress HTTPS settings. Each filter should be on one line.

== Frequently Asked Questions ==
= How do I fix partially encrypted/mixed content errors? =
To identify what is causing your page(s) to be insecure, please follow the instructions below.
<ol>
 <li>Download <a href="http://www.google.com/chrome" target="_blank">Google Chrome</a>.</li>
 <li>Open the page you're having trouble with in Google Chrome.</li>
 <li>Open the Developer Tools. <a href="http://code.google.com/chrome/devtools/docs/overview.html#access" target="_blank">How to access the Developer Tools.</a></li>
 <li>Click on the Console tab.</li>
</ol>
For each item that is making your page partially encrypted, you should see an entry in the console similar to "The page at https://www.example.com/ displayed insecure content from http://www.example.com/." Note that the URL that is loading insecure content is HTTP and not HTTPS.

Once you have identified the insecure elements, you need to figure out what theme or plugin is causing these elements to be loaded. Although WordPress HTTPS does its best to fix all insecure content, there are a few cases that are impossible to fix. Here are some typical examples.
<ul>
 <li>The element is external (not hosted on your server) and is not available over HTTPS. These elements will have to be removed from the page by disabling or modifying the theme or plugin that is adding the element.</li>
 <li>The element is internal (hosted on your server) but does not get changed to HTTPS. This is often due to a background image in CSS or an image or file path in JavaScript being hard-coded to HTTP inside of a CSS file. The plugin can not fix these. The image paths must be changed to relative links. For example `http://www.example.com/wp-content/themes/mytheme/images/background.jpg` to simply `/wp-content/themes/mytheme/images/background.jpg`. Ensure you copy the entire path, including the prepended slash (very important).</li>
</ul>

= I can't get into my admin panel. How do I fix it? =
Since it is possible to lock yourself out of the dasboard, WordPress HTTPS comes with a way to reset the plugin's settings. The plugin makes no permanent changes to WordPress, so this will restore all settings to their defaults. Follow directions under "How do I reset the plugin's settings?"

= How do I reset the plugin's settings? =
Go to /wp-content/plugins/wordpress-https/wordpress-https.php and uncomment (remove the two forward slashes before) the line below, or go to your wp-config.php file and add this line. Hit any page on your site, and then remove it or comment it out again.
`define('WPHTTPS_RESET', true);`

= The settings won't save! =
Did you reset the plugin following the steps above and forget to comment the line back out or remove it from wp-config.php?

= How do I make my whole website secure? =
To make your entire website secure, you simply need to change your site url to use HTTPS instead of HTTP. Please read <a href="http://codex.wordpress.org/Changing_The_Site_URL" target="_blank">how to change the site url</a>.
Alternatively, you can use URL Filters in the WordPress HTTPS Settings to secure your entire site by putting just '/' as a filter. This will cause any URL with a forward slash to be secure (all of them).

= How do I make only certain pages secure? =
The plugin adds a meta box to the add/edit post screen entitled HTTPS. In that meta box, a checkbox for 'Secure Post' has been added to make this process easy. See Screenshots if you're having a hard time finding it.
Alternatively, you can use URL Filters to secure post and pages by their permalink.

= I'm using Force SSL Administration and all of the links to the front-end of my site are HTTPS. Why? =
For many users this behavior is desirable. If you would like links the the front-end of your site to be HTTP, enable Force SSL Exclusively and do not secure your front-end pages.

= I'm getting 404 errors on all of my pages. Why? =
If you're using a public/shared SSL, try disabling your custom permalink structure. Some public/shared SSL's have issues with WordPress' permalinks because of the way they are configured. If you continue to recieve 404 errors, there may be no way to use WordPress with that particular public/shared SSL.

= I'm receiving a blank page with no error. What gives? =
This is most commonly due to PHP's memory limit being too low. Check your Apache error logs just to be sure. Talk to your hosting provider about increading PHP's memory limit.

= Is there a hook or filter to force pages to be secure? =
Yes! Here is an example of how to use the 'force_ssl' filter to force a page to be secure.
`function custom_force_ssl( $force_ssl, $post_id = 0, $url = '' ) {
	if ( $post_id == 5 ) {
		$force_ssl = true;
	}
	return $force_ssl;
}

add_filter('force_ssl' , 'custom_force_ssl', 10, 3);`

You can also use this filter to filter pages based on their URL. Let's say you have an E-commerce site and all of your E-commerce URL's contain 'store'.
`function store_force_ssl( $force_ssl, $post_id = 0, $url = '' ) {
	if ( strpos($url, 'store') !== false ) {
		$force_ssl = true;
	}
	return $force_ssl;
}

add_filter('force_ssl', 'store_force_ssl', 10, 3);`

== Screenshots ==
1. WordPress HTTPS Settings screen
2. Force SSL checkbox added to add/edit posts screen

== Changelog ==
= 3.3.6 =
* Fixed bug where admin links in multisite networks were being broken.
* Added check for Jigoshop admin-ajax.php calls.
= 3.3.5 =
* Enhanced multisite support and testing.
* Slightly adjusted settings page column widths.
* Now using admin-ajax.php for settings page.
* Added detection and conflict fixes for a few popular E-commerce plugins: WooCommerce, WP E-commerce and Jigoshop
* Bug Fix - Password protected pages in WordPress 3.5+ should now be properly secured.
* Bug Fix - The SSL Admin setting should now be properly retained when using FORCE_SSL_ADMIN.
* Bug Fix - Links to the home page should now properly be set to HTTP when using Force SSL Exclusively.
* Bug Fix - Installations with a non-default wp-content folder location should no longer experience issues with the WordPress HTTPS settings page.
= 3.3.0 =
* Tested with WordPress v3.5.
* Added German translation and gettext support. Thanks <a href="https://github.com/cfoellmann">Christian Foellmann</a>.
* Large sites using the default SSL Host (matching the Site URL) should experience a significant performance increase.
* Added the Access-Control-Allow-Origin header to AJAX calls to allow local HTTP pages make HTTPS AJAX calls.
= 3.2.3 =
* Bug Fix - Sites prevented from logging into the admin panel after the previous release should now be working again.
* Bug Fix - Fixed bug in Parser where links and forms could be written incorrectly.
= 3.2.2 =
* Performance Increase.
* Bug Fix - Sites prevented from logging into the admin panel after the previous release should now be working again.
= 3.2.1 =
* Added Network settings for multisite installations.
* Bug Fix - Elements should now be properly secured by the file extension check in the Parser.
* Bug Fix - Pages being redirected should no longer always redirect to index.php for some server configurations.
* Bug Fix - FORCE_SSL_ADMIN option should no longer cause redirect loops if the ssl_admin setting is set to false.
= 3.2 =
* Added domain mapping. Domain mapping allows you to map external domains that host their HTTPS content on a different domain.
* Added Remove Unsecure Elements option. If possible, this option removes external elements from the page that can not be loaded over HTTPS, preventing insecure content errors without modifying any code.
* ClouldFlare support.
* Substantial memory optimization.
* Removed Secure Front Page option. This can now be achieved through URL Filters.
* Bug Fix - Visiting the admin panel over HTTP when using Shared SSL should no longer log the user out, but will now redirect accordingly.
* Bug Fix - Random 404 errors should be gone.
* Bug Fix - Fixed bug where a bad setting for ssl_host would cause the code to fail.
* Bug Fix - CSS backgrounds that do not have quotes should no longer break debug output.
= 3.1.2 =
* Bug Fix - Redirects should no longer remove URL parameters.
* Bug Fix - Removed loginout filter that was changing links to plain text.
* Bug Fix - Plugin should no longer cause JavaScript errors from removing quotes from the end of URL's.
* Bug Fix - CSS backgrounds that do not have quotes should no longer break debug output.
= 3.1.1 =
* Bug Fix - Fixed bug in Parser.
= 3.1 =
* Memory optimization.
* Added secure URL filtering.
* Users receiving 404 errors on every page when using Shared SSL should now be able to use those Shared SSL's that previously did not work.
* Added support for qTranslate.
* Added support for securing custom post types.
* Added $url to the force_ssl filter as the third arguement. See FAQ for example usage.
= 3.0.4 =
* Fixed multiple bugs for sites using SSL for the entire site.
* Bug Fix - plugin should no longer try to load hidden files as modules.
= 3.0.3 =
* Any element on an HTTP page that is set to HTTPS should be auto-corrected.
* Added support for domain mapper plugin.
* Bug Fix - SSL Host should now always end in a trailing slash.
* Bug Fix - Fixed bug in cookie logic that prevented some users from logging in.
* Bug Fix - Fixed bug in redirects that would cause login issues and 404 errors.
= 3.0.2 =
* Added setting to change where HTTPS settings appear in the admin panel.
* Bug Fix - Plugin should no longer interefere with editing posts and using images from the Media Library.
* Bug Fix - Fixed major bug that occurred when site was installed in the base directory.
* Bug Fix - File uploader should no longer produce an HTTP Error.
* Bug Fix - Fixed performance issue that caused the login page to load for a long period of time.
* Bug Fix - Proxy check should no longer interfere with RSS Feeds, HTML Validators, etc.
* Bug Fix - Force SSL and SSL Front Page should no longer conflict.
* Bug Fix - If Force SSL Exclusively is enabled and Secure Front Page is not (or the front page is not secured), links to the front page will be set to HTTP.
= 3.0.1 =
* Bug Fix - Fixed major issue when upgrading from previous version of WordPress HTTPS.
* Bug Fix - Added is_ssl method back to main plugin class to avoid errors with Gravity Forms.
* Bug Fix - Archive widget links should now appear correctly.
= 3.0 =
* The plugin has been completely re-written.
* Redirect loops should no longer be an issue.
* Bugs are likely to occur.
= 2.0.4 =
* Bug Fix - Users using Shared SSL should no longer have broken URL's and redirects.
* Bug Fix - Pages should correctly be identified as HTTPS if PHP returns an IP address for SERVER_ADDR in $_SERVER.
* Bug Fix - Users using the default permalink structure should now have URL's being properly changed to/from HTTPS.
= 2.0.3 =
* Force SSL Admin will always be enabled when FORCE_SSL_ADMIN is true in wp-config.php.
* Bug Fix - Users using Shared SSL should no longer have issues with the SSL Host path duplicating in URL's.
* Bug Fix - The plugin should now function properly when using a subdomain as the SSL Host.
* Bug Fix - Page and post links will only be forced to HTTPS when using a different SSL Host that is not a subdomain of your Home URL.
* Bug Fix - WordPress HTTPS should no longer generate erroneous notices and warnings in apache error logs. (If I missed any, let me know)
= 2.0.2 =
* Bug Fix - SSL Host option was not being saved correctly upon subsequent saves. This was causing redirect loops for most users.
= 2.0.1 =
* Ensured that deprected options are removed from a WordPress installation when activating the plugin.
* Added a button to the WordPress HTTPS settings page to reset all plugin settings and cache.
* Bug Fix - URL's entered for SSL Host were not validing correctly.
* Bug Fix - External URL's were not always being identified as valid external elements.
* Bug Fix - Slight enhancement to SSL detection.
= 2.0 =
* Full support for using a custom SSL port has been added. A special thanks to <a href="http://chrisdoingweb.com/">Chris "doingweb" Antes</a> for his feedback and testing of this feature.
* Forcing pages to/from HTTPS is now pluggable using the 'force_ssl' filter.
* When using Force Shared SSL Admin, links to the admin panel will always be rewritten with the Shared SSL Host.
* When using Shared SSL, all links to post and pages from within the admin panel will use the Shared SSL Host to retain administration functionality on those pages.
* Redirects to the admin panel now hook into wp_redirect rather than using the auth_redirect pluggable function.
* Canonical redirects will now still occur on sites usinga different SSL Host, but not on secure pages.
* Cookies are now set with hooks rather than pluggable functions.
* Plugin will now delete all options and custom metadata when uninstalled.
* Added a HTTP_X_FORWARDED_PROTO check to the is_ssl function.
* Internal HTTPS Elements option has been removed. Disabling this option was never a good idea, so it was removed and the plugin will always act as it did when this option was enabled.
* External HTTPS Elements option has been removed. The handling of external elements has improved in such a way that this option is no longer required.
* Disable Automatic HTTPS option has been removed. This option should have generally been enabled anyway.
* Bug Fix - After logging in, the logged_in cookie was not being set properly. This caused the admin bar to not show up in both HTTP and HTTPS.
* Bug Fix - When using Shared SSL, the login page would not honor the redirect_to variable after a successful login.
= 1.9.2 =
* Added External URL caching to the plugin so that external elements will only be checked for once, increasing the speed of sites not using the Bypass External Check option.
* Any forms whose action points to page that has the Forced SSL option on will be updated to HTTPS even on HTTP pages.
* Bug Fix - When using Shared SSL, permalink structure was being buggy.
* Bug Fix - Certain server configurations were causing the plugin to create redirect loops when using the Force SSL Exclusively option.
= 1.9.1 =
* Bug Fix - Cookies were not being set to the correct paths when logging in, causing logins to fail.
* Bug Fix - Links to the front page when using latest posts were not correctly being set to HTTP/HTTPS.
* Bug Fix - When using Shared SSL, the HTTPS version of the site_url was not being correctly replaced with the Shared SSL URL for internal elements.
* Bug Fix - When using Shared SSL, the admin login page was not always redirecting properly due to output buffering.
* Bug Fix - When using Shared SSL, the auth_redirect function was not redirecting to the Shared SSL URL.
* Bug Fix - If the home_url contained 'www' but the URL appeared without 'www', the URL would not be fixed.
* Standards - Updated redirect method to use https or http as a an argument rather than true or false to better comply with WordPress coding standards.
= 1.9 =
* Created Updates widget on settings screen to allow for dynamic updates from the plugin developers.
* Added support for PHP4.
* Converted all spaces to tabs in source.
* Force Shared SSL Admin option added to allow those using Shared SSL the ability to use their certificate for their admin dashboard.
* Bug fix - Force SSL checkbox will now appear on WordPress versions below 2.9.
* Bug fix - Password protected pages forced to SSL will now work properly.
* Bug fix - Plugin should no longer break feeds.
* Numerous other bug fixes that have since been forgotten due to the length of time this version has been in development.
= 1.8.5 =
* In version 1.8.5, when a page is forced to HTTPS, any links to that page will always be HTTPS, even when using the 'Disable Automatic HTTPS' option. Likewise, when the 'Force SSL Exclusively' option is enabled, all links to pages not forced to HTTPS will be changed to HTTP on HTTPS pages.
* Updated RegEx's for more complicated URL's.
* Bug fix - When in the admin panel, only link URL's are changed back to HTTP again.
* Added support for using Shared SSL together with the FORCE_SSL_ADMIN and FORCE_SSL_LOGIN options.
= 1.8.1 =
* Re-enabled the canonical redirect for WordPres sites not using Shared SSL.
= 1.8 =
* Fixed cross-browser CSS issue on plugin settings page.
* Corrected and updated plugin settings validation.
* Lengthened the fade out timer on messages from the plugin settings page from 2 to 5 seconds so that the more lengthy error messages could be read before the message faded.
* If viewing an admin page via SSL, and your Home URL is not set to HTTPS, links to the front-end of the website will be forced to HTTP. By default, WordPress changes these links to HTTPS.
* When using Shared SSL, any anchor that links to the regular HTTPS version of the domain will be changed to use the Shared SSL Host.
* Added embed and param tags to the list of tags that are fixed by WordPress HTTPS. This is to fix flash movies.
= 1.7.5 =
* Bug fix - When using 'Latest Posts' as the front page, the front page would redirect to HTTP when viewed over HTTPS even if the 'Force SSL Exclusively' option was disabled.
* Prevented the 'Disable Automatic HTTPS' option from parsing URL's in the admin panel.
* General code cleanup and such.
= 1.7 =
* Bug fix - External URL's were not being forced to HTTPS after the last update.
* Added the functionality to correct relative URL's when using Shared SSL.
* General code cleanup and such.
= 1.6.5 =
* Added support for Shared SSL.
= 1.6.3 =
* Changed the redirection check to use `template_redirect` hook rather than `get_header`.
= 1.6.2 =
* Tag links were not being set back to HTTP when the 'Disable Automatic HTTPS' option was enabled.
= 1.6.1 =
* Bug fix - front page redirection was causing issues when a static page was selected for the posts page.
= 1.6 =
* Added the ability to force the front page to HTTPS.
* Multiple enhancements to core functionality of plugin. Mostly just changing code to integrate more smoothely with WordPress.
* Enhancements have been made to the plugin's settings page.
= 1.5.2 =
* Fixed a bug that would prevent stylesheets from being fixed if the rel attribute came after the href attribute. Bug could have also caused errors with other tags.
= 1.5.1 =
* Added input elements with the type of 'image' to be filtered for insecure content.
= 1.5 =
* Added the ability to force SSL on certain pages.
* Also added the option to exclusively force SSL on certain pages. Pages not forced to HTTPS are forced to HTTP.
* Plugin now filters the `bloginfo` and `bloginfo_url` functions for HTTPS URL's when the 'Disable Automatic HTTPS' option is enabled in WordPress 3.0+.
= 1.0.1 =
* Bug fix.
= 1.0 =
* Major modifications to plugin structure, efficiency, and documentation.
* Added the option to disable WordPress 3.0+ from changing all of your page, category and post links to HTTPS.
= 0.5.1 =
* Bug fix.
= 0.5 =
* Due to increasing concerns about plugin performance, the option to bypass the HTTPS check on external elements has been added.
= 0.4 =
* Plugin functions converted to OOP class.
* The plugin will now attempt to set the allow_url_fopen option to true with `ini_set` function if possible.
= 0.3 =
* Added the option to change external elements to HTTPS if the external server allows the elements to be accessed via HTTPS.
= 0.2 =
* Changed the way in which HTTPS was detected to be more reliable.
= 0.1 =
* Initial Release.

== Upgrade Notice ==
= 3.2 =
You may lose your SSL Host setting upon upgrading if it is not default (matching your Site URL).
