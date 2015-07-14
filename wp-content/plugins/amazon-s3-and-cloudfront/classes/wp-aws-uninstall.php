<?php
/**
 * WP AWS Uninstall
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
if ( ! class_exists( 'WP_AWS_Uninstall' ) ) {

	/**
	 * WP_AWS_Uninstall Class
	 *
	 * This class handles shared functions for uninstalling AWS plugins
	 *
	 * @since 0.1
	 */
	class WP_AWS_Uninstall {

		/**
		 * @var array Options to be deleted
		 */
		protected $options;

		/**
		 * @var array Post meta to be deleted
		 */
		protected $postmeta;

		/**
		 * @var array Cron hooks to be unscheduled
		 */
		protected $crons;

		/**
		 * @var array Transients to be deleted
		 */
		protected $transients;

		/**
		 * @var Blog(s) in site
		 */
		protected $blog_ids;

		/**
		 * WP_AWS_Uninstall constructor.
		 *
		 * @param array $options
		 * @param array $postmeta
		 * @param array $crons
		 * @param array $transients
		 */
		public function __construct(
			$options = array(),
			$postmeta = array(),
			$crons = array(),
			$transients = array()
		) {
			$this->options    = $options;
			$this->postmeta   = $postmeta;
			$this->crons      = $crons;
			$this->transients = $transients;

			$this->set_blog_ids();

			$this->delete_options();
			$this->delete_postmeta();
			$this->clear_crons();
			$this->delete_transients();
		}

		/**
		 * Set the blog id(s) for a site
		 */
		private function set_blog_ids() {
			$blog_ids[] = 1;
			if ( function_exists( 'is_multisite' ) && is_multisite() ) {
				$args     = array(
					'limit'    => false,
					'spam'     => 0,
					'deleted'  => 0,
					'archived' => 0,
				);
				$blogs    = wp_get_sites( $args );
				$blog_ids = wp_list_pluck( $blogs, 'blog_id' );
			}

			$this->blog_ids = $blog_ids;
		}

		/**
		 * Check and ensure a property has been filled with an array
		 *
		 * @param string $property
		 *
		 * @return bool
		 */
		private function check_property( $property ) {
			if ( empty( $this->$property ) ) {
				return false;
			}

			if ( ! is_array( $this->$property ) ) {
				// Convert any strings to an array
				$this->$property = array( $this->$property );
			}

			return true;
		}

		/**
		 * Delete site wide options
		 */
		public function delete_options() {
			if ( ! $this->check_property( 'options' ) ) {
				return;
			}

			foreach ( $this->options as $option ) {
				delete_site_option( $option );
			}
		}

		/**
		 * Delete post meta data for all blogs
		 */
		public function delete_postmeta() {
			if ( ! $this->check_property( 'postmeta' ) ) {
				return;
			}

			global $wpdb;

			foreach ( $this->blog_ids as $blog_id ) {
				$prefix = $wpdb->get_blog_prefix( $blog_id );

				foreach ( $this->postmeta as $postmeta ) {
					$sql = $wpdb->prepare( "DELETE FROM {$prefix}postmeta WHERE meta_key = %s", $postmeta );
					$wpdb->query( $sql );
				}
			}
		}

		/**
		 * Clear any scheduled cron jobs
		 */
		public function clear_crons() {
			if ( ! $this->check_property( 'crons' ) ) {
				return;
			}

			foreach ( $this->crons as $cron ) {
				$timestamp = wp_next_scheduled( $cron );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, $cron );
				}
			}
		}

		/**
		 * Delete site wide transients
		 */
		public function delete_transients() {
			if ( ! $this->check_property( 'transients' ) ) {
				return;
			}

			foreach ( $this->transients as $transient ) {
				delete_site_transient( $transient );
			}
		}
	}
}