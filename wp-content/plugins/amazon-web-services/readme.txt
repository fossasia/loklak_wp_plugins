=== Amazon Web Services ===
Contributors: bradt, deliciousbrains
Tags: amazon, amazon web services
Requires at least: 3.7
Tested up to: 4.5
Stable tag: trunk
License: GPLv3

Houses the Amazon Web Services (AWS) PHP libraries and manages access keys. Required by other AWS plugins.

== Description ==

This plugin is required by other plugins, which uses its libraries and its settings to connect to AWS services. Currently, there is only one plugin that requires this plugin:

* [WP Offload S3](http://wordpress.org/plugins/amazon-s3-and-cloudfront/)

= Requirements =

* PHP version 5.3.3 or greater
* PHP cURL library 7.16.2 or greater
* cURL compiled with OpenSSL and zlib

== Installation ==

1. Use WordPress' built-in installer
2. A new AWS menu will appear in the side menu

== Screenshots ==

1. Settings screen

== Changelog ==

= 0.3.5 - 2016-03-07 =
* Improvement: Support for `DBI_` prefixed constants to avoid conflicts with other plugins
* Improvement: Redesign of the Addons page
* Improvement: Compatibility with WP Offload S3 Lite 1.0
* Improvement: Compatibility with WP Offload S3 1.1

= 0.3.4 - 2015-11-02 =
* Improvement: Compatibility with WP Offload S3 Pro 1.0.3

= 0.3.3 - 2015-10-26 =
* Improvement: Updated Amazon SDK to version 2.8.18
* Improvement: Fix inconsistent notice widths on _Access Keys_ screen
* New: WP Offload S3 Pro addons (Enable Media Replace, Meta Slider, WPML) added to the _Addons_ screen

= 0.3.2 - 2015-08-26 =
* New: WP Offload S3 Pro upgrade and addons added to the _Addons_ screen

= 0.3.1 - 2015-07-29 =
* Bug fix: Style inconsistencies on the _Addons_ screen

= 0.3 - 2015-07-08 =
* New: Support for [IAM Roles on Amazon EC2](https://deliciousbrains.com/wp-offload-s3/doc/iam-roles/) using the `AWS_USE_EC2_IAM_ROLE` constant
* New: Redesigned _Access Keys_ and _Addons_ screens
* Improvement: _Settings_ menu item renamed to _Access Keys_
* Improvement: _Access Keys_ link added to plugin row on _Plugins_ screen
* Improvement: Activate addons directly from within _Addons_ screen
* Improvement: [Quick Start Guide](https://deliciousbrains.com/wp-offload-s3/doc/quick-start-guide/) documentation

= 0.2.2 - 2015-01-19 =
* Bug Fix: Reverting AWS client config of region and signature

= 0.2.1 - 2015-01-10 =
* New: AWS SDK updated to 2.7.13
* New: Translation ready
* Improvement: Code cleanup to WordPress coding standards
* Improvement: Settings notice UI aligned with WordPress style
* Bug: Error if migrating keys over from old Amazon S3 and CloudFront plugin settings

= 0.2 - 2014-12-04 =
* New: AWS SDK updated to 2.6.16
* New: Set the region for the AWS client by defining `AWS_REGION` in your wp-config.php
* New: Composer file for Packagist support
* Improvement: Base plugin class performance of installed version
* Improvement: Base plugin class accessor for various properties
* Improvement: Addon plugin modal now responsive
* Improvement: Better menu icon
* Improvement: Code formatting to WordPress standards

= 0.1 - 2013-09-20 =
* First release
