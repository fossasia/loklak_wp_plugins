# WP Offload S3 Lite #
**Contributors:** bradt, deliciousbrains  
**Tags:** uploads, amazon, s3, amazon s3, mirror, admin, media, cdn, cloudfront  
**Requires at least:** 4.4  
**Tested up to:** 4.5.2  
**Stable tag:** 1.0.4  
**License:** GPLv3  

Copies files to Amazon S3 as they are uploaded to the Media Library. Optionally configure Amazon CloudFront for faster delivery.

## Description ##

https://www.youtube.com/watch?v=_PVybEGaRXc

This plugin automatically copies images, videos, documents, and any other media added through WordPress' media uploader to [Amazon S3](http://aws.amazon.com/s3/). It then automatically replaces the URL to each media file with their respective Amazon S3 URL or, if you have configured [Amazon CloudFront](http://aws.amazon.com/cloudfront/), the respective CloudFront URL. Image thumbnails are also copied to Amazon S3 and delivered through S3/CloudFront.

Uploading files *directly* to your Amazon S3 account is not currently supported by this plugin. They are uploaded to your server first, then copied to Amazon S3. There is an option to automatically remove the files from your server once they are copied to Amazon S3 however.

If you're adding this plugin to a site that's been around for a while, your existing media files will not be copied or served from Amazon S3. Only newly uploaded files will be copied and served from Amazon S3. The pro upgrade has an upload tool to handle existing media files.

**PRO Upgrade with Email Support and More Features**

* Upload existing Media Library to Amazon S3
* Find & replace file URLs in content
* Control Amazon S3 files from the Media Library
* [Assets addon](https://deliciousbrains.com/wp-offload-s3/?utm_source=wordpress.org&utm_medium=web&utm_content=desc&utm_campaign=os3-free-plugin#assets-addon) - Serve your CSS & JS from Amazon S3/CloudFront
* [WooCommerce addon](https://deliciousbrains.com/wp-offload-s3/?utm_source=wordpress.org&utm_medium=web&utm_content=desc&utm_campaign=os3-free-plugin#woocommerce-addon)
* [Easy Digital Downloads addon](https://deliciousbrains.com/wp-offload-s3/?utm_source=wordpress.org&utm_medium=web&utm_content=desc&utm_campaign=os3-free-plugin#edd-addon)
* PriorityExpert&trade; email support

[Compare pro vs free &rarr;](http://deliciousbrains.com/wp-offload-s3/upgrade/?utm_source=wordpress.org&utm_medium=web&utm_content=desc&utm_campaign=os3-free-plugin)

The video below runs through the pro upgrade features...

https://www.youtube.com/watch?v=55xNGnbJ_CY

*This plugin has been completely rewritten, but was originally a fork of
[Amazon S3 for WordPress with CloudFront](http://wordpress.org/extend/plugins/tantan-s3-cloudfront/)
which is a fork of [Amazon S3 for WordPress](http://wordpress.org/extend/plugins/tantan-s3/), also known as tantan-s3.*

## Installation ##

1. Install the required [Amazon Web Services plugin](http://wordpress.org/extend/plugins/amazon-web-services/) using WordPress' built-in installer
2. Follow the instructions to setup your AWS access keys
3. Install this plugin using WordPress' built-in installer
4. Access the *S3 and CloudFront* option under *AWS* and configure

## Frequently Asked Questions ##

### What are the minimum requirements? ###

You can see the minimum requirements [here](https://deliciousbrains.com/wp-offload-s3/pricing/?utm_source=wordpress.org&utm_medium=web&utm_content=desc&utm_campaign=os3-free-plugin#requirements).

## Screenshots ##

### 1. Choosing/creating a bucket ###
![Choosing/creating a bucket](https://raw.githubusercontent.com/deliciousbrains/wp-wp-offload-s3-lite/assets/screenshot-1.png)

### 2. Settings screen ###
![Settings screen](https://raw.githubusercontent.com/deliciousbrains/wp-wp-offload-s3-lite/assets/screenshot-2.png)


## Upgrade Notice ##

### 0.6 ###
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

### 0.6.1 ###
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

### 0.6.2 ###
This version requires PHP 5.3.3+ and the Amazon Web Services plugin

## Changelog ##

### WP Offload S3 Lite 1.0.4 - 2016-05-30 ###
* New: Now using simpler Force HTTPS setting, removed redundant Always Use HTTP setting.
* New: `as3cf_cloudfront_path_parts` filter allows changing served CloudFront path (useful when distribution pulls subdirectory).
* Improvement: Better compatibility with non-standard notices from other plugins and themes.
* Improvement: Added basic auth and proxy info to diagnostic info.
* Improvement: Added `allow_url_fopen` status to diagnostic info.
* Improvement: Added memory usage to diagnostic info.
* Improvement: Ensure notice text is 800px or less in width.
* Improvement: Reduced database queries on settings screen.
* Bug fix: Properly handle _wp_attachment_data metadata when it is a serialized WP_Error.

### WP Offload S3 Lite 1.0.3 - 2016-03-23 ###
* Bug fix: Don't replace srcset URLs when Rewrite File URLs option disabled
* Bug fix: Fatal error: Cannot redeclare as3cf_get_secure_attachment_url()

### WP Offload S3 Lite 1.0.2 - 2016-03-08 ###
* Bug fix: Uninstall would run even if pro plugin installed

### WP Offload S3 Lite 1.0.1 - 2016-03-08 ###
* Bug fix: Fatal error on plugin activation
* Bug fix: Unable to activate Pro upgrade

### WP Offload S3 Lite 1.0 - 2016-03-07 ###
* New: Plugin renamed to "WP Offload S3 Lite"
* New: Define any and all settings with a constant in wp-config.php
* New: Documentation links for each setting
* Improvement: Simplified domain setting UI
* Improvement: Far future expiration header set by default
* Improvement: Newly created bucket now immediately appears in the bucket list
* Improvement: Cleanup user meta on uninstall
* Improvement: WP Retina 2x integration [removed](https://deliciousbrains.com/wp-offload-s3/doc/copy-hidpi-2x-images-support/)
* Bug fix: Year/Month folder structure on S3 not created if the 'Organise my uploads into month and year-based folders' WordPress setting is disabled
* Bug fix: Responsive srcset PHP notices
* Bug fix: Compatibility addon notices displayed to non-admin users
* Bug fix: Potential PHP fatal error in MySQL version check in diagnostic log
* Bug fix: Missing image library notices displaying before plugin is setup

### WP Offload S3 0.9.12 - 2016-02-03 ###
* Improvement: Compatibility with WP Offload S3 Assets 1.1
* Bug fix: Object versioned responsive images in post content not working when served from S3 on WordPress 4.4+

### WP Offload S3 0.9.11 - 2015-12-19 ###
* Bug fix: Responsive images in post content not working when served from S3
* Bug fix: Responsive images using wrong image size when there are multiple images with the same width

### WP Offload S3 0.9.10 - 2015-11-26 ###
* Improvement: Support for responsive images in WP 4.4
* Bug fix: Incorrect file path for intermediate image size files uploaded to S3 with no prefix
* Bug fix: Thumbnail previews return 404 error during image edit screen due to character encoding

### WP Offload S3 0.9.9 - 2015-11-12 ###
* Improvement: Improve wording of compatibility notices
* Improvement: Compatibility with Easy Digital Downloads 1.0.1 and WooCommerce 1.0.3 addons
* Improvement: Better determine available memory for background processes
* Bug fix: URL previews incorrect due to stripping `/` characters
* Bug fix: PHP Warning: stream_wrapper_register(): Protocol s3:// is already defined
* Bug fix: PHP Fatal error:  Call to undefined method WP_Error::get()

### WP Offload S3 0.9.8 - 2015-11-02 ###
* Bug fix: Attachment URLs containing query string parameters incorrectly encoded

### WP Offload S3 0.9.7 - 2015-10-26 ###
* Improvement: Improve compatibility with third party plugins when the _Remove Files From Server_ option is enabled
* Improvement: Fix inconsistent spacing on the WP Offload S3 settings screen
* Improvement: Validate _CloudFront or custom domain_ input field
* Improvement: Link to current S3 bucket added to WP Offload S3 settings screen
* Improvement: Show notice when neither GD or Imagick image libraries are not installed
* Improvement: Supply Cache-Control header to S3 when the _Far Future Expiration Header_ option is enabled
* Improvement: Additional information added to _Diagnostic Information_
* Improvement: Added warning when _Remove Files From Server_ option is enabled
* Improvement: Filter added to allow additional image versions to be uploaded to S3
* Bug fix: File size not stored in _wp_attachment_metadata_ when _Remove Files From Server_ option is enabled
* Bug fix: Uploads on Multisite installs allowed after surpassing upload limit
* Bug fix: Site icon in WordPress customizer returns 404
* Bug fix: Image versions remain locally and on S3 after deletion, when the file name contains characters which require escaping
* Bug fix: Files with the same file name overwritten when __Remove Files From Server_ option is enabled
* Bug fix: Cron tasks incorrectly scheduled due to passing the wrong time to `wp_schedule_event`
* Bug fix: Default options not shown in the UI after first install

### WP Offload S3 0.9.6 - 2015-10-01 ###
* Improvement: Update text domains for translate.wordpress.org integration

### WP Offload S3 0.9.5 - 2015-09-01 ###
* Bug fix: Fatal error: Cannot use object of type WP_Error as array

### WP Offload S3 0.9.4 - 2015-08-27 ###
* New: Update all existing attachments with missing file sizes when the 'Remove Files From Server' option is enabled (automatically runs in the background)
* Improvement: Show when constants are used to set bucket and region options
* Improvement: Don't show compatibility notices on plugin update screen
* Improvement: On Multisite installs don't call `restore_current_blog()` on successive loop iterations
* Bug fix: 'Error getting URL preview' alert shown when enter key pressed on settings screen
* Bug fix: Unable to crop header images when the 'Remove Files From Server' option is enabled
* Bug fix: Incorrect storage space shown on Multisite installs when the 'Remove Files From Server' option is enabled
* Bug fix: Upload attempted to non existent bucket when defined by constant
* Bug fix: 'SignatureDoesNotMatch' error shown when using signed URLs with bucket names containing '.' characters

### WP Offload S3 0.9.3 - 2015-08-17 ###
* New: Pro upgrade sidebar
* Bug fix: Create buckets in US standard region causing S3 URLs to 404 errors

### WP Offload S3 0.9.2 - 2015-07-29 ###
* Bug fix: Accidentally released the sidebar for after we launch the pro version

### WP Offload S3 0.9.1 - 2015-07-29 ###
* Improvement: Access denied sample IAM policy replaced with link to [Quick Start Guide](https://deliciousbrains.com/wp-offload-s3/doc/quick-start-guide/)
* Improvement: Access denied messages on bucket selection or bucket creation now link to [Quick Start Guide](https://deliciousbrains.com/wp-offload-s3/doc/quick-start-guide/)
* Improvement: Object expires time can now be filtered using the `as3cf_object_meta` filter
* Bug fix: Error not always shown when S3 bucket inaccessible due to incorrect permissions
* Bug fix: Permission checks fail when S3 bucket is in a non-default region and defined by `AS3CF_BUCKET` constant
* Bug fix: Restore `as3cf_get_attached_file_copy_back_to_local` filter
* Bug fix: Image versions not uploaded to S3 when an edited image is restored
* Bug fix: Original image version not deleted from server when _Remove Files From Server_ option enabled
* Bug fix: Media library items with non-ascii characters in the file name are not removed from S3
* Bug fix: Compatibility notices shown on plugin install pages
* Bug fix: WordPress footer overlaps WP Offload S3 sidebar
* Bug fix: Upon initial setup the settings changed alert shows when no settings have changed

### WP Offload S3 0.9 - 2015-07-08 ###
* New: Plugin rebranded to WP Offload S3
* New: Support tab added to _Offload S3_ screen containing diagnostic information
* New: Compatibility with the [Media Replace](https://wordpress.org/plugins/enable-media-replace/) plugin
* New: Select bucket region when creating a new bucket
* New: Toggle switches redesigned
* Improvement: Compatibility with release candidate of Pro plugin
* Improvement: Example IAM policy more secure
* Improvement: Set default bucket region using the `AS3CF_REGION` constant
* Improvement: Added `as3cf_object_meta` filter for developers
* Improvement: Bucket selection moved to modal window
* Improvement: Don't allow bucket names to contain invalid characters on creation
* Improvement: More verbose error messages on bucket selection
* Improvement: Settings link added to plugin row on _Plugins_ screen
* Improvement: Object versioning enabled by default
* Improvement: Uninstall routines added
* Improvement: JavaScript coding standards
* Improvement: Cache result when checking S3 bucket permissions
* Bug fix: Bucket region errors result in blank WP Offload S3 screen
* Bug fix: Editing an image when _Remove Files From Server_ option is enabled results in error
* Bug fix: Metadata upgrade procedure triggered on new installs
* Bug fix: File URLs when uploaded to a subdirectory result in incorrect S3 URLs
* Bug fix: Errors logged when trying to delete non-existent HiDPI images
* Bug fix: SignatureDoesNotMatch errors on regions with v4 authentication
* Bug fix: Customizer background image not editable
* Bug fix: Error when creating buckets with US Standard region
* Bug fix: Notices appearing incorrectly on some admin screens
* Bug fix: Subsite upload paths repeated on multisite installs
* Bug fix: Handle multisite installs where `BLOG_ID_CURRENT_SITE` is not 1

### WP Offload S3 0.8.2 - 2015-01-31 ###
* New: Input bucket in settings to avoid listing all buckets
* New: Specify bucket with 'AS3CF_BUCKET' constant
* Improvement: Compatibility with beta release of Pro plugin
* Bug Fix: Incorrect file prefix in S3 permission check

### WP Offload S3 0.8.1 - 2015-01-19 ###
* Bug Fix: Permission problems on installs running on EC2s
* Bug Fix: Blank settings page due to WP_Error on S3 permission check
* Bug Fix: Warning: strtolower() expects parameter 1 to be string, object given
* Bug Fix: Region post meta update running on subsites of Multisite installs

### WP Offload S3 0.8 - 2015-01-10 ###
* New: Redesigned settings UI
* Improvement: SSL setting can be fully controlled, HTTPS for urls always, based on request or never
* Improvement: Download files from S3 that are not found on server when running Regenerate Thumbnails plugin
* Improvement: When calling `get_attached_file()` and file is missing from server, return S3 URL
* Improvement: Code cleanup to WordPress coding standards
* Bug Fix: Files for all subsites going into the same S3 folder on multisite installs setup prior to WP 3.5
* Bug Fix: 'attempting to access local file system' error for some installs

### WP Offload S3 0.7.2 - 2014-12-11 ###
* Bug: Some buckets in the EU region causing permission and HTTP errors
* Bug: Undefined variable: message in view/error.php also causing white screens

### WP Offload S3 0.7.1 - 2014-12-05 ###
* Bug: Read-only error on settings page sometimes false positive

### WP Offload S3 0.7 - 2014-12-04 ###
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

### WP Offload S3 0.6.1 - 2013-09-21 ###
* WP.org download of Amazon Web Services plugin is giving a 404 Not Found, so directing people to download from Github instead

### WP Offload S3 0.6 - 2013-09-20 ###
* Complete rewrite
* Now requires PHP 5.3.3+
* Now requires the [Amazon Web Services plugin](http://wordpress.org/extend/plugins/amazon-web-services/) which contains the latest PHP libraries from Amazon
* Now works with multisite
* New Option: Custom S3 object path
* New Option: Always serve files over https (SSL)
* New Option: Enable object versioning by appending a timestamp to the S3 file path
* New Option: Remove uploaded file from local filesystem once it has been copied to S3
* New Option: Copy any HiDPI (@2x) images to S3 (works with WP Retina 2x plugin)

### WP Offload S3 0.5 - 2013-01-29 ###
* Forked [Amazon S3 for WordPress with CloudFront](http://wordpress.org/extend/plugins/tantan-s3-cloudfront/)
* Cleaned up the UI to fit with today's WP UI
* Fixed issues causing error messages when WP_DEBUG is on
* [Delete files on S3 when deleting WP attachment](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/e777cd49a4b6999f999bd969241fb24cbbcece60)
* [Added filter to the get_attachment_url function](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/bbe1aed5c2ae900e9ba1b16ba6806c28ab8e2f1c)
* [Added function to get a temporary, secure download URL for private files](https://github.com/deliciousbrains/wp-amazon-s3-and-cloudfront/commit/11f46ec2714d34907009e37ad3b97f4421aefed3)
