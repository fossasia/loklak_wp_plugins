<?php
/**
 * Plugin Name: Any User Twitter Feed
 * Plugin URI: http://www.webdesignservices.net/free-wordpress-twitter-plugin.html
 * Description: Embed anyone's Twitter Timeline using only their username, or display tweets based on a keyword. Fully compatible with the latest Twitter API and guaranteed to work even with the forthcoming Twitter changes!
 * Version: 1.0
 * Author: Web Design Services
 * Author URI: http://www.webdesignservices.net/
 * License: GPLv2 or later
 * Text Domain: any-user-twitter-feed
 */

/*
	Copyright Web Design Services. All rights reserved.

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	( at your option ) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

class AnyUserTwitterFeed extends WP_Widget {
	public function AnyUserTwitterFeed() {
				$this->options = array(
			array(
				'label' => '<div style="background-color: #ddd; padding: 5px; text-align:center; color: red; font-weight:bold;">Authentication settings (compulsory)</div>',
				'type'	=> 'separator'),
			array(
				'name'	=> 'consumer_key',	'label'	=> 'Consumer Key',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Consumer key for your app at https://dev.twitter.com/apps/new'),
			array(
				'name'	=> 'consumer_secret',	'label'	=> 'Consumer Secret',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Consumer secret for your app at https://dev.twitter.com/apps/new'),
			array(
				'name'	=> 'access_token',	'label'	=> 'Access Token',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Access token for your app at https://dev.twitter.com/apps/new'),
			array(
				'name'	=> 'access_secret',	'label'	=> 'Access Token Secret',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Access token secret for your app at https://dev.twitter.com/apps/new'),
			array(
				'label' => '<div style="background-color: #ddd; padding: 5px; text-align:center; color: red; font-weight:bold;">Widget settings</div>',
				'type'	=> 'separator'),
			array(
				'name'	=> 'widget_title',	'label'	=> 'Widget title',
				'type'	=> 'text',	'default' => 'Latest Tweets', 'tooltip' => 'Title of the widget'),
			array(
				'name'	=> 'type',	'label'	=> 'Widget type',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Choose Timeline to display tweets of a specific user. Choose Search to get results about some query',
				'options' => array('1' => 'Timeline', '0' => 'Search')),
			array(
				'name'	=> 'username',	'label'	=> 'Username',
				'type'	=> 'text',	'default' => 'twitter', 'tooltip' => 'Twitter username for which you want to display tweets if widget type is set to Timeline'),
			array(
				'name'	=> 'query',	'label'	=> 'Search query',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Query to be searched if widget type is set to Search'),
			array(
				'name'	=> 'title',	'label'	=> 'Search title',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Search title to be displayed in the header'),
			array(
				'name'	=> 'link_title',	'label'	=> 'Link search title',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'When set to yes, title will be link to the search query on Twitter',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'count',	'label'	=> 'Tweet number',
				'type'	=> 'text',	'default' => '5', 'tooltip' => 'Number of Tweets to display'),
			array(
				'label' => '<div style="background-color: #666; padding: 5px; text-align:center; color: #fff; font-weight:bold;">Module appearance </div>',
				'type'	=> 'separator'),
			array(
				'name'	=> 'width',	'label'	=> 'Width',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Module width. Set to empty to use width of the parent container'),
			array(
				'name'	=> 'height',	'label'	=> 'Height',
				'type'	=> 'text',	'default' => '', 'tooltip' => 'Module height. If the height is smaller than the space required for tweets to fit, scrollbar will be displayed. Set to empty to never have the scrollbar and use full height'),
			array(
				'name'	=> 'header',	'label'	=> 'Show header',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Show header on top of tweets. For timeline, this will be name, username and avatar, and for search it will be the search title',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'twitter_icon',	'label'	=> 'Show Twitter icon',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Set to yes to display small twitter icon int the header',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'label' => '<div style="background-color: #666; padding: 5px; text-align:center; color: #fff; font-weight:bold;">Color options </div>',
				'type'	=> 'separator'),
			array(
				'name'	=> 'bgd_color',	'label'	=> 'Background color',
				'type'	=> 'color',	'default' => '#ffffff', 'tooltip' => 'Module background color. Default is white'),
			array(
				'name'	=> 'link_color',	'label'	=> 'Link color',
				'type'	=> 'color',	'default' => '#0084B4', 'tooltip' => 'Link color. Default is variation of blue'),
			array(
				'name'	=> 'border_color',	'label'	=> 'Border color',
				'type'	=> 'color',	'default' => '#cccccc', 'tooltip' => 'Border color, default is light gray'),
			array(
				'name'	=> 'text_color',	'label'	=> 'Text color',
				'type'	=> 'color',	'default' => '#333333', 'tooltip' => 'Text color, default is dark gray'),
			array(
				'name'	=> 'header_link_color',	'label'	=> 'Header name color',
				'type'	=> 'color',	'default' => '#333333', 'tooltip' => 'Link color for the Twitter name when widget type is set to Timeline'),
			array(
				'name'	=> 'header_sub_color',	'label'	=> 'Header username color',
				'type'	=> 'color',	'default' => '#999999', 'tooltip' => 'Link color for the Twitter username when widget type is set to Timeline'),
			array(
				'name'	=> 'header_sub_hover_color',	'label'	=> 'Header username on hover color',
				'type'	=> 'color',	'default' => '#666666', 'tooltip' => 'Header username on hover color" description="Link color for the Twitter username on mouse hover when widget type is set to Timeline'),
			array(
				'name'	=> 'search_title_color',	'label'	=> 'Search title color',
				'type'	=> 'color',	'default' => '#333333', 'tooltip' => 'Link color for the Search title when widget type is set to Search'),
			array(
				'label' => '<div style="background-color: #666; padding: 5px; text-align:center; color: #fff; font-weight:bold;">Tweet appearance </div>',
				'type'	=> 'separator'),
			array(
				'name'	=> 'display_name',	'label'	=> 'Display username',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Should the twitter username be displayed?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'avatars',	'label'	=> 'Display avatars',
				'type'	=> 'list',	'default' => '0', 'tooltip' => 'Should avatars be displayed?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'timestamps',	'label'	=> 'Display timestamps',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Should timestamps be shown?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'reply',	'label'	=> 'Reply link',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Should reply link be shown?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'retweet',	'label'	=> 'Retweet link',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Should retweet link be shown?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'favorite',	'label'	=> 'Favorite link',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'Should favorite link be shown?',
				'options' => array('1' => 'Yes',	'0' => 'No')),
			array(
				'name'	=> 'show_link',	'label'	=> '<span style="font-size: 9px; text-decoration: underline;">Donate a link to us?</span>',
				'type'	=> 'list',	'default' => '1', 'tooltip' => 'This is a small link at the bottom of this app, it links to our main website and helps us grow our business. Please consider leaving it active as a small donation for our free plugin. However, you can get rid of the link by not checking this box. Hope this is possible.',
				'options' => array('1' => 'Yes',	'0' => 'No')),
		);
		
		$control_ops = array('width' => 400);
      parent::WP_Widget(false, 'Any User Twitter Feed', array('description' => 'Embedding Twitter timelines or search'), $control_ops);
	}
	
   public function widget($args, $instance) {		
		extract( $args );
		$title = apply_filters('widget_title', $instance['widget_title']);
		echo $before_widget;  
		if ($title != '') {
			echo $before_title;
			echo $title;
			echo $after_title;
		}
		echo $this->getTweets($instance);
		echo $after_widget;
    }
    
    protected function getTweets($options) {
    	 require_once dirname(__FILE__).'/helper.php';
    	 $params = new AnyUserParams($options);
    	 $model = new AnyUserTwitterFeedHelper();
		 $model->addStyles($params);
		 $data = $model->getData($params);
		 if($data === false)
		 	require_once dirname(__FILE__) . '/error.php';
		 else if($data === '')
            echo 'No tweets returned';
         else
		 	require_once dirname(__FILE__) . '/output.php';
    }
    
    public function update($new_instance, $old_instance) {				
		$instance = $old_instance;
		
		foreach($this->options as $val) {
			if($val['type'] == 'text' || $val['type'] == 'color') {
				$instance[$val['name']] = strip_tags($new_instance[$val['name']]);
			}
			else if($val['type'] == 'checkbox') {
				$instance[$val['name']] = ($new_instance[$val['name']]=='on') ? true : false;
			}
			else if($val['type'] == 'list') {
				$instance[$val['name']] = strip_tags($new_instance[$val['name']]);
			}
		}
        return $instance;
    }
    
    public function form($instance) {
		if(empty($instance)) {
			foreach($this->options as $val) {
				if ($val['type'] == 'separator') {
					continue;
				}
				$instance[$val['name']] = $val['default'];
			}
		}					
	
		if (!is_callable('curl_init')) {
			echo __('Your PHP doesn\'t have cURL extension enabled. Please contact your host and ask them to enable it.');
			return;
		}
		require dirname(__FILE__) . '/description.php';
		
		foreach ($this->options as $val) {
			$title = '';
			if(!empty($val['tooltip'])) {
				$title = ' title="' . $val['tooltip'] . '"';
			}
			if($val['type'] == 'separator') {
				echo $val['label'] . '<br/ >';
			}
			else if($val['type'] == 'text') {
				$label = '<label for="' . $this->get_field_id($val['name']) . '" ' . $title . '>' . $val['label'] . '</label>';
				$value = $val['default'];
				if(isset($instance[$val['name']]))
					$value = esc_attr($instance[$val['name']]);
				echo '<p>' . $label . '<br />';
				echo '<input class="widefat" id="' . $this->get_field_id($val['name']) . '" name="' . $this->get_field_name($val['name']) . '" type="text" value="' . $value . '" ' . $title . '/></p>';
			}
			else if($val['type'] == 'checkbox') {
				$label = '<label for="' . $this->get_field_id($val['name']).'" ' . $title . '>' . $val['label'] . '</label>';
				$checked = ($instance[$val['name']]) ? 'checked="checked"' : '';
				echo '<input id="' . $this->get_field_id($val['name']) . '" name="' . $this->get_field_name($val['name']) . '" type="checkbox" ' . $checked . ' ' . $title . '/> ' . $label . '<br />';
			}
			else if($val['type'] == 'list') {
				$label = '<label for="' . $this->get_field_id($val['name']).'" ' . $title . '>' . $val['label'] . '</label>';
				echo '<p>' . $label . '<br />';
				echo '<select id="' . $this->get_field_id($val['name']) . '" name="' . $this->get_field_name($val['name']) . '" ' . $title . '>';
				foreach($val['options'] as $value => $title) {
					$selected = '';
					if($instance[$val['name']] == $value)
						$selected = 'selected="selected"';
					echo '<option value="' . $value . '" ' . $selected . '>' . $title . '</option>';
				}
				echo '</select></p>';
			}
			else if($val['type'] == 'color') {				
				$label = '<label for="' . $this->get_field_id($val['name']) . '" ' . $title . '>' . $val['label'] . '</label>';
				$value = $val['default'];
				if(isset($instance[$val['name']]))
					$value = esc_attr($instance[$val['name']]);
				echo '<p>' . $label . '<br />';
				echo '<input class="wds-color-field" id="' . $this->get_field_id($val['name']) . '" name="' . $this->get_field_name($val['name']) . '" type="text" value="' . $value . '" ' . $title . ' data-default-color="' . $val['default'] . '"/></p>';
			}
		}
		echo '<script type="text/javascript">jQuery(\'.wds-color-field\').wpColorPicker();</script>';
	}
}

// register AnyUserTwitterFeed
add_action('widgets_init', create_function('', 'return register_widget("AnyUserTwitterFeed");'));

add_action('admin_enqueue_scripts', 'loadAdminJs');
function loadAdminJs($hook) {
	if('widgets.php' != $hook)
        return;
	wp_enqueue_style('wp-color-picker');
	wp_enqueue_script('wdssettings', plugins_url('/js/wdssettings.js', __FILE__ ), array('jquery', 'wp-color-picker'));
}
