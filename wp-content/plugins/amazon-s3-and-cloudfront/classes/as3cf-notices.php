<?php

class AS3CF_Notices {

	/**
	 * @var AS3CF_Notices
	 */
	protected static $instance = null;

	/**
	 * @var Amazon_S3_And_CloudFront
	 */
	private $as3cf;

	/**
	 * @var string
	 */
	private $plugin_file_path;

	/**
	 * Make this class a singleton
	 *
	 * Use this instead of __construct()
	 *
	 * @param Amazon_S3_And_CloudFront $as3cf
	 * @param string                   $plugin_file_path
	 *
	 * @return AS3CF_Notices
	 */
	public static function get_instance( $as3cf, $plugin_file_path ) {
		if ( ! isset( static::$instance ) ) {
			static::$instance = new static( $as3cf, $plugin_file_path );
		}

		return static::$instance;
	}

	/**
	 * Constructor
	 *
	 * @param Amazon_S3_And_CloudFront $as3cf
	 * @param string                   $plugin_file_path
	 */
	protected function __construct( $as3cf, $plugin_file_path ) {
		$this->as3cf            = $as3cf;
		$this->plugin_file_path = $plugin_file_path;

		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'network_admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'as3cf_pre_tab_render', array( $this, 'admin_notices' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_notice_scripts' ) );
		add_action( 'wp_ajax_as3cf-dismiss-notice', array( $this, 'ajax_dismiss_notice' ) );
	}

	/**
	 * As this class is a singelton it should not be clone-able
	 */
	protected function __clone() {
		// Singleton
	}

	/**
	 * Create a notice
	 *
	 * @param string $message
	 * @param array  $args
	 *
	 * @return string notice ID
	 */
	public function add_notice( $message, $args = array() ) {
		$defaults = array(
			'type'                  => 'info',
			'dismissible'           => true,
			'inline'                => false,
			'flash'                 => true,
			'only_show_to_user'     => true, // The user who has initiated an action resulting in notice. Otherwise show to all users.
			'user_capabilities'     => array( 'as3cf_compat_check', 'check_capabilities' ), // A user with these capabilities can see the notice. Can be a callback with the first array item the name of global class instance.
			'only_show_in_settings' => false,
			'only_show_on_tab'      => false, // Only show on a specific WP Offload S3 tab.
			'custom_id'             => '',
			'auto_p'                => true, // Automatically wrap the message in a <p>
			'class'                 => '', // Extra classes for the notice
			'show_callback'         => false, // Callback to display extra info on notices. Passing a callback automatically handles show/hide toggle.
			'callback_args'         => array(), // Arguments to pass to the callback.
			'lock_key'              => '', // If lock key set, do not show message until lock released.
		);

		$notice                 = array_intersect_key( array_merge( $defaults, $args ), $defaults );
		$notice['message']      = $message;
		$notice['triggered_by'] = get_current_user_id();
		$notice['created_at']   = time();

		if ( $notice['custom_id'] ) {
			$notice['id'] = $notice['custom_id'];
		} else {
			$notice['id'] = apply_filters( 'as3cf_notice_id_prefix', 'as3cf-notice-' ) . sha1( trim( $notice['message'] ) . trim( $notice['lock_key'] ) );
		}

		if ( isset( $notice['only_show_on_tab'] ) && false !== $notice['only_show_on_tab'] ) {
			$notice['inline'] = true;
		}

		$this->save_notice( $notice );

		return $notice['id'];
	}

	/**
	 * Save a notice
	 *
	 * @param array $notice
	 */
	protected function save_notice( $notice ) {
		$user_id = get_current_user_id();

		if ( $notice['only_show_to_user'] ) {
			$notices = get_user_meta( $user_id, 'as3cf_notices', true );
		} else {
			$notices = get_site_transient( 'as3cf_notices' );
		}

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		if ( ! array_key_exists( $notice['id'], $notices ) ) {
			$notices[ $notice['id'] ] = $notice;

			if ( $notice['only_show_to_user'] ) {
				update_user_meta( $user_id, 'as3cf_notices', $notices );
			} else {
				set_site_transient( 'as3cf_notices', $notices );
			}
		}
	}

	/**
	 * Remove a notice
	 *
	 * @param array $notice
	 */
	public function remove_notice( $notice ) {
		$user_id = get_current_user_id();

		if ( $notice['only_show_to_user'] ) {
			$notices = get_user_meta( $user_id, 'as3cf_notices', true );
		} else {
			$notices = get_site_transient( 'as3cf_notices' );
		}

		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		if ( array_key_exists( $notice['id'], $notices ) ) {
			unset( $notices[ $notice['id'] ] );

			if ( $notice['only_show_to_user'] ) {
				$this->update_user_meta( $user_id, 'as3cf_notices', $notices );
			} else {
				$this->set_site_transient( 'as3cf_notices', $notices );
			}
		}
	}

	/**
	 * Remove a notice by it's ID
	 *
	 * @param string $notice_id
	 */
	public function remove_notice_by_id( $notice_id ) {
		$notice = $this->find_notice_by_id( $notice_id );
		if ( $notice ) {
			$this->remove_notice( $notice );
		}
	}

	/**
	 * Dismiss a notice
	 *
	 * @param string $notice_id
	 */
	protected function dismiss_notice( $notice_id ) {
		$user_id = get_current_user_id();

		$notice = $this->find_notice_by_id( $notice_id );
		if ( $notice ) {
			if ( $notice['only_show_to_user'] ) {
				$notices = get_user_meta( $user_id, 'as3cf_notices', true );
				unset( $notices[ $notice['id'] ] );

				$this->update_user_meta( $user_id, 'as3cf_notices', $notices );
			} else {
				$dismissed_notices = $this->get_dismissed_notices( $user_id );

				if ( ! in_array( $notice['id'], $dismissed_notices ) ) {
					$dismissed_notices[] = $notice['id'];
					update_user_meta( $user_id, 'as3cf_dismissed_notices', $dismissed_notices );
				}
			}
		}
	}

	/**
	 * Check if a notice has been dismissed for the current user
	 *
	 * @param null|int $user_id
	 *
	 * @return array
	 */
	public function get_dismissed_notices( $user_id = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$dismissed_notices = get_user_meta( $user_id, 'as3cf_dismissed_notices', true );
		if ( ! is_array( $dismissed_notices ) ) {
			$dismissed_notices = array();
		}

		return $dismissed_notices;
	}

	/**
	 * Un-dismiss a notice for a user
	 *
	 * @param string     $notice_id
	 * @param null|int   $user_id
	 * @param null|array $dismissed_notices
	 */
	public function undismiss_notice_for_user( $notice_id, $user_id = null, $dismissed_notices = null ) {
		if ( is_null( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		if ( is_null( $dismissed_notices ) ) {
			$dismissed_notices = $this->get_dismissed_notices( $user_id );
		}

		$key = array_search( $notice_id, $dismissed_notices );
		unset( $dismissed_notices[ $key ] );

		$this->update_user_meta( $user_id, 'as3cf_dismissed_notices', $dismissed_notices );
	}

	/**
	 * Un-dismiss a notice for all users that have dismissed it
	 *
	 * @param string $notice_id
	 */
	public function undismiss_notice_for_all( $notice_id ) {
		$args = array(
			'meta_key'     => 'as3cf_dismissed_notices',
			'meta_value'   => $notice_id,
			'meta_compare' => 'LIKE',
		);

		$users = get_users( $args );

		foreach( $users as $user ) {
			$this->undismiss_notice_for_user( $notice_id, $user->ID );
		}
	}

	/**
	 * Find a notice by it's ID
	 *
	 * @param string $notice_id
	 *
	 * @return array|null
	 */
	public function find_notice_by_id( $notice_id ) {
		$user_id = get_current_user_id();

		$user_notices = get_user_meta( $user_id, 'as3cf_notices', true );
		if ( ! is_array( $user_notices ) ) {
			$user_notices = array();
		}

		$global_notices = get_site_transient( 'as3cf_notices' );
		if ( ! is_array( $global_notices ) ) {
			$global_notices = array();
		}
		$notices = array_merge( $user_notices, $global_notices );

		if ( array_key_exists( $notice_id, $notices ) ) {
			return $notices[ $notice_id ];
		}

		return null;
	}

	/**
	 * Show the notices
	 *
	 * @param string $tab
	 */
	public function admin_notices( $tab = '' ) {
		if ( empty( $tab ) ) {
			// Callbacks with no $tab property return empty string, so convert to bool.
			$tab = false;
		}

		$user_id           = get_current_user_id();
		$dismissed_notices = get_user_meta( $user_id, 'as3cf_dismissed_notices', true );
		if ( ! is_array( $dismissed_notices ) ) {
			$dismissed_notices = array();
		}

		$user_notices = get_user_meta( $user_id, 'as3cf_notices', true );
		$user_notices = $this->cleanup_corrupt_user_notices( $user_id, $user_notices );
		if ( is_array( $user_notices ) && ! empty( $user_notices ) ) {
			foreach ( $user_notices as $notice ) {
				$this->maybe_show_notice( $notice, $dismissed_notices, $tab );
			}
		}

		$global_notices = get_site_transient( 'as3cf_notices' );
		if ( is_array( $global_notices ) && ! empty( $global_notices ) ) {
			foreach ( $global_notices as $notice ) {
				$this->maybe_show_notice( $notice, $dismissed_notices, $tab );
			}
		}
	}

	/**
	 * Cleanup corrupt user notices. Corrupt notices start with a
	 * numerically indexed array, opposed to string ID
	 *
	 * @param int   $user_id
	 * @param array $notices
	 *
	 * @return array
	 */
	protected function cleanup_corrupt_user_notices( $user_id, $notices ) {
		if ( ! is_array( $notices ) || empty( $notices ) ) {
			return $notices;
		}

		foreach ( $notices as $key => $notice ) {
			if ( is_int( $key ) ) {
				// Corrupt, remove
				unset( $notices[ $key ] );

				$this->update_user_meta( $user_id, 'as3cf_notices', $notices );
			}
		}

		return $notices;
	}

	/**
	 * If it should be shown, display an individual notice
	 *
	 * @param array       $notice
	 * @param array       $dismissed_notices
	 * @param string|bool $tab
	 */
	protected function maybe_show_notice( $notice, $dismissed_notices, $tab ) {
		$screen = get_current_screen();
		if ( $notice['only_show_in_settings'] && false === strpos( $screen->id, $this->as3cf->hook_suffix ) ) {
			return;
		}

		if ( ! $notice['only_show_to_user'] && in_array( $notice['id'], $dismissed_notices ) ) {
			return;
		}

		if ( ! isset( $notice['only_show_on_tab'] ) && false !== $tab ) {
			return;
		}

		if ( isset( $notice['only_show_on_tab'] ) && $tab !== $notice['only_show_on_tab'] ) {
			return;
		}

		if ( ! $this->check_capability_for_notice( $notice ) ) {
			return;
		}

		if ( ! empty( $notice['lock_key'] ) && class_exists( 'AS3CF_Tool' ) && AS3CF_Tool::lock_key_exists( $notice['lock_key'] ) ) {
			return;
		}

		if ( 'info' === $notice['type'] ) {
			$notice['type'] = 'notice-info';
		}

		$this->as3cf->render_view( 'notice', $notice );

		if ( $notice['flash'] ) {
			$this->remove_notice( $notice );
		}
	}

	/**
	 * Ensure the user has the correct capabilities for the notice to be displayed.
	 *
	 * @param array $notice
	 *
	 * @return bool|mixed
	 */
	protected function check_capability_for_notice( $notice ) {
		if ( ! isset( $notice['user_capabilities'] ) || empty( $notice['user_capabilities'] ) ) {
			// No capability restrictions, show the notice
			return true;
		}

		$caps = $notice['user_capabilities'];

		if ( 2 === count( $caps ) && isset( $GLOBALS[ $caps[0] ] ) && is_callable( array( $GLOBALS[ $caps[0] ], $caps[1] ) ) ) {
			// Handle callback passed for capabilities
			return call_user_func( array( $GLOBALS[ $caps[0] ], $caps[1] ) );
		}

		foreach ( $caps as $cap ) {
			if ( is_string( $cap ) && ! current_user_can( $cap ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Enqueue notice scripts in the admin
	 */
	public function enqueue_notice_scripts() {
		$version = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $GLOBALS['aws_meta']['amazon-s3-and-cloudfront']['version'];
		$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		// Enqueue notice.css & notice.js globally as some notices can be shown & dismissed on any admin page.
		$src = plugins_url( 'assets/css/notice.css', $this->plugin_file_path );
		wp_enqueue_style( 'as3cf-notice', $src, array(), $version );

		$src = plugins_url( 'assets/js/notice' . $suffix . '.js', $this->plugin_file_path );
		wp_enqueue_script( 'as3cf-notice', $src, array( 'jquery' ), $version, true );

		wp_localize_script( 'as3cf-notice', 'as3cf_notice', array(
			'strings' => array(
				'dismiss_notice_error' => __( 'Error dismissing notice.', 'amazon-s3-and-cloudfront' ),
			),
			'nonces'  => array(
				'dismiss_notice' => wp_create_nonce( 'as3cf-dismiss-notice' ),
			),
		) );
	}

	/**
	 * Handler for AJAX request to dismiss a notice
	 */
	public function ajax_dismiss_notice() {
		$this->as3cf->verify_ajax_request();

		if ( ! isset( $_POST['notice_id'] ) || ! ( $notice_id = sanitize_text_field( $_POST['notice_id'] ) ) ) {
			$out = array( 'error' => __( 'Invalid notice ID.', 'amazon-s3-and-cloudfront' ) );
			$this->as3cf->end_ajax( $out );
		}

		$this->dismiss_notice( $notice_id );

		$out = array(
			'success' => '1',
		);
		$this->as3cf->end_ajax( $out );
	}

	/**
	 * Helper to update/delete user meta
	 *
	 * @param int    $user_id
	 * @param string $key
	 * @param array  $value
	 */
	protected function update_user_meta( $user_id, $key, $value ) {
		if ( empty( $value ) ) {
			delete_user_meta( $user_id, $key);
		} else {
			update_user_meta( $user_id, $key, $value );
		}
	}

	/**
	 * Helper to update/delete site transient
	 *
	 * @param string $key
	 * @param array  $value
	 */
	protected function set_site_transient( $key, $value ) {
		if ( empty( $value ) ) {
			delete_site_transient( $key );
		} else {
			set_site_transient( $key, $value );
		}
	}

}