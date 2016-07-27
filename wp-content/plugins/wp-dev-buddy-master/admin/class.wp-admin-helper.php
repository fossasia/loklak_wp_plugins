<?php

/**
* A class that holds basic, generic admin methods
*
* The methods in this class are generic and
* are designed to be useable with any DevBuddy
* feed plugin.
*
* @version 1.0.0
*/
if ( ! class_exists( 'DB_Plugin_WP_Admin_Helper' ) ) {

class DB_Plugin_WP_Admin_Helper extends DB_Twitter_Feed_Base {


	/**
	* Take attributes/value pairs and write them to an element
	*
	* @access public
	* @return string the attribute/value pairs as valid HTML
	* @since 2.0.0
	*
	* @param array $attributes An associative array of attribute/value pairs
	*/
	public function write_attr( $attributes ) {
		$data = '';

		if ( is_array($attributes) && count($attributes) !== 0 ) {
			foreach ( $attributes as $attr => $value ) {
				$data .= ' '.$attr.'="'.$value.'"';
			}
		}

		return $data;
	}


	/**
	* Add a description to the setting
	*
	* @access public
	* @return string The description provided wrapped in a tag that WordPress will use to format appropriately
	* @since 1.0.0
	*
	* @param $desc string The decription to accompany a settings field in the WordPress admin interface
	*/
	public function write_desc( $desc = '' ) {
		if ( $desc != '' ) {
			return '<p class="description">'.$desc.'</p>';
		}
	}


	/**
	* Output a basic checkbox field
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param array $args[option] The name of the option as stored in the database
	* @param array $args[desc]   The description to accompany this field in the admin
	* @param array $args[attr]   An array of attributes to be included on the input element
	*/
	public function write_checkbox_field( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		echo '<input type="checkbox" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'['.$args['option'].']" value="yes"';

		// If nothing is entered in the database we grab from defaults
		if ( $stored_value === FALSE ) {
			$stored_value = $this->defaults[ $args['option'] ];
		}

		if( $stored_value === 'yes') {
			echo ' checked="checked"';
		}

		echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

		echo ' />';

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


	/**
	* Output basic radio fields
	*
	* @access public
	* @return void
	* @since 2.0.0
	*
	* @param array $args[option]  The name of the option as stored in the database
	* @param array $args[options] An associative array of radio options and their values (keys and values respectively)
	* @param array $args[desc]    The description to accompany this field in the admin
	* @param array $args[attr]    An array of attributes to be included on the input element
	*/
	public function write_radio_fields( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		foreach ( $args['options'] as $name => $value ) {
			echo '<div class="'.$this->plugin_short_name.'_radio_item">';

			echo '<label for="'.$this->_html_item_id_attr( $args['option'] ).'_'.$value.'">'.$name.'</label>';

			echo '<input type="radio" id="'.$this->_html_item_id_attr( $args['option'] ).'_'.$value.'" name="'.$this->options_name_main.'['.$args['option'].']" value="'.$value.'"';

			if ( $stored_value && $stored_value === $value ) {
				echo ' checked="checked"';
			}

			echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

			echo ' />';

			echo '</div>';
		}

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


	/**
	* Output basic dropdown field that supports numbered dropdowns only
	*
	* @access public
	* @return void
	* @since 1.0.0
	*
	* @param array $args[option] The name of the option as stored in the database
	* @param array $args[min]    The lowest number that the dropdown should reach
	* @param array $args[max]    The highest number that the dropdown should reach
	* @param array $args[desc]   The description to accompany this field in the admin
	* @param array $args[attr]   An array of attributes to be included on the input element
	*/
	public function write_numeric_dropdown_field( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		echo '<select id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'['.$args['option'].']"';

		echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

		echo '>';

		for ( $num = $args['min']; $num <= $args['max']; $num++ ) {
			echo '<option value="'.$num.'"';

			if ( $stored_value && (int) $stored_value === $num ) {
				echo ' selected="selected"';
			}

			echo '>'.$num.'</option>';
		}
		echo '</select>';

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


	/**
	* Output basic text field
	*
	* @access public
	* @return void
	* @since 2.0.0
	*
	* @param array $args[option] The name of the option as stored in the database
	* @param array $args[desc]   The description to accompany this field in the admin
	* @param array $args[attr]   An array of attributes to be included on the input element
	*/
	public function write_input_text_field( $args ) {
		$stored_value = $this->get_db_plugin_option( $this->options_name_main, $args['option'] );

		echo '<input type="text" id="'.$this->_html_item_id_attr( $args['option'] ).'" name="'.$this->options_name_main.'['.$args['option'].']"';

		echo ' value="';
		if( $stored_value && $stored_value != '') {
			echo $stored_value;
		}
		echo '"';

		echo ( isset( $args['attr'] ) ) ? $this->write_attr( $args['attr'] ) : '';

		echo ' />';

		echo ( isset( $args['desc'] ) ) ? $this->write_desc( $args['desc'] ) : '';
	}


} // END class

} // END class_exists