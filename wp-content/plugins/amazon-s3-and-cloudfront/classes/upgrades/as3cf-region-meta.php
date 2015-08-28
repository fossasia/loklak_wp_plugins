<?php
/**
 * Upgrade Region in Meta
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Upgrades/Region-Meta
 * @copyright   Copyright (c) 2014, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.6.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Upgrade_Region_Meta Class
 *
 * This class handles updating the region of the attachment's bucket in the meta data
 *
 * @since 0.6.2
 */
class AS3CF_Upgrade_Region_Meta extends AS3CF_Upgrade {

	/**
	 * Initiate the upgrade
	 *
	 * @param object $as3cf Instance of calling class
	 */
	public function __construct( $as3cf ) {
		$this->upgrade_id   = 1;
		$this->upgrade_name = 'meta_with_region';
		$this->upgrade_type = 'metadata';

		$this->running_update_text = __( 'and updating the metadata with the bucket region it is served from. This will allow us to serve your files from the proper S3 region subdomain <span style="white-space:nowrap;">(e.g. s3-us-west-2.amazonaws.com)</span>.', 'as3cf' );

		parent::__construct( $as3cf );
	}

	/**
	 * Get the region for the bucket where an attachment is located, update the S3 meta.
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
		// retrieve region and update the attachment metadata
		$region = $this->as3cf->get_s3object_region( $s3object, $attachment->ID );
		if ( is_wp_error( $region ) ) {
			error_log( 'Error updating region: ' . $region->get_error_message() );
			$this->error_count++;

			return false;
		}

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
			$count = $this->count_attachments_without_region( $table_prefix );
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
		$attachments = $this->get_attachments_without_region_results( $prefix, false, $limit );

		return $attachments;
	}

	/**
	 * Get a count of attachments that don't have region in their S3 meta data for a blog
	 * @param $prefix
	 *
	 * @return int
	 */
	function count_attachments_without_region( $prefix ) {
		$count = $this->get_attachments_without_region_results( $prefix, true );

		return $count;
	}

	/**
	 * Wrapper for database call to get attachments without region
	 *
	 * @param string   $prefix
	 * @param bool     $count return count of attachments
	 * @param null|int $limit
	 *
	 * @return mixed
	 */
	function get_attachments_without_region_results( $prefix, $count = false, $limit = null ) {
		global $wpdb;

		$sql = " FROM `{$prefix}postmeta`
				WHERE `meta_key` = 'amazonS3_info'
				AND `meta_value` NOT LIKE '%%\"region\"%%'";

		if ( $count ) {
			$sql = 'SELECT COUNT(*)' . $sql;

			return $wpdb->get_var( $sql );
		}

		$sql = "SELECT `post_id` as `ID`, `meta_value` AS 's3object'" . $sql;

		if ( ! is_null( $limit ) ) {
			$sql .= ' LIMIT %d';

			$sql = $wpdb->prepare( $sql, $limit );
		}

		return $wpdb->get_results( $sql, OBJECT );
	}
}