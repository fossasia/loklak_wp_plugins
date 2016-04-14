<?php
/**
 * Plugin Utilities
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Utils
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'AS3CF_Utils' ) ) {

	/**
	 * AS3CF_Utils Class
	 *
	 * This class contains utility functions that need to be available
	 * across the Pro plugin codebase
	 *
	 */
	class AS3CF_Utils {

		/**
		 * Checks if another version of WP Offload S3 (Lite) is active and deactivates it.
		 * To be hooked on `activated_plugin` so other plugin is deactivated when current plugin is activated.
		 *
		 * @param string $plugin
		 *
		 * @return bool
		 */
		public static function deactivate_other_instances( $plugin ) {
			if ( ! in_array( basename( $plugin ), array( 'amazon-s3-and-cloudfront-pro.php', 'wordpress-s3.php' ) ) ) {
				return false;
			}

			$plugin_to_deactivate             = 'wordpress-s3.php';
			$deactivated_notice_id            = '1';
			$activated_plugin_min_version     = '1.1';
			$plugin_to_deactivate_min_version = '1.0';
			if ( basename( $plugin ) === $plugin_to_deactivate ) {
				$plugin_to_deactivate             = 'amazon-s3-and-cloudfront-pro.php';
				$deactivated_notice_id            = '2';
				$activated_plugin_min_version     = '1.0';
				$plugin_to_deactivate_min_version = '1.1';
			}

			$version = self::get_plugin_version_from_basename( $plugin );

			if ( version_compare( $version, $activated_plugin_min_version, '<' ) ) {
				return false;
			}

			if ( is_multisite() ) {
				$active_plugins = (array) get_site_option( 'active_sitewide_plugins', array() );
				$active_plugins = array_keys( $active_plugins );
			} else {
				$active_plugins = (array) get_option( 'active_plugins', array() );
			}

			foreach ( $active_plugins as $basename ) {
				if ( false !== strpos( $basename, $plugin_to_deactivate ) ) {
					$version = self::get_plugin_version_from_basename( $basename );

					if ( version_compare( $version, $plugin_to_deactivate_min_version, '<' ) ) {
						return false;
					}

					set_transient( 'as3cf_deactivated_notice_id', $deactivated_notice_id, HOUR_IN_SECONDS );
					deactivate_plugins( $basename );

					return true;
				}
			}

			return false;
		}

		/**
		 * Get plugin data from basename
		 *
		 * @param string $basename
		 *
		 * @return string
		 */
		public static function get_plugin_version_from_basename( $basename ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';

			$plugin_path = WP_PLUGIN_DIR . '/' . $basename;
			$plugin_data = get_plugin_data( $plugin_path );

			return $plugin_data['Version'];
		}
	}
}