<?php
/*
Plugin Name: Disable WordPress Plugin Updates
Description: Disables the plugin update checking and notification system.
Plugin URI:  http://lud.icro.us/disable-wordpress-plugin-updates/
Version:     1.4
Author:      John Blackbourn
Author URI:  http://johnblackbourn.com/

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

*/

# 2.3 to 2.7:
add_action( 'admin_menu', create_function( '$a', "remove_action( 'load-plugins.php', 'wp_update_plugins' );") );
	# Why use the admin_menu hook? It's the only one available between the above hook being added and being applied
add_action( 'admin_init', create_function( '$a', "remove_action( 'admin_init', 'wp_update_plugins' );"), 2 );
add_action( 'init', create_function( '$a', "remove_action( 'init', 'wp_update_plugins' );"), 2 );
add_filter( 'pre_option_update_plugins', create_function( '$a', "return null;" ) );

# 2.8 to 3.0:
remove_action( 'load-plugins.php', 'wp_update_plugins' );
remove_action( 'load-update.php', 'wp_update_plugins' );
remove_action( 'admin_init', '_maybe_update_plugins' );
remove_action( 'wp_update_plugins', 'wp_update_plugins' );
add_filter( 'pre_transient_update_plugins', create_function( '$a', "return null;" ) );

# 3.0:
remove_action( 'load-update-core.php', 'wp_update_plugins' );
add_filter( 'pre_site_transient_update_plugins', create_function( '$a', "return null;" ) );

?>