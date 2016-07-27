<?php
/*
Plugin Name: DevBuddy Twitter Feed Plugin
Plugin URI: http://wordpress.org/plugins/devbuddy-twitter-feed/
Description: A Twitter (v1.1) feed plugin for the developers that's fully customisable and support timelines, searches and lists.
Author: Eji Osigwe
Version: 4.0.0
Author URI: http://www.eji-osigwe.co.uk/
Text Domain: devbuddy-twitter-feed
Domain Path: /languages

========================================================================
Copyright 2013  Eji Osigwe  (email : web@eji-osigwe.co.uk)

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
========================================================================
*/


// --------------------------------------------
// Define plugin-wide constants
// --------------------------------------------
define( 'DBTF_PATH', plugin_dir_path( __FILE__ ) );
define( 'DBTF_URL', plugins_url( NULL, __FILE__ ) );


// --------------------------------------------
// Require Twitter API exchange
// --------------------------------------------
require_once DBTF_PATH . '/lib/twitter-api-exchange.php';


// --------------------------------------------
// Require DevBuddy feed plugin base
// --------------------------------------------
require_once DBTF_PATH . '/lib/class.plugin-base.php';


// --------------------------------------------
// Require class that manages the plugin
// --------------------------------------------
require_once DBTF_PATH . '/lib/class.twitter-feed-base.php';


// --------------------------------------------
// Require and load WP options and settings
// --------------------------------------------
require_once DBTF_PATH . '/admin/class.wp-admin-helper.php';
require_once DBTF_PATH . '/admin/class.main-options.php';
$dbtf_wp_options = new DB_Twitter_Feed_Main_Options;


// --------------------------------------------
// Require class that handles html rendering
// --------------------------------------------
require_once DBTF_PATH . '/lib/class.feed-html.php';


// --------------------------------------------
// Require class that manages feed instances
// --------------------------------------------
require_once DBTF_PATH . '/lib/class.twitter-feed.php';


// --------------------------------------------
// Load the template tag
// --------------------------------------------
require_once DBTF_PATH . '/lib/twitter-feed-template-tag.php';


// --------------------------------------------
// Fire it up
// --------------------------------------------
$dbtf = new DB_Twitter_Feed_Base;