=== Amazon Web Services ===
Contributors: bradt
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5VPMGLLK94XJC
Tags: amazon, amazon web services
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 0.2.2
License: GPLv3

Houses the Amazon Web Services (AWS) PHP libraries and manages access keys. Required by other AWS plugins.

== Description ==

This plugin is required by other plugins, which uses its libraries and its settings to connect to AWS services. Currently, there is only one plugin that requires this plugin:

* [Amazon S3 and CloudFront](http://wordpress.org/plugins/amazon-s3-and-cloudfront/)

== Installation ==

1. Use WordPress' built-in installer
2. A new AWS menu will appear in the side menu

== Screenshots ==

1. Settings screen

== Changelog ==

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