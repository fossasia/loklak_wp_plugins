<?php
/**
 * Update File Sizes
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Upgrades/File-Sizes
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Upgrade_File_Sizes Class
 *
 * This class handles updating the file sizes in the meta data
 * for attachments that have been removed from the local server
 *
 * @since 0.9.3
 */
class AS3CF_Upgrade_File_Sizes extends AS3CF_Upgrade {

	/**
	 * Initiate the upgrade
	 *
	 * @param object $as3cf Instance of calling class
	 */
	public function __construct( $as3cf ) {
		$this->upgrade_id   = 2;
		$this->upgrade_name = 'file_sizes';
		$this->upgrade_type = 'attachments';

		$this->running_update_text = __( 'and updating the metadata with the sizes of files that have been removed from the server. This will allow us to serve the correct size for media items and the total space used in Multisite subsites.', 'as3cf' );

		parent::__construct( $as3cf );
	}

	/**
	 * Get the total file sizes for an attachment and associated files.
	 *
	 * @param $attachment
	 *
	 * @return bool
	 */
	function upgrade_attachment( $attachment ) {
		$s3object = unserialize( $attachment->s3object );
		if ( false === $s3object ) {
			error_log( 'Failed to unserialize S3 meta for attachment ' . $attachment->ID . ': ' . $attachment->s3object );
			$this->error_count++;

			return false;
		}

		$region = $this->as3cf->get_s3object_region( $s3object );
		if ( is_wp_error( $region ) ) {
			error_log( 'Failed to get the region for the bucket of the attachment ' . $attachment->ID );
			$this->error_count++;

			return false;
		}

		$s3client   = $this->as3cf->get_s3client( $region, true );
		$main_file  = $s3object['key'];

		$path_parts = pathinfo( $main_file );
		$prefix     = trailingslashit( dirname( $s3object['key'] ) );

		// Used to search S3 for all files related to an attachment
		$search_prefix = $prefix . basename( $main_file, '.' . $path_parts['extension'] );

		$args = array(
			'Bucket' => $s3object['bucket'],
			'Prefix' => $search_prefix,
		);

		try {
			// List objects for the attachment
			$result = $s3client->ListObjects( $args );
		} catch ( Exception $e ) {
			error_log( 'Error listing objects of prefix ' . $search_prefix . ' for attachment ' . $attachment->ID . ' from S3: ' . $e->getMessage() );
			$this->error_count ++;

			return false;
		}

		$file_size_total = 0;
		$main_file_size = 0;

		foreach ( $result->get( 'Contents' ) as $object ) {
			if ( ! isset( $object['Size'] ) ) {
				continue;
			}

			$size = $object['Size'];

			// Increment the total size of files for the attachment
			$file_size_total += $size;

			if ( $object['Key'] === $main_file ) {
				// Record the size of the main file
				$main_file_size = $size;
			}
		}

		if ( 0 === $file_size_total ) {
			error_log( 'Total file size for the attachment is 0: ' . $attachment->ID );
			$this->error_count ++;

			return false;
		}

		// Update the main file size for the attachment
		$meta             = get_post_meta( $attachment->ID, '_wp_attachment_metadata', true );
		$meta['filesize'] = $main_file_size;
		update_post_meta( $attachment->ID, '_wp_attachment_metadata', $meta );

		// Add the total file size for all image sizes
		update_post_meta( $attachment->ID, 'wpos3_filesize_total', $file_size_total );

		return true;
	}

	/**
	 * Get a count of all attachments without region in their S3 metadata
	 * for the whole site
	 *
	 * @return int
	 */
	function count_attachments_to_process() {
		// get the table prefixes for all the blogs
		$table_prefixes = $this->as3cf->get_all_blog_table_prefixes();
		$all_count      = 0;

		foreach ( $table_prefixes as $blog_id => $table_prefix ) {
			$count = $this->get_attachments_removed_from_server( $table_prefix, true );
			$all_count += $count;
		}

		return $all_count;
	}

	/**
	 * Get all attachments that don't have region in their S3 meta data for a blog
	 *
	 * @param string $prefix
	 * @param int    $limit
	 *
	 * @return mixed
	 */
	function get_attachments_to_process( $prefix, $limit ) {
		$attachments = $this->get_attachments_removed_from_server( $prefix, false, $limit );

		return $attachments;
	}

	/**
	 * Wrapper for database call to get attachments uploaded to S3,
	 * that don't have the file size meta added already
	 *
	 * @param string   $prefix
	 * @param null|int $limit
	 *
	 * @return mixed
	 */
	function get_s3_attachments( $prefix, $limit = null ) {
		global $wpdb;

		$sql = "SELECT pm1.`post_id` as `ID`, pm1.`meta_value` AS 's3object'
				FROM `{$prefix}postmeta` pm1
					LEFT OUTER JOIN `{$prefix}postmeta` pm2
					ON pm1.`post_id` = pm2.`post_id`
					AND pm2.`meta_key` = 'wpos3_filesize_total'
				WHERE pm1.`meta_key` = 'amazonS3_info'
				AND pm2.`post_id` is null";

		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT %d';

			$sql = $wpdb->prepare( $sql, $limit );
		}

		return $wpdb->get_results( $sql, OBJECT );
	}

	/**
	 * Get S3 attachments that have had their local file removed from the server
	 *
	 * @param string     $prefix
	 * @param bool|false $count
	 * @param null|int   $limit
	 *
	 * @return array|int
	 */
	function get_attachments_removed_from_server( $prefix, $count = false, $limit = null ) {
		$all_attachments = $this->get_s3_attachments( $prefix, $limit );
		$attachments     = array();

		foreach ( $all_attachments as $attachment ) {
			if ( ! file_exists( get_attached_file( $attachment->ID, true ) ) ) {
				$attachments[] = $attachment;
			}
		}

		if ( $count ) {
			return count( $attachments );
		}

		return $attachments;
	}

}