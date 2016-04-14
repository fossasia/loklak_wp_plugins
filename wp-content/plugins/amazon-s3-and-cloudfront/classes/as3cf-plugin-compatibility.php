<?php
/**
 * Plugin Compatibility
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Plugin-Compatibility
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.8.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Plugin_Compatibility Class
 *
 * This class handles compatibility code for third party plugins used in conjunction with AS3CF
 *
 * @since 0.8.3
 */
class AS3CF_Plugin_Compatibility {

	/**
	 * @var Amazon_S3_And_CloudFront
	 */
	protected $as3cf;

	/**
	 * @var array
	 */
	protected static $stream_wrappers = array();

	/**
	 * @var array
	 */
	protected $compatibility_addons;

	/**
	 * @param Amazon_S3_And_CloudFront $as3cf
	 */
	function __construct( $as3cf ) {
		$this->as3cf = $as3cf;

		$this->compatibility_init();
	}

	/**
	 * Register the compatibility hooks for the plugin.
	 */
	function compatibility_init() {
		/*
		 * WP_Customize_Control
		 * /wp-includes/class-wp-customize_control.php
		 */
		add_filter( 'attachment_url_to_postid', array( $this, 'customizer_background_image' ), 10, 2 );

		/*
		 * Responsive Images WP 4.4
		 */
		add_filter( 'wp_calculate_image_srcset', array( $this, 'wp_calculate_image_srcset' ), 10, 5 );

		global $wp_version;
		if ( 0 === version_compare( $wp_version, '4.4' ) ) {
			// Hot fix for 4.4
			add_filter( 'the_content', array( $this, 'wp_make_content_images_responsive' ), 11 );
		}

		/*
		 * Responsive Images WP 4.4.1+
		 */
		add_filter( 'wp_calculate_image_srcset_meta', array( $this, 'wp_calculate_image_srcset_meta' ), 10, 4 );

		if ( $this->as3cf->is_plugin_setup() ) {
			$this->compatibility_init_if_setup();
		}
	}

	/**
	 * Register the compatibility hooks as long as the plugin is setup.
	 */
	function compatibility_init_if_setup() {
		// Add notices about compatibility addons to install
		add_action( 'admin_init', array( $this, 'maybe_render_compatibility_addons_notice' ) );

		// Turn on stream wrapper S3 file
		add_filter( 'as3cf_get_attached_file', array( $this, 'get_stream_wrapper_file' ), 20, 4 );

		/*
		 * Legacy filter
		 * 'as3cf_get_attached_file_copy_back_to_local'
		 */
		add_filter( 'as3cf_get_attached_file', array( $this, 'legacy_copy_back_to_local'), 10, 4 );

		/*
		 * WP_Image_Editor
		 * /wp-includes/class-wp-image-editor.php
		 */
		add_action( 'as3cf_upload_attachment_pre_remove', array( $this, 'image_editor_remove_files' ), 10, 4 );
		add_filter( 'as3cf_get_attached_file', array( $this, 'image_editor_download_file' ), 10, 4 );
		add_filter( 'as3cf_upload_attachment_local_files_to_remove', array( $this, 'image_editor_remove_original_image' ), 10, 3 );
		add_filter( 'as3cf_get_attached_file', array( $this, 'customizer_crop_download_file' ), 10, 4 );
		add_filter( 'as3cf_upload_attachment_local_files_to_remove', array( $this, 'customizer_crop_remove_original_image' ), 10, 3 );

		/*
		 * Regenerate Thumbnails
		 * https://wordpress.org/plugins/regenerate-thumbnails/
		 */
		add_filter( 'as3cf_get_attached_file', array( $this, 'regenerate_thumbnails_download_file' ), 10, 4 );
	}

	/**
	 * Get the addons for the Pro upgrade
	 *
	 * @return array
	 */
	public function get_pro_addons() {
		global $amazon_web_services;

		$all_addons = $amazon_web_services->get_addons( true );
		if ( ! isset( $all_addons['amazon-s3-and-cloudfront-pro']['addons'] ) ) {
			return array();
		}

		$addons = $all_addons['amazon-s3-and-cloudfront-pro']['addons'];

		return $addons;
	}

	/**
	 * Get compatibility addons that are required to be installed
	 *
	 * @return array
	 */
	public function get_compatibility_addons_to_install() {
		if ( isset( $this->compatibility_addons ) ) {
			return $this->compatibility_addons;
		}

		$addons            = $this->get_pro_addons();
		$addons_to_install = array();

		if ( empty ( $addons ) ) {
			return $addons_to_install;
		}

		foreach( $addons as $addon_slug => $addon ) {
			if ( file_exists( WP_PLUGIN_DIR . '/' . $addon_slug . '/' . $addon_slug . '.php' ) ) {
				// Addon already installed, ignore.
				continue;
			}

			if ( ! isset( $addon['parent_plugin_basename'] ) || '' === $addon['parent_plugin_basename'] ) {
				// Addon doesn't have a parent plugin, ignore.
				continue;
			}

			if ( ! file_exists( WP_PLUGIN_DIR . '/' . $addon['parent_plugin_basename'] ) || ! is_plugin_active( $addon['parent_plugin_basename'] ) ) {
				// Parent plugin not installed or not activated, ignore.
				continue;
			}

			$addons_to_install[ $addon_slug ] = array(
				'title' => $addon['title'],
				'url'   => $addon['url'],
			);
		}

		$this->compatibility_addons = $addons_to_install;

		return $addons_to_install;
	}

	/**
	 * Maybe show a notice about installing addons when the site is using the
	 * plugins they add compatibility for.
	 */
	public function maybe_render_compatibility_addons_notice() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		global $as3cf_compat_check;
		if ( ! $as3cf_compat_check->check_capabilities() ){
			// User can't install plugins anyway, bail.
			return;
		}

		if ( ! $this->should_show_compatibility_notice() ) {
			// No addons to install, or addons haven't changed
			return;
		}

		$notice_id         = 'as3cf-compat-addons';
		$addons_to_install = $this->get_compatibility_addons_to_install();

		// Remove previous notice to refresh addon list
		$this->remove_compatibility_notice();

		$title       = __( 'WP Offload S3 Compatibility Addons', 'amazon-s3-and-cloudfront' );
		$compat_url  = 'https://deliciousbrains.com/wp-offload-s3/doc/compatibility-with-other-plugins/';
		$compat_link = sprintf( '<a href="%s">%s</a>', $compat_url, __( 'compatibility addons', 'amazon-s3-and-cloudfront' ) );
		$message     = sprintf( __( "To get WP Offload S3 to work with certain 3rd party plugins, you might need to install and activate some of our %s. We've detected the following addons might need to be installed. Please click the links for more information about each addon to determine if you need it or not.", 'amazon-s3-and-cloudfront' ), $compat_link );

		$notice_addons_text = $this->render_addon_list( $addons_to_install );
		$support_email      = 'nom@deliciousbrains.com';
		$support_link       = sprintf( '<a href="mailto:%1$s">%1$s</a>', $support_email );

		$notice_addons_text .= '<p>' . sprintf( __( "You will need to purchase a license to get access to these addons. If you're having trouble determining whether or not you need the addons, send an email to %s.", 'amazon-s3-and-cloudfront' ), $support_link ). '</p>';
		$notice_addons_text .= sprintf( '<p><a href="%s" class="button button-large">%s</a></p>', 'https://deliciousbrains.com/wp-offload-s3/pricing/', __( 'View Licenses', 'amazon-s3-and-cloudfront' ) );

		$notice_addons_text = apply_filters( 'wpos3_compat_addons_notice', $notice_addons_text, $addons_to_install );

		if ( false === $notice_addons_text ) {
			// Allow the notice to be aborted.
			return;
		}

		$notice = '<p><strong>' . $title . '</strong> &mdash; ' . $message . '</p>' . $notice_addons_text;

		$notice_args = array(
			'type'              => 'notice-info',
			'custom_id'         => $notice_id,
			'only_show_to_user' => false,
			'flash'             => false,
			'auto_p'            => false,
		);

		$notice_args = apply_filters( 'wpos3_compat_addons_notice_args', $notice_args, $addons_to_install );

		update_site_option( 'as3cf_compat_addons_to_install', $addons_to_install );

		$this->as3cf->notices->add_notice( $notice, $notice_args );
	}

	/**
	 * Should show compatibility notice
	 *
	 * @return bool
	 */
	protected function should_show_compatibility_notice() {
		$addons          = $this->get_compatibility_addons_to_install();
		$previous_addons = get_site_option( 'as3cf_compat_addons_to_install', array() );

		if ( empty( $addons ) && empty( $previous_addons ) ) {
			// No addons to install
			return false;
		}

		if ( empty( $addons ) && ! empty( $previous_addons ) ) {
			// No addons to install but previous exist
			$this->remove_compatibility_notice( true );

			return false;
		}

		if ( $previous_addons === $addons ) {
			// Addons have not changed
			return false;
		}

		return true;
	}

	/**
	 * Remove compatibility notice
	 *
	 * @param bool $delete_option
	 */
	protected function remove_compatibility_notice( $delete_option = false ) {
		$notice_id = 'as3cf-compat-addons';

		if ( $this->as3cf->notices->find_notice_by_id( $notice_id ) ) {
			$this->as3cf->notices->undismiss_notice_for_all( $notice_id );
			$this->as3cf->notices->remove_notice_by_id( $notice_id );
		}

		if ( $delete_option ) {
			delete_site_option( 'as3cf_compat_addons_to_install' );
		}
	}

	/**
	 * Render list of addons for a notice
	 *
	 * @param array $addons
	 *
	 * @return string
	 */
	protected function render_addon_list( $addons ) {
		if ( ! is_array( $addons ) || empty( $addons ) ) {
			return '';
		}

		sort( $addons );

		$html = '<ul style="list-style-type: disc; padding: 0 0 0 30px; margin: 5px 0;">';
		foreach ( $addons as $addon ) {
			$html .= '<li style="margin: 0;">';
			$html .= '<a href="' . $addon['url'] . '">' . $addon['title'] . '</a>';
			$html .= '</li>';
		}
		$html .= '</ul>';

		return $html;
	}

	/**
	 * Allow any process to trigger the copy back to local with
	 * the filter 'as3cf_get_attached_file_copy_back_to_local'
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $s3_object
	 *
	 * @return string
	 */
	function legacy_copy_back_to_local( $url, $file, $attachment_id, $s3_object ) {
		$copy_back_to_local = apply_filters( 'as3cf_get_attached_file_copy_back_to_local', false, $file, $attachment_id, $s3_object );
		if ( false === $copy_back_to_local ) {
			// Not copying back file
			return $url;
		}

		if ( ( $file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
			// Return the file if successfully downloaded from S3
			return $file;
		};

		// Return S3 URL as a fallback
		return $url;
	}

	/**
	 * Is this an AJAX process?
	 *
	 * @return bool
	 */
	function is_ajax() {
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return true;
		}

		return false;
	}

	/**
	 * Check the current request is a specific one based on action and
	 * optional context
	 *
	 * @param string      $action_key
	 * @param bool        $ajax
	 * @param null|string $context_key
	 *
	 * @return bool
	 */
	function maybe_process_on_action( $action_key, $ajax, $context_key = null ) {
		if ( $ajax !== $this->is_ajax() ) {
			return false;
		}

		$var_type = 'GET';

		if ( isset( $_GET['action'] ) ) {
			$action = $this->as3cf->filter_input( 'action' );
		} else if ( isset( $_POST['action'] ) ) {
			$var_type = 'POST';
			$action   = $this->as3cf->filter_input( 'action', INPUT_POST );
		} else {
			return false;
		}

		$context_check = true;
		if ( ! is_null( $context_key ) ) {
			$global        = constant( 'INPUT_' . $var_type );
			$context       = $this->as3cf->filter_input( 'context', $global );
			$context_check = ( $context_key === $context );
		}

		return ( $action_key === sanitize_key( $action ) && $context_check );
	}

	/**
	 * Generic method for copying back an S3 file to the server on a specific AJAX action
	 *
	 * @param string $action_key Action that must be in process
	 * @param bool   $ajax       Must the process be an AJAX one?
	 * @param string $url        S3 URL
	 * @param string $file       Local file path of image
	 * @param array  $s3_object  S3 meta data
	 *
	 * @return string
	 */
	function copy_image_to_server_on_action( $action_key, $ajax, $url, $file, $s3_object ) {
		if ( false === $this->maybe_process_on_action( $action_key, $ajax ) ) {
			return $url;
		}

		if ( ( $file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
			// Return the file if successfully downloaded from S3
			return $file;
		};

		return $url;
	}

	/**
	 * Get the file path of the original image file before an update
	 *
	 * @param int    $post_id
	 * @param string $file_path
	 *
	 * @return bool|string
	 */
	function get_original_image_file( $post_id, $file_path ) {
		// remove original main image after edit
		$meta          = get_post_meta( $post_id, '_wp_attachment_metadata', true );
		$original_file = trailingslashit( dirname( $file_path ) ) . basename( $meta['file'] );
		if ( file_exists( $original_file ) ) {
			return $original_file;
		}

		return false;
	}

	/**
	 * Allow the WordPress Image Editor to remove edited version of images
	 * if the original image is being restored and 'IMAGE_EDIT_OVERWRITE' is set
	 *
	 * @param int    $post_id
	 * @param array  $s3object
	 * @param string $prefix
	 * @param array  $args
	 */
	function image_editor_remove_files( $post_id, $s3object, $prefix, $args ) {
		if ( ! isset( $_POST['do'] ) || 'restore' !== $_POST['do'] ) {
			return;
		}

		if ( ! defined( 'IMAGE_EDIT_OVERWRITE' ) || ! IMAGE_EDIT_OVERWRITE ) {
			return;
		}

		$this->as3cf->remove_attachment_files_from_s3( $post_id, $s3object, false );
	}

	/**
	 * Allow the WordPress Image Editor to edit files that have been copied to S3
	 * but removed from the local server, by copying them back temporarily
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $s3_object
	 *
	 * @return string
	 */
	function image_editor_download_file( $url, $file, $attachment_id, $s3_object ) {
		if ( ! $this->is_ajax() ) {
			return $url;
		}

		// When the image-editor restores the original it requests the edited image,
		// but we actually need to copy back the original image at this point
		// for the restore to be successful and edited images to be deleted from S3
		// via image_editor_remove_files()
		if ( isset( $_POST['do'] ) && 'restore' == $_POST['do'] ) {
			$backup_sizes      = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );
			$original_filename = $backup_sizes['full-orig']['file'];

			$orig_s3        = $s3_object;
			$orig_s3['key'] = dirname( $s3_object['key'] ) . '/' . $original_filename;
			$orig_file      = dirname( $file ) . '/' . $original_filename;

			// Copy the original file back to the server for the restore process
			$this->copy_s3_file_to_server( $orig_s3, $orig_file );

			// Copy the edited file back to the server as well, it will be cleaned up later
			if ( ( $s3_file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
				// Return the file if successfully downloaded from S3
				return $s3_file;
			};
		}

		// must be the image-editor process
		if ( isset( $_POST['action'] ) && 'image-editor' == sanitize_key( $_POST['action'] ) ) { // input var okay
			$callers = debug_backtrace();
			foreach ( $callers as $caller ) {
				if ( isset( $caller['function'] ) && '_load_image_to_edit_path' == $caller['function'] ) {
					// check this has been called by '_load_image_to_edit_path' so as only to copy back once
					if ( ( $s3_file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
						// Return the file if successfully downloaded from S3
						return $s3_file;
					};
				}
			}
		}

		return $url;
	}

	/**
	 * Allow the WordPress Image Editor to remove the main image file after it has been copied
	 * back from S3 after it has done the edit.
	 *
	 * @param array  $files
	 * @param int    $post_id
	 * @param string $file_path
	 *
	 * @return array
	 */
	function image_editor_remove_original_image( $files, $post_id, $file_path ) {
		if ( ! $this->is_ajax() ) {
			return $files;
		}

		if ( isset( $_POST['action'] ) && 'image-editor' === sanitize_key( $_POST['action'] ) ) { // input var okay
			// remove original main image after edit
			if ( ( $original_file = $this->get_original_image_file( $post_id, $file_path ) ) ) {
				$files[] = $original_file;
			}
		}

		return $files;
	}

	/**
	 * Generic check for Customizer crop actions
	 *
	 * @return bool
	 */
	protected function is_customizer_crop_action() {
		$header_crop   = $this->maybe_process_on_action( 'custom-header-crop', true );
		$identity_crop = $this->maybe_process_on_action( 'crop-image', true, 'site-icon' );

		if ( ! $header_crop && ! $identity_crop ) {
			// Not doing a Customizer action
			return false;
		}

		return true;
	}

	/**
	 * Allow the WordPress Customizer to crop images that have been copied to S3
	 * but removed from the local server, by copying them back temporarily
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $s3_object
	 *
	 * @return string
	 */
	function customizer_crop_download_file( $url, $file, $attachment_id, $s3_object ) {
		if ( false === $this->is_customizer_crop_action() ) {
			return $url;
		}

		if ( ( $file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
			// Return the file if successfully downloaded from S3
			return $file;
		};

		return $url;
	}

	/**
	 * Allow the WordPress Image Editor to remove the main image file after it has been copied
	 * back from S3 after it has done the edit.
	 *
	 * @param array  $files
	 * @param int    $post_id
	 * @param string $file_path
	 *
	 * @return array
	 */
	function customizer_crop_remove_original_image( $files, $post_id, $file_path ) {
		if ( false === $this->is_customizer_crop_action() ) {
			return $files;
		}

		// remove original main image after edit
		if ( ( $original_file = $this->get_original_image_file( $_POST['id'], $file_path ) ) ) {
			$files[] = $original_file;
		}

		return $files;
	}

	/**
	 * Show the correct background image in the customizer
	 *
	 * @param int|null $post_id
	 * @param string   $url
	 *
	 * @return int|null
	 */
	function customizer_background_image( $post_id, $url ) {
		if ( ! is_null( $post_id ) ) {
			return $post_id;
		}
		$url = parse_url( $url );

		if ( ! isset( $url['path'] ) ) {
			return $post_id; // URL path can't be determined
		}

		$key1    = ltrim( $url['path'], '/' );
		$length1 = strlen( $key1 );

		// URLs may contain the bucket name within the path, therefore we must
		// also perform the search with the first path segment removed
		$parts = explode( '/', $key1 );
		unset( $parts[0] );

		$key2    = implode( '/', $parts );
		$length2 = strlen( $key2 );

		global $wpdb;
		$sql = "
			SELECT `post_id`
			FROM `{$wpdb->prefix}postmeta`
			WHERE `{$wpdb->prefix}postmeta`.`meta_key` = 'amazonS3_info'
			AND ( `{$wpdb->prefix}postmeta`.`meta_value` LIKE '%s:3:\"key\";s:{$length1}:\"{$key1}\";%'
			OR `{$wpdb->prefix}postmeta`.`meta_value` LIKE '%s:3:\"key\";s:{$length2}:\"{$key2}\";%' )
		";

		if ( $id = $wpdb->get_var( $sql ) ) {
			return $id;
		}

		return $post_id; // No attachment found on S3
	}

	/**
	 * Allow the Regenerate Thumbnails plugin to copy the S3 file back to the local
	 * server when the file is missing on the server via get_attached_file
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $s3_object
	 *
	 * @return string
	 */
	function regenerate_thumbnails_download_file( $url, $file, $attachment_id, $s3_object ) {
		return $this->copy_image_to_server_on_action( 'regeneratethumbnail', true, $url, $file, $s3_object );
	}

	/**
	 * Download a file from S3 if the file does not exist locally and places it where
	 * the attachment's file should be.
	 *
	 * @param array  $s3_object
	 * @param string $file
	 *
	 * @return string|bool File if downloaded, false on failure
	 */
	public function copy_s3_file_to_server( $s3_object, $file ) {
		// Make sure the directory exists
		$dir = dirname( $file );
		if ( ! wp_mkdir_p( $dir ) ) {
			$error_message = sprintf( __( 'The local directory %s does not exist and could not be created.', 'amazon-s3-and-cloudfront' ), $dir );
			AS3CF_Error::log( sprintf( __( 'There was an error attempting to download the file %s from S3: %s', 'amazon-s3-and-cloudfront' ), $s3_object['key'], $error_message ) );

			return false;
		}

		try {
			$this->as3cf->get_s3client( $s3_object['region'], true )->getObject(
				array(
					'Bucket' => $s3_object['bucket'],
					'Key'    => $s3_object['key'],
					'SaveAs' => $file,
				)
			);
		} catch ( Exception $e ) {
			AS3CF_Error::log( sprintf( __( 'There was an error attempting to download the file %s from S3: %s', 'amazon-s3-and-cloudfront' ), $s3_object['key'], $e->getMessage() ) );

			return false;
		}

		return $file;
	}

	/**
	 * Register stream wrappers per region
	 *
	 * @param string $region
	 *
	 * @return mixed
	 */
	protected function register_stream_wrapper( $region ) {
		$stored_region = ( '' === $region ) ? Amazon_S3_And_CloudFront::DEFAULT_REGION : $region;

		if ( in_array( $stored_region, self::$stream_wrappers ) ) {
			return;
		}

		$client   = $this->as3cf->get_s3client( $region, true );
		$protocol = $this->get_stream_wrapper_protocol( $region );

		// Register the region specific S3 stream wrapper to be used by plugins
		AS3CF_Stream_Wrapper::register( $client, $protocol );

		self::$stream_wrappers[] = $stored_region;
	}

	/**
	 * Generate the stream wrapper protocol
	 *
	 * @param string $region
	 *
	 * @return string
	 */
	protected function get_stream_wrapper_protocol( $region ) {
		$protocol = 's3';
		$protocol .= str_replace( '-', '', $region );

		return $protocol;
	}

	/**
	 * Generate an S3 stream wrapper compatible URL
	 *
	 * @param string $bucket
	 * @param string $key
	 *
	 * @return string
	 */
	function prepare_stream_wrapper_file( $bucket, $region, $key ) {
		$protocol = $this->get_stream_wrapper_protocol( $region );

		return $protocol . '://' . $bucket . '/' . $key;
	}

	/**
	 * Allow access to the S3 file via the stream wrapper.
	 * This is useful for compatibility with plugins when attachments are removed from the
	 * local server after upload.
	 *
	 * @param string $url
	 * @param string $file
	 * @param int    $attachment_id
	 * @param array  $s3_object
	 *
	 * @return string
	 */
	public function get_stream_wrapper_file( $url, $file, $attachment_id, $s3_object ) {
		if ( $url === $file ) {
			// Abort if an earlier hook to get the file has been called and it has been copied back.
			return $file;
		}

		// Make sure the region stream wrapper is registered
		$this->register_stream_wrapper( $s3_object['region'] );

		return $this->prepare_stream_wrapper_file( $s3_object['bucket'], $s3_object['region'], $s3_object['key'] );
	}

	/**
	 * Filters 'img' elements in post content to add 'srcset' and 'sizes' attributes for S3 URLs.
	 *
	 * @param string $content The raw post content to be filtered.
	 *
	 * @return string Converted content with 'srcset' and 'sizes' attributes added to images.
	 */
	public function wp_make_content_images_responsive( $content ) {
		if ( ! preg_match_all( '/<img [^>]+>/', $content, $matches ) ) {
			return $content;
		}

		$selected_images = $attachment_ids = array();

		foreach( $matches[0] as $image ) {
			if ( false === strpos( $image, ' srcset=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) &&
			     ( $attachment_id = absint( $class_id[1] ) ) ) {

				/*
				 * If exactly the same image tag is used more than once, overwrite it.
				 * All identical tags will be replaced later with 'str_replace()'.
				 */
				$selected_images[ $image ] = $attachment_id;
				// Overwrite the ID when the same image is included more than once.
				$attachment_ids[ $attachment_id ] = true;
			}
		}

		foreach ( $selected_images as $image => $attachment_id ) {
			if ( ! ( $s3object = $this->as3cf->is_attachment_served_by_s3( $attachment_id ) ) ) {
				// Attachment not uploaded to S3, abort
				continue;
			}

			$image_meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
			$content    = str_replace( $image, $this->wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id ), $content );
		}

		return $content;
	}

	/**
	 * Adds 'srcset' and 'sizes' attributes to an existing S3 'img' element.
	 *
	 * @param string $image         An HTML 'img' element to be filtered.
	 * @param array  $image_meta    The image meta data as returned by 'wp_get_attachment_metadata()'.
	 * @param int    $attachment_id Image attachment ID.
	 *
	 * @return string Converted 'img' element with 'srcset' and 'sizes' attributes added.
	 */
	public function wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id ) {
		// Ensure the image meta exists.
		if ( empty( $image_meta['sizes'] ) ) {
			return $image;
		}

		$image_src         = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';
		list( $image_src ) = explode( '?', $image_src );

		// Return early if we couldn't get the image source.
		if ( ! $image_src ) {
			return $image;
		}

		// Bail early if an image has been inserted and later edited.
		if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) &&
		     strpos( wp_basename( $image_src ), $img_edit_hash[0] ) === false ) {

			return $image;
		}

		$width  = preg_match( '/ width="([0-9]+)"/',  $image, $match_width  ) ? (int) $match_width[1]  : 0;
		$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : 0;

		if ( ! $width || ! $height ) {
			/*
			 * If attempts to parse the size value failed, attempt to use the image meta data to match
			 * the image file name from 'src' against the available sizes for an attachment.
			 */
			$image_filename = wp_basename( $image_src );

			if ( $image_filename === wp_basename( $image_meta['file'] ) ) {
				$width  = (int) $image_meta['width'];
				$height = (int) $image_meta['height'];
			} else {
				foreach( $image_meta['sizes'] as $image_size_data ) {
					if ( $image_filename === $image_size_data['file'] ) {
						$width  = (int) $image_size_data['width'];
						$height = (int) $image_size_data['height'];
						break;
					}
				}
			}
		}

		if ( ! $width || ! $height ) {
			return $image;
		}

		$size_array = array( $width, $height );
		$srcset     = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );
		$sizes      = false;

		if ( $srcset ) {
			// Check if there is already a 'sizes' attribute.
			$sizes = strpos( $image, ' sizes=' );

			if ( ! $sizes ) {
				$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
			}
		}

		if ( $srcset && $sizes ) {
			// Format the 'srcset' and 'sizes' string and escape attributes.
			$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );

			if ( is_string( $sizes ) ) {
				$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
			}

			// Add 'srcset' and 'sizes' attributes to the image markup.
			$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<img $1' . $attr . ' />', $image );
		}

		return $image;
	}

	/**
	 * Alter the image meta data to add srcset support for object versioned S3 URLs
	 *
	 * @param array  $image_meta
	 * @param array  $size_array
	 * @param string $image_src
	 * @param int    $attachment_id
	 *
	 * @return array
	 */
	public function wp_calculate_image_srcset_meta( $image_meta, $size_array, $image_src, $attachment_id ) {
		if ( empty( $image_meta['file'] ) ) {
			// Corrupt `_wp_attachment_metadata`
			return $image_meta;
		}

		if ( false !== strpos( $image_src, $image_meta['file']  ) ) {
			// Path matches URL, no need to change
			return $image_meta;
		}

		if ( ! ( $s3object = $this->as3cf->is_attachment_served_by_s3( $attachment_id ) ) ) {
			// Attachment not uploaded to S3, abort
			return $image_meta;
		}

		$image_basename = wp_basename( $image_meta['file'] );

		if ( false === strpos( $s3object['key'], $image_basename ) ) {
			// Not the correct attachment, abort
			return $image_meta;
		}

		// Strip the meta file prefix so the just the filename will always match
		// the S3 URL regardless of different prefixes for the offloaded file
		$image_meta['file'] = $image_basename;

		return $image_meta;
	}

	/**
	 * Replace local URLs with S3 ones for srcset image sources
	 *
	 * @param array  $sources
	 * @param array  $size_array
	 * @param string $image_src
	 * @param array  $image_meta
	 * @param int    $attachment_id
	 *
	 * @return array
	 */
	public function wp_calculate_image_srcset( $sources, $size_array, $image_src, $image_meta, $attachment_id ) {
		if ( ! is_array( $sources ) ) {
			// Sources corrupt
			return $sources;
		}

		if ( ! ( $s3object = $this->as3cf->is_attachment_served_by_s3( $attachment_id ) ) ) {
			// Attachment not uploaded to S3, abort
			return $sources;
		}

		foreach ( $sources as $width => $source ) {
			$filename = basename( $source['url'] );
			$size     = $this->find_image_size_from_width( $image_meta['sizes'], $width, $filename );
			$s3_url   = $this->as3cf->get_attachment_s3_url( $attachment_id, $s3object, null, $size, $image_meta );

			if ( false === $s3_url || is_wp_error( $s3_url ) ) {
				continue;
			}

			$sources[ $width ]['url'] = $s3_url;
		}

		return $sources;
	}

	/**
	 * Helper function to find size name from width and filename
	 *
	 * @param array  $sizes
	 * @param string $width
	 * @param string $filename
	 *
	 * @return null|string
	 */
	protected function find_image_size_from_width( $sizes, $width, $filename ) {
		foreach ( $sizes as $name => $size ) {
			if ( $width === $size['width'] && $size['file'] === $filename ) {
				return $name;
			}
		}

		return null;
	}
}