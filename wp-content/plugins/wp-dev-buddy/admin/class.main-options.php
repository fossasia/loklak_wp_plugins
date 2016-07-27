<?php

/**
 * A class to create the settings page for this plugin within WordPress
 *
 * @version 2.2.0
 */
if ( ! class_exists( 'DB_Twitter_Feed_Main_Options' ) ) {

	class DB_Twitter_Feed_Main_Options extends DB_Plugin_WP_Admin_Helper {

		/**
		 * @var array Holds important information about sections on the settings page
		 * @since 1.0.0
		 */
		private $sections = array();

		/**
		 * @var array Holds important information about individual settings on the settings page
		 * @since 1.0.0
		 */
		private $settings = array();

		/**
		 * @var string The prefix used to ensure that the IDs of HTML items are unique to the plugin
		 * @since 2.0.0
		 */
		protected $html_item_id_prefix;


		/**
		 * Sets up the settings and initialises them within WordPress
		 *
		 * @access public
		 * @since 1.0.0
		 */
		public function __construct() {
			$this->set_main_admin_vars();

			$this->html_item_id_prefix = $this->plugin_short_name.'_';

			$this->set_sections();
			$this->set_settings();

			add_action( 'admin_menu', array( $this, 'add_menu_item' ) );
			add_action( 'admin_init', array( $this, 'init_options' ) );
			add_action( 'admin_head', array( $this, 'set_admin_vars_js' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_styles' ) );
		}


		/**
		 * Establish the details of the sections to be rendered by WP on this settings page
		 *
		 * @access private
		 * @return void
		 * @since 1.0.0
		 */
		private function set_sections() {
			$this->sections =
				array(
					'cache' => array(
						'id'       => 'cache_sec',
						'title'    => __( 'Cache Management', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_cache_sec' ),
						'page'     => $this->page_uri_main
					),
					'config' => array(
						'id'       => 'configuration_sec',
						'title'    => __( 'Configuration', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_configuration_sec' ),
						'page'     => $this->page_uri_main
					),
					'feed' => array(
						'id'       => 'feed_sec',
						'title'    => __( 'Feed Settings', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_feed_sec' ),
						'page'     => $this->page_uri_main
					),
					'settings' => array(
						'id'       => 'settings_sec',
						'title'    => __( 'General Settings', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_settings_sec' ),
						'page'     => $this->page_uri_main
					)
				);
		}


		/**
		 * Establish the details of the settings to be rendered by WP on this settings page
		 *
		 * @access private
		 * @return void
		 * @since 1.0.0
		 */
		private function set_settings() {
			$this->settings =
				array(
					'consumer_key' => array(
						'id'       => 'consumer_key',
						'title'    => __( 'Consumer Key', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_consumer_key_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'configuration_sec',
						'args'     => ''
					),
					'consumer_secret' => array(
						'id'       => 'consumer_secret',
						'title'    => __( 'Consumer Secret', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_consumer_secret_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'configuration_sec',
						'args'     => ''
					),
					'oauth_access_token' => array(
						'id'       => 'oauth_access_token',
						'title'    => __( 'OAuth Access Token', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_oauth_access_token_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'configuration_sec',
						'args'     => ''
					),
					'oauth_access_token_secret' => array(
						'id'       => 'oauth_access_token_secret',
						'title'    => __( 'OAuth Access Token Secret', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_oauth_access_token_secret_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'configuration_sec',
						'args'     => ''
					),
					'feed_type' => array(
						'id'       => 'feed_type',
						'title'    => __( 'Feed Type', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_radio_fields' ),
						'page'     => $this->page_uri_main,
						'section'  => 'feed_sec',
						'args'     => array(
							'no_label' => TRUE,
							'options'  => array(
								__( 'Timeline', 'devbuddy-twitter-feed' ) => 'user_timeline',
								__( 'Search', 'devbuddy-twitter-feed' )   => 'search',
								__( 'List', 'devbuddy-twitter-feed' )   => 'list'
							)
						)
					),
					'user' => array(
						'id'       => 'twitter_username',
						'title'    => __( 'Twitter Username', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_twitter_username_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'feed_sec',
						'args'     => array(
							'attr' => array(
								'class' => 'input_feed_type'
							)
						)
					),
					'search_term' => array(
						'id'       => 'search_term',
						'title'    => __( 'Search Term', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_search_term_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'feed_sec',
						'args'     => array(
							'desc'   => __( 'Searches with or without a hashtag are supported.', 'devbuddy-twitter-feed' ),
							'attr'   => array(
								'class' => 'input_feed_type'
							)
						)
					),
					'list' => array(
						'id'       => 'list',
						'title'    => __( 'Twitter List', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_list_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'feed_sec',
						'args'     => array(
							'desc'   => __( 'Enter the slug of a list followed by the username of the owner, separated by a "/".', 'devbuddy-twitter-feed' ) . ' <strong>e.g. slug-of-list/twitterUsername</strong>',
							'attr'   => array(
								'class' => 'input_feed_type'
							)
						)
					),
					'result_count' => array(
						'id'       => 'result_count',
						'title'    => __( 'Number of tweets to show' , 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_numeric_dropdown_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'min'    => 1,
							'max'    => 30,
							'desc'   => '<p class="description">' . __( 'If you&rsquo;re excluding data (e.g. retweets) the feed cannot guarantee to have the number of tweets you request.', 'devbuddy-twitter-feed' ) . '</p>'
						)
					),
					'cache_hours' => array(
						'id'       => 'cache_hours',
						'title'    => __( 'Cache the feed for how many hours?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_numeric_dropdown_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'min'    => 0,
							'max'    => 24,
							'desc'   => '<p class="description">' . __( 'Select 0 if you don&rsquo;t wish to cache the feed.', 'devbuddy-twitter-feed' ) . '</p>' )
					),
					'exclude_replies' => array(
						'id'       => 'exclude_replies',
						'title'    => __( 'Exclude replies?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' .
								__( 'Doesn&rsquo;t apply to the &ldquo;Search&rdquo; feed type.', 'devbuddy-twitter-feed' ) . ' ' .
								__( 'It&rsquo;s strongly advised that the cache is activated when this setting is on.', 'devbuddy-twitter-feed' ) .
								'</p>'
						)
					),
					'exclude_retweets' => array(
						'id'       => 'exclude_retweets',
						'title'    => __( 'Exclude retweets?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' .
								__( 'Doesn&rsquo;t apply to the &ldquo;Search&rdquo; feed type.', 'devbuddy-twitter-feed' ) . ' ' .
								__( 'It&rsquo;s strongly advised that the cache is activated when this setting is on.', 'devbuddy-twitter-feed' ) .
								'</p>'
						)
					),
					'relative_times' => array(
						'id'       => 'relative_times',
						'title'    => 'Display relative times?',
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' . __( 'For example, &ldquo;10 mins ago&rdquo;.', 'devbuddy-twitter-feed' ) . '</p>'
						)
					),
					'show_images' => array(
						'id'       => 'show_images',
						'title'    => __( 'Show embedded images?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' . __( 'Load and display images embedded in tweets.', 'devbuddy-twitter-feed' ) . '</p>'
						)
					),
					'https' => array(
						'id'       => 'https',
						'title'    => __( 'Load media over HTTPS?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' . __( 'This only affects media served by Twitter.', 'devbuddy-twitter-feed' ) . '</p>'
						)
					),
					'default_styling' => array(
						'id'       => 'default_styling',
						'title'    => __( 'Load default stylesheet?', 'devbuddy-twitter-feed' ),
						'callback' => array( $this, 'write_checkbox_field' ),
						'page'     => $this->page_uri_main,
						'section'  => 'settings_sec',
						'args'     => array(
							'desc'   => '<p class="description">' . __( 'Load the stylesheet bundled with this plugin.', 'devbuddy-twitter-feed' ) . '</p>'
						)
					)/*,
			'' => array(
				'id'       => '',
				'title'    => __( '', 'devbuddy-twitter-feed' ),
				'callback' => array( $this, 'write__field' ),
				'page'     => $this->page_uri_main,
				'section'  => '',
				'args'     => ''
			)*/
				);

			foreach ( $this->settings as $name => $setting ) {
				$id           = $setting['id'];
				$title        = $setting['title'];
				$html_item_id = $this->html_item_id_prefix.$id;

				// Wrap title in label, add it to appropriate $settings property
				$no_label = ( isset( $setting['args']['no_label'] ) ) ? $setting['args']['no_label'] : FALSE;
				if ( $no_label !== TRUE ) {
					$this->settings[ $name ]['title'] = '<label for="'.$html_item_id.'">'.$title.'</label>';
				}


				// Add standard data to the arguments of the setting
				if ( is_array( $setting['args'] ) ) {
					$this->settings[ $name ]['args']['option'] = $id;
				} else {
					$this->settings[ $name ]['args'] = array( 'option' => $id );
				}

				$this->settings[ $name ]['args']['html_item_id'] = $html_item_id;
			}

		}


		/**
		 * Load JavaScripts and styles necessary for the page
		 *
		 * @access public
		 * @return void
		 * @since 2.0.0
		 */
		public function enqueue_scripts_styles( $hook ) {
			if ( $hook != 'settings_page_db-twitter-feed-settings' ) {
				return;
			}
			wp_enqueue_style( $this->plugin_name . '_admin_styles', DBTF_URL . '/assets/main-admin.css', NULL, '1.0.1', 'all' );
			wp_enqueue_script( $this->plugin_name . '_admin_functions', DBTF_URL . '/assets/main-admin.js', array( 'jquery-core' ), '1.0.0', true );
		}


		/**
		 * Create global JavaScript object that will hold certain plugin information
		 *
		 * @access public
		 * @return void
		 * @since 2.0.0
		 */
		public function set_admin_vars_js() {
			$br      = "\n";
			$tab     = '	';

			$class_name = strtoupper( $this->plugin_short_name );

			$output  = $br . '<script type="text/javascript">' . $br;
			$output .= $tab . 'var ' . $class_name . ' = ' . $class_name . ' || {};' . $br;

			$output .= $tab . $class_name . '.pluginName      = \'' . $this->plugin_name . '\';' . $br;
			$output .= $tab . $class_name . '.pluginShortName = \'' . $this->plugin_short_name . '\';' . $br;

			$output .= $tab . $class_name . '.optionsNameMain  = \'' . $this->options_name_main . '\';' . $br;
			$output .= $tab . $class_name . '.optionsGroup     = \'' . $this->options_group_main . '\';' . $br;

			$output .= '</script>' . $br . $br;

			echo $output;
		}


		/**
		 * Add the item to the WordPress admin menu and call the function that renders the markup
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function add_menu_item() {
			add_submenu_page(
				'options-general.php',
				__( 'Configure your Twitter feed set up', 'devbuddy-twitter-feed' ),
				__( 'Twitter Feed Settings', 'devbuddy-twitter-feed' ),
				'manage_options',
				$this->page_uri_main,
				array( $this, 'settings_page_markup' )
			);
		}


		/**
		 * Officially register the sections/settings with WordPress
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function init_options() {
			register_setting( $this->options_group_main, $this->options_name_main, array( $this, 'sanitize_settings_submission' ) );

			// Loop through the Sections/Settings arrays and add them to WordPress
			foreach ( $this->sections as $section ) {
				add_settings_section(
					$section['id'],
					$section['title'],
					$section['callback'],
					$section['page']
				);
			}
			foreach ( $this->settings as $setting ) {
				add_settings_field(
					$setting['id'],
					$setting['title'],
					$setting['callback'],
					$setting['page'],
					$setting['section'],
					$setting['args']
				);
			}
		}


		/**************************************************************************************************************
		Callbacks for writing the option fields themselves to the options page
		 **************************************************************************************************************/
		/**
		 * Write the markup for the settings page
		 *
		 * This method also checks to see if settings have been updated. If they have
		 * the method will clear the cache of the ID currently in the twitter_username
		 * field.
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function settings_page_markup() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'devbuddy-twitter-feed' ) );
			} ?>

			<div id="<?php echo $this->plugin_short_name ?>" class="wrap">

				<?php screen_icon() ?>
				<h2><?php __( 'Twitter Feed Settings', 'devbuddy-twitter-feed' ) ?></h2>

				<form id="<?php echo $this->plugin_name ?>_settings" action="options.php" method="post">
					<?php
					settings_fields( $this->options_group_main );
					do_settings_sections( $this->page_uri_main );

					submit_button( __( 'Save Changes', 'devbuddy-twitter-feed' ) )
					?>
				</form>

			</div><!--END-<?php echo $this->plugin_short_name ?>-->
		<?php }


		/**
		 * Takes a string and returns it with the plugin's shortname prefixed to it
		 *
		 * This method should only be used to prefix HTML
		 * id attributes
		 *
		 * @access protected
		 * @return string
		 * @since 2.0.0
		 *
		 * @param string $item_id The ID of the item to be prefixed
		 */
		protected function _html_item_id_attr( $item_id ) {
			return $this->html_item_id_prefix . $item_id;
		}


		/* Write Cache Management section
		*******************************************/
		/**
		 * Output the Cache management section and its fields
		 *
		 * @access public
		 * @return void
		 * @since 2.0.0
		 */
		public function write_cache_sec() {
			echo 'Select a cache segment to clear.';
			echo '<div class="' . $this->plugin_short_name . '_cache_management_section settings_item">';

			echo '<select name="' . $this->options_name_main . '[cache_segment]" id="' . $this->plugin_short_name . '_cache_segment">
				<option value="0">--</option>
				<option value="user_timeline">' . __( 'User timelines', 'devbuddy-twitter-feed' ) . '</option>
				<option value="search">' . __( 'Searches', 'devbuddy-twitter-feed' ) . '</option>
				<option value="list">' . __( 'Lists', 'devbuddy-twitter-feed' ) . '</option>
				<option value="all">' . __( 'All', 'devbuddy-twitter-feed' ) . '</option>
			</select>';

			echo '<input type="hidden" id="' . $this->plugin_short_name . '_cache_clear_flag" name="' . $this->options_name_main . '[cache_clear_flag]" value="0" />';

			echo get_submit_button(
				__( 'Clear Cache', 'devbuddy-twitter-feed' ),
				'secondary',
				$this->plugin_short_name . '_batch_clear_cache'
			);
			echo '</div>';
		}


		/**
		 * Output batch clear cache field
		 *
		 * @access public
		 * @return void
		 * @since 2.0.0
		 */
		public function write_cache_segment_field() {
			echo '';
		}


		/**
		 * Output batch clear clear flag field
		 *
		 * @access public
		 * @return void
		 * @since 2.0.0
		 */
		public function write_cache_clear_flag() {
			echo '';
		}


		/* Write Configuration section
		*******************************************/
		/**
		 * Output the section as set in the set_sections() method along with a little bit of guidance
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_configuration_sec() {
			_e( 'You\'ll need to log into the Twitter Developers site and set up an app. Once you\'ve set one up you will get the data necessary for below. For a step by step, see the <a href="http://wordpress.org/plugins/devbuddy-twitter-feed/installation/" target="_blank">walkthrough</a>.', 'devbuddy-twitter-feed' );
		}


		/**
		 * Output the Consumer Key setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_consumer_key_field( $args ) {
			$consumer_key = $this->get_db_plugin_option( $this->options_name_main, 'consumer_key' );
			$consumer_key = $this->mask_data( $consumer_key );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[consumer_key]" value="' . $consumer_key . '" style="width:450px;" />';
		}


		/**
		 * Output the Consumer Secret setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_consumer_secret_field( $args ) {
			$consumer_secret = $this->get_db_plugin_option( $this->options_name_main, 'consumer_secret' );
			$consumer_secret = $this->mask_data( $consumer_secret );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[consumer_secret]" value="' . $consumer_secret . '" style="width:450px;" />';
		}


		/**
		 * Output the OAuth Access Token setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_oauth_access_token_field( $args ) {
			$oauth_access_token = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token' );

			$oat_arr = explode( '-', $oauth_access_token );
			$start = strlen( $oat_arr[0] );

			$oauth_access_token = $this->mask_data( $oauth_access_token, $start );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[oauth_access_token]" value="' . $oauth_access_token . '" style="width:450px;" />';
		}


		/**
		 * Output the OAuth Access Token Secret setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_oauth_access_token_secret_field( $args ) {
			$oauth_access_token_secret = $this->get_db_plugin_option( $this->options_name_main, 'oauth_access_token_secret' );
			$oauth_access_token_secret = $this->mask_data( $oauth_access_token_secret );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[oauth_access_token_secret]" value="' . $oauth_access_token_secret . '" style="width:450px;" />';
		}


		/* Write Feed Settings section
		*******************************************/
		/**
		 * Output the section as set in the set_sections() method
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_feed_sec() {
			echo '';
		}


		/**
		 * Output the Twitter username setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_twitter_username_field( $args ) {
			$twitter_username = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );

			echo '<strong>twitter.com/</strong><input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[twitter_username]"';

			if ( $twitter_username ) {
				echo ' value="' . $twitter_username . '"';
			}

			echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

			echo ' />';

			echo '<input type="hidden" name="' . $this->options_name_main . '[twitter_username_hid]"';

			if ( $twitter_username ) {
				echo ' value="' . $twitter_username . '"';
			}

			echo ' />';

			echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
		}


		/**
		 * Output the Twitter username setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_search_term_field( $args ) {
			$search_term = $this->get_db_plugin_option( $this->options_name_main, 'search_term' );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main.'[search_term]"';

			if ( $search_term ) {
				echo ' value="' . $search_term . '"';
			}

			echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

			echo ' />';

			echo '<input type="hidden" name="' . $this->options_name_main . '[search_term_hid]"';

			if ( $search_term ) {
				echo ' value="' . $search_term . '"';
			}

			echo ' />';

			echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
		}


		/**
		 * Output the Twitter list setting's field
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_list_field( $args ) {
			$list = $this->get_db_plugin_option( $this->options_name_main, 'list' );

			echo '<input type="text" id="' . $this->_html_item_id_attr( $args['option'] ) . '" name="' . $this->options_name_main . '[list]"';

			if ( $list ) {
				echo ' value="' . $list . '"';
			}

			echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

			echo ' />';

			echo '<input type="hidden" name="' . $this->options_name_main . '[list_hid]"';

			if ( $list ) {
				echo ' value="' . $list . '"';
			}

			echo ' />';

			echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
		}


		/* Write General Settings section
		*******************************************/
		/**
		 * Output the section as set in the set_sections() method
		 *
		 * @access public
		 * @return void
		 * @since 1.0.0
		 */
		public function write_settings_sec() {
			echo '';
		}

	}// END class

}// END class_exists