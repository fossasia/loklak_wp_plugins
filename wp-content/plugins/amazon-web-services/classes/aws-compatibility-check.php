<?php
require_once dirname( __FILE__ ) . '/wp-aws-compatibility-check.php';

class AWS_Compatibility_Check extends WP_AWS_Compatibility_Check {

	function __construct( $plugin_file_path ) {
		parent::__construct( 'Amazon Web Services', 'amazon-web-services', $plugin_file_path, null, null, null, null, true );
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
	function get_error_msg() {
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
}