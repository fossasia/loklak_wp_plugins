<?php

require_once('wordpress-https.php');

if ( !defined('WP_UNINSTALL_PLUGIN') ) {
	die();
}

$options = array(
	'wordpress-https_external_urls',
	'wordpress-https_secure_external_urls',
	'wordpress-https_unsecure_external_urls',
	'wordpress-https_ssl_host',
	'wordpress-https_ssl_host_diff',
	'wordpress-https_ssl_port',
	'wordpress-https_exclusive_https',
	'wordpress-https_frontpage',
	'wordpress-https_ssl_login',
	'wordpress-https_ssl_admin',
	'wordpress-https_ssl_proxy',
	'wordpress-https_ssl_host_subdomain',
	'wordpress-https_version',
	'wordpress-https_debug',
	'wordpress-https_admin_menu',
	'wordpress-https_secure_filter',
	'wordpress-https_ssl_host_mapping'
);

global $wpdb;
if ( is_multisite() && is_network_admin() ) {
	$blogs = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM " . $wpdb->blogs, NULL));
} else {
	$blogs = array($wpdb->blogid);
}

// Delete WordPress HTTPS options
foreach ( $blogs as $blog_id ) {
	foreach( $options as $option ) {
		if ( is_multisite() ) {
			delete_blog_option($blog_id, $option);
		} else {
			delete_option($option);
		}
	}
}

// Delete force_ssl custom_field from posts and pages
delete_metadata('post', null, 'force_ssl', null, true);