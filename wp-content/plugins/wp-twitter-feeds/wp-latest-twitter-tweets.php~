<?php
/**
 *Plugin Name: WP Twitter Feeds
 *Plugin URI: http://www.vivacityinfotech.net/
 *Description: Displays latest tweets from your Twitter account using Twitter oAuth API 1.1.
 *Author: Vivacity Infotech Pvt. Ltd.
 *Version: 1.4.8
 *Author URI: http://www.vivacityinfotech.net/
 *Author Email: support@vivacityinfotech.net
 */
 /* Copyright 2014  Vivacity InfoTech Pvt. Ltd.  (email : support@vivacityinfotech.net)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
include('twitter_usr_validation.php');
require_once('controller/twitter_widget.class.php');
add_action( 'widgets_init', 'wpltf_reg_widget');
function wpltf_reg_widget()
{

	register_widget("wptt_TwitterTweets");
}

add_filter('plugin_row_meta', 'add_meta_links',10, 2);
function add_meta_links($links, $file) {
	if ( strpos( $file, 'wp-latest-twitter-tweets.php' ) !== false ) {
		$links[] = '<a href="http://vivacityinfotech.net/support">Support</a>';
		$links[] = '<a href="http://bit.ly/1icl56K">Donate</a>';
	}
	return $links;
}
?>
