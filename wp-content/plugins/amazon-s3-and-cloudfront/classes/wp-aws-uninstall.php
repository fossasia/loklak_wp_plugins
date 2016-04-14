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
		 * @var array|string Options to be deleted
		 */
		protected $options;

		/**
		 * @var array|string Post meta to be deleted
		 */
		protected $postmeta;

		/**
		 * @var array|string Cron hooks to be unscheduled
		 */
		protected $crons;

		/**
		 * @var array|string Transients to be deleted, this can be site wide and subsite, e.g.
		 *
		 *      array(
		 *          'site'    => array(...),
		 *          'subsite' => array(...),
		 *      )
		 *
		 * By default, an array of transients will be treated as site wide.
		 *
		 */
		protected $transients;

		/**
		 * @var array|string User meta to be deleted
		 */
		protected $usermeta;

		/**
		 * @var array Blog(s) in site
		 */
		protected $blog_ids;

		/**
		 * WP_AWS_Uninstall constructor.
		 *
		 * @param array|string $options
		 * @param array|string $postmeta
		 * @param array|string $crons
		 * @param array|string $transients
		 * @param array|string $usermeta
		 */
		public function __construct(
			$options = array(),
			$postmeta = array(),
			$crons = array(),
			$transients = array(),
			$usermeta = array()
		) {
			$this->options    = $this->maybe_convert_to_array( $options );
			$this->postmeta   = $this->maybe_convert_to_array( $postmeta );
			$this->crons      = $this->maybe_convert_to_array( $crons );
			$this->transients = $this->maybe_convert_to_array( $transients );
			$this->usermeta   = $this->maybe_convert_to_array( $usermeta );

			$this->set_blog_ids();

			$this->delete_options();
			$this->delete_postmeta();
			$this->clear_crons();
			$this->delete_transients();
			$this->delete_usermeta();
		}

		/**
		 * Set the blog id(s) for a site
		 */
		private function set_blog_ids() {
			$blog_ids = array( 1 );
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
		 * Is the current blog ID that specified in wp-config.php
		 *
		 * @param int $blog_id
		 *
		 * @return bool
		 */
		private function is_current_blog( $blog_id ) {
			$default = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;

			if ( $default === $blog_id ) {
				return true;
			}

			return false;
		}

		/**
		 * Helper to ensure a value is an array
		 *
		 * @param array|string $data
		 *
		 * @return array
		 */
		private function maybe_convert_to_array( $data ) {
			if ( ! is_array( $data ) ) {
				// Convert a string to an array
				$data = array( $data );
			}

			return $data;
		}

		/**
		 * Delete site wide options
		 */
		public function delete_options() {
			foreach ( $this->options as $option ) {
				delete_site_option( $option );
			}
		}

		/**
		 * Delete post meta data for all blogs
		 */
		public function delete_postmeta() {
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
			foreach ( $this->crons as $cron ) {
				$timestamp = wp_next_scheduled( $cron );
				if ( $timestamp ) {
					wp_unschedule_event( $timestamp, $cron );
				}
			}
		}

		/**
		 * Delete transients
		 */
		public function delete_transients() {
			if ( ! isset( $this->transients['site'] ) && ! isset( $this->transients['subsite'] ) ) {
				// Single array of site wide transients
				foreach ( $this->transients as $transient ) {
					delete_site_transient( $transient );
				}

				return;
			}

			// Deal with site wide transients
			if ( isset( $this->transients['site'] ) ) {
				$site_transients = $this->maybe_convert_to_array( $this->transients['site'] );

				foreach ( $site_transients as $transient ) {
					delete_site_transient( $transient );
				}
			}

			// Deal with subsite specific transients
			if ( isset( $this->transients['subsite'] ) ) {
				$subsite_transients = $this->maybe_convert_to_array( $this->transients['subsite'] );

				foreach ( $this->blog_ids as $blog_id ) {
					if ( is_multisite() && $blog_id !== get_current_blog_id() ) {
						switch_to_blog( $blog_id );
					}

					foreach ( $subsite_transients as $transient ) {
						delete_transient( $transient );
					}

					if ( is_multisite() ) {
						restore_current_blog();
					}
				}
			}
		}

		/**
		 * Delete user meta.
		 */
		public function delete_usermeta() {
			global $wpdb;

			if ( empty( $this->usermeta ) ) {
				return;
			}

			// Loop through our user meta keys to create our WHERE clauses.
			$where_array = array();
			foreach ( $this->usermeta as $usermeta ) {
				$where_array[] = $wpdb->prepare( "meta_key = '%s'", $usermeta );
			}

			// Merge all WHERE clauses into an OR comparison.
			$where_sql = implode( ' OR ', $where_array );

			// Get any user ids that have keys to be deleted.
			$user_ids = $wpdb->get_col( "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE {$where_sql}" );

			// Bail if no user has keys to be deleted.
			if ( empty( $user_ids ) ) {
				return;
			}

			// Loop through the list of users and delete our user meta.
			foreach ( $user_ids as $user_id ) {
				foreach ( $this->usermeta as $usermeta ) {
					delete_user_meta( $user_id, $usermeta );
				}
			}
		}
	}
}