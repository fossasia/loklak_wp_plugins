WP SendGrid
===========
* Contributors: [itsananderson](http://profiles.wordpress.org/itsananderson),
  [Zer0Divisor](http://profiles.wordpress.org/Zer0Divisor)
* Tags: [email](http://wordpress.org/extend/plugins/tags/email)
* Requires at least: 3.0
* Tested up to: 3.5.1
* Stable tag: 2.1.0

WP SendGrid routes all emails through SendGrid to improve deliverability

Description
-----------

By sending all emails through SendGrid, you can help them end up in an inbox, not a spam folder.
SendGrid provides a high-deliverability email service, protecting your emails from overzealous spam filters.
WP SendGrid uses SendGrid's API to make sure your site's emails are delivered.
This helps ensure reliability for plugins that notify subscribers of new posts, verify new user emails, and perform other email-based tasks.

If you do WordPress development on Windows, or are hosting your site on Windows Azure, this plugin is an easy way to make sure your emails can be delivered.
Because Windows doesn't have a built-in sendmail service, WordPress can't send emails by default,
but since WP SendGrid routes all emails through the SendGrid API, they will be delivered, even if you're running on Windows.

To install, enable WP SendGrid like you would any other WordPress plugin. Enter your SendGrid credentials (you'll need a SendGrid account), and you should be ready to go. If you wish, you can also choose between SendGrid's REST API and their SMTP servers, and whether to connect to SendGrid using a secure connection.

Installation
------------

To manually upload WP SendGrid files to your site:

1. Upload the WP SendGrid to the /wp-contents/plugins/ folder.
1. Activate the plugin from the "Plugins" menu in WordPress.
1. Navigate to "Settings" &rarr; "SendGrid Settings" and enter your SendGrid API credentials

Or to install from the WordPress admin:

1. Navigate to "Plugins" &rarr; "Add New"
1. Search for "WP SendGrid" and click "Install Now" for the "WP SendGrid" plugin listing
1. Activate the plugin from the "Plugins" menu in WordPress, or from the plugin installation screen.
1. Navigate to "Settings" &rarr; "SendGrid Settings" and enter your SendGrid API credentials

Changelog
---------

#### 2.1.0 ####
* Added support for network-level settings in Multisite

#### 2.0.1 ####
* Fix a typo in wp_mail function definition

#### 2.0 ####
* Rewrote settings page using the Settings API
* Added filters to make extending easier

#### 1.0.1 ####
* Remove hardcoded "from" address and use WordPress provided address instead

#### 1.0 ####
* Initial release
