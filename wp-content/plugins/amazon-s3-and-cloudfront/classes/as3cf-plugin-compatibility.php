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

	function __construct( $as3cf ) {
		$this->as3cf = $as3cf;

		$this->compatibility_init();
	}

	/**
	 * Register the compatibility hooks
	 */
	function compatibility_init() {
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
		add_filter( 'as3cf_get_attached_file', array( $this, 'customizer_header_crop_download_file' ), 10, 4 );
		add_filter( 'as3cf_upload_attachment_local_files_to_remove', array( $this, 'customizer_header_crop_remove_original_image' ), 10, 3 );

		/*
		 * WP_Customize_Control
		 * /wp-includes/class-wp-customize_control.php
		 */
		add_filter( 'attachment_url_to_postid', array( $this, 'customizer_background_image' ), 10, 2 );
		/*
		 * Regenerate Thumbnails
		 * https://wordpress.org/plugins/regenerate-thumbnails/
		 */
		add_filter( 'as3cf_get_attached_file', array( $this, 'regenerate_thumbnails_download_file' ), 10, 4 );
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
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
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
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
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
	function customizer_header_crop_download_file( $url, $file, $attachment_id, $s3_object ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $url;
		}

		if ( isset( $_POST['action'] ) && 'custom-header-crop' === sanitize_key( $_POST['action'] ) ) { // input var okay
			if ( ( $file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
				// Return the file if successfully downloaded from S3
				return $file;
			};
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
	function customizer_header_crop_remove_original_image( $files, $post_id, $file_path ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $files;
		}

		if ( isset( $_POST['action'] ) && 'custom-header-crop' === sanitize_key( $_POST['action'] ) ) { // input var okay
			// remove original main image after edit
			if ( ( $original_file = $this->get_original_image_file( $_POST['id'], $file_path ) ) ) {
				$files[] = $original_file;
			}
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
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $url;
		}

		if ( isset( $_POST['action'] ) && 'regeneratethumbnail' == sanitize_key( $_POST['action'] ) ) { // input var okay
			if ( ( $file = $this->copy_s3_file_to_server( $s3_object, $file ) ) ) {
				// Return the file if successfully downloaded from S3
				return $file;
			};
		}

		return $url;
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
	protected function copy_s3_file_to_server( $s3_object, $file ) {
		try {
			$this->as3cf->get_s3client( $s3_object['region'], true )->getObject(
				array(
					'Bucket' => $s3_object['bucket'],
					'Key'    => $s3_object['key'],
					'SaveAs' => $file,
				)
			);
		} catch ( Exception $e ) {
			error_log( sprintf( __( 'There was an error attempting to download the file %s from S3: %s', 'as3cf' ), $s3_object['key'], $e->getMessage() ) );

			return false;
		}

		return $file;
	}
}