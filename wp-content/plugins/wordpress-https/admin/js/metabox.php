<?php

require_once(realpath(dirname(__FILE__) . '/../../../../..') . '/wp-load.php');

// Disable errors
error_reporting(0);

// Set headers
header("Status: 200");
header("HTTP/1.1 200 OK");
header('Content-Type: text/html');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');
header("Vary: Accept-Encoding");

if ( ! wp_verify_nonce($_POST['_nonce'], 'wordpress-https') ) {
	exit;
}

$content = WordPressHTTPS_Url::fromString( $_POST['url'] )->getContent();

if ( $content ) {
	echo $content;
}
?>