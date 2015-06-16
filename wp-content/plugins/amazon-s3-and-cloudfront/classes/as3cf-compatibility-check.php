<?php
class AS3CF_Compatibility_Check {

	private $plugin_file_path, $aws_plugin_version_required;

	function __construct( $plugin_file_path, $aws_plugin_version_required ) {
		$this->plugin_file_path = $plugin_file_path;
		$this->aws_plugin_version_required = $aws_plugin_version_required;

		add_action( 'admin_notices', array( $this, 'hook_admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'hook_admin_notices' ) );
	}

	function is_compatible() {
		return $this->get_error_msg() ? false : true;
	}

	function get_error_msg() {
		static $msg;

		if ( ! is_null( $msg ) ) {
			return $msg;
		}

		$hide_notice_msg = '<br>' . __( 'You can deactivate the Amazon S3 and CloudFront plugin to get rid of this notice.', 'as3cf' );

		if ( ! class_exists( 'Amazon_Web_Services' ) ) {
			$msg = sprintf( __( 'Amazon S3 and CloudFront has been disabled as it requires the <a style="text-decoration:none;" href="%s">Amazon&nbsp;Web&nbsp;Services</a> plugin.', 'as3cf' ), 'http://wordpress.org/extend/plugins/amazon-web-services/' );

			if ( file_exists( WP_PLUGIN_DIR . '/amazon-web-services/amazon-web-services.php' ) ) {
				$msg .= ' ' . __( 'It appears to be installed already.', 'as3cf' );
				$activate_url = wp_nonce_url( network_admin_url( 'plugins.php?action=activate&amp;plugin=amazon-web-services/amazon-web-services.php' ), 'activate-plugin_amazon-web-services/amazon-web-services.php' );
				$msg .= ' <a style="font-weight:bold;text-decoration:none;" href="' . $activate_url . '">' . _x( 'Activate it now', 'Activate plugin', 'as3cf' ) . '</a>';
			}
			else {
				$install_url = wp_nonce_url( network_admin_url( 'update.php?action=install-plugin&plugin=amazon-web-services' ), 'install-plugin_amazon-web-services' );
				$msg .= ' ' . sprintf( __( '<a href="%s">Install it</a> and activate.', 'as3cf' ), $install_url );
			}

			$msg .= $hide_notice_msg;

			return $msg;
		}

		$aws_plugin_version = isset( $GLOBALS['aws_meta']['amazon-web-services']['version'] ) ? $GLOBALS['aws_meta']['amazon-web-services']['version'] : 0;

		if ( ! version_compare( $aws_plugin_version, $this->aws_plugin_version_required, '>=' ) ) {
			$msg = sprintf( __( 'Amazon S3 and CloudFront has been disabled as it requires version %s or later of the <a style="text-decoration:none;" href="%s">Amazon&nbsp;Web&nbsp;Services</a> plugin.', 'as3cf' ), $this->aws_plugin_version_required, 'http://wordpress.org/extend/plugins/amazon-web-services/' );

			if ( $aws_plugin_version ) {
				$msg .= ' ' . sprintf( __( 'You currently have version %s installed.', 'as3cf' ), $aws_plugin_version );
			}

			$update_url = wp_nonce_url( network_admin_url( 'update.php?action=upgrade-plugin&plugin=amazon-web-services/amazon-web-services.php' ), 'upgrade-plugin_amazon-web-services/amazon-web-services.php' );
			$msg .= ' <a style="font-weight:bold;text-decoration:none;white-space:nowrap;" href="' . $update_url . '">' . __( 'Update to the latest version', 'as3cf' ) . '</a>';

			$msg .= $hide_notice_msg;

			return $msg;
		}

		$as3cf_plugin_version_required = $GLOBALS['aws_meta']['amazon-web-services']['supported_addon_versions']['amazon-s3-and-cloudfront'];
		$as3cf_plugin_version = $GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'];

		if ( ! version_compare( $as3cf_plugin_version, $as3cf_plugin_version_required, '>=' ) ) {
			$msg = sprintf( __( 'Amazon S3 and CloudFront has been disabled because it will not work with the version of the Amazon&nbsp;Web&nbsp;Services plugin installed. Amazon&nbsp;S3&nbsp;and&nbsp;CloudFront %s or later is required.', 'as3cf' ), $as3cf_plugin_version_required );

			$plugin_basename = plugin_basename( __FILE__ );
			$update_url = wp_nonce_url( network_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $plugin_basename ), 'upgrade-plugin_' . $plugin_basename );
			$msg .= ' <a style="font-weight:bold;text-decoration:none;white-space:nowrap;" href="' . $update_url . '">' . __( 'Update Amazon S3 and CloudFront to the latest version', 'as3cf' ) . '</a>';

			$msg .= $hide_notice_msg;

			return $msg;
		}

		$msg = false;
		return $msg;
	}

	function hook_admin_notices() {
		if ( is_multisite() ) {
			if ( ! current_user_can( 'manage_network_plugins' ) ) {
				return; // Don't show notices if the user can't manage network plugins
			}
		}
		else {
			// Don't show notices if user doesn't have plugin management privileges
			$caps = array( 'activate_plugins', 'update_plugins', 'install_plugins' );
			foreach ( $caps as $cap ) {
				if ( ! current_user_can( $cap ) ) {
					return;
				}
			}
		}

		$error_msg = $this->get_error_msg();

		if ( ! $error_msg ) {
			return;
		}

		printf( '<div class="error"><p>%s</p></div>', $error_msg );
	}
}