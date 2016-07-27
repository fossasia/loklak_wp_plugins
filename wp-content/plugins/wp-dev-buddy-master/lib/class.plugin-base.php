<?php

/**
* A class that will be common across feed plugins
*
* This class is used as a provider of properties
* and methods that will be common across feed
* plugins.
*
* @version 1.1.2
*/
if ( ! class_exists( 'DevBuddy_Feed_Plugin' ) ) {

class DevBuddy_Feed_Plugin {

	/**
	* @var string The name of the plugin to be used within the code
	*/
	public $plugin_name;

	/**
	* @var mixed Holds raw feed data returned from API after main request is made
	*/
	public $feed_data;

	/**
	 * @var mixed Holds the feed data after it has been parsed by the plugin
	 */
	public $parsed_feed_data = array();

	/**
	* @var array Holds the configuration options once the feed class has been instantiated
	*/
	public $options;

	/**
	* @var string The output of the entire feed will be stored here
	*/
	public $output = '';

	/**
	* @var int The number of feed items that have been rendered
	*/
	protected $item_count = 0;

	/**
	* @var bool A boolean indication of whether or not a cached version of the output is available
	*/
	public $is_cached;

	/**
	* @var bool A boolean indication of whether or not the feed has been called via shortcode
	*/
	public $is_shortcode_called = FALSE;

	/**
	* @var string The width of the display picture when set by the user
	*/
	private $dp_width;

	/**
	* @var string The height of the display picture when set by the user
	*/
	private $dp_height;


	/**
	* Used to get the value of an option stored in the database
	* 
	* Option data is typically stored within an array of values
	* under one option entry within the WordPress database. So
	* to get an option's value you need to provide the option
	* entry along with the specific option you want the value
	* of.
	*
	* @access public
	* @return mixed The value of the option you're looking for or FALSE if no value exists
	*
	* @param string $option_entry The option name that WP recognises as an entry. Passing only this will return all option data for that entry
	* @param string $option_name  The name of the specific plugin option you want the value of
	*
	* @since 1.0.1
	*/
	public function get_option( $option_entry, $option_name = NULL ) {
		$options = get_option( $option_entry );

		if ( ! $options ) {
			return FALSE;
		}

		if ( $option_name === NULL ) {
			return $options;

		} else {
			if ( isset( $options[ $option_name ] ) && $options[ $option_name ] != '' ) {
				return $options[ $option_name ];
			} else {
				return FALSE;
			}
		}
	}


	/**
	* An alias of DevBuddy_Feed_Plugin::get_option()
	*
	* @access public
	* @return mixed The value of the option you're looking for or FALSE if no value exists
	*
	* @param string $option_entry The option name that WP recognises as an entry
	* @param string $option_name  The name of the specific plugin option you want the value of
	*
	* @since 1.0.0
	*/
	public function get_db_plugin_option( $option_entry, $option_name = NULL ) {
		return $this->get_option( $option_entry, $option_name );
	}


	/**
	* Update a specific plugin option
	*
	* @access protected
	* @return bool An indication of whether or not the update was successful
	*
	* @param string $option_entry The option name that WP recognises as an entry
	* @param string $option_name  The name of the specific plugin option you want to update
	* @param mixed  $new_value    The value with which to update the option
	*
	* @since 1.1.0
	*/
	protected function update_option( $option_entry, $option_name, $new_value ) {
		$options = get_option( $option_entry );
		$options[ $option_name ] = $new_value;

		if ( update_option( $option_entry, $options ) ) {
			return TRUE;
		} else {
			return FALSE;
		}
	}


	/**
	* Update a specific plugin option
	*
	* @access protected
	* @return bool An indication of whether or not the update was successful
	*
	* @param string $option_entry The option name that WP recognises as an entry
	* @param string $option_name  The name of the specific plugin option you want to update
	* @param mixed  $new_value    The value with which to update the option
	*
	* @since 1.1.0
	*/
	protected function update_db_plugin_option( $option_entry, $option_name, $new_value ) {
		return $this->update_option( $option_entry, $option_name, $new_value );
	}


	/**
	* Increase the feed item count by one
	*
	* @access public
	* @return void
	* @since 1.0.1
	*/
	public function increase_feed_item_count() {
		$this->item_count++;
	}


	/**
	* Return the current item count of the current feed
	*
	* @access public
	* @return int
	* @since 1.0.1
	*/
	public function get_item_count() {
		return $this->item_count;
	}


	/**
	* Cache whatever is in the DevBuddy_Feed_Plugin::$output property
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached property
	* to TRUE once the cache is set.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param int $hours The number of hours the output should be cached for
	*/
	public function cache_output( $hours = 0 ) {
		if ( (int) $hours !== 0 ) {
			set_transient( $this->plugin_name . '_output_' . $this->options['user'], $this->output, 3600 * $hours );

			$cache_successful = get_transient( $this->plugin_name . '_output_' . $this->options['user'] );

			if ( $cache_successful ) {
				$this->is_cached = TRUE;
			}
		}
	}


	/**
	* Clear the cached output of a specific user
	*
	* This method also sets the DevBuddy_Feed_Plugin::$is_cached
	* property to FALSE once the cache is deleted and is called
	* when changes have been saved on the settings page in
	* WordPress.
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param string $user The username/ID of the feed owner
	*/
	public function clear_cache_output( $user ) {
		delete_transient( $this->plugin_name . '_output_' . $user );

		$clear_cache_successful = ( get_transient( $this->plugin_name . '_output_' . $user ) ) ? FALSE : TRUE;

		if ( $clear_cache_successful ) {
			$this->is_cached = FALSE;
		}
	}


	/**
	* Format the date based on what the time that the data given represents
	*
	* An option for relative datetimes has been included,
	* which will be useful in cases where the output is
	* to be cached and the relative times would thus be
	* inaccurate.
	*
	* @access protected
	* @return string Some human readable representation of the date the post was published
	* @since 1.0.0
	*
	* @param mixed $datetime          The datetime that the post was published in any format that PHP's strtotime() can parse
	* @param bool  $relative_datetime Whether or not to return relative datetimes, e.g. "2 hours ago"
	*/
	protected function formatify_date( $datetime, $relative_datetime = TRUE ) {
		$an_hour = 3600;
		$a_day   = $an_hour*24;
		$a_week  = $a_day*7;

		$now  = time();
		$then = strtotime( $datetime );
		$diff = $now - $then;

		$mins          = $diff / 60 % 60;
		$the_mins_ago  = $mins;
		$the_mins_ago .= ( $mins == '1' ) ? __( ' minute ago', 'devbuddy-twitter-feed' ) : __( ' minutes ago', 'devbuddy-twitter-feed' );

		$hours          = $diff / 3600 % 24;
		$the_hours_ago  = 'About ';
		$the_hours_ago .= $hours;
		$the_hours_ago .= ( $hours == '1' ) ? __( ' hour ago', 'devbuddy-twitter-feed' ) : __( ' hours ago', 'devbuddy-twitter-feed' );

		$the_time = date( 'H:i', $then );
		$the_day  = date( 'D', $then );
		$the_date = date( 'j M', $then );


		if ( $relative_datetime && $diff <= $an_hour ) {
			return $the_mins_ago;

		} elseif ( $diff <= $an_hour ) {
			return $the_time.', '.$the_day;

		} elseif ( $relative_datetime && $diff > $an_hour && $diff <= $a_day ) {
			return $the_hours_ago;

		} elseif ( $diff > $an_hour && $diff <= $a_day ) {
			return $the_time.', '.$the_day;

		} elseif ( $diff > $a_day && $diff <= $a_week ) {
			return $the_time.', '.$the_day;

		} else {
			return $the_date;
		}
	}


	/**
	* Turn plain text links within text into hyperlinks and return the full text
	*
	* @access public
	* @return string The original text with plain text links converted into hyperlinks
	* @since 1.0.0
	*
	* @param string $text The text to parse for plain text links
	*/
	public function hyperlinkify_text( $text ) {
		$new_text = preg_replace('@(https?://([-\w\.]+[-\w])+(:\d+)?(/([\w/_\.#-]*(\?\S+)?[^\.\s])?)?)@', '<a href="$1" target="_blank">$1</a>', $text);
		return $new_text;
	}


	/**
	* Set the width and the height for the user display picture
	*
	* This does not manipulate any image, this method
	* only sets the values that other methods/function
	* can take advantage of.
	*
	* This method can accept an array with the width and
	* height in seperate indexes, a string with just one
	* number which will be used for both width and height,
	* or a string with the width and height seperated with
	* an "x".
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param mixed $width_height The desired width/height of the display picture; either a string or an array is accepted
	*/
	/************************************************/
	public function set_dp_size( $width_height ) {
		$min_size = 10;
		$max_size = 200;

		if ( ! is_array( $width_height ) ) {
			$width_height_arr = explode( 'x', $width_height );

			// No "x" was present
			if ( is_array( $width_height_arr ) && count( $width_height_arr ) === 1 ) {
				$width_height_arr[0] = $width_height_arr[0];
				$width_height_arr[1] = $width_height_arr[0];

			// "x" was present
			} elseif ( is_array( $width_height_arr ) && count( $width_height_arr ) === 2 ) {
				/* Don't actually need to do anything here,
				   but we don't want this condition getting
				   caught in the "else" either */

			// Empty string
			} else {
				$width_height_arr[0] = $this->defaults['dp_size'];
				$width_height_arr[1] = $this->defaults['dp_size'];
			}

		// An array of two items, both numeric
		} elseif( is_array( $width_height ) && count( $width_height ) === 2 ) {
			$width_height_arr[0] = $width_height[0];
			$width_height_arr[1] = $width_height[1];
		}

		// Check for minimums and maximums
		$i = 0;
		foreach ( $width_height_arr as $dimension ) {
			if ( $dimension < $min_size ) {
				$width_height_arr[ $i ] = $min_size;

			} elseif( $dimension > $max_size ) {
				$width_height_arr[ $i ] = $max_size;
			}

			$i++;
		}
		unset( $i );

		$this->dp_width  = $width_height_arr[0];
		$this->dp_height = $width_height_arr[1];
	}


	/**
	* Return the values of the display picture size
	*
	* This value is set via DevBuddy_Feed_Plugin::set_dp_size()
	*
	* @access public
	* @return array
	* @since 1.0.1
	*/
	public function get_dp_size() {
		$dp = array( 'width' => $this->dp_width, 'height' => $this->dp_height );
		return $dp;
	}


	/**
	* Converts comma-separated values in a string to an array
	*
	* Sometimes a value may be either an array or a string
	* so this is a way to ensure that we always get a the
	* format we want
	*
	* @access public
	* @return mixed
	* @since 1.0.2
	*/
	public function list_convert( $list ) {
		if ( ! is_array( $list ) ) {
			$list = explode( ',', $list );
		}

		return $list;
	}


	/**
	* Mask sensitive information
	*
	* Takes a string and replaces certain values with
	* an "x" to retain the privacy of sensitive data.
	*
	* @access protected
	* @return string
	* @since 1.0.0
	*
	* @param string $string     The string you wish to have masked
	* @param int    $start      The point at which you wish masking to begin
	* @param int    $end_offset The point at which you wish masking to end; 0 will result in masking until the end of the $string
	*/
	protected function mask_data( $string, $start = 3, $end_offset = 3 ) {
		$char_data = str_split( $string );
		$length    = count( $char_data ) - 1;

		for ( $i = $start; $i <= $length - $end_offset; $i++ ) {
			if ( $char_data[ $i ] != '-' ) {
				$char_data[ $i ] = 'x';
			}
		}

		$string = '';
		foreach ( $char_data as $char ) {
			$string .= $char;
		}

		return $string;
	}


	/**
	* A request to hide the plugin's WordPress admin menu item
	*
	* This method is used to register the hiding of
	* this plugin's menu item from the WordPress admin
	* menu but it does not execute it.
	*
	* @access public
	* @return void
	* @used_by DevBuddy_Feed_Plugin::hide_wp_admin_menu_item() Executes this request
	* @since 1.0.0
	*/
	public function hide_admin_page() {
		remove_submenu_page( 'options-general.php', $this->page_uri_main );
	}


	/**
	* A request to hide the plugin's WordPress admin menu item
	*
	* This method is used to execute the hiding of
	* this plugin's menu item from the WordPress admin.
	*
	* @access public
	* @return void
	* @uses DevBuddy_Feed_Plugin::hide_admin_page() Registers this request
	* @since 1.0.0
	*/
	public function hide_wp_admin_menu_item() {
		add_action( 'admin_menu', array( $this, 'hide_admin_page' ), 999 );
	}


	/**
	 * Write a message to the debug log, usually found
	 * in wp-content/debug.log. In some cases, such as
	 * the value of $msg being an array or object, the
	 * log will not be written to.
	 *
	 * @access public
	 * @since  1.1.1
	 *
	 * @return void
	 *
	 * @param  string  $msg      The message to be written to the log
	 * @param  boolean $override Write to the log regardless of whether or not WP_DEBUG_LOG is set to TRUE
	 */
	public function log( $msg, $override = FALSE ) {
		if ( WP_DEBUG_LOG === TRUE || $override === TRUE ) {
			@error_log( $msg );
		}
	}


	/**
	 * Output the $output to the screen as and when this method
	 * is called. Use DevBuddy_Feed_Plugin::kill() instead of
	 * this if you want to end script execution immediately.
	 *
	 * @access public
	 * @since 1.1.0
	 *
	 * @return void
	 * 
	 * @param mixed $output The data you wish to output to screen
	 * @param bool  $log    Send the $output to the configured error log
	 */
	public function debug( $output, $log = FALSE ) {
		// If $output is an array or object, convert it to a string
		if ( is_array( $output ) || is_object( $output ) ) {
			$output = print_r( $output, TRUE );
		}

		// If $output is boolean, convert it to a string
		if ( is_bool( $output ) ) {
			$output  = '(bool) ';
			$output .= ( $output ) ? 'true' : 'false';
		}

		// If $output is NULL, convert it to a string
		if ( $output === NULL ) {
			$output = 'NULL';
		}

		echo '<pre>' . $output . '</pre>' . "\n\n";

		if ( $log ) {
			$this->log( $output );
		}
	}


	/**
	 * Kills all script execution at the point that this method
	 * is called and sends $output to the error log. By setting
	 * the $print parameter to TRUE, the output can be sent to
	 * the screen too. $output will also be printed to screen if
	 * WP_DEBUG is set to TRUE.
	 *
	 * @access public
	 * @since  1.1.0
	 *
	 * @return void
	 * 
	 * @param  mixed   $output The text that will be output in the error log, and on screen is $print is TRUE
	 * @param  boolean $print  Set to TRUE to print the output to screen
	 */
	public function kill( $output, $print = FALSE ) {
		// $print param should be a boolean value
		if ( ! is_bool( $print ) ) {
			$this->debug( __METHOD__ . ' expects second parameter to be boolean, ' . gettype( $print ) . ' given', TRUE );
		}

		// Print error to page only if explicitly declared to or if in debug environment
		if ( $print === TRUE || WP_DEBUG === TRUE ) {
			$this->debug( $output );
		}

		// Log the output
		$this->log( $output );

		// Kill
		   die;
	}


	/**
	 * Declare an error
	 *
	 * @access protected
	 * @since  1.1.1
	 *
	 * @return void
	 *
	 * @param  int    $level The error level
	 * @param  string $msg   The error message/details to be written to the page and the error log
	 */
	protected function error( $level, $msg ) {
		// Set error levels
		$levels = array(
			1 => 'fatal',
			2 => 'warning',
			3 => 'notice'
		);

		$msg = '<b>' . $levels[$level] . '</b>: ' . $msg;

		// Kill script if fatal error
		if ( $level === 1 ) {
			$this->kill( $msg );
		}

		// Print error message to page for any other error
		if ( WP_DEBUG === TRUE ) {
			$this->debug( $msg );
		}

		// Log error if environment permits
		if ( WP_DEBUG_LOG === TRUE ) {
			$this->log( strip_tags($msg) );
		}
	}

} // END class

} // END class_exists