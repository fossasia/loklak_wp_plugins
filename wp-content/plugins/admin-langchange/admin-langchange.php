<?php
/*
Plugin Name: admin lang change
Plugin URI: https://github.com/macminiosx/openshift-wordpress-ja/tree/master/.openshift/plugins/admin-langchange
Description:  Change lang admin in 'ja' and site in 'en' plugin.

Version: 0.1
Author: macminiosx
Author URI: http://www.macminiosx.com/

*/

add_filter('locale', 'Admin_lang_change_func');

function Admin_lang_change_func() {
	if (strpos($_SERVER['REQUEST_URI'], '/wp-admin/') === 0) {
		return 'ja';
	} else {
		return 'en';
	}

}

add_filter('protected_title_format', 'remove_protected');
function remove_protected($title) {
	return '%s';
}
