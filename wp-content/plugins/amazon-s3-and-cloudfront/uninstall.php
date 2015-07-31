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

$options    = 'tantan_wordpress_s3';
$postmeta   = 'amazonS3_info';
$crons      = 'as3cf_cron_update_meta_with_region';
$transients = 'as3cf_notices';

$as3cf_uninstall = new WP_AWS_Uninstall( $options, $postmeta, $crons, $transients );
