<?php
/**
 * WP AWS Compatibility Check
 *
 * @package     wp-aws
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check if already defined
if ( ! class_exists( 'WP_AWS_Compatibility_Check' ) ) {

	/**
	 * WP_AWS_Compatibility_Check Class
	 *
	 * This class handles compatibility between an AWS plugin and a required parent plugin
	 *
	 * @since 0.1
	 */
	class WP_AWS_Compatibility_Check {
		/**
		 * @var string The derived key of the plugin from the name, e.g. amazon-s3-and-cloudfront
		 */
		protected $plugin_slug;

		/**
		 * @var string The name of the plugin, e.g. WP Offload S3
		 */
		protected $plugin_name;

		/**
		 * @var string The file path to the plugin's main file
		 */
		protected $plugin_file_path;

		/**
		 * @var null|string The name of the required parent plugin
		 */
		protected $parent_plugin_name;

		/**
		 * @var null|string The key of the required parent plugin, e.g. amazon-web-services
		 */
		protected $parent_plugin_slug;

		/**
		 * @var null|string The required version of the parent plugin
		 */
		protected $parent_plugin_required_version;

		/**
		 * @var null|string Optional name of the required parent plugin's filename if different to {$parent_plugin}.php
		 */
		protected $parent_plugin_filename;

		/**
		 * @var bool Do we deactivate the plugin if the requirements are not met?
		 */
		protected $deactivate_if_not_compatible;

		/**
		 * @var string The URL of the plugin if not on the WordPress.org repo
		 */
		protected $parent_plugin_url;

		/**
		 * @var string The error message to display in the admin notice
		 */
		protected $error_message;

		/**
		 * @var string The CSS class for the notice
		 */
		protected $notice_class = 'error';

		/**
		 * @var bool Used to store if we are installing or updating plugins once per page request
		 */
		protected static $is_installing_or_updating_plugins;

		/**
		 * @param string      $plugin_name
		 * @param  string     $plugin_slug
		 * @param string      $plugin_file_path
		 * @param string|null $parent_plugin_name
		 * @param string|null $parent_plugin_slug
		 * @param string|null $parent_plugin_required_version
		 * @param string|null $parent_plugin_filename
		 * @param bool|false  $deactivate_if_not_compatible
		 * @param string|null $parent_plugin_url
		 */
		function __construct( $plugin_name, $plugin_slug, $plugin_file_path, $parent_plugin_name = null, $parent_plugin_slug = null, $parent_plugin_required_version = null, $parent_plugin_filename = null, $deactivate_if_not_compatible = false, $parent_plugin_url = null ) {
			$this->plugin_name                    = $plugin_name;
			$this->plugin_slug                    = $plugin_slug;
			$this->plugin_file_path               = $plugin_file_path;
			$this->parent_plugin_name             = $parent_plugin_name;
			$this->parent_plugin_slug             = $parent_plugin_slug;
			$this->parent_plugin_required_version = $parent_plugin_required_version;
			$this->parent_plugin_filename         = $parent_plugin_filename;
			$this->deactivate_if_not_compatible   = $deactivate_if_not_compatible;
			$this->parent_plugin_url              = $parent_plugin_url;

			add_action( 'admin_notices', array( $this, 'hook_admin_notices' ) );
			add_action( 'network_admin_notices', array( $this, 'hook_admin_notices' ) );
		}

		/**
		 * Is the plugin compatible?
		 *
		 * @return bool
		 */
		function is_compatible() {
			$compatible = $this->get_error_msg() ? false : true;

			$GLOBALS['aws_meta'][ $this->plugin_slug ]['compatible'] = $compatible;

			return $compatible;
		}

		/**
		 * Is a plugin active
		 *
		 * @param string $plugin_base
		 *
		 * @return bool
		 */
		function is_plugin_active( $plugin_base ) {
			include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

			return is_plugin_active( $plugin_base );
		}

		/**
		 * Get the basename for the plugin
		 *
		 * @return string
		 */
		function get_plugin_basename() {
			return plugin_basename( $this->plugin_file_path );
		}

		/**
		 * Get the name of the parent plugin
		 *
		 * @return string
		 */
		function get_parent_plugin_name() {
			if ( ! is_null( $this->parent_plugin_name ) ) {
				return $this->parent_plugin_name;
			}

			return '';
		}

		/**
		 * Get the class of the parent plugin
		 *
		 * @return string
		 */
		function get_parent_plugin_class() {
			if ( ! is_null( $this->parent_plugin_slug ) ) {
				$class = ucwords( str_replace( '-', ' ', $this->parent_plugin_slug ) );

				return str_replace( ' ', '_', $class );
			}

			return '';
		}

		/**
		 * Get the filename of the main parent plugin file
		 *
		 * @return string
		 */
		function get_parent_plugin_filename() {
			if ( ! is_null( $this->parent_plugin_slug ) ) {
				$filename = $this->parent_plugin_slug;
				if ( ! is_null( $this->parent_plugin_filename ) ) {
					$filename = basename( $this->parent_plugin_filename, '.php' );
				}

				return $filename . '.php';
			}

			return '';
		}

		/**
		 * Get the basename of the parent plugin {slug}/{slug}.php
		 *
		 * @return string
		 */
		function get_parent_plugin_basename() {
			if ( ! is_null( $this->parent_plugin_slug ) ) {
				$file_name = $this->get_parent_plugin_filename();

				return $this->parent_plugin_slug . '/' . $file_name;
			}

			return '';
		}

		/**
		 * Get the URL for the parent plugin. Defaults to a wordpress.org URL.
		 *
		 * @return string
		 */
		function get_parent_plugin_url() {
			if ( ! is_null( $this->parent_plugin_slug ) ) {
				$url = 'http://wordpress.org/extend/plugins/' . $this->parent_plugin_slug . '/';
				if ( ! is_null( $this->parent_plugin_url ) ) {
					$url = $this->parent_plugin_url;
				}

				return $url;
			}

			return '';
		}

		/**
		 * Generate a URL to perform core actions on for a plugin
		 *
		 * @param string      $action Such as activate, deactivate, install, upgrade
		 * @param string|null $basename
		 *
		 * @return string
		 */
		function get_plugin_action_url( $action, $basename = null ) {
			if ( is_null( $basename ) ) {
				$basename = $this->get_plugin_basename();
			}

			$nonce_action = $action . '-plugin_' . $basename;
			$page         = 'plugins';

			if ( in_array( $action, array( 'upgrade', 'install' ) ) ) {
				$page = 'update';
				$action .= '-plugin';
			}

			$url = wp_nonce_url( network_admin_url( $page . '.php?action=' . $action . '&amp;plugin=' . $basename ), $nonce_action );

			return $url;
		}

		/**
		 * Set the error message to be returned for the admin notice
		 *
		 * @param string $message
		 *
		 * @return string
		 */
		function set_error_msg( $message ) {
			// Replace the space between the last two words with &nbsp; to prevent typographic widows
			$message = preg_replace( '/\s([\w]+[.,!\:;\\"-?]{0,1})$/', '&nbsp;\\1', $message, 1 );

			$this->error_message = $message;

			return $this->error_message;
		}

		/**
		 * Check if the parent plugin is active and enabled, ie. not disabled due to
		 * compatibility issues up the chain.
		 *
		 * @return bool
		 */
		function is_parent_plugin_enabled() {
			$class = $this->get_parent_plugin_class();
			if ( ! class_exists( $class ) ) {
				// Class not even loaded
				return false;
			}

			// call_user_func overcomes parse errors on PHP versions < 5.3
			if ( method_exists( $class, 'is_compatible' ) && ! call_user_func( array( $class, 'is_compatible' ) ) ) {
				// The plugin is active but not compatible
				return false;
			}

			return true;
		}

		/**
		 * Check the parent plugin is at a specific version
		 *
		 * @param string $version
		 *
		 * @return bool
		 */
		function is_parent_plugin_at_version( $version ) {
			$current_parent_plugin_version = isset( $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['version'] ) ? $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['version'] : 0;

			return version_compare( $current_parent_plugin_version, $version, '>=' );
		}

		/**
		 * Get the compatibility error message
		 *
		 * @return string|void
		 */
		function get_error_msg() {
			if ( is_null( $this->parent_plugin_slug ) ) {
				return false;
			}

			if ( ! is_null( $this->error_message ) ) {
				return $this->error_message;
			}

			$plugin_basename         = $this->get_plugin_basename();
			$parent_basename         = $this->get_parent_plugin_basename();
			$parent_plugin_link_html = sprintf( '<a style="text-decoration:none;" href="%s">%s</a>', $this->get_parent_plugin_url(), $this->get_parent_plugin_name() );

			$deactivate_url  = $this->get_plugin_action_url( 'deactivate', $plugin_basename );
			$deactivate_link = sprintf( '<a style="text-decoration:none;" href="%s">%s</a>', $deactivate_url, __( 'deactivate', 'amazon-s3-and-cloudfront' ) );
			$hide_notice_msg = '<br><em>' . sprintf( __( 'You can %s the %s plugin to get rid of this notice.', 'amazon-s3-and-cloudfront' ), $deactivate_link, $this->plugin_name ) . '</em>';

			if ( ! $this->is_parent_plugin_enabled() ) {
				$msg = sprintf( __( '%s has been disabled as it requires the %s plugin.', 'amazon-s3-and-cloudfront' ), $this->plugin_name, $parent_plugin_link_html );

				if ( file_exists( WP_PLUGIN_DIR . '/' . $parent_basename ) ) {
					if ( isset( $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['compatible'] ) && ! $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['compatible'] ) {
						$msg = rtrim( $msg, '.' ) . ', ' . __( 'which is currently disabled.', 'amazon-s3-and-cloudfront' );
					} else {
						$msg .= ' ' . __( 'It appears to be installed already.', 'amazon-s3-and-cloudfront' );
						$activate_url = $this->get_plugin_action_url( 'activate', $parent_basename );
						$msg .= ' <a style="font-weight:bold;text-decoration:none;" href="' . $activate_url . '">' . _x( 'Activate it now.', 'Activate plugin', 'amazon-s3-and-cloudfront' ) . '</a>';
					}
				} else {
					$install_url = 'https://deliciousbrains.com/my-account/';
					if ( is_null( $this->parent_plugin_url ) ) {
						$install_url = $this->get_plugin_action_url( 'install', $this->parent_plugin_slug );
					}
					$msg .= ' ' . sprintf( __( '<a href="%s">Install</a> and activate it.', 'amazon-s3-and-cloudfront' ), $install_url );
				}

				$msg .= $hide_notice_msg;

				return $this->set_error_msg( $msg );
			}

			$current_parent_plugin_version = isset( $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['version'] ) ? $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['version'] : 0;

			if ( ! version_compare( $current_parent_plugin_version, $this->parent_plugin_required_version, '>=' ) ) {
				$msg = sprintf( __( '%s has been disabled as it requires version %s or later of the %s plugin.', 'amazon-s3-and-cloudfront' ), $this->plugin_name, $this->parent_plugin_required_version, $parent_plugin_link_html );

				if ( $current_parent_plugin_version ) {
					$msg .= ' ' . sprintf( __( 'You currently have version %s installed.', 'amazon-s3-and-cloudfront' ), $current_parent_plugin_version );
				}

				global $as3cfpro;
				if ( ! empty( $as3cfpro ) && $as3cfpro->get_plugin_slug( true ) === $this->parent_plugin_slug ) {
					// Don't show update link for addons of a licensed plugin where the license is invalid
					if ( ! $as3cfpro->is_valid_licence() ) {
						$msg .= ' ' . sprintf( __( 'A valid license for %s is required to update.', 'amazon-s3-and-cloudfront' ), $this->get_parent_plugin_name() );
						$msg .= $hide_notice_msg;

						return $this->set_error_msg( $msg );
					}
				}

				$update_url = $this->get_plugin_action_url( 'upgrade', $parent_basename );
				$msg .= ' <a style="font-weight:bold;text-decoration:none;white-space:nowrap;" href="' . $update_url . '">' . __( 'Update to the latest version', 'amazon-s3-and-cloudfront' ) . '</a>';

				$msg .= $hide_notice_msg;

				return $this->set_error_msg( $msg );
			}

			if ( ! isset( $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['supported_addon_versions'] ) ) {
				return false;
			}

			if ( ! isset( $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['supported_addon_versions'][ $this->plugin_slug ] ) ) {
				$msg = sprintf( __( '%1$s has been disabled because it is not a supported addon of the %2$s plugin.', 'amazon-s3-and-cloudfront' ), $this->plugin_name, $this->get_parent_plugin_name() );

				return $this->set_error_msg( $msg );
			}

			$this_plugin_version_required = $GLOBALS['aws_meta'][ $this->parent_plugin_slug ]['supported_addon_versions'][ $this->plugin_slug ];
			$this_plugin_version          = $GLOBALS['aws_meta'][ $this->plugin_slug ]['version'];

			if ( ! version_compare( $this_plugin_version, $this_plugin_version_required, '>=' ) ) {
				$msg = sprintf( __( '%1$s has been disabled because it will not work with the version of the %2$s plugin installed. %1$s %3$s or later is required.', 'amazon-s3-and-cloudfront' ), $this->plugin_name, $this->get_parent_plugin_name(), $this_plugin_version_required );

				$update_url  = $this->get_plugin_action_url( 'upgrade', $plugin_basename );
				$upgrade_msg = ' <a style="font-weight:bold;text-decoration:none;white-space:nowrap;" href="' . $update_url . '">' . sprintf( __( 'Update %s to the latest version', 'amazon-s3-and-cloudfront' ), $this->plugin_name ) . '</a>';

				global $as3cfpro;
				if ( ! empty( $as3cfpro ) && $as3cfpro->get_plugin_slug( true ) === $this->parent_plugin_slug ) {
					// Don't show update link for addons of a licensed plugin where the license is invalid
					if ( ! $as3cfpro->is_valid_licence() ) {
						$upgrade_msg = ' ' . sprintf( __( 'A valid license for %s is required to update.', 'amazon-s3-and-cloudfront' ), $this->get_parent_plugin_name() );
					}
				}

				$msg .= $upgrade_msg;
				$msg .= $hide_notice_msg;

				return $this->set_error_msg( $msg );
			}

			return false;
		}

		/**
		 * Check plugin capabilities for a user
		 *
		 * @return bool
		 */
		function check_capabilities() {
			if ( is_multisite() ) {
				if ( ! current_user_can( 'manage_network_plugins' ) ) {
					return false; // Don't allow if the user can't manage network plugins
				}
			} else {
				// Don't allow if user doesn't have plugin management privileges
				$caps = array( 'activate_plugins', 'update_plugins', 'install_plugins' );
				foreach ( $caps as $cap ) {
					if ( ! current_user_can( $cap ) ) {
						return false;
					}
				}
			}

			return true;
		}

		/**
		 * Display compatibility notices to users who can manage plugins
		 */
		function hook_admin_notices() {
			if ( ! $this->check_capabilities() ){
				return;
			}

			if ( self::is_installing_or_updating_plugins() ) {
				// Don't show notice when installing or updating plugins
				return;
			}

			$this->get_admin_notice();
		}

		/**
		 * Get the admin notice to be displayed
		 */
		function get_admin_notice() {
			$error_msg = $this->get_error_msg();

			if ( false === $error_msg || '' === $error_msg ) {
				return;
			}

			if ( $this->deactivate_if_not_compatible ) {
				$deactivated_msg = sprintf( __( 'The %s plugin has been deactivated.', 'amazon-s3-and-cloudfront' ), $this->plugin_name );

				$error_msg = $deactivated_msg . ' ' . $error_msg;
				$this->render_notice( $error_msg );

				require_once ABSPATH . 'wp-admin/includes/plugin.php';
				deactivate_plugins( $this->plugin_file_path );
			} else {
				$this->render_notice( $error_msg );
			}
		}

		/**
		 * Render the notice HTML
		 *
		 * @param string $message
		 */
		function render_notice( $message ) {
			printf( '<div class="' . $this->notice_class . ' aws-compatibility-notice"><p>%s</p></div>', $message );
		}

		/**
		 * Is the current process an install or upgrade of plugin(s)
		 *
		 * @return bool
		 */
		public static function is_installing_or_updating_plugins() {
			if ( ! is_null( self::$is_installing_or_updating_plugins ) ) {
				return self::$is_installing_or_updating_plugins;
			}

			self::$is_installing_or_updating_plugins = false;

			global $pagenow;

			if ( 'update.php' === $pagenow && isset( $_GET['action'] ) && 'install-plugin' === $_GET['action'] ) {
				// We are installing a plugin
				self::$is_installing_or_updating_plugins = true;
			}

			if ( 'plugins.php' === $pagenow && isset( $_POST['action'] ) ) {
				$action = $_POST['action'];
				if ( isset( $_POST['action2'] ) && '-1' !== $_POST['action2'] ) {
					$action = $_POST['action2'];
				}

				if ( 'update-selected' === $action ) {
					// We are updating plugins from the plugin page
					self::$is_installing_or_updating_plugins = true;
				}
			}

			if ( 'update-core.php' === $pagenow && isset( $_GET['action'] ) && 'do-plugin-upgrade' === $_GET['action'] ) {
				// We are updating plugins from the updates page
				self::$is_installing_or_updating_plugins = true;
			}

			return self::$is_installing_or_updating_plugins;
		}
	}
}