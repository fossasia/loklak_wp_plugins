<?php

class WP_SendGrid_Settings {

	// Unique identifier for the settings page
	const SETTINGS_PAGE_SLUG = 'wp-sendgrid-settings';
	const SETTINGS_SECTION_ID = 'wp-sendgrid-account-settings';

	// Where WP SendGrid settings are stored
	const SETTINGS_OPTION_NAME = 'wp_sendgrid_options';
	const SETTINGS_NETWORK_OPTION_NAME = 'wp_sendgrid_network_options';

	// Constants to hold the API access options
	const API_REST = 'rest';
	const API_SMTP = 'smtp';

	private static $settings;
	private static $default_settings = array( 
		'username' => '',
		'password' => '',
		'api'      => self::API_REST,
		'secure'   => false
	);

	public static function start() {
		add_action( 'admin_init',         array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu',         array( __CLASS__, 'register_menu' ) );
		add_action( 'network_admin_menu', array( __CLASS__, 'register_network_menu') );
		add_action( 'current_screen',     array( __CLASS__, 'queue_resources' ) );
		add_action( 'wp_ajax_wp_sendgrid_check_settings', array( __CLASS__, 'ajax_check_settings' ) );

		// This action will be triggered when the user submits the network admin SG settings form.
		//  Based on: http://wordpress.stackexchange.com/questions/64968/settings-api-in-multisite-missing-update-message
		add_action( 'network_admin_edit_update_sendgrid_network_options', array(__CLASS__, 'update_network_options'));
	}

	public static function register_settings() {
	$option_name = ( self::is_network_admin_page() ) ? self::SETTINGS_NETWORK_OPTION_NAME : self::SETTINGS_OPTION_NAME;
		// Register settings and sections
		register_setting( self::SETTINGS_PAGE_SLUG, $option_name, array( __CLASS__, 'validate_settings' ) );
		add_settings_section( self::SETTINGS_SECTION_ID, __( 'Account Settings' ), array( __CLASS__, 'show_settings_section_description' ), self::SETTINGS_PAGE_SLUG );

		// Username/Password
		self::add_settings_field( 'username', __( 'Your SendGrid Username' ), 'text' );
		self::add_settings_field( 'password', __( 'Your SendGrid Password' ), 'password' );
		self::add_settings_field( 'api', __( 'Send Emails With' ), 'select', array(
			'description' => __( 'You shouldn\'t need to change this unless the default doesn\'t work on your server' ),
			'options' => array( self::API_REST => __( 'REST' ), self::API_SMTP => __( 'SMTP' ) )
		) );
		self::add_settings_field( 'secure', __( 'Secure Connection' ), 'checkbox', array(
			'description' => ' Make sure you have the SSL extension for PHP installed before enabling.',
			'label' => 'Use a secure connection (recommended).'
		) );

		if ( self::is_network_admin_page() ) {
			// Only add the override option if viewing the settings under network admin
			self::add_settings_field( 'override', __('Allow Override'), 'checkbox', array(
				'label' => __('Allow individual sites to override the network-level settings'),
			));
		}
		//add_settings_field( self::SETTINGS_SECTION_ID . '-username', 'Your SendGrid Username', array( __CLASS__, 'show_settings_field' ), self::SETTINGS_PAGE_SLUG, self::SETTINGS_SECTION_ID );
	}

	public static function register_menu() {
		// Check to be sure the network settings allow for individual site override, before adding option page
		$network_settings = self::get_network_settings();
		if ( $network_settings['override'] ) {
			add_options_page( __( 'SendGrid Settings' ), __( 'SendGrid Settings' ),
				'manage_options', self::SETTINGS_PAGE_SLUG, array( __CLASS__, 'show_settings_page' ) );
		}
	}

	public static function register_network_menu() {
		// add_options_page does not work for the network admin page, so we need to use add_submenu_page
		add_submenu_page('settings.php', __('SendGrid Settings'), __('SendGrid Settings'),
			'manage_network_options', self::SETTINGS_PAGE_SLUG, array(__CLASS__, 'show_settings_page'));
	}

	public static function queue_resources($screen) {
		if ( 'settings_page_' . self::SETTINGS_PAGE_SLUG == $screen->base || 'settings_page_' . self::SETTINGS_PAGE_SLUG . '-network' == $screen->base ) {
			wp_enqueue_script( 'wp-sendgrid', WP_SendGrid::plugin_url( 'resources/wp-sendgrid.js' ), array( 'jquery' ) );
			wp_enqueue_style( 'wp-sendgrid', WP_SendGrid::plugin_url( 'resources/wp-sendgrid.css' ) );
		}
	}

	public static function ajax_check_settings() {
		$user_id = get_current_user_id();
		$user = get_userdata( $user_id );
		if ( wp_mail( $user->user_email, __( 'SendGrid Test' ), __( 'If you\'re reading this, it looks like your SendGrid settings are correct' ) ) ) {
			wp_send_json( array( 'success' => 'Test email sent. If it doesn\'t show up, double check your settings.' ) );
		} else {
			wp_send_json( array( 'error' => 'There was a problem sending the test email. Double check your settings.' ) );
		}
	}

	public static function show_settings_page() {
		if ( self::is_network_admin_page() ) {
			// Compose the network admin url to post to.
			$url = add_query_arg( 'action', 'update_sendgrid_network_options', network_admin_url('edit.php'));

			if ( isset( $_REQUEST['settings-updated'] ) ) {
				// If the request contains a 'settings-updated' parameter, we know we just saved the settings
				// and need to display an 'updated' message along with the 'test' button.
				// We can't call 'validate_settings' then 'settings_errors' because the 'update_network_options'
				// call happens outside of the normal sttings API (we had to create a custom method to handle
				// saving the network admin settings)
				?>
				<div id='setting-error-wp_sendgrid_settings_updated' class='updated settings-error'>
					<p>
						<strong><?php _e( 'SendGrid options updated' ); ?> 
							<input type="button" class="button" id="wp-sendgrid-test-settings" value="<?php echo esc_attr( __( 'Send Test Email' ) ); ?>" />
							<span class="spinner"></span>
							<span id="wp-sendgrid-test-settings-response"></span>
						</strong>
					</p>
				</div>
				<?php
			}
		} else {
			// Use the regular admin options url.
			$url = admin_url( 'options.php' );
		}
		?>
		<div class="wrap">
			<h2><?php _e( 'WP SendGrid Settings' ); ?></h2>
			<form action="<?php echo esc_url( $url ); ?>" method="POST">
				<?php do_action( 'wp_sendgrid_settings_form_begin' ); ?>
				<?php settings_fields( self::SETTINGS_PAGE_SLUG ); ?>
				<?php do_settings_sections( self::SETTINGS_PAGE_SLUG ); ?>
				<?php do_action( 'wp_sendgrid_settings_before_submit_button'); ?>
				<?php submit_button(); ?>
				<?php do_action( 'wp_sendgrid_settings_after_submit_button' ); ?>
				<?php do_action( 'wp_sendgrid_settings_form_end' ); ?>
			</form>
		</div>
		<?php
	}

	public static function show_settings_section_description() {
		WP_SendGrid::load_view( 'settings-section-description.php' );
	}

	public static function add_settings_field( $id, $label, $type, $args = array() ) {
		$default_args = array(
			'id' => $id,
			'description' => '',
			'type' => $type,
		);
		$args = array_merge( $default_args, $args );

		$callback = isset( $args['callback'] ) ? $args['callback'] : array( __CLASS__, "show_{$type}_field" );

		add_settings_field( self::SETTINGS_SECTION_ID . '-' . $id, $label, $callback, self::SETTINGS_PAGE_SLUG, self::SETTINGS_SECTION_ID, $args );
	}

	public static function load_field_view( $view, $args ) {
		$args['settings'] = self::is_network_admin_page() ? self::get_network_settings() : self::get_local_settings();
		$args['value'] = $args['settings'][$args['id']];
		WP_SendGrid::load_view( $view, $args );
	}

	public static function show_input_field( $type, $args ) {
		$args['type'] = $type;
		self::load_field_view( 'input-field.php', $args );
	}

	public static function show_text_field( $args ) {
		self::show_input_field( 'text', $args );
	}

	public static function show_password_field( $args ) {
		self::show_input_field( 'password', $args );
	}

	public static function show_select_field( $args ) {
		self::load_field_view( 'select-field.php', $args );
	}

	public static function show_checkbox_field( $args ) {
		self::load_field_view( 'checkbox-field.php', $args );
	}

	public static function get_default_settings() {
		return apply_filters( 'wp_sendgrid_default_settings', self::$default_settings );
	}

	public static function get_default_network_settings() {
		$settings = array_merge( self::$default_settings, array( 'override' => true ) );
		return apply_filters( 'wp_sendgrid_default_network_settings', $settings );
	}

	public static function validate_settings( $settings ) {
		add_settings_error( 'general', 'wp_sendgrid_settings_updated',
			__( 'SendGrid options updated' ) .
			' <input type="button" class="button" id="wp-sendgrid-test-settings" value="' .
			esc_attr( __( 'Send Test Email' ) ) . '" /><span class="spinner"></span>' .
			' <span id="wp-sendgrid-test-settings-response"></span>', 'updated' );
		$settings = apply_filters( 'wp_sendgrid_validate_settings', $settings );
		return $settings;
	}

	public static function get_settings() {
		if ( isset( self::$settings ) ) {
			return self::$settings;
		}

		// First check the network settings.
		$settings = self::get_network_settings();

		// If network settings are empty, or override is enabled, check local settings
		if ( isset( $settings['override'] ) && $settings['override'] && !self::is_network_admin_page() ) {
			$local_settings = self::get_local_settings();
			if ( !empty( $local_settings ) ) {
				$settings = array_merge( $settings, $local_settings );
			}
		}
		
		self::$settings = apply_filters( 'wp_sendgrid_get_settings', $settings );
		return self::$settings;
	}

	private static function get_local_settings() {
		$settings = array_merge( self::get_default_settings(), get_option( self::SETTINGS_OPTION_NAME, array() ) );
		return apply_filters( 'wp_sendgrid_get_local_settings', $settings );
	}

	private static function get_network_settings() {
		$settings = get_site_option( self::SETTINGS_NETWORK_OPTION_NAME, array());
		$settings = array_merge( self::get_default_network_settings(), $settings );
		return apply_filters( 'wp_sendgrid_get_network_settings', $settings );
	}

	private static function is_network_admin_page() {
		return ( defined( 'WP_NETWORK_ADMIN' ) && WP_NETWORK_ADMIN == 1 );
	}

	public static function update_network_options() {
		// Only make changes if the self::SETTINGS_OPTION_NAME key exists.
		//  Note: we use the SETTINGS_OPTION_NAME because it is used by files in the '/views' folder
		if ( isset( $_REQUEST[self::SETTINGS_OPTION_NAME] ) ) {
			$value = stripslashes_deep( $_REQUEST[self::SETTINGS_OPTION_NAME] );
		
			// Since a false value from the override checkbox won't be saved, we need to add it here.
			$_REQUEST[self::SETTINGS_OPTION_NAME]['override'] = isset( $_REQUEST[self::SETTINGS_OPTION_NAME]['override'] );
			// Update the network option.
			update_site_option( self::SETTINGS_NETWORK_OPTION_NAME, $_REQUEST[self::SETTINGS_OPTION_NAME] );
		
			// Redirect back to the network settings page.
			$params = array( 'page' => self::SETTINGS_PAGE_SLUG, 'settings-updated' => 'true' );
			wp_redirect( add_query_arg( $params, network_admin_url('settings.php') ) );
			exit();
		}
	}
}

WP_SendGrid_Settings::start();
