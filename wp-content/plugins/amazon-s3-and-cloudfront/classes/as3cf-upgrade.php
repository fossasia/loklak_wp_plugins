<?php
/**
 * Upgrade
 *
 * @package     amazon-s3-and-cloudfront
 * @subpackage  Classes/Upgrade
 * @copyright   Copyright (c) 2014, Delicious Brains
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.6.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AS3CF_Upgrade Class
 *
 * This class handles data updates and other migrations after a plugin update
 *
 * @since 0.6.2
 */
class AS3CF_Upgrade {

	private $as3cf;
	private $cron_interval_in_minutes;
	private $error_threshold;

	const CRON_HOOK = 'as3cf_cron_update_meta_with_region';
	const CRON_SCHEDULE_KEY = 'as3cf_update_meta_with_region_interval';

	const STATUS_RUNNING = 1;
	const STATUS_ERROR = 2;
	const STATUS_PAUSED = 3;

	/**
	 * Start it up
	 *
	 * @param Amazon_S3_And_CloudFront $as3cf - the instance of the as3cf class
	 */
	function __construct( $as3cf ) {
		$this->as3cf = $as3cf;

		$this->cron_interval_in_minutes = apply_filters( 'as3cf_update_meta_with_region_interval', 10 );
		$this->error_threshold = apply_filters( 'as3cf_update_meta_with_region_error_threshold', 20 );

		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( self::CRON_HOOK, array( $this, 'cron_update_meta_with_region' ) );

		add_action( 'as3cf_pre_settings_render', array( $this, 'maybe_display_notices' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_action' ) );

		$this->maybe_init_upgrade();
	}

	/**
	 * Maybe initialize the upgrade
	 */
	function maybe_init_upgrade() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// make sure this only fires inside the network admin for multisites
		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		// Have we completed the upgrade yet?
		if ( $this->as3cf->get_setting( 'post_meta_version', 0 ) > 0 ) {
			return;
		}

		// If the upgrade status is already set, then we've already initialized the upgrade
		if ( $this->get_upgrade_status() ) {
			return;
		}

		// Do we actually have S3 meta data without regions to update?
		// No need to bother for fresh sites, or media not uploaded to S3
		if ( 0 == $this->count_all_attachments_without_region() ) {
			$this->as3cf->set_setting( 'post_meta_version', 1 );
			$this->as3cf->save_settings();

			return;
		}

		// Initialize the upgrade
		$this->save_session( array( 'status' => self::STATUS_RUNNING ) );

		$this->as3cf->schedule_event( self::CRON_HOOK, self::CRON_SCHEDULE_KEY );
	}

	/**
	 * Adds notices about issues with upgrades allowing user to restart them
	 */
	function maybe_display_notices() {
		$action_url = $this->as3cf->get_plugin_page_url( array( 'action' => 'restart_update_meta_with_region' ), 'self' );
		$msg_type   = 'notice-info';

		switch ( $this->get_upgrade_status() ) {
			case self::STATUS_RUNNING :
				$msg         = sprintf( __( '<strong>Running Metadata Update</strong> &mdash; We&#8217;re going through all the Media Library items uploaded to S3 and updating the metadata with the bucket region it is served from. This will allow us to serve your files from the proper S3 region subdomain <span style="white-space:nowrap;">(e.g. s3-us-west-2.amazonaws.com)</span>. This will be done quietly in the background, processing a small batch of Media Library items every %d minutes. There should be no noticeable impact on your server&#8217;s performance.', 'as3cf' ), $this->cron_interval_in_minutes );
				$action_text = __( 'Pause Update', 'as3cf' );
				$action_url  = $this->as3cf->get_plugin_page_url( array( 'action' => 'pause_update_meta_with_region' ), 'self' );
				break;
			case self::STATUS_PAUSED :
				$msg         = __( '<strong>Metadata Update Paused</strong> &mdash; Updating Media Library metadata has been paused.', 'as3cf' );
				$action_text = __( 'Restart Update', 'as3cf' );
				break;
			case self::STATUS_ERROR :
				$msg         = __( '<strong>Error Updating Metadata</strong> &mdash; We ran into some errors attempting to update the metadata for all your Media Library items that have been uploaded to S3. Please check your error log for details.', 'as3cf' );
				$action_text = __( 'Try Run It Again', 'as3cf' );
				$msg_type    = 'error';
				break;
			default :
				return;
		}

		$msg .= ' <strong><a href="' . $action_url . '">' . $action_text . '</a></strong>';

		$args = array(
			'message'     => $msg,
			'type'        => $msg_type,
		);

		$this->as3cf->render_view( 'notice', $args );
	}

	function maybe_handle_action() {
		if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) != $this->as3cf->get_plugin_slug() || ! isset( $_GET['action'] ) ) { // input var okay
			return;
		}

		$method_name = 'action_' . sanitize_key( $_GET['action'] ); // input var okay
		if ( method_exists( $this, $method_name ) ) {
			call_user_func( array( $this, $method_name ) );
		}
	}

	/**
	 * Restart upgrade
	 */
	function action_restart_update_meta_with_region() {
		$this->change_status_request( self::STATUS_RUNNING );
		$this->as3cf->schedule_event( self::CRON_HOOK, self::CRON_SCHEDULE_KEY );
	}

	/**
	 * Pause upgrade
	 */
	function action_pause_update_meta_with_region() {
		$this->clear_scheduled_event();
		$this->change_status_request( self::STATUS_PAUSED );
	}

	/**
	 * Helper for the above action requests
	 *
	 * @param integer $status
	 */
	function change_status_request( $status ) {
		$session = $this->get_session();
		$session['status'] = $status;
		$this->save_session( $session );

		$url = $this->as3cf->get_plugin_page_url( array(), 'self' );
		wp_redirect( $url );
	}

	/**
	 * Add custom cron interval schedules
	 *
	 * @param array   $schedules
	 *
	 * @return array
	 */
	function cron_schedules( $schedules ) {
		// Adds every 10 minutes to the existing schedules.
		$schedules[ self::CRON_SCHEDULE_KEY ] = array(
			'interval' => $this->cron_interval_in_minutes * 60,
			'display'  => sprintf( __( 'Every %d Minutes', 'as3cf' ), $this->cron_interval_in_minutes ),
		);

		return $schedules;
	}
	
	/**
	 * Cron jon to update the region of the bucket in s3 metadata
	 */
	function cron_update_meta_with_region() {
		// Check if the cron should even be running
		if ( $this->as3cf->get_setting( 'post_meta_version', 0 ) > 0 || $this->get_upgrade_status() != self::STATUS_RUNNING ) {
			$this->as3cf->clear_scheduled_event( self::CRON_HOOK );
			return;
		}

		// set the batch size limit for the query
		$limit     = apply_filters( 'as3cf_update_meta_with_region_batch_size', 500 );
		$all_limit = $limit;

		$session   = $this->get_session();

		// find the blog IDs that have been processed so we can skip them
		$processed_blog_ids = isset( $session['processed_blog_ids'] ) ? $session['processed_blog_ids'] : array();
		$error_count  = isset( $session['error_count'] ) ? $session['error_count'] : 0;

		// get the table prefixes for all the blogs
		$table_prefixes = $this->as3cf->get_all_blog_table_prefixes( $processed_blog_ids );

		$all_attachments = array();
		$all_count       = 0;

		foreach ( $table_prefixes as $blog_id => $table_prefix ) {
			$attachments = $this->get_attachments_without_region( $table_prefix, $limit );
			$count       = count( $attachments );

			if ( 0 == $count ) {
				// no more attachments, record the blog ID to skip next time
				$processed_blog_ids[] = $blog_id;
			} else {
				$all_count += $count;
				$all_attachments[ $blog_id ] = $attachments;
			}

			if ( $all_count >= $all_limit ) {
				break;
			}

			$limit = $limit - $count;
		}

		if ( 0 == $all_count ) {
			$this->as3cf->set_setting( 'post_meta_version', 1 );
			$this->as3cf->remove_setting( 'update_meta_with_region_session' );
			$this->as3cf->save_settings();
			$this->as3cf->clear_scheduled_event( self::CRON_HOOK );
			return;
		}

		// only process the loop for a certain amount of time
		$minutes = $this->cron_interval_in_minutes * 60;

		// smaller time limit so won't run into another instance of cron
		$minutes = $minutes * 0.8;

		$finish  = time() + $minutes;

		// loop through and update s3 meta with region
		foreach ( $all_attachments as $blog_id => $attachments ) {
			if ( is_multisite() && ! $this->as3cf->is_current_blog( $blog_id ) ) {
				switch_to_blog( $blog_id );
			}

			foreach ( $attachments as $attachment ) {
				if ( $error_count >= $this->error_threshold ) {
					$session['status'] = self::STATUS_ERROR;
					$this->save_session( $session );
					$this->clear_scheduled_event();
					return;
				}

				if ( time() >= $finish ) {
					break;
				}

				$s3object = unserialize( $attachment->s3object );
				if ( false === $s3object ) {
					error_log( 'Failed to unserialize S3 meta for attachment ' . $attachment->ID . ': ' . $attachment->s3object );
					$error_count++;
					continue;
				}

				// retrieve region and update the attachment metadata
				$region = $this->as3cf->get_s3object_region( $s3object, $attachment->ID );
				if ( is_wp_error( $region ) ) {
					error_log( 'Error updating region: ' . $region->get_error_message() );
					$error_count++;
				}
			}

			if ( is_multisite() && ! $this->as3cf->is_current_blog( $blog_id ) ) {
				restore_current_blog();
			}
		}

		$session['processed_blog_ids'] = $processed_blog_ids;
		$session['error_count']        = $error_count;

		$this->save_session( $session );
	}

	/**
	 * Get a count of all attachments without region in their S3 metadata
	 * for the whole site
	 *
	 * @return int
	 */
	function count_all_attachments_without_region() {
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
	 * Get the current status of the upgrade
	 * See STATUS_* constants in the class declaration above.
	 */
	function get_upgrade_status() {
		$session = $this->get_session();

		if ( ! isset( $session['status'] ) ) {
			return '';
		}

		return $session['status'];
	}

	/**
	 * Retrieve session data from plugin settings
	 *
	 * @return array
	 */
	function get_session() {
		return $this->as3cf->get_setting( 'update_meta_with_region_session', array() );
	}

	/**
	 * Store data to be used between requests in plugin settings
	 *
	 * @param $session array of session data to store
	 */
	function save_session( $session ) {
		$this->as3cf->set_setting( 'update_meta_with_region_session', $session );
		$this->as3cf->save_settings();
	}

	/**
	 * Get all the table prefixes for the blogs in the site. MS compatible
	 *
	 * @param array $exclude_blog_ids blog ids to exclude
	 *
	 * @return array associative array with blog ID as key, prefix as value
	 */
	function get_all_blog_table_prefixes( $exclude_blog_ids = array() ) {
		global $wpdb;
		$prefix = $wpdb->prefix;

		$table_prefixes = array();

		if ( ! in_array( 1, $exclude_blog_ids ) ) {
			$table_prefixes[1] = $prefix;
		}

		if ( is_multisite() ) {
			$blog_ids = $this->as3cf->get_blog_ids();
			foreach ( $blog_ids as $blog_id ) {
				if ( in_array( $blog_id, $exclude_blog_ids ) ) {
					continue;
				}
				$table_prefixes[ $blog_id ] = $wpdb->get_blog_prefix( $blog_id );
			}
		}

		return $table_prefixes;
	}

	/**
	 * Get all attachments that don't have region in their S3 meta data for a blog
	 *
	 * @param string $prefix
	 * @param int    $limit
	 *
	 * @return mixed
	 */
	function get_attachments_without_region( $prefix, $limit ) {
		$attachments = $this->get_attachments_without_region_results( $prefix, false, $limit );

		return $attachments;
	}

	/**
	 * Get a count of attachments  that don't have region in their S3 meta data for a blog
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
