<?php

class Amazon_S3_And_CloudFront extends AWS_Plugin_Base {

	/**
	 * @var Amazon_Web_Services
	 */
	private $aws;

	/**
	 * @var Aws\S3\S3Client
	 */
	private $s3client;

	/**
	 * @var string
	 */
	protected $plugin_title;

	/**
	 * @var string
	 */
	protected $plugin_menu_title;

	/**
	 * @var array
	 */
	protected static $admin_notices = array();

	/**
	 * @var
	 */
	protected static $plugin_page;

	/**
	 * @var string
	 */
	protected $plugin_prefix = 'as3cf';

	/**
	 * @var string
	 */
	protected $default_tab = '';

	/**
	 * @var string
	 */
	public $hook_suffix;

	/**
	 * @var array Store if each bucket, used by the plugin and addons, is writable
	 */
	protected static $buckets_check = array();

	const DEFAULT_ACL = 'public-read';
	const PRIVATE_ACL = 'private';
	const DEFAULT_EXPIRES = 900;
	const DEFAULT_REGION = 'us-east-1';

	const SETTINGS_KEY = 'tantan_wordpress_s3';

	/**
	 * @param string              $plugin_file_path
	 * @param Amazon_Web_Services $aws
	 * @param string|null         $slug
	 */
	function __construct( $plugin_file_path, $aws, $slug = null ) {
		$this->plugin_slug = ( is_null( $slug ) ) ? 'amazon-s3-and-cloudfront' : $slug;

		parent::__construct( $plugin_file_path );

		$this->aws = $aws;

		$this->init( $plugin_file_path );
	}

	/**
	 * Abstract class constructor
	 *
	 * @param string $plugin_file_path
	 */
	function init( $plugin_file_path ) {
		self::$plugin_page       = $this->plugin_slug;
		$this->plugin_title      = __( 'Offload S3', 'as3cf' );
		$this->plugin_menu_title = __( 'S3 and CloudFront', 'as3cf' );

		new AS3CF_Upgrade_Region_Meta( $this );
		new AS3CF_Upgrade_File_Sizes( $this );
		new AS3CF_Upgrade_Meta_WP_Error( $this );

		add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_as3cf-get-buckets', array( $this, 'ajax_get_buckets' ) );
		add_action( 'wp_ajax_as3cf-save-bucket', array( $this, 'ajax_save_bucket' ) );
		add_action( 'wp_ajax_as3cf-create-bucket', array( $this, 'ajax_create_bucket' ) );
		add_action( 'wp_ajax_as3cf-manual-save-bucket', array( $this, 'ajax_save_bucket' ) );
		add_action( 'wp_ajax_as3cf-get-url-preview', array( $this, 'ajax_get_url_preview' ) );

		// Admin notices
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'maybe_show_admin_notices' ) );
		add_action( 'shutdown', array( $this, 'save_admin_notices' ) );

		add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 99, 2 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'wp_handle_upload_prefilter' ), 1 );
		add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 110, 2 );
		add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 10, 2 );
		add_filter( 'delete_attachment', array( $this, 'delete_attachment' ), 20 );
		add_filter( 'update_attached_file', array( $this, 'update_attached_file' ), 100, 2 );
		add_filter( 'get_attached_file', array( $this, 'get_attached_file' ), 10, 2 );
		add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2 );
		add_filter( 'pre_get_space_used', array( $this, 'multisite_get_spaced_used' ) );

		// include compatibility code for other plugins
		new AS3CF_Plugin_Compatibility( $this );

		load_plugin_textdomain( 'as3cf', false, dirname( plugin_basename( $plugin_file_path ) ) . '/languages/' );

		// Register modal scripts and styles
		$this->register_modal_assets();
	}

	/**
	 * Get the plugin title to be used in page headings
	 *
	 * @return string
	 */
	function get_plugin_page_title() {
		return apply_filters( 'as3cf_settings_page_title', $this->plugin_title );
	}

	/**
	 * Get the plugin prefix in slug format, ie. replace underscores with hyphens
	 *
	 * @return string
	 */
	function get_plugin_prefix_slug() {
		return str_replace( '_', '-', $this->plugin_prefix );
	}

	/**
	 * Get the nonce key for the settings form of the plugin
	 *
	 * @return string
	 */
	function get_settings_nonce_key() {
		return $this->get_plugin_prefix_slug() . '-save-settings';
	}

	/**
	 * Accessor for a plugin setting with conditions to defaults and upgrades
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return int|mixed|string|WP_Error
	 */
	function get_setting( $key, $default = '' ) {
		// use settings from $_POST when generating URL preview via AJAX
		if ( isset( $_POST['action'] ) && 'as3cf-get-url-preview' == sanitize_key( $_POST['action'] ) ) { // input var okay
			$value = 0;
			if ( isset( $_POST[ $key ] ) ) { // input var okay
				$value = $_POST[ $key ]; // input var okay
				if ( is_array( $value ) ) {
					// checkbox is checked
					$value = 1;
				}
			}

			return $value;
		}

		$settings = $this->get_settings();

		// If legacy setting set, migrate settings
		if ( isset( $settings['wp-uploads'] ) && $settings['wp-uploads'] && in_array( $key, array( 'copy-to-s3', 'serve-from-s3' ) ) ) {
			return '1';
		}

		// Turn on object versioning by default
		if ( 'object-versioning' == $key && ! isset( $settings['object-versioning'] ) ) {
			return '1';
		}

		// Default object prefix
		if ( 'object-prefix' == $key && ! isset( $settings['object-prefix'] ) ) {
			return $this->get_default_object_prefix();
		}

		// Default use year and month folders
		if ( 'use-yearmonth-folders' == $key && ! isset( $settings['use-yearmonth-folders'] ) ) {
			return get_option( 'uploads_use_yearmonth_folders' );
		}

		// Default enable object prefix - enabled unless path is empty
		if ( 'enable-object-prefix' == $key ) {
			if ( isset( $settings['enable-object-prefix'] ) && '0' == $settings['enable-object-prefix'] ) {
				return 0;
			}

			if ( isset( $settings['object-prefix'] ) && '' == trim( $settings['object-prefix'] ) ) {
				return 0;
			} else {
				return 1;
			}
		}

		// Region of bucket if not already retrieved
		if ( 'region' == $key && ! isset( $settings['region'] ) ) {
			$bucket = $this->get_setting( 'bucket' );
			$region = $default;
			if ( $bucket ) {
				$region = $this->get_bucket_region( $bucket );
			}

			// Store the region for future use
			parent::set_setting( 'region', $region );
			$this->save_settings();

			return $region;
		}

		// Region of bucket translation
		if ( 'region' == $key && isset( $settings['region'] ) ) {

			return $this->translate_region( $settings['region'] );
		}

		// Domain setting since 0.8
		if ( 'domain' == $key && ! isset( $settings['domain'] ) ) {
			if ( $this->get_setting( 'cloudfront' ) ) {
				$domain = 'cloudfront';
			} elseif ( $this->get_setting( 'virtual-host' ) ) {
				$domain = 'virtual-host';
			} elseif ( $this->use_ssl() ) {
				$domain = 'path';
			} else {
				$domain = 'subdomain';
			}

			return $domain;
		}

		// SSL radio buttons since 0.8
		if ( 'ssl' == $key && ! isset( $settings['ssl'] ) ) {
			if ( $this->get_setting( 'force-ssl', false ) ) {
				$ssl = 'https';
			} else {
				$ssl = 'request';
			}

			return $ssl;
		}

		$value = parent::get_setting( $key, $default );

		if ( 'bucket' == $key && defined( 'AS3CF_BUCKET' ) ) {
			$bucket = AS3CF_BUCKET;

			if ( $bucket !== $value ) {
				// Save the defined bucket
				parent::set_setting( 'bucket', $bucket );
				// Clear region
				$this->remove_setting( 'region' );
				$this->save_settings();
			}

			return $bucket;
		}

		return apply_filters( 'as3cf_setting_' . $key, $value );
	}

	/**
	 * Setter for a plugin setting with custom hooks
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	function set_setting( $key, $value ) {
		// Run class specific hooks before the setting is saved
		$this->pre_set_setting( $key, $value );

		$value = apply_filters( 'as3cf_set_setting_' . $key, $value );

		parent::set_setting( $key, $value );
	}

	/**
	 * Return the default object prefix
	 *
	 * @return string
	 */
	function get_default_object_prefix() {
		if ( is_multisite() ) {
			return 'wp-content/uploads/';
		}

		$uploads = wp_upload_dir();
		$parts   = parse_url( $uploads['baseurl'] );
		$path    = ltrim( $parts['path'], '/' );

		return trailingslashit( $path );
	}

	/**
	 * Allowed mime types array that can be edited for specific S3 uploading
	 *
	 * @return array
	 */
	function get_allowed_mime_types() {
		return apply_filters( 'as3cf_allowed_mime_types', get_allowed_mime_types() );
	}

	/**
	 * Wrapper for scheduling  cron jobs
	 *
	 * @param string      $hook
	 * @param null|string $interval Defaults to hook if not supplied
	 * @param array       $args
	 */
	function schedule_event( $hook, $interval = null, $args = array() ) {
		if ( is_null( $interval ) ) {
			$interval = $hook;
		}
		if ( ! wp_next_scheduled( $hook ) ) {
			wp_schedule_event( current_time( 'timestamp' ), $interval, $hook, $args );
		}
	}

	/**
	 * Wrapper for clearing scheduled events for a specific cron job
	 *
	 * @param string $hook
	 */
	function clear_scheduled_event( $hook ) {
		$timestamp = wp_next_scheduled( $hook );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, $hook );
		}
	}

	/**
	 * Generate a preview of the URL of files uploaded to S3
	 *
	 * @param bool $escape
	 * @param string $suffix
	 *
	 * @return string
	 */
	function get_url_preview( $escape = true, $suffix = 'photo.jpg' ) {
		$scheme = $this->get_s3_url_scheme();
		$bucket = $this->get_setting( 'bucket' );
		$path   = $this->get_file_prefix();
		$region = $this->get_setting( 'region' );
		if ( is_wp_error( $region ) ) {
			$region = '';
		}
		$domain = $this->get_s3_url_domain( $bucket, $region );

		$url = $scheme . '://' . $domain . '/' . $path . $suffix;

		// replace hyphens with non breaking hyphens for formatting
		if ( $escape ) {
			$url = str_replace( '-', '&#8209;', $url );
		}

		return $url;
	}

	/**
	 * AJAX handler for get_url_preview()
	 */
	function ajax_get_url_preview() {
		$this->verify_ajax_request();

		$url = $this->get_url_preview();

		$out = array(
			'success' => '1',
			'url'     => $url,
		);

		$this->end_ajax( $out );
	}

	/**
	 * Delete bulk objects from an S3 bucket
	 *
	 * @param string $region
	 * @param string $bucket
	 * @param array  $objects
	 * @param bool   $log_error
	 * @param bool   $return_on_error
	 * @param bool   $force_new_s3_client if we are deleting in bulk, force new S3 client
	 *                                    to cope with possible different regions
	 */
	function delete_s3_objects( $region, $bucket, $objects, $log_error = false, $return_on_error = false, $force_new_s3_client = false ) {
		try {
			$this->get_s3client( $region, $force_new_s3_client )->deleteObjects( array(
				'Bucket'  => $bucket,
				'Objects' => $objects,
			) );
		} catch ( Exception $e ) {
			if ( $log_error ) {
				error_log( 'Error removing files from S3: ' . $e->getMessage() );
			}
			if ( $return_on_error ) {
				return;
			}
		}
	}

	/**
	 * Removes an attachment's files from S3.
	 *
	 * @param int   $post_id
	 * @param array $s3object
	 * @param bool  $remove_backup_sizes       remove previous edited image versions
	 * @param bool  $log_error
	 * @param bool  $return_on_error
	 * @param bool  $force_new_s3_client       if we are deleting in bulk, force new S3 client
	 *                                         to cope with possible different regions
	 */
	function remove_attachment_files_from_s3( $post_id, $s3object, $remove_backup_sizes = true, $log_error = false, $return_on_error = false, $force_new_s3_client = false  ) {
		$prefix = trailingslashit( dirname( $s3object['key'] ) );
		$bucket = $s3object['bucket'];
		$region = $this->get_s3object_region( $s3object );
		$paths  = $this->get_attachment_file_paths( $post_id, false, false, $remove_backup_sizes );

		if ( is_wp_error( $region ) ) {
			$region = '';
		}

		$objects_to_remove = array();

		foreach ( $paths as $path ) {
			$objects_to_remove[] = array(
				'Key' => $prefix . basename( $path ),
			);
		}

		// finally delete the objects from S3
		$this->delete_s3_objects( $region, $bucket, $objects_to_remove, $log_error, $return_on_error, $force_new_s3_client );
	}

	/**
	 * Removes an attachment and intermediate image size files from S3
	 *
	 * @param int  $post_id
	 * @param bool $force_new_s3_client if we are deleting in bulk, force new S3 client
	 *                                  to cope with possible different regions
	 */
	function delete_attachment( $post_id, $force_new_s3_client = false  ) {
		if ( ! $this->is_plugin_setup() ) {
			return;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $post_id ) ) ) {
			return;
		}

		$this->remove_attachment_files_from_s3( $post_id, $s3object, true, true, true, $force_new_s3_client );

		delete_post_meta( $post_id, 'amazonS3_info' );
	}

	/**
	 * Handles the upload of the attachment to S3 when an attachment is updated using
	 * the 'wp_update_attachment_metadata' filter
	 *
	 * @param array $data meta data for attachment
	 * @param int   $post_id
	 *
	 * @return array
	 */
	function wp_update_attachment_metadata( $data, $post_id ) {
		if ( ! $this->is_plugin_setup() ) {
			return $data;
		}

		if ( ! ( $old_s3object = $this->get_attachment_s3_info( $post_id ) ) && ! $this->get_setting( 'copy-to-s3' ) ) {
			// abort if not already uploaded to S3 and the copy setting is off
			return $data;
		}

		// allow S3 upload to be cancelled for any reason
		$pre = apply_filters( 'as3cf_pre_update_attachment_metadata', false, $data, $post_id, $old_s3object );
		if ( false !== $pre ) {
			return $data;
		}

		// upload attachment to S3
		$this->upload_attachment_to_s3( $post_id, $data );

		return $data;
	}

	/**
	 * Upload attachment to S3
	 *
	 * @param int         $post_id
	 * @param array|null  $data
	 * @param string|null $file_path
	 * @param bool        $force_new_s3_client if we are uploading in bulk, force new S3 client
	 *                                         to cope with possible different regions
	 * @param bool        $remove_local_files
	 *
	 * @return array|WP_Error $s3object|$meta If meta is supplied, return it. Else return S3 meta
	 */
	function upload_attachment_to_s3( $post_id, $data = null, $file_path = null, $force_new_s3_client = false, $remove_local_files = true ) {
		if ( is_null( $data ) ) {
			$data = wp_get_attachment_metadata( $post_id, true );
		}

		if ( is_null( $file_path ) ) {
			$file_path = get_attached_file( $post_id, true );
		}

		// Check file exists locally before attempting upload
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'exception', sprintf( __( 'File %s does not exist', 'as3cf' ), $file_path ) );
		}

		$file_name     = basename( $file_path );
		$type          = get_post_mime_type( $post_id );
		$allowed_types = $this->get_allowed_mime_types();

		// check mime type of file is in allowed S3 mime types
		if ( ! in_array( $type, $allowed_types ) ) {
			return new WP_Error( 'exception', sprintf( __( 'Mime type %s is not allowed', 'as3cf' ), $type ) );
		}

		$acl = self::DEFAULT_ACL;

		// check the attachment already exists in S3, eg. edit or restore image
		if ( ( $old_s3object = $this->get_attachment_s3_info( $post_id ) ) ) {
			// use existing non default ACL if attachment already exists
			if ( isset( $old_s3object['acl'] ) ) {
				$acl = $old_s3object['acl'];
			}
			// use existing prefix
			$prefix = trailingslashit( dirname( $old_s3object['key'] ) );
			// use existing bucket
			$bucket = $old_s3object['bucket'];
			// get existing region
			if ( isset( $old_s3object['region'] ) ) {
				$region = $old_s3object['region'];
			};
		} else {
			// derive prefix from various settings
			if ( isset( $data['file'] ) ) {
				$time = $this->get_folder_time_from_url( $data['file'] );
			} else {
				$time = $this->get_attachment_folder_time( $post_id );
				$time = date( 'Y/m', $time );
			}

			$prefix = $this->get_file_prefix( $time, $post_id );

			// use bucket from settings
			$bucket = $this->get_setting( 'bucket' );
			$region = $this->get_setting( 'region' );
			if ( is_wp_error( $region ) ) {
				$region = '';
			}
		}

		$acl = apply_filters( 'wps3_upload_acl', $acl, $type, $data, $post_id, $this ); // Old naming convention, will be deprecated soon
		$acl = apply_filters( 'as3cf_upload_acl', $acl, $data, $post_id );

		$s3object = array(
			'bucket' => $bucket,
			'key'    => $prefix . $file_name,
			'region' => $region,
		);

		// store acl if not default
		if ( $acl != self::DEFAULT_ACL ) {
			$s3object['acl'] = $acl;
		}

		$s3client = $this->get_s3client( $region, $force_new_s3_client );

		$args = array(
			'Bucket'     => $bucket,
			'Key'        => $prefix . $file_name,
			'SourceFile' => $file_path,
			'ACL'        => $acl,
		);

		// If far future expiration checked (10 years)
		if ( $this->get_setting( 'expires' ) ) {
			$args['Expires'] = date( 'D, d M Y H:i:s O', time() + 315360000 );
		}
		$args = apply_filters( 'as3cf_object_meta', $args, $post_id );

		do_action( 'as3cf_upload_attachment_pre_remove', $post_id, $s3object, $prefix, $args );

		$files_to_remove = array();

		if ( file_exists( $file_path ) ) {
			$files_to_remove[] = $file_path;
			try {
				$s3client->putObject( $args );
			}
			catch ( Exception $e ) {
				$error_msg = sprintf( __( 'Error uploading %s to S3: %s', 'as3cf' ), $file_path, $e->getMessage() );
				error_log( $error_msg );

				return new WP_Error( 'exception', $error_msg );
			}
		}

		delete_post_meta( $post_id, 'amazonS3_info' );

		add_post_meta( $post_id, 'amazonS3_info', $s3object );

		$file_paths        = $this->get_attachment_file_paths( $post_id, true, $data );
		$additional_images = array();

		$filesize_total = 0;
		$remove_local_files_setting = $this->get_setting( 'remove-local-file' );

		if ( $remove_local_files_setting ) {
			$bytes = filesize( $file_path );
			if ( false !== $bytes ) {
				// Store in the attachment meta data for use by WP
				$data['filesize'] = $bytes;

				// Update metadata with filesize
				update_post_meta( $post_id, '_wp_attachment_metadata', $data );

				// Add to the file size total
				$filesize_total += $bytes;
			}
		}

		foreach ( $file_paths as $file_path ) {
			if ( ! in_array( $file_path, $files_to_remove ) ) {
				$additional_images[] = array(
					'Key'        => $prefix . basename( $file_path ),
					'SourceFile' => $file_path,
				);

				$files_to_remove[] = $file_path;

				if ( $remove_local_files_setting ) {
					// Record the file size for the additional image
					$bytes = filesize( $file_path );
					if ( false !== $bytes ) {
						$filesize_total += $bytes;
					}
				}
			}
		}

		foreach ( $additional_images as $image ) {
			try {
				$args = array_merge( $args, $image );
				$args['ACL'] = self::DEFAULT_ACL;
				$s3client->putObject( $args );
			}
			catch ( Exception $e ) {
				error_log( 'Error uploading ' . $args['SourceFile'] . ' to S3: ' . $e->getMessage() );
			}
		}

		if ( $remove_local_files ) {
			if ( $remove_local_files_setting ) {
				// Allow other functions to remove files after they have processed
				$files_to_remove = apply_filters( 'as3cf_upload_attachment_local_files_to_remove', $files_to_remove, $post_id, $file_path );
				// Remove duplicates
				$files_to_remove = array_unique( $files_to_remove );

				// Delete the files
				$this->remove_local_files( $files_to_remove );
			}
		}

		// Store the file size in the attachment meta if we are removing local file
		if ( $remove_local_files_setting ) {
			if ( $filesize_total > 0 ) {
				// Add the total file size for all image sizes
				update_post_meta( $post_id, 'wpos3_filesize_total', $filesize_total );
			}
		} else {
			if ( isset( $data['filesize'] ) ) {
				// Make sure we don't have a cached file sizes in the meta
				unset( $data['filesize'] );

				// Remove the filesize from the metadata
				update_post_meta( $post_id, '_wp_attachment_metadata', $data );

				delete_post_meta( $post_id, 'wpos3_filesize_total' );
			}
		}

		return $s3object;
	}

	/**
	 * Remove files from the local site
	 *
	 * @param array $file_paths array of files to remove
	 */
	function remove_local_files( $file_paths ) {
		foreach ( $file_paths as $path ) {
			if ( ! @unlink( $path ) ) {
				error_log( 'Error removing local file ' . $path );
			}
		}
	}

	function get_hidpi_file_path( $orig_path ) {
		$hidpi_suffix = apply_filters( 'as3cf_hidpi_suffix', '@2x' );
		$pathinfo     = pathinfo( $orig_path );

		return $pathinfo['dirname'] . '/' . $pathinfo['filename'] . $hidpi_suffix . '.' . $pathinfo['extension'];
	}

	/**
	 * Get the object versioning string prefix
	 *
	 * @param int $post_id
	 *
	 * @return string
	 */
	function get_object_version_string( $post_id ) {
		if ( $this->get_setting( 'use-yearmonth-folders' ) ) {
			$date_format = 'dHis';
		} else {
			$date_format = 'YmdHis';
		}

		$time = $this->get_attachment_folder_time( $post_id );

		$object_version = date( $date_format, $time ) . '/';
		$object_version = apply_filters( 'as3cf_get_object_version_string', $object_version );

		return $object_version;
	}

	/**
	 * Get the upload folder time from given URL
	 *
	 * @param string $url
	 *
	 * @return null|string
	 */
	function get_folder_time_from_url( $url ) {
		preg_match( '@[0-9]{4}/[0-9]{2}@', $url, $matches );

		if ( isset( $matches[0] ) ) {
			return $matches[0];
		}

		return null;
	}

	/**
	 * Get the time of attachment upload.
	 *
	 * Use post datetime if attached.
	 *
	 * @param int $post_id
	 *
	 * @return int|string
	 */
	function get_attachment_folder_time( $post_id ) {
		$time = current_time( 'timestamp' );

		if ( ! ( $attach = get_post( $post_id ) ) ) {
			return $time;
		}

		if ( ! $attach->post_parent ) {
			return $time;
		}

		if ( ! ( $post = get_post( $attach->post_parent ) ) ) {
			return $time;
		}

		if ( substr( $post->post_date_gmt, 0, 4 ) > 0 ) {
			return strtotime( $post->post_date_gmt . ' +0000' );
		}

		return $time;
	}

	/**
	 * Create unique names for file to be uploaded to AWS
	 * This only applies when the remove local file option is enabled
	 *
	 * @param array   $file An array of data for a single file.
	 *
	 * @return array $file The altered file array with AWS unique filename.
	 */
	function wp_handle_upload_prefilter( $file ) {
		if ( ! $this->get_setting( 'copy-to-s3' ) || ! $this->is_plugin_setup() ) {
			return $file;
		}

		// only do this when we are removing local versions of files
		if ( ! $this->get_setting( 'remove-local-file' ) ) {
			return $file;
		}

		$filename = $file['name'];

		// sanitize the file name before we begin processing
		$filename = sanitize_file_name( $filename );

		// separate the filename into a name and extension
		$info = pathinfo( $filename );
		$ext  = ! empty( $info['extension'] ) ? '.' . $info['extension'] : '';
		$name = basename( $filename, $ext );

		// edge case: if file is named '.ext', treat as an empty name
		if ( $name === $ext ) {
			$name = '';
		}

		// rebuild filename with lowercase extension as S3 will have converted extension on upload
		$ext      = strtolower( $ext );
		$filename = $info['filename'] . $ext;

		$time = current_time( 'timestamp' );
		$time = date( 'Y/m', $time );

		$prefix = ltrim( trailingslashit( $this->get_object_prefix() ), '/' );
		$prefix .= ltrim( trailingslashit( $this->get_dynamic_prefix( $time ) ), '/' );

		$bucket = $this->get_setting( 'bucket' );
		$region = $this->get_setting( 'region' );
		if ( is_wp_error( $region ) ) {
			return $file;
		}

		$s3client = $this->get_s3client( $region );

		$number = '';
		while ( $s3client->doesObjectExist( $bucket, $prefix . $filename ) !== false ) {
			$previous = $number;
			++$number;
			if ( '' == $previous ) {
				$filename = $name . $number . $ext;
			} else {
				$filename = str_replace( "$previous$ext", $number . $ext, $filename );
			}
		}

		$file['name'] = $filename;

		return $file;
	}

	function wp_get_attachment_url( $url, $post_id ) {
		$new_url = $this->get_attachment_url( $post_id );
		if ( false === $new_url ) {
			return $url;
		}

		$new_url = apply_filters( 'wps3_get_attachment_url', $new_url, $post_id, $this ); // Old naming convention, will be deprecated soon
		$new_url = apply_filters( 'as3cf_wp_get_attachment_url', $new_url, $post_id );

		return $new_url;
	}

	function get_attachment_s3_info( $post_id ) {
		return get_post_meta( $post_id, 'amazonS3_info', true );
	}

	/**
	 * Check the plugin is correctly setup
	 *
	 * @return bool
	 */
	function is_plugin_setup() {
		if ( is_wp_error( $this->aws->get_client() ) ) {
			// AWS not configured
			return false;
		}

		if ( false === (bool) $this->get_setting( 'bucket' ) ) {
			// No bucket selected
			return false;
		}

		if ( is_wp_error( $this->get_setting( 'region' ) ) ) {
			// Region error when retrieving bucket location
			return false;
		}

		// All good, let's do this
		return true;
	}

	/**
	 * Generate a link to download a file from Amazon S3 using query string
	 * authentication. This link is only valid for a limited amount of time.
	 *
	 * @param int         $post_id Post ID of the attachment
	 * @param int|null    $expires Seconds for the link to live
	 * @param string|null $size    Size of the image to get
	 * @param array       $headers Header overrides for request
	 *
	 * @return mixed|void|WP_Error
	 */
	function get_secure_attachment_url( $post_id, $expires = null, $size = null, $headers = array() ) {
		if ( is_null( $expires ) ) {
			$expires = self::DEFAULT_EXPIRES;
		}
		return $this->get_attachment_url( $post_id, $expires, $size, null, $headers );
	}

	/**
	 * Return the scheme to be used in URLs
	 *
	 * @param string|null $ssl
	 *
	 * @return string
	 */
	function get_s3_url_scheme( $ssl = null ) {
		if ( $this->use_ssl( $ssl ) ) {
			$scheme = 'https';
		}
		else {
			$scheme = 'http';
		}

		return $scheme;
	}

	/**
	 * Determine when to use https in URLS
	 *
	 * @param string|null $ssl
	 *
	 * @return bool
	 */
	function use_ssl( $ssl = null ) {
		$use_ssl = false;

		if ( is_null( $ssl ) ) {
			$ssl = $this->get_setting( 'ssl' );
		}

		if ( 'request' == $ssl && is_ssl() ) {
			$use_ssl = true;
		} else if ( 'https' == $ssl ) {
			$use_ssl = true;
		}

		return apply_filters( 'as3cf_use_ssl', $use_ssl );
	}

	/**
	 * Get the custom object prefix if enabled
	 *
	 * @return string
	 */
	function get_object_prefix() {
		if ( $this->get_setting( 'enable-object-prefix' ) ) {
			$prefix = trim( $this->get_setting( 'object-prefix' ) );
		} else {
			$prefix = '';
		}

		return $prefix;
	}

	/**
	 * Get the file prefix
	 *
	 * @param null|string $time
	 * @param null|int $post_id
	 *
	 * @return string
	 */
	function get_file_prefix( $time = null, $post_id = null ) {
		$prefix = ltrim( trailingslashit( $this->get_object_prefix() ), '/' );
		$prefix .= ltrim( trailingslashit( $this->get_dynamic_prefix( $time ) ), '/' );

		if ( $this->get_setting( 'object-versioning' ) ) {
			$prefix .= $this->get_object_version_string( $post_id );
		}

		return $prefix;
	}

	/**
	 * Get the region specific prefix for S3 URL
	 *
	 * @param string   $region
	 * @param null|int $expires
	 *
	 * @return string
	 */
	function get_s3_url_prefix( $region = '', $expires = null ) {
		$prefix = 's3';

		if ( '' !== $region ) {
			$delimiter = '-';
			if ( 'eu-central-1' == $region && ! is_null( $expires ) ) {
				// if we are creating a secure URL for a Frankfurt base file use the alternative delimiter
				// http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
				$delimiter = '.';
			}

			$prefix .= $delimiter . $region;
		}

		return $prefix;
	}

	/**
	 * Get the S3 url for the files
	 *
	 * @param string $bucket
	 * @param string $region
	 * @param int    $expires
	 * @param array  $args    Allows you to specify custom URL settings
	 *
	 * @return mixed|string|void
	 */
	function get_s3_url_domain( $bucket, $region = '', $expires = null, $args = array() ) {
		if ( ! isset( $args['cloudfront'] ) ) {
			$args['cloudfront'] = $this->get_setting( 'cloudfront' );
		}

		if ( ! isset( $args['domain'] ) ) {
			$args['domain'] = $this->get_setting( 'domain' );
		}

		if ( ! isset( $args['ssl'] ) ) {
			$args['ssl'] = $this->get_setting( 'ssl' );
		}

		$prefix = $this->get_s3_url_prefix( $region, $expires );

		if ( 'cloudfront' === $args['domain'] && is_null( $expires ) && $args['cloudfront'] ) {
			$s3_domain = $args['cloudfront'];
		}
		elseif ( 'virtual-host' === $args['domain'] ) {
			$s3_domain = $bucket;
		}
		elseif ( 'path' === $args['domain'] || $this->use_ssl( $args['ssl'] ) ) {
			$s3_domain = $prefix . '.amazonaws.com/' . $bucket;
		}
		else {
			$s3_domain = $bucket . '.' . $prefix . '.amazonaws.com';
		}

		return $s3_domain;
	}

	/**
	 * Get the url of the file from Amazon S3
	 *
	 * @param int         $post_id Post ID of the attachment
	 * @param int|null    $expires Seconds for the link to live
	 * @param string|null $size    Size of the image to get
	 * @param array|null  $meta    Pre retrieved _wp_attachment_metadata for the attachment
	 * @param array       $headers Header overrides for request
	 *
	 * @return bool|mixed|void|WP_Error
	 */
	function get_attachment_url( $post_id, $expires = null, $size = null, $meta = null, $headers = array() ) {
		if ( ! $this->get_setting( 'serve-from-s3' ) ) {
			return false;
		}

		// check that the file has been uploaded to S3
		if ( ! ( $s3object = $this->get_attachment_s3_info( $post_id ) ) ) {
			return false;
		}

		$scheme = $this->get_s3_url_scheme();

		// We don't use $this->get_s3object_region() here because we don't want
		// to make an AWS API call and slow down page loading
		if ( isset( $s3object['region'] ) && self::DEFAULT_REGION !== $s3object['region'] ) {
			$region = $this->translate_region( $s3object['region'] );
		} else {
			$region = '';
		}

		// force use of secured url when ACL has been set to private
		if ( is_null( $expires ) && isset( $s3object['acl'] ) && self::PRIVATE_ACL == $s3object['acl'] ) {
			$expires = self::DEFAULT_EXPIRES;
		}

		$domain_bucket = $this->get_s3_url_domain( $s3object['bucket'], $region, $expires );

		if ( ! is_null( $size ) ) {
			if ( is_null( $meta ) ) {
				$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
			}
			if ( isset( $meta['sizes'][ $size ]['file'] ) ) {
				$s3object['key'] = dirname( $s3object['key'] ) . '/' . $meta['sizes'][ $size ]['file'];
			}
		}

		if ( ! is_null( $expires ) ) {
			try {
				$expires    = time() + $expires;
				$secure_url = $this->get_s3client( $region )->getObjectUrl( $s3object['bucket'], $s3object['key'], $expires, $headers );

				return apply_filters( 'as3cf_get_attachment_secure_url', $secure_url, $s3object, $post_id, $expires, $headers );
			}
			catch ( Exception $e ) {
				return new WP_Error( 'exception', $e->getMessage() );
			}
		}

		$file = $this->encode_filename_in_path( $s3object['key'] );
		$url  = $scheme . '://' . $domain_bucket . '/' . $file;

		return apply_filters( 'as3cf_get_attachment_url', $url, $s3object, $post_id, $expires, $headers );
	}

	/**
	 * Override the attachment metadata
	 *
	 * @param unknown $data
	 * @param unknown $post_id
	 *
	 * @return mixed
	 */
	function wp_get_attachment_metadata( $data, $post_id ) {
		return $this->maybe_encoded_file_of_resized_images( $data, $post_id );
	}

	/**
	 * Encodes the file names for resized image files for an attachment where necessary
	 *
	 * @param array $data
	 * @param int   $post_id
	 *
	 * @return mixed Attachment meta data
	 */
	function maybe_encoded_file_of_resized_images( $data, $post_id ) {
		if ( ! $this->get_setting( 'serve-from-s3' ) ) {
			return $data;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $post_id ) ) ) {
			return $data;
		}

		// we only need to encode the file name if url encoding is needed
		$filename = basename( $s3object['key'] );
		if ( $filename == rawurlencode( $filename ) ) {
			return $data;
		}

		// we only need to encode resized image files
		if ( ! isset( $data['sizes'] ) ) {
			return $data;
		}

		foreach ( $data['sizes'] as $key => $size ) {
			$data['sizes'][ $key ]['file'] = $this->encode_filename_in_path( $data['sizes'][ $key ]['file'] );
		}

		return $data;
	}

	/**
	 * Encode file names according to RFC 3986 when generating urls
	 * As per Amazon https://forums.aws.amazon.com/thread.jspa?threadID=55746#jive-message-244233
	 *
	 * @param string $file
	 *
	 * @return string Encoded filename with path prefix untouched
	 */
	function encode_filename_in_path( $file ) {
		$file_path = dirname( $file );
		$file_path = ( '.' != $file_path ) ? trailingslashit( $file_path ) : '';
		$file_name = rawurlencode( basename( $file ) );

		return $file_path . $file_name;
	}

	/**
	 * Allow processes to update the file on S3 via update_attached_file()
	 *
	 * @param string $file
	 * @param int $attachment_id
	 *
	 * @return string
	 */
	function update_attached_file( $file, $attachment_id ) {
		if ( ! $this->is_plugin_setup() ) {
			return $file;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $attachment_id ) ) ) {
			return $file;
		}

		$file = apply_filters( 'as3cf_update_attached_file', $file, $attachment_id, $s3object );

		return $file;
	}

	/**
	 * Return the S3 URL when the local file is missing
	 * unless we know the calling process is and we are happy
	 * to copy the file back to the server to be used
	 *
	 * @param string $file
	 * @param int    $attachment_id
	 *
	 * @return string
	 */
	function get_attached_file( $file, $attachment_id ) {
		if ( file_exists( $file ) || ! $this->get_setting( 'serve-from-s3' ) ) {
			return $file;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $attachment_id ) ) ) {
			return $file;
		}

		$url = $this->get_attachment_url( $attachment_id );

		// return the URL by default
		$file = apply_filters( 'as3cf_get_attached_file', $url, $file, $attachment_id, $s3object );

		return $file;
	}

	/**
	 * Helper method for returning data to AJAX call
	 *
	 * @param array $return
	 */
	function end_ajax( $return = array() ) {
		echo json_encode( $return );
		exit;
	}

	function verify_ajax_request() {
		if ( ! is_admin() || ! wp_verify_nonce( sanitize_key( $_POST['_nonce'] ), sanitize_key( $_POST['action'] ) ) ) { // input var okay
			wp_die( __( 'Cheatin&#8217; eh?', 'as3cf' ) );
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'as3cf' ) );
		}
	}

	function ajax_check_bucket() {
		if ( ! isset( $_POST['bucket_name'] ) || ! ( $bucket = sanitize_text_field( $_POST['bucket_name'] ) ) ) { // input var okay
			$out = array( 'error' => __( 'No bucket name provided.', 'as3cf' ) );

			$this->end_ajax( $out );
		}

		return strtolower( $bucket );
	}

	/**
	 * Handler for AJAX callback to create a bucket in S3
	 */
	function ajax_create_bucket() {
		$this->verify_ajax_request();

		$bucket = $this->ajax_check_bucket();

		if ( defined( 'AS3CF_REGION' ) ) {
			// Are we defining the region?
			$region = AS3CF_REGION;
		} else {
			// Are we specifying the region via the form?
			$region = isset( $_POST['region'] ) ? sanitize_text_field( $_POST['region'] ) : null; // input var okay
		}

		$result = $this->create_bucket( $bucket, $region );
		if ( is_wp_error( $result ) ) {
			$out = $this->prepare_bucket_error( $result, false );

			$this->end_ajax( $out );
		}

		// check if we were previously selecting a bucket manually via the input
		$previous_manual_bucket_select = $this->get_setting( 'manual_bucket', false );

		$args = array(
			'_nonce' => wp_create_nonce( 'as3cf-create-bucket' )
		);

		$this->save_bucket_for_ajax( $bucket, $previous_manual_bucket_select, $region, $args );
	}

	/**
	 * Create an S3 bucket
	 *
	 * @param string      $bucket_name
	 * @param null|string $region option location constraint
	 *
	 * @return bool|WP_Error
	 */
	function create_bucket( $bucket_name, $region = null ) {
		try {
			$args = array( 'Bucket' => $bucket_name );

			if ( defined( 'AS3CF_REGION' ) ) {
				// Make sure we always use the defined region
				$region = AS3CF_REGION;
			}

			if ( ! is_null( $region ) && self::DEFAULT_REGION !== $region ) {
				$args['LocationConstraint'] = $region;
			}

			$this->get_s3client()->createBucket( $args );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}

		return true;
	}

	/**
	 * Handler for AJAX callback to save the selection of a bucket
	 */
	function ajax_save_bucket() {
		$this->verify_ajax_request();

		$bucket = $this->ajax_check_bucket();

		$manual = false;
		// are we inputting the bucket manually?
		if ( isset( $_POST['action'] ) && false !== strpos( $_POST['action'], 'manual-save-bucket' ) ) {
			$manual = true;
		}

		$this->save_bucket_for_ajax( $bucket, $manual );
	}

	/**
	 * Wrapper method for saving a bucket when creating or selecting
	 *
	 * @param string      $bucket
	 * @param bool|false  $manual_select
	 * @param null|string $region
	 * @param array       $defaults
	 */
	function save_bucket_for_ajax( $bucket, $manual_select = false, $region = null, $defaults = array() ) {
		$region = $this->save_bucket( $bucket, $manual_select, $region );

		if ( ! is_wp_error( $region ) ) {
			$out = array(
				'success' => '1',
				'region'  => $region,
			);

			$out = wp_parse_args( $out, $defaults );

			$can_write = $this->check_write_permission( $bucket, $region );

			if ( is_wp_error( $can_write ) ) {
				$out = $this->prepare_bucket_error( $can_write );
			} else {
				$out['can_write'] = $can_write;
			}
		} else {
			$out = $this->prepare_bucket_error( $region );
		}

		$this->end_ajax( $out );
	}

	/**
	 * Prepare the bucket error before returning to JS
	 *
	 * @param WP_Error $object
	 * @param bool     $single Are we dealing with a single bucket?
	 *
	 * @return array
	 */
	function prepare_bucket_error( $object, $single = true ) {
		if ( 'Access Denied' === $object->get_error_message() ) {
			// If the bucket error is access denied, show our notice message
			$out = array( 'error' => $this->get_access_denied_notice_message( $single ) );
		} else {
			$out = array( 'error' => $object->get_error_message() );
		}

		return $out;
	}

	/**
	 * Perform custom actions before the setting is saved
	 *
	 * @param string $key
	 * @param string $value
	 */
	function pre_set_setting( $key, $value ) {
		if ( 'bucket' === $key && ! $this->get_setting( 'bucket' ) ) {
			// first time bucket select - enable main options by default
			$this->set_setting( 'copy-to-s3', '1' );
			$this->set_setting( 'serve-from-s3', '1' );
		}
	}

	/**
	 * Save bucket and bucket's region
	 *
	 * @param string      $bucket_name
	 * @param bool        $manual if we are entering the bucket via the manual input form
	 * @param null|string $region
	 *
	 * @return string|bool region on success
	 */
	function save_bucket( $bucket_name, $manual = false, $region = null ) {
		if ( $bucket_name ) {
			$this->get_settings();

			$this->set_setting( 'bucket', $bucket_name );

			if ( is_null( $region ) ) {
				// retrieve the bucket region if not supplied
				$region = $this->get_bucket_region( $bucket_name );
				if ( is_wp_error( $region ) ) {
					return $region;
				}
			}

			if ( self::DEFAULT_REGION === $region ) {
				$region = '';
			}

			$this->set_setting( 'region', $region );

			if ( $manual ) {
				// record that we have entered the bucket via the manual form
				$this->set_setting( 'manual_bucket', true );
			} else {
				$this->remove_setting( 'manual_bucket' );
			}

			$this->save_settings();

			return $region;
		}

		return false;
	}

	/**
	 * Get all AWS regions
	 *
	 * @return array
	 */
	function get_aws_regions() {
		$regionEnum  = new ReflectionClass( 'Aws\Common\Enum\Region' );
		$all_regions = $regionEnum->getConstants();

		$regions = array();
		foreach ( $all_regions as $label => $region ) {
			// Nicely format region name
			if ( self::DEFAULT_REGION === $region ) {
				$label = 'US Standard';
			} else {
				$label = strtolower( $label );
				$label = str_replace( '_', ' ', $label );
				$label = ucwords( $label );
			}

			$regions[ $region ] = $label;
		}

		return $regions;
	}

	/**
	 * Add the settings menu item
	 *
	 * @param Amazon_Web_Services $aws
	 */
	function admin_menu( $aws ) {
		$hook_suffix = $aws->add_page( $this->get_plugin_page_title(), $this->plugin_menu_title, 'manage_options', $this->plugin_slug, array( $this, 'render_page' ) );

		if ( false !== $hook_suffix ) {
			$this->hook_suffix = $hook_suffix;
			add_action( 'load-' . $this->hook_suffix, array( $this, 'plugin_load' ) );
		}
	}

	/**
	 * Get the S3 client
	 *
	 * @param bool|string $region specify region to client for signature
	 * @param bool        $force  force return of new S3 client when swapping regions
	 *
	 * @return mixed
	 */
	function get_s3client( $region = false, $force = false ) {
		if ( is_null( $this->s3client ) || $force ) {

			if ( $region ) {
				$args = array(
					'region'    => $this->translate_region( $region ),
					'signature' => 'v4',
				);
			} else {
				$args = array();
			}

			$this->s3client = $this->aws->get_client()->get( 's3', $args );
		}

		return $this->s3client;
	}

	/**
	 * Get the region of a bucket
	 *
	 * @param string $bucket
	 *
	 * @return string|WP_Error
	 */
	function get_bucket_region( $bucket ) {
		try {
			$region = $this->get_s3client()->getBucketLocation( array( 'Bucket' => $bucket ) );
		} catch ( Exception $e ) {
			$error_msg_title = '<strong>' . __( 'Error Getting Bucket Region', 'as3cf' ) . '</strong> &mdash;';
			$error_msg       = sprintf( __( 'There was an error attempting to get the region of the bucket %s: %s', 'as3cf' ), $bucket, $e->getMessage() );
			error_log( $error_msg );

			return new WP_Error( 'exception', $error_msg_title . $error_msg );
		}

		$region = $this->translate_region( $region['Location'] );

		return $region;
	}

	/**
	 * Get the region of the bucket stored in the S3 metadata.
	 *
	 *
	 * @param array $s3object
	 * @param int   $post_id  - if supplied will update the s3 meta if no region found
	 *
	 * @return string|WP_Error - region name
	 */
	function get_s3object_region( $s3object, $post_id = null ) {
		if ( ! isset( $s3object['region'] ) ) {
			// if region hasn't been stored in the s3 metadata retrieve using the bucket
			$region = $this->get_bucket_region( $s3object['bucket'] );
			if ( is_wp_error( $region ) ) {
				return $region;
			}

			$s3object['region'] = $region;

			if ( ! is_null( $post_id ) ) {
				// retrospectively update s3 metadata with region
				update_post_meta( $post_id, 'amazonS3_info', $s3object );
			}
		}

		return $s3object['region'];
	}

	/**
	 * Translate older bucket locations to newer S3 region names
	 * http://docs.aws.amazon.com/general/latest/gr/rande.html#s3_region
	 *
	 * @param $region
	 *
	 * @return string
	 */
	function translate_region( $region ) {
		if ( ! is_string( $region ) ) {
			// Don't translate any region errors
			return $region;
		}

		$region = strtolower( $region );

		switch ( $region ) {
			case 'eu':
				$region = 'eu-west-1';
				break;
		}

		return $region;
	}

	/**
	 * AJAX handler for get_buckets()
	 */
	function ajax_get_buckets() {
		$this->verify_ajax_request();

		$result = $this->get_buckets();
		if ( is_wp_error( $result ) ) {
			$out = $this->prepare_bucket_error( $result, false );
		} else {
			$out = array(
				'success' => '1',
				'buckets' => $result,
			);
		}

		$this->end_ajax( $out );
	}

	/**
	 * Get a list of buckets from S3
	 *
	 * @return array|WP_Error - list of buckets
	 */
	function get_buckets() {
		try {
			$result = $this->get_s3client()->listBuckets();
		}
		catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}

		return $result['Buckets'];
	}

	/**
	 * Checks the user has write permission for S3
	 *
	 * @param string $bucket
	 * @param string $region
	 *
	 * @return bool|WP_Error
	 */
	function check_write_permission( $bucket = null, $region = null ) {
		if ( is_null( $bucket ) ) {
			if ( ! ( $bucket = $this->get_setting( 'bucket' ) ) ) {
				// if no bucket set then no need check
				return true;
			}
		}

		if ( isset( self::$buckets_check[ $bucket ] ) ) {
			return self::$buckets_check[ $bucket ];
		}

		$file_name     = 'as3cf-permission-check.txt';
		$file_contents = __( 'This is a test file to check if the user has write permission to S3. Delete me if found.', 'as3cf' );

		$path = $this->get_object_prefix();
		$key  = $path . $file_name;

		$args = array(
			'Bucket' => $bucket,
			'Key'    => $key,
			'Body'   => $file_contents,
			'ACL'    => 'public-read',
		);

		try {
			// need to set region for buckets in non default region
			if ( is_null( $region ) ) {
				$region = $this->get_setting( 'region' );

				if ( is_wp_error( $region ) ) {
					return $region;
				}
			}
			// attempt to create the test file
			$this->get_s3client( $region, true )->putObject( $args );
			// delete it straight away if created
			$this->get_s3client()->deleteObject( array(
				'Bucket' => $bucket,
				'Key'    => $key,
			) );
			$can_write = true;
		} catch ( Exception $e ) {
			// if we encounter an error that isn't access denied, throw that error
			if ( ! $e instanceof Aws\Common\Exception\ServiceResponseException || 'AccessDenied' !== $e->getExceptionCode() ) {
				$error_msg = sprintf( __( 'There was an error attempting to check the permissions of the bucket %s: %s', 'as3cf' ), $bucket, $e->getMessage() );
				error_log( $error_msg );

				return new WP_Error( 'exception', $error_msg );
			}

			// write permission not found
			$can_write = false;
		}

		self::$buckets_check[ $bucket ] = $can_write;

		return $can_write;
	}

	/**
	 * Render error messages in a view for bucket permission and access issues
	 */
	function render_bucket_permission_errors() {
		$can_write = $this->check_write_permission();
		// catch any checking issues
		if ( is_wp_error( $can_write ) ) {
			$this->render_view( 'error-fatal', array( 'message' => $can_write->get_error_message() ) );
			$can_write = true;
		}
		// display a error message if the user does not have write permission to S3 bucket
		$this->render_view( 'error-access', array( 'can_write' => $can_write ) );
	}

	/**
	 * Register modal scripts and styles so they can be enqueued later
	 */
	function register_modal_assets()
	{
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$src = plugins_url( 'assets/css/modal.css', $this->plugin_file_path );
		wp_register_style( 'as3cf-modal', $src, array(), $version );

		$src = plugins_url( 'assets/js/modal' . $suffix . '.js', $this->plugin_file_path );
		wp_register_script( 'as3cf-modal', $src, array( 'jquery' ), $version, true );
	}

	function plugin_load() {
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
		wp_enqueue_style( 'as3cf-styles', $src, array( 'as3cf-modal' ), $version );

		$src = plugins_url( 'assets/js/script' . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( 'as3cf-script', $src, array( 'jquery', 'as3cf-modal' ), $version, true );

		wp_localize_script( 'as3cf-script',
			'as3cf',
			array(
				'strings' => array(
					'create_bucket_error'         => __( 'Error creating bucket', 'as3cf' ),
					'create_bucket_name_short'    => __( 'Bucket name too short.', 'as3cf' ),
					'create_bucket_name_long'     => __( 'Bucket name too long.', 'as3cf' ),
					'create_bucket_invalid_chars' => __( 'Invalid character. Bucket names can contain lowercase letters, numbers, periods and hyphens.', 'as3cf' ),
					'save_bucket_error'           => __( 'Error saving bucket', 'as3cf' ),
					'get_buckets_error'           => __( 'Error fetching buckets', 'as3cf' ),
					'get_url_preview_error'       => __( 'Error getting URL preview: ', 'as3cf' ),
					'save_alert'                  => __( 'The changes you made will be lost if you navigate away from this page', 'as3cf' )
				),
				'nonces'  => array(
					'create_bucket'   => wp_create_nonce( 'as3cf-create-bucket' ),
					'manual_bucket'   => wp_create_nonce( 'as3cf-manual-save-bucket' ),
					'get_buckets'     => wp_create_nonce( 'as3cf-get-buckets' ),
					'save_bucket'     => wp_create_nonce( 'as3cf-save-bucket' ),
					'get_url_preview' => wp_create_nonce( 'as3cf-get-url-preview' ),
				),
				'is_pro' => $this->is_pro(),
			)
		);

		$this->handle_post_request();
		$this->http_prepare_download_log();

		do_action( 'as3cf_plugin_load' );
	}

	/**
	 * Whitelist of settings allowed to be saved
	 *
	 * @return array
	 */
	function get_settings_whitelist() {
		return array(
			'bucket',
			'region',
			'domain',
			'virtual-host',
			'expires',
			'permissions',
			'cloudfront',
			'object-prefix',
			'copy-to-s3',
			'serve-from-s3',
			'remove-local-file',
			'ssl',
			'hidpi-images',
			'object-versioning',
			'use-yearmonth-folders',
			'enable-object-prefix',
		);
	}

	/**
	 * Handle the saving of the settings page
	 */
	function handle_post_request() {
		if ( empty( $_POST['plugin'] ) || $this->get_plugin_slug() != sanitize_key( $_POST['plugin'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), $this->get_settings_nonce_key() ) ) { // input var okay
			die( __( "Cheatin' eh?", 'as3cf' ) );
		}

		do_action( 'as3cf_pre_save_settings' );

		$post_vars = $this->get_settings_whitelist();

		foreach ( $post_vars as $var ) {
			$this->remove_setting( $var );

			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			$this->set_setting( $var, $value );
		}

		$this->save_settings();

		$url = $this->get_plugin_page_url( array( 'updated' => '1' ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Helper method to return the settings page URL for the plugin
	 *
	 * @param array  $args
	 * @param string $url_method To prepend to admin_url()
	 * @param bool   $escape     Should we escape the URL
	 *
	 * @return string
	 */
	function get_plugin_page_url( $args = array(), $url_method = 'network', $escape = true ) {
		$default_args = array(
			'page' => self::$plugin_page,
		);

		$args = array_merge( $args, $default_args );

		switch ( $url_method ) {
			case 'self':
				$base_url = self_admin_url( 'admin.php' );
				break;
			case '':
				$base_url = admin_url( 'admin.php' );
				break;
			default:
				$base_url = network_admin_url( 'admin.php' );
		}

		// Add a hash to the URL
		$hash = false;
		if ( isset( $args['hash'] ) ) {
			$hash = $args['hash'];
			unset( $args['hash'] );
		} else if ( $this->default_tab ) {
			$hash = $this->default_tab;
		}

		$url = add_query_arg( $args, $base_url );

		if ( $hash ) {
			$url .= '#' . $hash;
		}

		if ( $escape ) {
			$url = esc_url_raw( $url );
		}

		return $url;
	}

	/**
	 * Display the main settings page for the plugin
	 */
	function render_page() {
		$this->aws->render_view( 'header', array( 'page_title' => $this->get_plugin_page_title(), 'page' => 'as3cf' ) );

		$aws_client = $this->aws->get_client();

		if ( is_wp_error( $aws_client ) ) {
			$this->render_view( 'error-fatal', array( 'message' => $aws_client->get_error_message() ) );
		}
		else {
			$this->render_view( 'settings-tabs' );
			do_action( 'as3cf_pre_settings_render' );
			$this->render_view( 'settings' );
			do_action( 'as3cf_post_settings_render' );
		}

		$this->aws->render_view( 'footer' );
	}

	/**
	 * Get the tabs available for the plugin settings page
	 *
	 * @return array
	 */
	function get_settings_tabs() {
		$tabs = array(
			'media'   => _x( 'Media Library', 'Show the media library tab', 'as3cf' ),
			'support' => _x( 'Support', 'Show the support tab', 'as3cf' )
		);

		return apply_filters( 'as3cf_settings_tabs', $tabs );
	}

	/**
	 * Get the prefix path for the files
	 *
	 * @param string $time
	 *
	 * @return string
	 */
	function get_dynamic_prefix( $time = null ) {
		$prefix = '';
		if ( $this->get_setting( 'use-yearmonth-folders' ) ) {
			$uploads = wp_upload_dir( $time );
			$prefix  = str_replace( $this->get_base_upload_path(), '', $uploads['path'] );
		}

		// support legacy MS installs (<3.5 since upgraded) for subsites
		if ( is_multisite() && ! ( is_main_network() && is_main_site() ) && false === strpos( $prefix, 'sites/' ) ) {
			$details          = get_blog_details( get_current_blog_id() );
			$legacy_ms_prefix = 'sites/' . $details->blog_id . '/';
			$legacy_ms_prefix = apply_filters( 'as3cf_legacy_ms_subsite_prefix', $legacy_ms_prefix, $details );
			$prefix           = '/' . trailingslashit( ltrim( $legacy_ms_prefix, '/' ) ) . ltrim( $prefix, '/' );
		}

		return $prefix;
	}

	/**
	 * Get the base upload path
	 * without the multisite subdirectory
	 *
	 * @return string
	 */
	function get_base_upload_path() {
		if ( defined( 'UPLOADS' ) && ! ( is_multisite() && get_site_option( 'ms_files_rewriting' ) ) ) {
			return ABSPATH . UPLOADS;
		}

		$upload_path = trim( get_option( 'upload_path' ) );

		if ( empty( $upload_path ) || 'wp-content/uploads' == $upload_path ) {
			return WP_CONTENT_DIR . '/uploads';
		} elseif ( 0 !== strpos( $upload_path, ABSPATH ) ) {
			// $dir is absolute, $upload_path is (maybe) relative to ABSPATH
			return path_join( ABSPATH, $upload_path );
		} else {
			return $upload_path;
		}
	}

	/**
	 * Get all the blog IDs for the multisite network used for table prefixes
	 *
	 * @return array
	 */
	function get_blog_ids() {
		$args = array(
			'limit'    => false,
			'spam'     => 0,
			'deleted'  => 0,
			'archived' => 0,
		);
		$blogs = wp_get_sites( $args );

		$blog_ids = array();
		foreach ( $blogs as $blog ) {
			$blog_ids[] = $blog['blog_id'];
		}

		return $blog_ids;
	}

	/**
	 * Check whether the pro addon is installed.
	 *
	 * @return bool
	 */
	function is_pro() {
		if ( ! class_exists( 'Amazon_S3_And_CloudFront_Pro' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Apply ACL to an attachment and associated files
	 *
	 * @param int    $post_id
	 * @param array  $s3object
	 * @param string $acl
	 */
	function set_attachment_acl_on_s3( $post_id, $s3object, $acl ) {
		// set ACL as private
		$args = array(
			'ACL'    => $acl,
			'Bucket' => $s3object['bucket'],
			'Key'    => $s3object['key'],
		);

		$region   = ( isset( $s3object['region'] ) ) ? $s3object['region'] : false;
		$s3client = $this->get_s3client( $region, true );

		try {
			$s3client->PutObjectAcl( $args );
			$s3object['acl'] = $acl;

			// Add attachment to ACL update notice
			$message = $this->make_acl_admin_notice_text( $s3object );
			$this->set_admin_notice( $message );

			// update S3 meta data
			if ( $acl == self::DEFAULT_ACL ) {
				unset( $s3object['acl'] );
			}
			update_post_meta( $post_id, 'amazonS3_info', $s3object );
		} catch ( Exception $e ) {
			error_log( 'Error setting ACL to ' . $acl . ' for ' . $s3object['key'] . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Make admin notice text for when object ACL has changed
	 *
	 * @param array $s3object
	 *
	 * @return string
	 */
	function make_acl_admin_notice_text( $s3object ) {
		$filename = basename( $s3object['key'] );
		$acl      = $this->get_acl_display_name( $s3object['acl'] );

		return sprintf( __( 'The file %s has been given %s permissions on Amazon S3.', 'as3cf' ), "<strong>{$filename}</strong>", "<strong>{$acl}</strong>" );
	}

	/**
	 * Set admin notice
	 *
	 * @param string $message
	 * @param string $type info, updated, error
	 * @param bool   $dismissible
	 * @param bool   $inline
	 */
	function set_admin_notice( $message, $type = 'info', $dismissible = true, $inline = false ) {
		self::$admin_notices[] = array(
			'message'     => $message,
			'type'        => $type,
			'dismissible' => $dismissible,
			'inline'      => $inline,
		);
	}

	/**
	 * Save admin notices to transients before shutdown
	 */
	function save_admin_notices() {
		if ( ! empty( self::$admin_notices ) ) {
			set_site_transient( 'as3cf_notices', self::$admin_notices );
		}
	}

	/**
	 * Maybe show notices on admin page
	 */
	function maybe_show_admin_notices() {
		if ( $notices = get_site_transient( 'as3cf_notices' ) ) {
			foreach ( $notices as $notice ) {
				if ( 'info' === $notice['type'] ) {
					$notice['type'] = 'notice-info';
				}

				$args = array(
					'message'     => $notice['message'],
					'type'        => $notice['type'],
					'dismissible' => $notice['dismissible'],
					'inline'      => $notice['inline'],
				);

				$this->render_view( 'notice', $args );
			}

			delete_site_transient( 'as3cf_notices' );
		}
	}

	/**
	 * Diagnostic information for the support tab
	 *
	 * @param bool $escape
	 */
	function output_diagnostic_info( $escape = true ) {
		global $table_prefix;
		global $wpdb;

		echo 'site_url(): ';
		echo esc_html( site_url() );
		echo "\r\n";

		echo 'home_url(): ';
		echo esc_html( home_url() );
		echo "\r\n";

		echo 'Database Name: ';
		echo esc_html( $wpdb->dbname );
		echo "\r\n";

		echo 'Table Prefix: ';
		echo esc_html( $table_prefix );
		echo "\r\n";

		echo 'WordPress: ';
		echo bloginfo( 'version' );
		if ( is_multisite() ) {
			echo ' Multisite';
		}
		echo "\r\n";

		echo 'Web Server: ';
		echo esc_html( ! empty( $_SERVER['SERVER_SOFTWARE'] ) ? $_SERVER['SERVER_SOFTWARE'] : '' );
		echo "\r\n";

		echo 'PHP: ';
		if ( function_exists( 'phpversion' ) ) {
			echo esc_html( phpversion() );
		}
		echo "\r\n";

		echo 'MySQL: ';
		echo esc_html( empty( $wpdb->use_mysqli ) ? mysql_get_server_info() : mysqli_get_server_info( $wpdb->dbh ) );
		echo "\r\n";

		echo 'ext/mysqli: ';
		echo empty( $wpdb->use_mysqli ) ? 'no' : 'yes';
		echo "\r\n";

		echo 'WP Memory Limit: ';
		echo esc_html( WP_MEMORY_LIMIT );
		echo "\r\n";

		echo 'Blocked External HTTP Requests: ';
		if ( ! defined( 'WP_HTTP_BLOCK_EXTERNAL' ) || ! WP_HTTP_BLOCK_EXTERNAL ) {
			echo 'None';
		} else {
			$accessible_hosts = ( defined( 'WP_ACCESSIBLE_HOSTS' ) ) ? WP_ACCESSIBLE_HOSTS : '';

			if ( empty( $accessible_hosts ) ) {
				echo 'ALL';
			} else {
				echo 'Partially (Accessible Hosts: ' . esc_html( $accessible_hosts ) . ')';
			}
		}
		echo "\r\n";

		echo 'WP Locale: ';
		echo esc_html( get_locale() );
		echo "\r\n";

		echo 'Debug Mode: ';
		echo esc_html( ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No' );
		echo "\r\n";

		echo 'WP Max Upload Size: ';
		echo esc_html( size_format( wp_max_upload_size() ) );
		echo "\r\n";

		echo 'PHP Time Limit: ';
		if ( function_exists( 'ini_get' ) ) {
			echo esc_html( ini_get( 'max_execution_time' ) );
		}
		echo "\r\n";

		echo 'PHP Error Log: ';
		if ( function_exists( 'ini_get' ) ) {
			echo esc_html( ini_get( 'error_log' ) );
		}
		echo "\r\n";

		echo 'fsockopen: ';
		if ( function_exists( 'fsockopen' ) ) {
			echo 'Enabled';
		} else {
			echo 'Disabled';
		}
		echo "\r\n";

		echo 'OpenSSL: ';
		if ( $this->open_ssl_enabled() ) {
			echo esc_html( OPENSSL_VERSION_TEXT );
		} else {
			echo 'Disabled';
		}
		echo "\r\n";

		echo 'cURL: ';
		if ( function_exists( 'curl_init' ) ) {
			echo 'Enabled';
		} else {
			echo 'Disabled';
		}
		echo "\r\n";

		echo 'Zlib Compression: ';
		if ( function_exists( 'gzcompress' ) ) {
			echo 'Enabled';
		} else {
			echo 'Disabled';
		}
		echo "\r\n\r\n";

		$media_counts = $this->diagnostic_media_counts();

		echo 'Media Files: ';
		echo number_format_i18n( $media_counts['all'] );
		echo "\r\n";

		echo 'Media Files on S3: ';
		echo number_format_i18n( $media_counts['s3'] );
		echo "\r\n";

		echo 'Number of Image Sizes: ';
		$sizes = count( get_intermediate_image_sizes() );
		echo number_format_i18n( $sizes );
		echo "\r\n\r\n";

		echo 'Bucket: ';
		echo $this->get_setting( 'bucket' );
		echo "\r\n";
		echo 'Region: ';
		$region = $this->get_setting( 'region' );
		if ( ! is_wp_error( $region ) ) {
			echo $region;
		}
		echo "\r\n";
		echo 'Copy Files to S3: ';
		echo $this->on_off( 'copy-to-s3' );
		echo "\r\n";
		echo 'Rewrite File URLs: ';
		echo $this->on_off( 'serve-from-s3' );
		echo "\r\n";
		echo "\r\n";

		echo 'URL Preview: ';
		echo $this->get_url_preview( $escape );
		echo "\r\n";
		echo "\r\n";

		echo 'Domain: ';
		echo $this->get_setting( 'domain' );
		echo "\r\n";
		echo 'Enable Path: ';
		echo $this->on_off( 'enable-object-prefix' );
		echo "\r\n";
		echo 'Custom Path: ';
		echo $this->get_setting( 'object-prefix' );
		echo "\r\n";
		echo 'Use Year/Month: ';
		echo $this->on_off( 'use-yearmonth-folders' );
		echo "\r\n";
		echo 'SSL: ';
		echo $this->get_setting( 'ssl' );
		echo "\r\n";
		echo 'Remove Files From Server: ';
		echo $this->on_off( 'remove-local-file' );
		echo "\r\n";
		echo 'Object Versioning: ';
		echo $this->on_off( 'object-versioning' );
		echo "\r\n";
		echo 'Far Future Expiration Header: ';
		echo $this->on_off( 'expires' );
		echo "\r\n";
		echo 'Copy HiDPI (@2x) Images: ';
		echo $this->on_off( 'hidpi-images' );
		echo "\r\n\r\n";

		do_action( 'as3cf_diagnostic_info' );
		if ( has_action( 'as3cf_diagnostic_info' ) ) {
			echo "\r\n";
		}

		echo "Active Plugins:\r\n";
		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$network_active_plugins = wp_get_active_network_plugins();
			$active_plugins = array_map( array( $this, 'remove_wp_plugin_dir' ), $network_active_plugins );
		}

		foreach ( $active_plugins as $plugin ) {
			$this->print_plugin_details( WP_PLUGIN_DIR . '/' . $plugin );
		}
	}

	/**
	 * Helper for displaying settings
	 *
	 * @param string $key setting key
	 *
	 * @return string
	 */
	function on_off( $key ) {
		$value = $this->get_setting( $key, 0 );

		return ( 1 == $value ) ? 'On' : 'Off';
	}

	/**
	 * Helper to display plugin details
	 *
	 * @param string $plugin_path
	 * @param string $suffix
	 */
	function print_plugin_details( $plugin_path, $suffix = '' ) {
		$plugin_data = get_plugin_data( $plugin_path );
		if ( empty( $plugin_data['Name'] ) ) {
			return;
		}

		printf( "%s%s (v%s) by %s\r\n", $plugin_data['Name'], $suffix, $plugin_data['Version'], strip_tags( $plugin_data['AuthorName'] ) );
	}

	/**
	 * Helper to remove the plugin directory from the plugin path
	 *
	 * @param string $path
	 *
	 * @return string
	 */
	function remove_wp_plugin_dir( $path ) {
		$plugin = str_replace( WP_PLUGIN_DIR, '', $path );

		return substr( $plugin, 1 );
	}

	/**
	 * Check for as3cf-download-log and related nonce and if found begin the
	 * download of the diagnostic log
	 *
	 * @return void
	 */
	function http_prepare_download_log() {
		if ( isset( $_GET['as3cf-download-log'] ) && wp_verify_nonce( $_GET['nonce'], 'as3cf-download-log' ) ) {
			ob_start();
			$this->output_diagnostic_info( false );
			$log      = ob_get_clean();
			$url      = parse_url( home_url() );
			$host     = sanitize_file_name( $url['host'] );
			$filename = sprintf( '%s-diagnostic-log-%s.txt', $host, date( 'YmdHis' ) );
			header( 'Content-Description: File Transfer' );
			header( 'Content-Type: application/octet-stream' );
			header( 'Content-Length: ' . strlen( $log ) );
			header( 'Content-Disposition: attachment; filename=' . $filename );
			echo $log;
			exit;
		}
	}

	/**
	 * Return human friendly ACL name
	 *
	 * @param string $acl
	 *
	 * @return string
	 */
	function get_acl_display_name( $acl ) {
		return ucwords( str_replace( '-', ' ', $acl ) );
	}

	/**
	 * Detect if OpenSSL is enabled
	 *
	 * @return bool
	 */
	function open_ssl_enabled() {
		if ( defined( 'OPENSSL_VERSION_TEXT' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Is the current blog ID that specified in wp-config.php
	 *
	 * @param int $blog_id
	 *
	 * @return bool
	 */
	function is_current_blog( $blog_id ) {
		$default = defined( 'BLOG_ID_CURRENT_SITE' ) ? BLOG_ID_CURRENT_SITE : 1;

		if ( $default === $blog_id ) {
			return true;
		}

		return false;
	}

	/**
	 * Helper to switch to a Multisite blog
	 *  - If the site is MS
	 *  - If the blog is not the current blog defined
	 *
	 * @param $blog_id
	 */
	function switch_to_blog( $blog_id ) {
		if ( is_multisite() && ! $this->is_current_blog( $blog_id ) ) {
			switch_to_blog( $blog_id );
		}
	}

	/**
	 * Helper to restore to the current Multisite blog
	 *  - If the site is MS
	 *  - If the blog is not the current blog defined
	 *
	 * @param $blog_id
	 */
	function restore_current_blog( $blog_id ) {
		if ( is_multisite() && ! $this->is_current_blog( $blog_id ) ) {
			restore_current_blog();
		}
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
			$blog_ids = $this->get_blog_ids();
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
	 * Get file paths for all attachment versions.
	 *
	 * @param int        $attachment_id
	 * @param bool       $exists_locally
	 * @param array|bool $meta
	 * @param bool       $include_backups
	 *
	 * @return array
	 */
	public function get_attachment_file_paths( $attachment_id, $exists_locally = true, $meta = false, $include_backups = true ) {
		$paths     = array();
		$file_path = get_attached_file( $attachment_id, true );
		$file_name = basename( $file_path );
		$backups   = get_post_meta( $attachment_id, '_wp_attachment_backup_sizes', true );

		if ( ! $meta ) {
			$meta = get_post_meta( $attachment_id, '_wp_attachment_metadata', true );
		}

		$original_file = $file_path; // Not all attachments will have meta

		if ( isset( $meta['file'] ) ) {
			$original_file = str_replace( $file_name, basename( $meta['file'] ), $file_path );
		}

		// Original file
		$paths[] = $original_file;

		// Sizes
		if ( isset( $meta['sizes'] ) ) {
			foreach ( $meta['sizes'] as $size ) {
				if ( isset( $size['file'] ) ) {
					$paths[] = str_replace( $file_name, $size['file'], $file_path );
				}
			}
		}

		// Thumb
		if ( isset( $meta['thumb'] ) ) {
			$paths[] = str_replace( $file_name, $meta['thumb'], $file_path );
		}

		// Backups
		if ( $include_backups && is_array( $backups ) ) {
			foreach ( $backups as $backup ) {
				$paths[] = str_replace( $file_name, $backup['file'], $file_path );
			}
		}

		// HiDPI
		if ( $this->get_setting( 'hidpi-images' ) ) {
			foreach ( $paths as $path ) {
				$paths[] = $this->get_hidpi_file_path( $path );
			}
		}

		// Remove duplicates
		$paths = array_unique( $paths );

		// Remove paths that don't exist
		if ( $exists_locally ) {
			foreach ( $paths as $key => $path ) {
				if ( ! file_exists( $path ) ) {
					unset( $paths[ $key ] );
				}
			}
		}

		return $paths;
	}

	/**
	 * Get the access denied bucket error notice message
	 *
	 * @param bool $single
	 *
	 * @return string
	 */
	function get_access_denied_notice_message( $single = true ) {
		$quick_start = sprintf( '<a class="js-link" href="%s">%s</a>', 'https://deliciousbrains.com/wp-offload-s3/doc/quick-start-guide/', __( 'Quick Start Guide', 'as3cf' ) );

		$message = sprintf( __( "Looks like we don't have write access to this bucket. It's likely that the user you've provided access keys for hasn't been granted the correct permissions. Please see our %s for instructions on setting up permissions correctly.", 'as3cf' ), $quick_start );
		if ( ! $single ) {
			$message = sprintf( __( "Looks like we don't have access to the buckets. It's likely that the user you've provided access keys for hasn't been granted the correct permissions. Please see our %s for instructions on setting up permissions correctly.", 'as3cf' ), $quick_start );

		}

		return $message;
	}

	/**
	 * Used to give a realistic total of storage space used on a Multisite subsite,
	 * when there have been attachments uploaded to S3 but removed from server
	 *
	 * @param $space_used bool
	 *
	 * @return float|int
	 */
	function multisite_get_spaced_used( $space_used ) {
		if ( false === ( $space_used = get_transient( 'wpos3_site_space_used' ) ) ) {
			global $wpdb;

			// Sum the total file size (including image sizes) for all S3 attachments
			$sql = "SELECT SUM( meta_value ) AS bytes_total
				FROM {$wpdb->postmeta}
				WHERE meta_key = 'wpos3_filesize_total'";

			$space_used = $wpdb->get_var( $sql );

			// Get local upload sizes
			$upload_dir = wp_upload_dir();
			$space_used += get_dirsize( $upload_dir['basedir'] );

			if ( $space_used > 0 ) {
				// Convert to bytes to MB
				$space_used = $space_used / 1024 / 1024;
			}

			set_transient( 'wpos3_site_space_used', $space_used, HOUR_IN_SECONDS );
		}

		return $space_used;
	}

	/**
	 * Memory exceeded
	 *
	 * Ensures the a process never exceeds 90% of the maximum WordPress memory.
	 *
	 * @param null|string $filter_name Name of filter to apply to the return
	 *
	 * @return bool
	 */
	public function memory_exceeded( $filter_name = null ) {
		$current_memory = memory_get_usage( true );
		$memory_limit   = ( intval( WP_MEMORY_LIMIT ) * 1024 * 1024 ) * 0.9; // 90% of max memory
		$return         = false;

		if ( $current_memory >= $memory_limit ) {
			$return = true;
		}

		if ( is_null( $filter_name ) || ! is_string( $filter_name ) ) {
			return $return;
		}

		return apply_filters( $filter_name, $return );
	}

	/**
	 * Count attachments on a site
	 *
	 * @param string    $prefix
	 * @param null|bool $uploaded_to_s3
	 *      null  - All attachments
	 *      true  - Attachments only uploaded to S3
	 *      false - Attachments not uploaded to S3
	 *
	 * @return null|string
	 */
	public function count_attachments( $prefix, $uploaded_to_s3 = null ) {
		global $wpdb;

		$sql = "SELECT COUNT(*)
				FROM `{$prefix}posts` p";

		$where = "WHERE p.post_type = 'attachment'";

		if ( ! is_null( $uploaded_to_s3 ) && is_bool( $uploaded_to_s3 ) ) {
			$sql .= " LEFT OUTER JOIN `{$prefix}postmeta` pm
					ON p.`ID` = pm.`post_id`
					AND pm.`meta_key` = 'amazonS3_info'";

			$operator = $uploaded_to_s3 ? 'not ' : '';
			$where .= " AND pm.`post_id` is {$operator}null";
		}

		$sql .= ' ' . $where;

		return $wpdb->get_var( $sql );
	}

	/**
	 * Get the total attachment and total S3 attachment counts for the diagnostic log
	 *
	 * @return array
	 */
	protected function diagnostic_media_counts() {
		if ( false === ( $attachment_counts = get_site_transient( 'wpos3_attachment_counts' ) ) ) {
			$table_prefixes = $this->get_all_blog_table_prefixes();
			$all_media      = 0;
			$all_media_s3   = 0;

			foreach ( $table_prefixes as $blog_id => $table_prefix ) {
				$count = $this->count_attachments( $table_prefix );
				$all_media += $count;
				$s3_count = $this->count_attachments( $table_prefix, true );
				$all_media_s3 += $s3_count;
			}

			$attachment_counts = array(
				'all' => $all_media,
				's3'  => $all_media_s3,
			);

			set_site_transient( 'wpos3_attachment_counts', $attachment_counts, 2 * HOUR_IN_SECONDS );
		}

		return $attachment_counts;
	}
}
