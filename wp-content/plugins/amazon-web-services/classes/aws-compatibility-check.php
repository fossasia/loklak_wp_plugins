<?php

class AWS_Compatibility_Check {

	private $plugin_file_path;

	function __construct( $plugin_file_path ) {
		$this->plugin_file_path = $plugin_file_path;

		add_action( 'admin_notices', array( $this, 'hook_admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'hook_admin_notices' ) );
	}

	/**
	 * Check the server is compatible with the AWS SDK
	 *
	 * @return bool
	 */
	function is_compatible() {
		return $this->get_sdk_requirements_errors() ? false : true;
	}

	/**
	 * Return an array of issues with the server's compatibility with the AWS SDK
	 *
	 * @return array
	 */
	function get_sdk_requirements_errors() {
		static $errors;

		if ( ! is_null( $errors ) ) {
			return $errors;
		}

		$errors = array();

		if ( version_compare( PHP_VERSION, '5.3.3', '<' ) ) {
			$errors[] = __( 'a PHP version less than 5.3.3', 'amazon-web-services' );
		}

		if ( ! function_exists( 'curl_version' ) ) {
			$errors[] = __( 'no PHP cURL library activated', 'amazon-web-services' );

			return $errors;
		}

		if ( ! ( $curl = curl_version() ) || empty( $curl['version'] ) || empty( $curl['features'] ) || version_compare( $curl['version'], '7.16.2', '<' ) ) {
			$errors[] = __( 'a cURL version less than 7.16.2', 'amazon-web-services' );
		}

		if ( ! empty( $curl['features'] ) ) {
			$curl_errors = array();

			if ( ! CURL_VERSION_SSL ) {
				$curl_errors[] = 'OpenSSL';
			}

			if ( ! CURL_VERSION_LIBZ ) {
				$curl_errors[] = 'zlib';
			}

			if ( $curl_errors ) {
				$errors[] = __( 'cURL compiled without', 'amazon-web-services' ) . ' ' . implode( ' or ', $curl_errors ); // xss ok
			}
		}

		return $errors;
	}

	/**
	 * Prepare an error message with compatibility issues
	 *
	 * @return string
	 */
	function get_sdk_requirements_error_msg() {
		$errors = $this->get_sdk_requirements_errors();

		if ( ! $errors ) {
			return '';
		}

		$msg = __( 'The official Amazon&nbsp;Web&nbsp;Services SDK requires PHP 5.3.3+ and cURL 7.16.2+ compiled with OpenSSL and zlib. Your server currently has', 'amazon-web-services' );

		if ( count( $errors ) > 1 ) {
			$last_one = ' and ' . array_pop( $errors );
		} else {
			$last_one = '';
		}

		$msg .= ' ' . implode( ', ', $errors ) . $last_one . '.';

		return $msg;
	}

	/**
	 * Display the compatibility error message for users
	 * Deactivate the plugin if there are errors
	 */
	function hook_admin_notices() {
		if ( is_multisite() ) {
			if ( ! current_user_can( 'manage_network_plugins' ) ) {
				return; // Don't show notices if the user can't manage network plugins
			}
		} else {
			// Don't show notices if user doesn't have plugin management privileges
			$caps = array( 'activate_plugins', 'update_plugins', 'install_plugins' );
			foreach ( $caps as $cap ) {
				if ( ! current_user_can( $cap ) ) {
					return;
				}
			}
		}

		$error_msg = $this->get_sdk_requirements_error_msg();

		if ( ! $error_msg ) {
			return;
		}

		$deactivated_msg = __( 'The Amazon&nbsp;Web&nbsp;Services plugin has been deactivated.', 'amazon-web-services' );
		printf( '<div class="error"><p>%s %s</p></div>', $deactivated_msg, $error_msg );

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		deactivate_plugins( $this->plugin_file_path );
	}
}