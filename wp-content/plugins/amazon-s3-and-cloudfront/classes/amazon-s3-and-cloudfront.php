<?php
use Aws\S3\S3Client;

class Amazon_S3_And_CloudFront extends AWS_Plugin_Base {
	private $aws;
	private $s3client;

	protected $plugin_title;
	protected $plugin_menu_title;

	const DEFAULT_ACL = 'public-read';
	const PRIVATE_ACL = 'private';
	const DEFAULT_EXPIRES = 900;

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
		$this->plugin_title = __( 'Amazon S3 and CloudFront', 'as3cf' );
		$this->plugin_menu_title = __( 'S3 and CloudFront', 'as3cf' );

		// fire up the plugin upgrade checker
		new AS3CF_Upgrade( $this );

		add_action( 'aws_admin_menu', array( $this, 'admin_menu' ) );
		add_action( 'wp_ajax_as3cf-get-buckets', array( $this, 'ajax_get_buckets' ) );
		add_action( 'wp_ajax_as3cf-save-bucket', array( $this, 'ajax_save_bucket' ) );
		add_action( 'wp_ajax_as3cf-create-bucket', array( $this, 'ajax_create_bucket' ) );
		add_action( 'wp_ajax_as3cf-manual-save-bucket', array( $this, 'ajax_manual_save_bucket' ) );
		add_action( 'wp_ajax_as3cf-get-url-preview', array( $this, 'ajax_get_url_preview' ) );

		add_filter( 'wp_get_attachment_url', array( $this, 'wp_get_attachment_url' ), 99, 2 );
		add_filter( 'wp_handle_upload_prefilter', array( $this, 'wp_handle_upload_prefilter' ), 1 );
		add_filter( 'wp_update_attachment_metadata', array( $this, 'wp_update_attachment_metadata' ), 100, 2 );
		add_filter( 'wp_get_attachment_metadata', array( $this, 'wp_get_attachment_metadata' ), 10, 2 );
		add_filter( 'delete_attachment', array( $this, 'delete_attachment' ), 20 );
		add_filter( 'get_attached_file', array( $this, 'get_attached_file' ), 10, 2 );
		add_filter( 'as3cf_get_attached_file_copy_back_to_local', array( $this, 'regenerate_thumbnails_get_attached_file' ) );

		load_plugin_textdomain( 'as3cf', false, dirname( plugin_basename( $plugin_file_path ) ) . '/languages/' );
	}

	/**
	 * Accessor for a plugin setting with conditions to defaults and upgrades
	 *
	 * @param string $key
	 * @param string $default
	 *
	 * @return int|mixed|string|void
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
			$region = $this->get_bucket_region( $bucket );
			if ( is_wp_error( $region ) ) {
				$region = '';
			}

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

		if ( 'bucket' == $key && defined( 'AS3CF_BUCKET' ) ) {

			return AS3CF_BUCKET;
		}

		$value = parent::get_setting( $key, $default );

		return apply_filters( 'as3cf_setting_' . $key, $value );
	}

	/**
	 * Return the default object prefix
	 *
	 * @return string
	 */
	function get_default_object_prefix() {
		$uploads = wp_upload_dir();
		$parts = parse_url( $uploads['baseurl'] );

		return substr( $parts['path'], 1 ) . '/';
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
	 * Generate a preview of the URL of files uploaded to S3
	 *
	 * @param string $suffix
	 *
	 * @return string
	 */
	function get_url_preview( $suffix = 'photo.jpg' ) {
		$scheme = $this->get_s3_url_scheme();
		$bucket = $this->get_setting( 'bucket' );
		$path   = $this->get_file_prefix();
		$region = $this->get_setting( 'region' );
		$domain = $this->get_s3_url_domain( $bucket, $region );

		$url = $scheme . '://' . $domain . '/' . $path . $suffix;

		// replace hyphens with non breaking hyphens for formatting
		$url = str_replace( '-', '&#8209;', $url );

		return $url;
	}

	/**
	 * AJAX handler for get_url_preview()
	 */
	function ajax_get_url_preview() {
		$this->verify_ajax_request();

		$url = $this->get_url_preview();

		echo json_encode( array(
			'success' => '1',
			'url'     => $url,
		) );
		exit;
	}

	/**
	 * Find backup images and add to array for removal
	 *
	 * @param int    $post_id
	 * @param array  $objects
	 * @param string $path
	 */
	function prepare_backup_size_images_to_remove( $post_id, &$objects, $path ) {
		$backup_sizes = get_post_meta( $post_id, '_wp_attachment_backup_sizes', true );

		if ( is_array( $backup_sizes ) ) {
			foreach ( $backup_sizes as $size ) {
				$objects[] = array(
					'Key' => path_join( $path, $size['file'] )
				);
			}
		}
	}

	/**
	 * Find intermediate size images and add to array for removal
	 *
	 * @param int   $post_id
	 * @param array $objects
	 * @param string $path
	 */
	function prepare_intermediate_images_to_remove( $post_id, &$objects, $path  ) {
		$intermediate_images = get_intermediate_image_sizes();

		foreach (  $intermediate_images as $size ) {
			if ( $intermediate = image_get_intermediate_size( $post_id, $size ) ) {
				$objects[] = array(
					'Key' => path_join( $path, $intermediate['file'] )
				);
			}
		}
	}

	/**
	 * Delete bulk objects from an S3 bucket
	 *
	 * @param string  $region
	 * @param string  $bucket
	 * @param array   $objects
	 * @param bool    $log_error
	 * @param bool    $return_on_error
	 */
	function delete_s3_objects( $region, $bucket, $objects, $log_error = false, $return_on_error = false ) {
		try {
			$this->get_s3client( $region )->deleteObjects( array(
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

	function delete_attachment( $post_id ) {
		if ( ! $this->is_plugin_setup() ) {
			return;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $post_id ) ) ) {
			return;
		}

		$bucket = $s3object['bucket'];
		$region = $this->get_s3object_region( $s3object );
		if ( is_wp_error( $region ) ) {
			error_log( 'Error retrieving region: ' . $region->get_error_message() );
			$region = '';
		}

		$amazon_path = dirname( $s3object['key'] );
		$objects     = array();

		// remove intermediate images
		$this->prepare_intermediate_images_to_remove( $post_id, $objects, $amazon_path );
		// remove backup images
		$this->prepare_backup_size_images_to_remove( $post_id, $objects, $amazon_path );

		// Try removing any @2x images but ignore any errors
		if ( $objects ) {
			$hidpi_images = array();
			foreach ( $objects as $object ) {
				$hidpi_images[] = array(
					'Key' => $this->get_hidpi_file_path( $object['Key'] )
				);
			}

			$this->delete_s3_objects( $region, $bucket, $hidpi_images );
		}

		// add main file to be deleted
		$objects[] = array(
			'Key' => $s3object['key'],
		);

		$this->delete_s3_objects( $region, $bucket, $objects, true, true );

		delete_post_meta( $post_id, 'amazonS3_info' );
	}

	function wp_update_attachment_metadata( $data, $post_id ) {
		if ( ! $this->get_setting( 'copy-to-s3' ) || ! $this->is_plugin_setup() ) {
			return $data;
		}

		// allow S3 upload to be cancelled for any reason
		$pre = apply_filters( 'as3cf_pre_update_attachment_metadata', false, $data, $post_id );
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
	 * @param $post_id
	 * @param $data
	 *
	 * @return array|WP_Error $s3object
	 */
	function upload_attachment_to_s3( $post_id, $data = null ) {
		if ( is_null( $data ) ) {
			$data = wp_get_attachment_metadata( $post_id, true );
		}

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
				$time = untrailingslashit( dirname( $data['file'] ) );
			} else {
				$time = $this->get_attachment_folder_time( $post_id );
				$time = date( 'Y/m', $time );
			}

			$prefix = $this->get_file_prefix( $time, $post_id );

			// use bucket from settings
			$bucket = $this->get_setting( 'bucket' );
			$region = $this->get_setting( 'region' );
		}

		$file_path = get_attached_file( $post_id, true );
		$file_name = basename( $file_path );

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

		$s3client = $this->get_s3client( $region );

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

		// if the original image is being restored and 'IMAGE_EDIT_OVERWRITE' is set
		// then we need to remove the edited image versions
		if ( isset( $_POST['do'] ) && 'restore' == $_POST['do'] && defined( 'IMAGE_EDIT_OVERWRITE' ) && IMAGE_EDIT_OVERWRITE ) {
			$objects_to_remove = array();
			// edited main file
			$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
			$objects_to_remove[] = array(
				'Key' => path_join( $prefix, basename( $meta['file'] ) )
			);
			// edited resized image files
			$this->prepare_intermediate_images_to_remove( $post_id, $objects_to_remove, $prefix );
			$this->delete_s3_objects( $region, $bucket, $objects_to_remove, true );
		}

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

		$additional_images = array();

		if ( isset( $data['thumb'] ) && $data['thumb'] ) {
			$path = str_replace( $file_name, $data['thumb'], $file_path );
			if ( file_exists( $path ) ) {
				$additional_images[] = array(
					'Key'        => $prefix . $data['thumb'],
					'SourceFile' => $path,
				);
				$files_to_remove[]   = $path;
			}
		} else if ( ! empty( $data['sizes'] ) ) {
			foreach ( $data['sizes'] as $size ) {
				$path = str_replace( $file_name, $size['file'], $file_path );
				if ( file_exists( $path ) ) {
					$additional_images[] = array(
						'Key'        => $prefix . $size['file'],
						'SourceFile' => $path,
					);
					$files_to_remove[]   = $path;
				}
			}
		}

		// Because we're just looking at the filesystem for files with @2x
		// this should work with most HiDPI plugins
		if ( $this->get_setting( 'hidpi-images' ) ) {
			$hidpi_images = array();

			foreach ( $additional_images as $image ) {
				$hidpi_path = $this->get_hidpi_file_path( $image['SourceFile'] );
				if ( file_exists( $hidpi_path ) ) {
					$hidpi_images[] = array(
						'Key'        => $this->get_hidpi_file_path( $image['Key'] ),
						'SourceFile' => $hidpi_path,
					);
					$files_to_remove[] = $hidpi_path;
				}
			}

			$additional_images = array_merge( $additional_images, $hidpi_images );
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

		if ( $this->get_setting( 'remove-local-file' ) ) {
			$this->remove_local_files( $files_to_remove );
		}

		return $s3object;
	}

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

	// Media files attached to a post use the post's date
	// to determine the folder path they are placed in
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

	function is_plugin_setup() {
		return (bool) $this->get_setting( 'bucket' ) && ! is_wp_error( $this->aws->get_client() );
	}

	/**
	 * Generate a link to download a file from Amazon S3 using query string
	 * authentication. This link is only valid for a limited amount of time.
	 *
	 * @param int $post_id Post ID of the attachment
	 * @param int     $expires Seconds for the link to live
	 * @param string    $size    Size of the image to get
	 *
	 * @return mixed|void|WP_Error
	 */
	function get_secure_attachment_url( $post_id, $expires = null, $size = null ) {
		if ( is_null( $expires ) ) {
			$expires = self::DEFAULT_EXPIRES;
		}
		return $this->get_attachment_url( $post_id, $expires, $size );
	}

	/**
	 * Return the scheme to be used in URLs
	 *
	 * @return string
	 */
	function get_s3_url_scheme() {
		if ( $this->use_ssl() ) {
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
	 * @return bool
	 */
	function use_ssl( ) {
		$use_ssl = false;

		$ssl = $this->get_setting( 'ssl' );

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
	 * @param null $time
	 * @param null $post_id
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
	 * @param $region
	 *
	 * @return string
	 */
	function get_s3_url_prefix( $region = '' ) {
		$prefix = ( '' == $region ) ? 's3' : 's3-' . $region;

		return $prefix;
	}

	/**
	 * Get the S3 url for the files
	 *
	 * @param        $bucket
	 * @param string $region
	 * @param int    $expires
	 *
	 * @return mixed|string|void
	 */
	function get_s3_url_domain( $bucket, $region = '', $expires = null ) {
		$domain = $this->get_setting( 'domain' );

		$prefix = $this->get_s3_url_prefix( $region );

		if ( 'cloudfront' == $domain && is_null( $expires ) && $this->get_setting( 'cloudfront' ) ) {
			$s3_domain = $this->get_setting( 'cloudfront' );
		}
		elseif ( 'virtual-host' == $domain ) {
			$s3_domain = $bucket;
		}
		elseif ( 'path' == $domain || $this->use_ssl() ) {
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
	 * @param int     $post_id Post ID of the attachment
	 * @param int     $expires Seconds for the link to live
	 * @param string  $size    Size of the image to get
	 * @param array   $meta    Pre retrieved _wp_attachment_metadata for the attachment
	 *
	 * @return bool|mixed|void|WP_Error
	 */
	function get_attachment_url( $post_id, $expires = null, $size = null, $meta = null ) {
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
		if ( isset( $s3object['region'] ) ) {
			$region = $this->translate_region( $s3object['region'] );
		}
		else {
			$region = '';
		}

		// force use of secured url when ACL has been set to private
		if ( is_null( $expires ) && isset( $s3object['acl'] ) && self::PRIVATE_ACL == $s3object['acl'] ) {
			$expires = self::DEFAULT_EXPIRES;
		}

		$domain_bucket = $this->get_s3_url_domain( $s3object['bucket'], $region, $expires );

		if ( $size ) {
			if ( is_null( $meta ) ) {
				$meta = get_post_meta( $post_id, '_wp_attachment_metadata', true );
			}
			if ( isset( $meta['sizes'][ $size ]['file'] ) ) {
				$s3object['key'] = dirname( $s3object['key'] ) . '/' . $meta['sizes'][ $size ]['file'];
			}
		}

		if ( ! is_null( $expires ) ) {
			try {
				$expires = time() + $expires;
				$secure_url = $this->get_s3client( $region )->getObjectUrl( $s3object['bucket'], $s3object['key'], $expires );
			}
			catch ( Exception $e ) {
				return new WP_Error( 'exception', $e->getMessage() );
			}
		}

		// encode file
		$file = $this->encode_filename_in_path( $s3object['key'] );

		$url = $scheme . '://' . $domain_bucket . '/' . $file;
		if ( isset( $secure_url ) ) {
			$url .= substr( $secure_url, strpos( $secure_url, '?' ) );
		}

		return apply_filters( 'as3cf_get_attachment_url', $url, $s3object, $post_id, $expires );
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
	 * Return the S3 URL when the local file is missing
	 * unless we know the calling process is and we are happy
	 * to copy the file back to the server to be used
	 *
	 * @param $file
	 * @param $attachment_id
	 *
	 * @return bool|mixed|void|WP_Error
	 */
	function get_attached_file( $file, $attachment_id ) {
		if ( file_exists( $file ) || ! $this->get_setting( 'serve-from-s3' ) ) {
			return $file;
		}

		if ( ! ( $s3object = $this->get_attachment_s3_info( $attachment_id ) ) ) {
			return $file;
		}

		$url = $this->get_attachment_url( $attachment_id );

		// the default behaviour is to return the S3 URL, however we can override this
		// and copy back the file to the local server for certain processes
		// where we know it will get removed again via wp_update_attachment_metadata
		$copy_back_to_local = apply_filters( 'as3cf_get_attached_file_copy_back_to_local', false, $file, $attachment_id );
		if ( false === $copy_back_to_local ) {
			// return S3 URL as a fallback
			return $url;
		}

		// fire up the filesystem API
		$filesystem = WP_Filesystem();
		global $wp_filesystem;
		if ( false === $filesystem || is_null( $wp_filesystem ) ) {
			error_log( __( 'There was an error attempting to access the file system', 'as3cf' ) );

			return $url;
		}

		// download the file from S3
		$temp_file = download_url( $url  );
		// copy the temp file to the attachments location
		if ( ! $wp_filesystem->copy( $temp_file, $file ) ) {
			// fallback to url
			$file = $url;
		}
		// clear up temp file
		@unlink( $temp_file );

		return $file;
	}

	/**
	 * Allow the Regenerate Thumbnails plugin to copy the S3 file back to the local
	 * server when the file is missing on the server via get_attached_file
	 *
	 * @param $copy_back_to_local
	 *
	 * @return bool
	 */
	function regenerate_thumbnails_get_attached_file( $copy_back_to_local ) {
		if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) {
			return $copy_back_to_local;
		}

		if ( isset( $_POST['action'] ) && 'regeneratethumbnail' == sanitize_key( $_POST['action'] ) ) { // input var okay
			return true;
		}

		return $copy_back_to_local;
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
			echo json_encode( array( 'error' => __( 'No bucket name provided.', 'as3cf' ) ) );
			exit;
		}

		return $bucket;
	}

	function ajax_create_bucket() {
		$this->verify_ajax_request();

		$bucket = $this->ajax_check_bucket();

		$result = $this->create_bucket( $bucket );
		if ( is_wp_error( $result ) ) {
			$out = array( 'error' => $result->get_error_message() );
		} else {
			$region = $this->save_bucket( $bucket );

			if ( $region !== false ) {
				$out = array(
					'success' => '1',
					'_nonce'  => wp_create_nonce( 'as3cf-create-bucket' ),
					'region'  => $region,
				);
				$out['can_write'] = $this->check_write_permission( $bucket, $region );
			} else {
				$out = array( 'error' => __( 'Failed to retrieve bucket region.', 'as3cf' ) );
			}
		}

		echo json_encode( $out );
		exit;
	}

	function create_bucket( $bucket_name ) {
		try {
			$this->get_s3client()->createBucket( array( 'Bucket' => $bucket_name ) );
		}
		catch ( Exception $e ) {
			return new WP_Error( 'exception', $e->getMessage() );
		}

		return true;
	}

	function ajax_save_bucket() {
		$this->verify_ajax_request();

		$bucket = $this->ajax_check_bucket();

		$region = $this->save_bucket( $bucket );

		if ( $region !== false ) {
			$out = array(
				'success' => '1',
				'region'  => $region,
			);
			$out['can_write'] = $this->check_write_permission( $bucket, $region );
		} else {
			$out = array( 'error' => __( 'Failed to retrieve bucket region.', 'as3cf' ) );
		}

		echo json_encode( $out );
		exit;
	}

	/**
	 * Save bucket and bucket's region
	 *
	 * @param string $bucket_name
	 * @param bool $manual if we are entering the bucket via the manual input form
	 *
	 * @return string|bool|WP_Error Region on success
	 */
	function save_bucket( $bucket_name, $manual = false ) {
		if ( $bucket_name ) {
			$this->get_settings();
			// first time bucket select - enable main options by default
			if ( ! $this->get_setting( 'bucket' ) ) {
				$this->set_setting( 'copy-to-s3', '1' );
				$this->set_setting( 'serve-from-s3', '1' );
			}
			$this->set_setting( 'bucket', $bucket_name );
			$region = $this->get_bucket_region( $bucket_name );
			if ( is_wp_error( $region ) ) {
				return false;
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

	function ajax_manual_save_bucket() {
		$this->verify_ajax_request();

		$bucket = $this->ajax_check_bucket();

		$region = $this->save_bucket( $bucket, true );

		if ( $region !== false ) {
			$out = array(
				'success' => '1',
				'region'  => $region,
			);
			$out['can_write'] = $this->check_write_permission( $bucket, $region );
		} else {
			$out = array( 'error' => __( 'Failed to retrieve bucket region.', 'as3cf' ) );
		}

		echo json_encode( $out );
		exit;
	}

	function admin_menu( $aws ) {
		$hook_suffix = $aws->add_page( $this->plugin_title, $this->plugin_menu_title, 'manage_options', $this->plugin_slug, array( $this, 'render_page' ) );
		add_action( 'load-' . $hook_suffix , array( $this, 'plugin_load' ) );
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
	 * @param $bucket
	 *
	 * @return string|WP_Error
	 */
	function get_bucket_region( $bucket ) {
		try {
			$region = $this->get_s3client()->getBucketLocation( array( 'Bucket' => $bucket ) );
		}
		catch ( Exception $e ) {
			error_log( sprintf( __( 'There was an error attempting to get the region of the bucket %s: %s', 'as3cf' ), $bucket, $e->getMessage() ) );

			return new WP_Error( 'exception', $e->getMessage() );
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
		$region = strtolower( $region );

		switch ( $region ) {
			case 'eu' :
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
			$out = array( 'error' => $result->get_error_message() );
		} else {
			$out = array(
				'success' => '1',
				'buckets' => $result,
				'selected' => $this->get_setting( 'bucket' )
			);
		}

		echo json_encode( $out );
		exit;
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
	 * @return bool
	 */
	function check_write_permission( $bucket = null, $region = null ) {
		if ( is_null( $bucket ) ) {
			if ( ! ( $bucket = $this->get_setting( 'bucket' ) ) ) {
				// if no bucket set then no need check
				return true;
			}
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
			// write permission not found
			$can_write = false;
		}

		return $can_write;
	}

	function plugin_load() {
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;

		$src = plugins_url( 'assets/css/styles.css', $this->plugin_file_path );
		wp_enqueue_style( 'as3cf-styles', $src, array(), $version );

		$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		$src = plugins_url( 'assets/js/script' . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( 'as3cf-script', $src, array( 'jquery' ), $version, true );

		wp_localize_script( 'as3cf-script', 'as3cf_i18n', array(
			'create_bucket_prompt'  => __( 'Bucket Name:', 'as3cf' ),
			'create_bucket_error'   => __( 'Error creating bucket: ', 'as3cf' ),
			'create_bucket_nonce'   => wp_create_nonce( 'as3cf-create-bucket' ),
			'manual_bucket_nonce'   => wp_create_nonce( 'as3cf-manual-save-bucket' ),
			'get_buckets_error'     => __( 'Error fetching buckets: ', 'as3cf' ),
			'get_buckets_nonce'     => wp_create_nonce( 'as3cf-get-buckets' ),
			'save_bucket_error'     => __( 'Error saving bucket: ', 'as3cf' ),
			'save_bucket_nonce'     => wp_create_nonce( 'as3cf-save-bucket' ),
			'get_url_preview_nonce' => wp_create_nonce( 'as3cf-get-url-preview' ),
			'get_url_preview_error' => __( 'Error getting URL preview: ', 'as3cf' ),
			'save_alert'            => __( 'The changes you made will be lost if you navigate away from this page', 'as3cf' ),
		) );

		$this->handle_post_request();
	}

	function handle_post_request() {
		if ( empty( $_POST['action'] ) || 'save' != sanitize_key( $_POST['action'] ) ) { // input var okay
			return;
		}

		if ( empty( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'as3cf-save-settings' ) ) { // input var okay
			die( __( "Cheatin' eh?", 'amazon-web-services' ) );
		}

		$post_vars = array( 'bucket', 'region', 'domain', 'virtual-host', 'expires', 'permissions', 'cloudfront', 'object-prefix', 'copy-to-s3', 'serve-from-s3', 'remove-local-file', 'ssl', 'hidpi-images', 'object-versioning', 'use-yearmonth-folders', 'enable-object-prefix' );

		foreach ( $post_vars as $var ) {
			$this->remove_setting( $var );

			if ( ! isset( $_POST[ $var ] ) ) { // input var okay
				continue;
			}

			$value = sanitize_text_field( $_POST[ $var ] ); // input var okay

			$this->set_setting( $var, $value );
		}

		$this->save_settings();

		wp_redirect( 'admin.php?page=' . $this->plugin_slug . '&updated=1' );
		exit;
	}

	/**
	 * Display the main settings page for the plugin
	 */
	function render_page() {
		$this->aws->render_view( 'header', array( 'page_title' => $this->plugin_title ) );

		$aws_client = $this->aws->get_client();

		if ( is_wp_error( $aws_client ) ) {
			$this->render_view( 'error-fatal', array( 'message' => $aws_client->get_error_message() ) );
		}
		else {
			do_action( 'as3cf_pre_settings_render' );
			$this->render_view( 'settings' );
			do_action( 'as3cf_post_settings_render' );
		}

		$this->aws->render_view( 'footer' );
	}

	/**
	 * Get the prefix path for the files
	 *
	 * @param string $time
	 *
	 * @return string
	 */
	function get_dynamic_prefix( $time = null ) {
		$uploads = wp_upload_dir( $time );
		$prefix = '';
		if ( $this->get_setting( 'use-yearmonth-folders' ) ) {
			$prefix = str_replace( $this->get_base_upload_path(), '', $uploads['path'] );
		}

		// support legacy MS installs (<3.5 since upgraded) for subsites
		if ( is_multisite() && 1 != ( $blog_id = get_current_blog_id() ) && strpos( $prefix, 'sites/' ) === false ) {
			$details          = get_blog_details( $blog_id );
			$sitename         = basename( $details->siteurl );
			$legacy_ms_prefix = $sitename . '/files/';
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
			if ( 1 == $blog['blog_id'] ) {
				// ignore the first blog which doesn't have the ID in the table prefix
				continue;
			}
			$blog_ids[] = $blog['blog_id'];
		}

		return $blog_ids;
	}
}
