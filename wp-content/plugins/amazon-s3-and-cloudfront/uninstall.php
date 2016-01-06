<?php
/**
 * Uninstall WP Offload S3
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  uninstall
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require dirname( __FILE__ ) . '/classes/wp-aws-uninstall.php';

$options = array(
	'tantan_wordpress_s3',
	'update_meta_with_region_session',
	'update_file_sizes_session',
	'as3cf_compat_addons_to_install'
);

$postmeta = array(
	'amazonS3_info',
	'wpos3_filesize_total',
);

$crons = array(
	'as3cf_cron_update_meta_with_region',
	'as3cf_cron_update_file_sizes',
);

$transients = array(
	'site'    => array(
		'as3cf_notices',
		'wpos3_attachment_counts',
	),
	'subsite' => array( 'wpos3_site_space_used' ),
);

$as3cf_uninstall = new WP_AWS_Uninstall( $options, $postmeta, $crons, $transients );
