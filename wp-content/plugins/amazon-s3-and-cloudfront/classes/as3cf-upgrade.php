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
 * This class handles updates to attachments and attachment meta data
 *
 * @since 0.6.2
 */
abstract class AS3CF_Upgrade {

	/**
	 * @var Amazon_S3_And_CloudFront
	 */
	protected $as3cf;

	/**
	 * @var int
	 */
	protected $upgrade_id;

	/**
	 * @var string
	 */
	protected $upgrade_name;

	/**
	 * @var string 'metadata', 'attachment'
	 */
	protected $upgrade_type;

	/**
	 * @var string
	 */
	protected $running_update_text;

	/**
	 * @var string
	 */
	protected $settings_key = 'post_meta_version';

	/**
	 * @var string
	 */
	protected $cron_hook;

	/**
	 * @var string
	 */
	protected $cron_schedule_key;

	/**
	 * @var mixed|void
	 */
	protected $cron_interval_in_minutes;

	/**
	 * @var mixed|void
	 */
	protected $error_threshold;

	/**
	 * @var int
	 */
	protected $error_count;

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

		$this->cron_hook         = 'as3cf_cron_update_' . $this->upgrade_name;
		$this->cron_schedule_key = 'as3cf_update_' . $this->upgrade_name . '_interval';

		$this->cron_interval_in_minutes = apply_filters( 'as3cf_update_' . $this->upgrade_name . '_interval', 5 );
		$this->error_threshold          = apply_filters( 'as3cf_update_' . $this->upgrade_name . '_error_threshold', 20 );

		add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
		add_action( $this->cron_hook, array( $this, 'do_upgrade' ) );

		add_action( 'as3cf_pre_settings_render', array( $this, 'maybe_display_notices' ) );
		add_action( 'admin_init', array( $this, 'maybe_handle_action' ) );

		// Do default checks if the upgrade can be started
		if ( $this->maybe_init() ) {
			$this->init();
		}
	}

	/**
	 * Can we start the upgrade using default checks
	 *
	 * @return bool
	 */
	protected function maybe_init() {
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return false;
		}

		// make sure this only fires inside the network admin for multisites
		if ( is_multisite() && ! is_network_admin() ) {
			return false;
		}

		// If the upgrade status is already set, then we've already initialized the upgrade
		if ( $upgrade_status = $this->get_upgrade_status() ) {
			if ( self::STATUS_RUNNING === $upgrade_status ) {
				// Make sure cron job is persisted in case it has dropped
				$this->schedule();
			}
			return false;
		}

		// Have we completed the upgrade?
		if ( $this->get_saved_upgrade_id() >= $this->upgrade_id ) {
			return false;
		}

		// Has the previous upgrade completed yet?
		if ( ! $this->has_previous_upgrade_completed() ) {
			return false;
		}

		// Do we actually attachments to process?
		if ( 0 === $this->count_attachments_to_process() ) {
			$this->upgrade_finished();

			return false;
		}

		return true;
	}

	/**
	 * @return int
	 */
	abstract protected function count_attachments_to_process();

	/**
	 * @param $prefix
	 * @param $limit
	 *
	 * @return array
	 */
	abstract protected function get_attachments_to_process( $prefix, $limit );

	/**
	 * @param $attachment
	 *
	 * @return bool
	 */
	abstract protected function upgrade_attachment( $attachment );

	/**
	 * Fire up the upgrade
	 */
	protected function init() {
		// Initialize the upgrade
		$this->save_session( array( 'status' => self::STATUS_RUNNING ) );

		$this->schedule();
	}

	/**
	 * Cron jon to update the region of the bucket in s3 metadata
	 */
	function do_upgrade() {
		// Check if the cron should even be running
		if ( $this->get_saved_upgrade_id() >= $this->upgrade_id || $this->get_upgrade_status() !== self::STATUS_RUNNING ) {
			$this->unschedule();

			return;
		}

		// set the batch size limit for the query
		$limit     = apply_filters( 'as3cf_update_' . $this->upgrade_name . '_batch_size', 500 );
		$all_limit = $limit;

		// only process the loop for a certain amount of time
		$minutes = $this->cron_interval_in_minutes * 60;
		// smaller time limit so won't run into another instance of cron
		$minutes = $minutes * 0.8;
		$finish  = time() + $minutes;

		$session = $this->get_session();

		// find the blog IDs that have been processed so we can skip them
		$processed_blog_ids = isset( $session['processed_blog_ids'] ) ? $session['processed_blog_ids'] : array();
		$this->error_count  = isset( $session['error_count'] ) ? $session['error_count'] : 0;

		// get the table prefixes for all the blogs
		$table_prefixes = $this->as3cf->get_all_blog_table_prefixes( $processed_blog_ids );

		$all_attachments = array();
		$all_count       = 0;

		foreach ( $table_prefixes as $blog_id => $table_prefix ) {
			$attachments = $this->get_attachments_to_process( $table_prefix, $limit );
			$count       = count( $attachments );

			if ( 0 === $count ) {
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

		if ( 0 === $all_count ) {
			$this->upgrade_finished();

			return;
		}

		// loop through and update s3 meta with region
		foreach ( $all_attachments as $blog_id => $attachments ) {
			$this->as3cf->switch_to_blog( $blog_id );

			foreach ( $attachments as $attachment ) {
				if ( $this->error_count >= $this->error_threshold ) {
					$this->upgrade_error( $session );

					return;
				}

				// Do the actual upgrade to the attachment
				$this->upgrade_attachment( $attachment );

				if ( time() >= $finish || $this->as3cf->memory_exceeded( 'as3cf_update_' . $this->upgrade_name . '_memory_exceeded' ) ) {
					// Batch limits reached
					$this->as3cf->restore_current_blog();

					break 2;
				}
			}

			$this->as3cf->restore_current_blog();
		}

		$session['processed_blog_ids'] = $processed_blog_ids;
		$session['error_count']        = $this->error_count;

		$this->save_session( $session );
	}

	/**
	 * Adds notices about issues with upgrades allowing user to restart them
	 */
	function maybe_display_notices() {
		$action_url = $this->as3cf->get_plugin_page_url( array( 'action' => 'restart_update', 'update' => $this->upgrade_name ), 'self' );
		$msg_type   = 'notice-info';

		switch ( $this->get_upgrade_status() ) {
			case self::STATUS_RUNNING:
				$msg         = sprintf( __( '<strong>Running %s Update</strong> &mdash; We&#8217;re going through all the Media Library items uploaded to S3 %s This will be done quietly in the background, processing a small batch of Media Library items every %d minutes. There should be no noticeable impact on your server&#8217;s performance.', 'amazon-s3-and-cloudfront' ), ucfirst( $this->upgrade_type ), $this->running_update_text, $this->cron_interval_in_minutes );
				$action_text = __( 'Pause Update', 'amazon-s3-and-cloudfront' );
				$action_url  = $this->as3cf->get_plugin_page_url( array( 'action' => 'pause_update', 'update' => $this->upgrade_name ), 'self' );
				break;
			case self::STATUS_PAUSED:
				$msg         = sprintf( __( '<strong>%s Update Paused</strong> &mdash; Updating Media Library %s has been paused.', 'amazon-s3-and-cloudfront' ), ucfirst( $this->upgrade_type ), $this->upgrade_type );
				$action_text = __( 'Restart Update', 'amazon-s3-and-cloudfront' );
				break;
			case self::STATUS_ERROR:
				$msg         = sprintf( __( '<strong>Error Updating %s</strong> &mdash; We ran into some errors attempting to update the %s for all your Media Library items that have been uploaded to S3. Please check your error log for details. (#%d)', 'amazon-s3-and-cloudfront' ), ucfirst( $this->upgrade_type ), $this->upgrade_type, $this->upgrade_id );
				$action_text = __( 'Try Run It Again', 'amazon-s3-and-cloudfront' );
				$msg_type    = 'error';
				break;
			default:
				return;
		}

		$msg .= ' <strong><a href="' . $action_url . '">' . $action_text . '</a></strong>';

		$args = array(
			'message' => $msg,
			'type'    => $msg_type,
		);

		$this->as3cf->render_view( 'notice', $args );
	}

	/**
	 * Handler for the running upgrade actions
	 */
	function maybe_handle_action() {
		if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) !== $this->as3cf->get_plugin_slug() ) { // input var okay
			return;
		}

		if ( ! isset( $_GET['action'] ) ) {
			return;
		}

		if ( ! isset( $_GET['update'] ) || sanitize_key( $_GET['update'] ) !== $this->upgrade_name ) { // input var okay
			return;
		}

		$method_name = 'action_' . sanitize_key( $_GET['action'] ); // input var okay
		if ( method_exists( $this, $method_name ) ) {
			call_user_func( array( $this, $method_name ) );
		}
	}

	/**
	 * Exit upgrade with an error
	 *
	 * @param array $session
	 */
	function upgrade_error( $session ) {
		$session['status'] = self::STATUS_ERROR;
		$this->save_session( $session );
		$this->unschedule();
	}

	/**
	 * Complete the upgrade
	 */
	function upgrade_finished() {
		$this->clear_session();
		$this->update_saved_upgrade_id();
		$this->unschedule();
	}

	/**
	 * Restart upgrade
	 */
	function action_restart_update() {
		$this->schedule();
		$this->change_status_request( self::STATUS_RUNNING );
	}

	/**
	 * Pause upgrade
	 */
	function action_pause_update() {
		$this->unschedule();
		$this->change_status_request( self::STATUS_PAUSED );
	}

	/**
	 * Helper for the above action requests
	 *
	 * @param int $status
	 */
	function change_status_request( $status ) {
		$session = $this->get_session();
		$session['status'] = $status;
		$this->save_session( $session );

		$url = $this->as3cf->get_plugin_page_url( array(), 'self' );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Schedule the cron
	 */
	function schedule() {
		$this->as3cf->schedule_event( $this->cron_hook, $this->cron_schedule_key );
	}

	/**
	 * Remove the cron schedule
	 */
	function unschedule() {
		$this->as3cf->clear_scheduled_event( $this->cron_hook );
	}

	/**
	 * Add custom cron interval schedules
	 *
	 * @param array $schedules
	 *
	 * @return array
	 */
	function cron_schedules( $schedules ) {
		// Add the upgrade interval to the existing schedules.
		$schedules[ $this->cron_schedule_key ] = array(
			'interval' => $this->cron_interval_in_minutes * 60,
			'display'  => sprintf( __( 'Every %d Minutes', 'amazon-s3-and-cloudfront' ), $this->cron_interval_in_minutes ),
		);

		return $schedules;
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
		return get_site_option( 'update_' . $this->upgrade_name . '_session', array() );
	}

	/**
	 * Store data to be used between requests in plugin settings
	 *
	 * @param array $session session data to store
	 */
	function save_session( $session ) {
		update_site_option( 'update_' . $this->upgrade_name . '_session', $session );
	}

	/**
	 * Remove the session data to be used between requests
	 *
	 */
	function clear_session() {
		delete_site_option( 'update_' . $this->upgrade_name . '_session' );
	}

	/**
	 * Get the saved upgrade ID
	 *
	 * @return int|mixed|string|WP_Error
	 */
	function get_saved_upgrade_id() {
		return $this->as3cf->get_setting( $this->settings_key, 0 );
	}

	/**
	 * Update the saved upgrade ID
	 */
	function update_saved_upgrade_id() {
		$this->as3cf->set_setting( $this->settings_key, $this->upgrade_id );
		$this->as3cf->save_settings();
	}

	/**
	 * Has previous upgrade completed
	 *
	 * @return bool
	 */
	function has_previous_upgrade_completed() {
		// Has the previous upgrade completed yet?
		$previous_id = $this->upgrade_id - 1;
		if ( 0 !== $previous_id && (int) $this->get_saved_upgrade_id() < $previous_id ) {
			// Previous still running, abort
			return false;
		}

		return true;
	}
}
