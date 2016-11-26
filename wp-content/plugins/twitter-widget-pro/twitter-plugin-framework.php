<?php
/**
 * Version: 1.0
 */
/**
 * Changelog:
 *
 * 1.0:
 *  - First version of the plugin!
 **/
if (!class_exists('TwitterPlugin')) {
	/**
	 * Abstract class TwitterPlugin used as a WordPress Plugin framework
	 *
	 * @abstractet Feed Plugin
	 */
	abstract class TwitterPlugin {
		/**
		 * @var array Plugin settings
		 */
		protected $_settings;

		/**
		 * @var string - The options page name used in the URL
		 */
		protected $_hook = '';

		/**
		 * @var string - The filename for the main plugin file
		 */
		protected $_file = '';

		/**
		 * @var string - The options page title
		 */
		protected $_pageTitle = '';

		/**
		 * @var string - The options page menu title
		 */
		protected $_menuTitle = '';

		/**
		 * @var string - The access level required to see the options page
		 */
		protected $_accessLevel = '';

		/**
		 * @var string - The option group to register
		 */
		protected $_optionGroup = '';

		/**
		 * @var array - An array of options to register to the option group
		 */
		protected $_optionNames = array();

		/**
		 * @var array - An associated array of callbacks for the options, option name should be index, callback should be value
		 */
		protected $_optionCallbacks = array();

		/**
		 * @var string - The plugin slug used on WordPress.org
		 */
		protected $_slug = '';

		protected $_optionsPageAction = 'options.php';

		/**
		 * This is our constructor, which is private to force the use of getInstance()
		 * @return void
		 */
		protected function __construct() {
			if ( is_callable( array($this, '_init') ) )
				$this->_init();

			$this->_get_settings();
			if ( is_callable( array($this, '_post_settings_init') ) )
				$this->_post_settings_init();

			add_filter( 'init', array( $this, 'init_locale' ) );
			add_action( 'admin_init', array( $this, 'register_options' ) );
			add_filter( 'plugin_action_links', array( $this, 'add_plugin_page_links' ), 10, 2 );
			add_filter( 'plugin_row_meta', array( $this, 'add_plugin_meta_links' ), 10, 2 );
			add_action( 'admin_menu', array( $this, 'register_options_page' ) );
			if ( is_callable(array( $this, 'add_options_meta_boxes' )) )
				add_action( 'admin_init', array( $this, 'add_options_meta_boxes' ) );

			add_action( 'admin_init', array( $this, 'add_default_options_meta_boxes' ) );
			add_action( 'admin_print_scripts', array( $this,'admin_print_scripts' ) );
			add_action( 'admin_enqueue_scripts', array( $this,'admin_enqueue_scripts' ) );

			add_action ( 'in_plugin_update_message-'.$this->_file , array ( $this , 'changelog' ), null, 2 );
			add_action('admin_enqueue_scripts', array( $this, 'add_wp_twitter_widget_script' ) );
		}

		public function init_locale() {
			$lang_dir = basename(dirname(__FILE__)) . '/languages';
			load_plugin_textdomain( $this->_slug, 'wp-content/plugins/' . $lang_dir, $lang_dir);
		}

		protected function _get_settings() {
			foreach ( $this->_optionNames as $opt ) {
				$this->_settings[$opt] = apply_filters($this->_slug.'-opt-'.$opt, get_option($opt));
			}
		}

		public function register_options() {
			foreach ( $this->_optionNames as $opt ) {
				if ( !empty($this->_optionCallbacks[$opt]) && is_callable( $this->_optionCallbacks[$opt] ) ) {
					$callback = $this->_optionCallbacks[$opt];
				} else {
					$callback = '';
				}
				register_setting( $this->_optionGroup, $opt, $callback );
			}
		}

		public function changelog ($pluginData, $newPluginData) {
			require_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );

			$plugin = plugins_api( 'plugin_information', array( 'slug' => $newPluginData->slug ) );

			if ( !$plugin || is_wp_error( $plugin ) || empty( $plugin->sections['changelog'] ) ) {
				return;
			}

			$changes = $plugin->sections['changelog'];
			$pos = strpos( $changes, '<h4>' . preg_replace('/[^\d\.]/', '', $pluginData['Version'] ) );
			if ( $pos !== false ) {
				$changes = trim( substr( $changes, 0, $pos ) );
			}

			$replace = array(
				'<ul>'	=> '<ul style="list-style: disc inside; padding-left: 15px; font-weight: normal;">',
				'<h4>'	=> '<h4 style="margin-bottom:0;">',
			);
			echo str_replace( array_keys($replace), $replace, $changes );
		}

		public function register_options_page() {
			if ( apply_filters( 'rpf-options_page-'.$this->_slug, true ) && is_callable( array( $this, 'options_page' ) ) )
				add_options_page( $this->_pageTitle, $this->_menuTitle, $this->_accessLevel, $this->_hook, array( $this, 'options_page' ) );
		}

		protected function _filter_boxes_main($boxName) {
			if ( 'main' == strtolower($boxName) )
				return false;

			return $this->_filter_boxes_helper($boxName, 'main');
		}

		protected function _filter_boxes_sidebar($boxName) {
			return $this->_filter_boxes_helper($boxName, 'sidebar');
		}

		protected function _filter_boxes_helper($boxName, $test) {
			return ( strpos( strtolower($boxName), strtolower($test) ) !== false );
		}

		public function options_page() {
			global $wp_meta_boxes;
			$allBoxes = array_keys( $wp_meta_boxes['twitter-'.$this->_slug] );
			$mainBoxes = array_filter( $allBoxes, array( $this, '_filter_boxes_main' ) );
			unset($mainBoxes['main']);
			sort($mainBoxes);
			$sidebarBoxes = array_filter( $allBoxes, array( $this, '_filter_boxes_sidebar' ) );
			unset($sidebarBoxes['sidebar']);
			sort($sidebarBoxes);

			$main_width = empty( $sidebarBoxes )? '100%' : '75%';
			?>
				<div class="wrap">
					<?php $this->screen_icon_link(); ?>
					<h2><?php echo esc_html($this->_pageTitle); ?></h2>
					<div class="metabox-holder">
						<div class="postbox-container" style="width:<?php echo $main_width; ?>;">
						<?php
							do_action( 'rpf-pre-main-metabox', $main_width );
							if ( in_array( 'main', $allBoxes ) ) {
						?>
							<form action="<?php esc_attr_e( $this->_optionsPageAction ); ?>" method="post"<?php do_action( 'rpf-options-page-form-tag' ) ?>>
								<?php
								settings_fields( $this->_optionGroup );
								do_meta_boxes( 'twitter-' . $this->_slug, 'main', '' );
								if ( apply_filters( 'rpf-show-general-settings-submit'.$this->_slug, true ) ) {
								?>
								<p class="submit">
									<input type="submit" name="Submit" value="<?php esc_attr_e('Update Options &raquo;', $this->_slug); ?>" />
								</p>
								<?php
								}
								?>
							</form>
						<?php
							}
							foreach( $mainBoxes as $context ) {
								do_meta_boxes( 'twitter-' . $this->_slug, $context, '' );
							}
						?>
						</div>
						<?php
						if ( !empty( $sidebarBoxes ) ) {
						?>
						<div class="alignright" style="width:24%;">
							<?php
							foreach( $sidebarBoxes as $context ) {
								do_meta_boxes( 'twitter-' . $this->_slug, $context, '' );
							}
							?>
						</div>
						<?php
						}
						?>
					</div>
				</div>
				<?php
		}

		public function add_plugin_page_links( $links, $file ){
			if ( $file == $this->_file ) {
				// Add Widget Page link to our plugin
				$link = $this->get_options_link();
				array_unshift( $links, $link );
			}
			return $links;
		}

		public function add_plugin_meta_links( $meta, $file ){
			if ( $file == $this->_file )
				$meta[] = $this->get_plugin_link(__('Rate Plugin'));
			return $meta;
		}

		public function get_plugin_link( $linkText = '' ) {
			if ( empty($linkText) )
				$linkText = __( 'Give it a good rating on WordPress.org.', $this->_slug );
			return "<a href='" . $this->get_plugin_url() . "'>{$linkText}</a>";
		}

		public function get_plugin_url() {
			return 'http://wordpress.org/extend/plugins/' . $this->_slug;
		}

		public function get_options_link( $linkText = '' ) {
			if ( empty($linkText) ) {
				$linkText = __( 'Settings', $this->_slug );
			}
			return '<a href="' . $this->get_options_url() . '">' . $linkText . '</a>';
		}

		public function get_options_url() {
			return admin_url( 'options-general.php?page=' . $this->_hook );
		}

		public function admin_enqueue_scripts() {
			if (isset($_GET['page']) && $_GET['page'] == $this->_hook) {
				wp_enqueue_style('dashboard');
			}
		}

		public function add_default_options_meta_boxes() {
			if ( apply_filters( 'show-twitter-like-this', true ) )
				add_meta_box( $this->_slug . '-like-this', __('Like this Plugin?', $this->_slug), array($this, 'like_this_meta_box'), 'twitter-' . $this->_slug, 'sidebar');

			if ( apply_filters( 'show-twitter-support', true ) )
				add_meta_box( $this->_slug . '-support', __('Need Support?', $this->_slug), array($this, 'support_meta_box'), 'twitter-' . $this->_slug, 'sidebar');
			if ( apply_filters( 'show-twitter-feed', true ) )
				add_meta_box( $this->_slug . '-twitter-feed', __('Latest news from twitter', $this->_slug), array($this, 'twitter_feed_meta_box'), 'twitter-' . $this->_slug, 'sidebar');
		}

		public function screen_icon_link($name = 'twitter') {
			$link = '<a href="http://fossasia.org">';
			if ( function_exists( 'get_screen_icon' ) ) {
				$link .= get_screen_icon( $name );
			} else {
				ob_start();
				screen_icon($name);
				$link .= ob_get_clean();
			}
			$link .= '</a>';
			echo apply_filters('rpf-screen_icon_link', $link, $name );
		}

		public function admin_print_scripts() {
			if (isset($_GET['page']) && $_GET['page'] == $this->_hook) {
				wp_enqueue_script('postbox');
				wp_enqueue_script('dashboard');
			}
		}

		public function register_tp_twitter_widget() {
			register_widget('tp_widget_recent_tweets');
		}
		public function add_wp_twitter_widget_script() {
			wp_register_script('twitter_widget_script', plugin_dir_url( __FILE__ ).'assets/js/wp-twitter-widget.js', array('jquery'));
			wp_enqueue_script('twitter_widget_script');

		}
	}
}
