<?php
/**
 * Bootstrap file for mu-plugins directory
 *
 * To load WP Multibyte Patch as a must-use plugin, place this file directly under the mu-plugins directory like:
 *
 * /wp-content/mu-plugins/wpmp-load.php
 *
 * @package WP_Multibyte_Patch
 */

/**
 */
if ( !defined( 'WP_INSTALLING' ) && defined( 'WPMU_PLUGIN_DIR' ) && defined( 'WPMU_PLUGIN_URL' ) )
	require_once WPMU_PLUGIN_DIR . '/wp-multibyte-patch/wp-multibyte-patch.php';
