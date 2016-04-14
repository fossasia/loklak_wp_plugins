<?php
/**
 * Update Broken _wp_attachment_metadata introduced in 0.9.4
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Upgrades/Meta-WP-Error
 * @copyright   Copyright (c) 2015, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.9.5
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Upgrade_Meta_WP_Error Class
 *
 * This class handles updating the _wp_attachment_metadata
 * for attachments that have been removed from the local server
 * and have had it corrupted by another plugin
 *
 * @since 0.9.5
 */
class AS3CF_Upgrade_Meta_WP_Error extends AS3CF_Upgrade {

	/**
	 * Initiate the upgrade
	 *
	 * @param object $as3cf Instance of calling class
	 */
	public function __construct( $as3cf ) {
		$this->upgrade_id   = 3;
		$this->upgrade_name = 'meta_error';
		$this->upgrade_type = 'attachments';

		$this->running_update_text = __( 'and rebuilding the metadata for attachments that may have been corrupted.', 'amazon-s3-and-cloudfront' );

		parent::__construct( $as3cf );
	}

	/**
	 * Rebuild the attachment metadata for an attachment
	 *
	 * @param $attachment
	 *
	 * @return bool
	 */
	function upgrade_attachment( $attachment ) {
		$s3object = unserialize( $attachment->s3object );
		if ( false === $s3object ) {
			AS3CF_Error::log( 'Failed to unserialize S3 meta for attachment ' . $attachment->ID . ': ' . $attachment->s3object );
			$this->error_count++;

			return false;
		}

		$file = get_attached_file( $attachment->ID, true );

		if ( ! file_exists( $file ) ) {
			// Copy back the file to the server if doesn't exist so we can successfully
			// regenerate the attachment metadata
			try {
				$args = array(
					'Bucket' => $s3object['bucket'],
					'Key'    => $s3object['key'],
					'SaveAs' => $file,
				);
				$this->as3cf->get_s3client( $s3object['region'], true )->getObject( $args );
			} catch ( Exception $e ) {
				AS3CF_Error::log( sprintf( __( 'There was an error attempting to download the file %s from S3: %s', 'amazon-s3-and-cloudfront' ), $s3object['key'], $e->getMessage() ) );

				return false;
			}
		}

		// Remove corrupted meta
		delete_post_meta( $attachment->ID, '_wp_attachment_metadata' );

		require_once ABSPATH . '/wp-admin/includes/image.php';
		// Generate new attachment meta
		wp_update_attachment_metadata( $attachment->ID, wp_generate_attachment_metadata( $attachment->ID, $file ) );

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
			$count = $this->get_attachments_with_error_metadata( $table_prefix, true );
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
		$attachments = $this->get_attachments_with_error_metadata( $prefix, false, $limit );

		return $attachments;
	}

	/**
	 * Get S3 attachments that have had their _wp_attachment_metadata corrupted
	 *
	 * @param string     $prefix
	 * @param bool|false $count
	 * @param null|int   $limit
	 *
	 * @return array|int
	 */
	function get_attachments_with_error_metadata( $prefix, $count = false, $limit = null ) {
		global $wpdb;

		$sql = "FROM `{$prefix}postmeta` pm1
					LEFT OUTER JOIN `{$prefix}postmeta` pm2
					ON pm1.`post_id` = pm2.`post_id`
					AND pm2.`meta_key` = '_wp_attachment_metadata'
				WHERE pm1.`meta_key` = 'amazonS3_info'
				AND pm2.`meta_value` like '%%WP_Error%%'";

		if ( $count ) {
			$sql = 'SELECT COUNT(*)' . $sql;

			return $wpdb->get_var( $sql );
		}

		$sql = "SELECT pm1.`post_id` as `ID`, pm1.`meta_value` AS 's3object'" . $sql;

		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT %d';

			$sql = $wpdb->prepare( $sql, $limit );
		}

		return $wpdb->get_results( $sql, OBJECT );
	}
}