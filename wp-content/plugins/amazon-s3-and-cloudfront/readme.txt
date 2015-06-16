=== Amazon S3 and Cloudfront ===
Contributors: bradt
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=5VPMGLLK94XJC
Tags: uploads, amazon, s3, mirror, admin, media, cdn, cloudfront
Requires at least: 3.5
Tested up to: 4.1
Stable tag: 0.8.2
License: GPLv3

Copies files to Amazon S3 as they are uploaded to the Media Library. Optionally configure Amazon CloudFront for faster delivery.

== Description ==

This plugin automatically copies images, videos, documents, and any other media added through WordPress' media uploader to [Amazon Simple Storage Service](http://aws.amazon.com/s3/) (S3). It then automatically replaces the URL to each media file with their respective S3 URL or, if you have configured [Amazon CloudFront](http://aws.amazon.com/cloudfront/), the respective CloudFront URL. Image thumbnails are also copied to S3 and delivered through S3/CloudFront.

Uploading files *directly* to your S3 account is not currently supported by this plugin. They are uploaded to your server first, then copied to S3. There is an option to automatically remove the files from your server once they are copied to S3 however.

If you're adding this plugin to a site that's been around for a while, your existing media files will not be copied or served from S3. Only newly uploaded files will be copied and served from S3.

**Pro Version**

We’re working on a pro version that will include the following features:

* Copy existing Media Library to S3
* Serve theme JS & CSS from S3/CloudFront
* WooCommerce & EDD integration
* Awesome email support

[Sign up for news about the pro version](https://confirmsubscription.com/h/t/295CA85AEB94E879)

[Request features, report bugs, and submit pull requests on Github](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/issues)

*This plugin has been completely rewritten, but was originally a fork of
[Amazon S3 for WordPress with CloudFront](http://wordpress.org/extend/plugins/tantan-s3-cloudfront/)
which is a fork of [Amazon S3 for WordPress](http://wordpress.org/extend/plugins/tantan-s3/), also known as tantan-s3.*

== Installation ==

1. Install the required [Amazon Web Services plugin](http://wordpress.org/extend/plugins/amazon-web-services/) using WordPress' built-in installer
2. Follow the instructions to setup your AWS access keys
3. Install this plugin using WordPress' built-in installer
4. Access the *S3 and CloudFront* option under *AWS* and configure

== Screenshots ==

1. Choosing/creating a bucket
2. Settings screen

== Upgrade Notice ==

= 0.6 =
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

= 0.6.1 =
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

= 0.6.2 =
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

== Changelog ==

= 0.8.2 - 2015-01-31 =
* New: Input bucket in settings to avoid listing all buckets
* New: Specify bucket with 'AS3CF_BUCKET' constant
* Improvement: Compatibility with beta release of Pro plugin
* Bug Fix: Incorrect file prefix in S3 permission check

= 0.8.1 - 2015-01-19 =
* Bug Fix: Permission problems on installs running on EC2s
* Bug Fix: Blank settings page due to WP_Error on S3 permission check
* Bug Fix: Warning: strtolower() expects parameter 1 to be string, object given
* Bug Fix: Region post meta update running on subsites of Multisite installs

= 0.8 - 2015-01-10 =
* New: Redesigned settings UI
* Improvement: SSL setting can be fully controlled, HTTPS for urls always, based on request or never
* Improvement: Download files from S3 that are not found on server when running Regenerate Thumbnails plugin
* Improvement: When calling `get_attached_file()` and file is missing from server, return S3 URL
* Improvement: Code cleanup to WordPress coding standards
* Bug Fix: Files for all subsites going into the same S3 folder on multisite installs setup prior to WP 3.5
* Bug Fix: 'attempting to access local file system' error for some installs

= 0.7.2 - 2014-12-11 =
* Bug: Some buckets in the EU region causing permission and HTTP errors
* Bug: Undefined variable: message in view/error.php also causing white screens

= 0.7.1 - 2014-12-05 =
* Bug: Read-only error on settings page sometimes false positive

= 0.7 - 2014-12-04 =
* New: Proper S3 region subdomain in URLs for buckets not in the US Standard region (e.g. https://s3-us-west-2.amazonaws.com/...)
* New: Update all existing attachment meta with bucket region (automatically runs in the background)
* New: Get secure URL for different image sizes (iamzozo)
* New: S3 bucket can be set with constant in wp-config.php (dberube)
* New: Filter for allowing/disallowing file types: `as3cf_allowed_mime_types`
* New: Filter to cancel upload to S3 for any reason: `as3cf_pre_update_attachment_metadata`
* New: Sidebar with email opt-in
* Improvement: Show warning when S3 policy is read-only
* Improvement: Tooltip added to clarify option
* Improvement: Move object versioning option to make it clear it does not require CloudFront
* Improvement: By default only allow file types in `get_allowed_mime_types()` to be uploaded to S3
* Improvement: Compatibility with WPML Media plugin
* Bug Fix: Edited images not removed on S3 when restoring image and IMAGE_EDIT_OVERWRITE true
* Bug Fix: File names with certain characters broken not working
* Bug Fix: Edited image uploaded to incorrect month folder
* Bug Fix: When creating a new bucket the bucket select box appears empty on success
* Bug Fix: SSL not working in regions other than US Standard
* Bug Fix: 'Error uploading' and 'Error removing local file' messages when editing an image
* Bug Fix: Upload and delete failing when bucket is non-US-region and bucket name contains dot
* Bug Fix: S3 file overwritten when file with same name uploaded and local file removed (dataferret)
* Bug Fix: Manually resized images not uploaded (gmauricio)

= 0.6.1 - 2013-09-21 =
* WP.org download of Amazon Web Services plugin is giving a 404 Not Found, so directing people to download from Github instead

= 0.6 - 2013-09-20 =
* Complete rewrite
* Now requires PHP 5.3.3+
* Now requires the [Amazon Web Services plugin](http://wordpress.org/extend/plugins/amazon-web-services/) which contains the latest PHP libraries from Amazon
* Now works with multisite
* New Option: Custom S3 object path
* New Option: Always serve files over https (SSL)
* New Option: Enable object versioning by appending a timestamp to the S3 file path
* New Option: Remove uploaded file from local filesystem once it has been copied to S3
* New Option: Copy any HiDPI (@2x) images to S3 (works with WP Retina 2x plugin)

= 0.5 - 2013-01-29 =
* Forked [Amazon S3 for WordPress with CloudFront](http://wordpress.org/extend/plugins/tantan-s3-cloudfront/)
* Cleaned up the UI to fit with today's WP UI
* Fixed issues causing error messages when WP_DEBUG is on
* [Delete files on S3 when deleting WP attachment](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/e777cd49a4b6999f999bd969241fb24cbbcece60)
* [Added filter to the get_attachment_url function](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/bbe1aed5c2ae900e9ba1b16ba6806c28ab8e2f1c)
* [Added function to get a temporary, secure download URL for private files](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/11f46ec2714d34907009e37ad3b97f4421aefed3)
