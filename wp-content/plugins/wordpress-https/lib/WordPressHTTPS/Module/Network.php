<?php 
/**
 * Network admin Settings Module
 * 
 * Adds the network settings page.
 *
 * @author Mike Ems
 * @package WordPressHTTPS
 * 
 */

class WordPressHTTPS_Module_Network extends Mvied_Plugin_Module {

	/**
	 * Initialize Module
	 *
	 * @param none
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			add_action('wp_ajax_' . $this->getPlugin()->getSlug() . '_network_settings_save', array(&$this, 'save'));
			if ( isset($_GET['page']) && strpos($_GET['page'], $this->getPlugin()->getSlug()) !== false && strpos($_SERVER['REQUEST_URI'], 'wp-admin/network') !== false ) {
				// Add meta boxes
				add_action('admin_init', array(&$this, 'add_meta_boxes'));

				// Add scripts
				add_action('admin_enqueue_scripts', array(&$this, 'admin_enqueue_scripts'));
			}
		}

		if ( is_multisite() ) {
			add_action('network_admin_menu', array(&$this, 'network_admin_menu'));
		}
	}

	/**
	 * Network admin panel menu option
	 * WordPress Hook - network_admin_menu
	 *
	 * @param none
	 * @return void
	 */
	public function network_admin_menu() {
		add_menu_page('HTTPS', 'HTTPS', 'manage_options', $this->getPlugin()->getSlug(), array(&$this, 'dispatch'), '', 88);
	}

	/**
	 * Add meta boxes to WordPress HTTPS Settings page.
	 *
	 * @param none
	 * @return void
	 */
	public function add_meta_boxes() {
		add_meta_box(
			$this->getPlugin()->getSlug() . '_settings',
			__( 'Network Settings', $this->getPlugin()->getSlug() ),
			array($this->getPlugin()->getModule('Admin'), 'meta_box_render'),
			'toplevel_page_' . $this->getPlugin()->getSlug() . '_network',
			'main',
			'core',
			array( 'metabox' => 'network' )
		);
		add_meta_box(
			$this->getPlugin()->getSlug() . '_donate2',
			__( 'Loading...', $this->getPlugin()->getSlug() ),
			array($this->getPlugin()->getModule('Admin'), 'meta_box_render'),
			'toplevel_page_' . $this->getPlugin()->getSlug() . '_network',
			'main',
			'low',
			array( 'metabox' => 'ajax', 'url' => 'http://wordpresshttps.com/client/donate2.php' )
		);
	}

	/**
	 * Dispatch request for settings page
	 *
	 * @param none
	 * @return void
	 */
	public function dispatch() {
		if ( !current_user_can('manage_network_options') ) {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}

		self::render();
	}

	/**
	 * Adds javascript and stylesheets to settings page in the admin panel.
	 * WordPress Hook - enqueue_scripts
	 *
	 * @param none
	 * @return void
	 */
	public function admin_enqueue_scripts() {
		wp_enqueue_style($this->getPlugin()->getSlug() . '-network-admin-page', $this->getPlugin()->getPluginUrl() . '/admin/css/network.css', array($this->getPlugin()->getSlug() . '-admin-page'), $this->getPlugin()->getVersion());
	}

	/**
	 * Render settings page
	 *
	 * @param none
	 * @return void
	 */
	public function render() {
		require_once($this->getPlugin()->getDirectory() . '/admin/templates/network.php');
	}
	
	/**
	 * Save Settings
	 *
	 * @param array $settings
	 * @return void
	 */
	public function save() {
		if ( !wp_verify_nonce($_POST['_wpnonce'], $this->getPlugin()->getSlug()) ) {
			return false;
		}

		$message = "Network settings saved.";
		$errors = array();
		$reload = false;
		$logout = false;

		if ( isset($_POST['blog']) && is_array($_POST['blog']) && sizeof($_POST['blog']) > 0 ) {
			foreach( $_POST['blog'] as $blog_id => $setting ) {
				foreach( $setting as $key => $value ) {
					if ( $key == 'ssl_host' && $value != '' ) {
						$blog_url = WordPressHTTPS_Url::fromString(get_site_url($blog_id, '', 'https'));
						$value = strtolower($value);
						// Add Scheme
						if ( strpos($value, 'http://') === false && strpos($value, 'https://') === false ) {
							$value = 'https://' . $value;
						}

						$ssl_host = WordPressHTTPS_Url::fromString($value);

						// Add Port
						$port = (($blog_url->getPort() && $blog_url->getPort() != 80 && $blog_url->getPort() != 443) ? $port : null);
						$ssl_host->setPort($port);

						// Add Path
						if ( strpos($ssl_host->getPath(), $blog_url->getPath()) !== true ) {
							$path = '/'. ltrim(str_replace(rtrim($blog_url->getPath(), '/'), '', $ssl_host->getPath()), '/');
							$ssl_host->setPath(rtrim($path, '/') . $blog_url->getPath());
						}
						$ssl_host->setPath(rtrim($ssl_host->getPath(), '/') . '/');
						$value = $ssl_host->toString();
					}
					$this->getPlugin()->setSetting($key, $value, $blog_id);
				}
			}
		}
		if ( isset($_POST['blog_default']) && is_array($_POST['blog_default']) && sizeof($_POST['blog_default']) > 0 ) {
			$this->getPlugin()->setSetting('network_defaults', $_POST['blog_default']);
		}

		if ( $logout ) {
			wp_logout();
		}

		require_once($this->getPlugin()->getDirectory() . '/admin/templates/ajax_message.php');
	}
	
}