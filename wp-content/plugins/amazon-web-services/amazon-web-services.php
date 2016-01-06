<?php
/*
Plugin Name: Amazon Web Services
Plugin URI: http://wordpress.org/extend/plugins/amazon-web-services/
Description: Includes the Amazon Web Services PHP libraries, stores access keys, and allows other plugins to hook into it.
Author: Delicious Brains
Version: 0.3.4
Author URI: http://deliciousbrains.com/
Network: True
Text Domain: amazon-web-services
Domain Path: /languages/
*/

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

$GLOBALS['aws_meta']['amazon-web-services']['version'] = '0.3.4';

$GLOBALS['aws_meta']['amazon-web-services']['supported_addon_versions'] = array(
	'amazon-s3-and-cloudfront' => '0.9',
);

require dirname( __FILE__ ) . '/classes/aws-compatibility-check.php';
global $aws_compat_check;
$aws_compat_check = new AWS_Compatibility_Check( __FILE__ );

if ( $aws_compat_check->is_compatible() ) {
	add_action( 'init', 'amazon_web_services_init' );
}

/**
 * Fire up the plugin if compatibility checks have been met
 */
function amazon_web_services_require_files() {
	$abspath = dirname( __FILE__ );
	require_once $abspath . '/classes/aws-plugin-base.php';
	require_once $abspath . '/classes/amazon-web-services.php';
	require_once $abspath . '/vendor/aws/aws-autoloader.php';
}

function amazon_web_services_init() {
	amazon_web_services_require_files();
	global $amazon_web_services;
	$amazon_web_services = new Amazon_Web_Services( __FILE__ );
}

/**
 * On activation check the plugin meets compatibility checks
 * and migrate any legacy settings over to the new option
 *
 */
function amazon_web_services_activation() {
	global $aws_compat_check;
	if ( ! $aws_compat_check->is_compatible() ) {
		$error_msg = $aws_compat_check->get_error_msg();
		include dirname( __FILE__ ) . '/view/activation-error.php';
		die();
	}

	// Migrate keys over from old Amazon S3 and CloudFront plugin settings
	if ( ! ( $as3cf = get_option( 'tantan_wordpress_s3' ) ) ) {
		return;
	}

	if ( ! isset( $as3cf['key'] ) || ! isset( $as3cf['secret'] ) ) {
		return;
	}

	amazon_web_services_require_files();

	if ( ! get_site_option( Amazon_Web_Services::SETTINGS_KEY ) ) {
		add_site_option( Amazon_Web_Services::SETTINGS_KEY, array(
			'access_key_id'     => $as3cf['key'],
			'secret_access_key' => $as3cf['secret'],
		) );
	}

	unset( $as3cf['key'] );
	unset( $as3cf['secret'] );

	update_option( 'tantan_wordpress_s3', $as3cf );
}

register_activation_hook( __FILE__, 'amazon_web_services_activation' );
