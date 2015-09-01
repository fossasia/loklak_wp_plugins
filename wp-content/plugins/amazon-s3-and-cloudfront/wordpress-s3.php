<?php
/*
Plugin Name: WP Offload S3
Plugin URI: http://wordpress.org/extend/plugins/amazon-s3-and-cloudfront/
Description: Automatically copies media uploads to Amazon S3 for storage and delivery. Optionally configure Amazon CloudFront for even faster delivery.
Author: Delicious Brains
Version: 0.9.5
Author URI: http://deliciousbrains.com/
Network: True
Text Domain: as3cf
Domain Path: /languages/

// Copyright (c) 2013 Delicious Brains. All rights reserved.
//
// Released under the GPL license
// http://www.opensource.org/licenses/gpl-license.php
//
// **********************************************************************
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// **********************************************************************
//
// Forked Amazon S3 for WordPress with CloudFront (http://wordpress.org/extend/plugins/tantan-s3-cloudfront/)
// which is a fork of Amazon S3 for WordPress (http://wordpress.org/extend/plugins/tantan-s3/).
// Then completely rewritten.
*/

$GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'] = '0.9.5';

$GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['supported_addon_versions'] = array(
	'amazon-s3-and-cloudfront-pro' => '1.0b1',
);

$aws_plugin_version_required = '0.3';

require dirname( __FILE__ ) . '/classes/wp-aws-compatibility-check.php';
global $as3cf_compat_check;
$as3cf_compat_check = new WP_AWS_Compatibility_Check(
	'WP Offload S3',
	'amazon-s3-and-cloudfront',
	__FILE__,
	'Amazon Web Services',
	'amazon-web-services',
	$aws_plugin_version_required
);

function as3cf_init( $aws ) {
	global $as3cf_compat_check;
	if ( ! $as3cf_compat_check->is_compatible() ) {
		return;
	}

	global $as3cf;
	$abspath = dirname( __FILE__ );
	require_once $abspath . '/include/functions.php';
	require_once $abspath . '/classes/as3cf-upgrade.php';
	require_once $abspath . '/classes/upgrades/as3cf-region-meta.php';
	require_once $abspath . '/classes/upgrades/as3cf-file-sizes.php';
	require_once $abspath . '/classes/upgrades/as3cf-meta-wp-error.php';
	require_once $abspath . '/classes/as3cf-plugin-compatibility.php';
	require_once $abspath . '/classes/amazon-s3-and-cloudfront.php';
	$as3cf = new Amazon_S3_And_CloudFront( __FILE__, $aws );
}

add_action( 'aws_init', 'as3cf_init' );
