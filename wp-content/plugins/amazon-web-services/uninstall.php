<?php
/**
 * Uninstall Amazon Web Services
 *
 * @package     amazon-web-services
 * @subpackage  uninstall
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.2.3
 */

// Exit if accessed directly
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require dirname( __FILE__ ) . '/classes/wp-aws-uninstall.php';

$as3cf_uninstall = new WP_AWS_Uninstall( 'aws_settings', array(), array(), array() );
