<?php

class AWS_Plugin_Base {

	protected $plugin_file_path;
	protected $plugin_dir_path;
	protected $plugin_slug;
	protected $plugin_basename;
	protected $plugin_version;

	/**
	 * @var array
	 */
	private $settings;

	/**
	 * @var array
	 */
	private $defined_settings;

	function __construct( $plugin_file_path ) {
		$this->plugin_file_path = $plugin_file_path;
		$this->plugin_dir_path  = rtrim( plugin_dir_path( $plugin_file_path ), '/' );
		$this->plugin_basename  = plugin_basename( $plugin_file_path );
		$this->plugin_version   = $GLOBALS['aws_meta'][ $this->plugin_slug ]['version'];
	}

	/**
	 * Accessor for plugin version
	 *
	 * @return mixed
	 */
	public function get_plugin_version() {
		return $this->plugin_version;
	}

	/**
	 * Accessor for plugin slug
	 *
	 * @return string
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Accessor for plugin basename
	 *
	 * @return string
	 */
	public function get_plugin_basename() {
		return $this->plugin_basename;
	}

	/**
	 * Accessor for plugin file path
	 *
	 * @return string
	 */
	public function get_plugin_file_path() {
		return $this->plugin_file_path;
	}

	/**
	 * Accessor for plugin dir path
	 *
	 * @return string
	 */
	public function get_plugin_dir_path() {
		return $this->plugin_dir_path;
	}

	/**
	 * Get the plugin's settings array
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	function get_settings( $force = false ) {
		if ( is_null( $this->settings ) || $force ) {
			$this->settings = $this->filter_settings( get_site_option( static::SETTINGS_KEY ) );
		}

		return $this->settings;
	}

	/**
	 * Get all settings that have been defined via constant for the plugin
	 *
	 * @param bool $force
	 *
	 * @return array
	 */
	function get_defined_settings( $force = false ) {
		if ( is_null( $this->defined_settings ) || $force ) {
			$this->defined_settings = array();
			$unserialized           = array();
			$class                  = get_class( $this );

			if ( defined( "$class::SETTINGS_CONSTANT" ) ) {
				$constant = static::SETTINGS_CONSTANT;
				if ( defined( $constant ) ) {
					$unserialized = maybe_unserialize( constant( $constant ) );
				}
			}

			$unserialized = is_array( $unserialized ) ? $unserialized : array();

			foreach ( $unserialized as $key => $value ) {
				if ( ! in_array( $key, $this->get_settings_whitelist() ) ) {
					continue;
				}

				if ( is_bool( $value ) || is_null( $value ) ) {
					$value = (int) $value;
				}

				if ( is_numeric( $value ) ) {
					$value = strval( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				$this->defined_settings[ $key ] = $value;
			}
		}

		return $this->defined_settings;
	}

	/**
	 * Filter the plugin settings array
	 *
	 * @param array $settings
	 *
	 * @return array $settings
	 */
	function filter_settings( $settings ) {
		$defined_settings = $this->get_defined_settings();

		// Bail early if there are no defined settings
		if ( empty( $defined_settings ) ) {
			return $settings;
		}

		foreach ( $defined_settings as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $settings;
	}

	/**
	 * Get the whitelisted settings for the plugin.
	 * Meant to be overridden in child classes.
	 *
	 * @return array
	 */
	function get_settings_whitelist() {
		return array();
	}

	/**
	 * Get a specific setting
	 *
	 * @param        $key
	 * @param string $default
	 *
	 * @return string
	 */
	function get_setting( $key, $default = '' ) {
		$this->get_settings();

		if ( isset( $this->settings[ $key ] ) ) {
			$setting = $this->settings[ $key ];
		} else {
			$setting = $default;
		}

		return apply_filters( 'aws_get_setting', $setting, $key );
	}

	/**
	 * Gets a single setting that has been defined in the plugin settings constant
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return mixed
	 */
	function get_defined_setting( $key, $default = '' ) {
		$defined_settings = $this->get_defined_settings();
		$setting = isset( $defined_settings[ $key ] ) ? $defined_settings[ $key ] : $default;

		return $setting;
	}

	/**
	 * Delete a setting
	 *
	 * @param $key
	 */
	function remove_setting( $key ) {
		$this->get_settings();

		if ( isset( $this->settings[ $key ] ) ) {
			unset( $this->settings[ $key ] );
		}
	}

	/**
	 * Removes a defined setting from the defined_settings array.
	 *
	 * Does not unset the actual constant.
	 *
	 * @param $key
	 */
	function remove_defined_setting( $key ) {
		$this->get_defined_settings();

		if ( isset( $this->defined_settings[ $key ] ) ) {
			unset( $this->defined_settings[ $key ] );
		}
	}

	/**
	 * Render a view template file
	 *
	 * @param       $view View filename without the extension
	 * @param array $args Arguments to pass to the view
	 */
	function render_view( $view, $args = array() ) {
		extract( $args );
		include $this->plugin_dir_path . '/view/' . $view . '.php';
	}

	/**
	 * Set a setting
	 *
	 * @param $key
	 * @param $value
	 */
	function set_setting( $key, $value ) {
		$this->settings[ $key ] = $value;
	}

	/**
	 * Bulk set the settings array
	 *
	 * @param array $settings
	 */
	function set_settings( $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Save the settings to the database
	 */
	function save_settings() {
		update_site_option( static::SETTINGS_KEY, $this->settings );
	}

	/**
	 * The URL for the plugin action link for the plugin on the plugins page.
	 *
	 * @return string
	 */
	function get_plugin_page_url() {
		return network_admin_url( 'admin.php?page=' . $this->plugin_slug );
	}

	/**
	 * The text for the plugin action link for the plugin on the plugins page.
	 *
	 * @return string
	 */
	function get_plugin_action_settings_text() {
		return __( 'Settings', 'amazon-web-services' );
	}

	/**
	 * Add a link to the plugin row for the plugin on the plugins page.
	 * Needs to be implemented for an extending class using -
	 *     add_filter( 'plugin_action_links', array( $this, 'plugin_actions_settings_link' ), 10, 2 );
	 *
	 * @param array $links
	 * @param string $file
	 *
	 * @return array
	 */
	function plugin_actions_settings_link( $links, $file ) {
		$url  = $this->get_plugin_page_url();
		$text = $this->get_plugin_action_settings_text();

		$settings_link = '<a href="' . $url . '">' . esc_html( $text ) . '</a>';

		if ( $file == $this->plugin_basename ) {
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	/**
	 * Get the version used for script enqueuing
	 *
	 * @return mixed
	 */
	public function get_asset_version() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? time() : $this->plugin_version;
	}

	/**
	 * Get the filename suffix used for script enqueuing
	 *
	 * @return mixed
	 */
	public function get_asset_suffix() {
		return defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
	}
}