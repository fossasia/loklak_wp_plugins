<?php
if ( ! defined('WP_UNINSTALL_PLUGIN') ) {
	exit();
}

delete_option( 'db_twitter_feed_options' );