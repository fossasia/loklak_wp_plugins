<?php
/*
Plugin Name: Amazon S3 and CloudFront
Plugin URI: http://wordpress.org/extend/plugins/amazon-s3-and-cloudfront/
Description: Automatically copies media uploads to Amazon S3 for storage and delivery. Optionally configure Amazon CloudFront for even faster delivery.
Author: Brad Touesnard
Version: 0.8.2
Author URI: http://bradt.ca
Network: True
Text Domain: as3cf
Domain Path: /languages/

// Copyright (c) 2013 Brad Touesnard. All rights reserved.
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

$GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'] = '0.8.2';

$GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['supported_addon_versions'] = array(
	'amazon-s3-and-cloudfront-edd' => '1.0.1',
	'amazon-s3-and-cloudfront-pro' => '0.9',
);

$aws_plugin_version_required = '0.2.2';

require dirname( __FILE__ ) . '/classes/as3cf-compatibility-check.php';
global $as3cf_compat_check;
$as3cf_compat_check = new AS3CF_Compatibility_Check( __FILE__, $aws_plugin_version_required );

function as3cf_init( $aws ) {
	global $as3cf_compat_check;
	if ( ! $as3cf_compat_check->is_compatible() ) {
		return;
	}

	global $as3cf;
	$abspath = dirname( __FILE__ );
	require_once $abspath . '/include/functions.php';
	require_once $abspath . '/classes/as3cf-upgrade.php';
	require_once $abspath . '/classes/amazon-s3-and-cloudfront.php';
	$as3cf = new Amazon_S3_And_CloudFront( __FILE__, $aws );
}

add_action( 'aws_init', 'as3cf_init' );
