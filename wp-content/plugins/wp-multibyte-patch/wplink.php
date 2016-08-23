<?php

error_reporting( 0 );

function get_file( $path = null ) {
	if ( function_exists( 'realpath' ) )
		$path = realpath( $path );

	if ( ! $path || ! is_file( $path ) )
		return false;

	return @file_get_contents( $path );
}

$etag = isset( $_GET['ver'] ) ? md5( $_GET['ver'] ) : false;

if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && $_SERVER['HTTP_IF_NONE_MATCH'] === $etag ) {
	$protocol = $_SERVER['SERVER_PROTOCOL'];

	if ( ! in_array( $protocol, array( 'HTTP/1.1', 'HTTP/2', 'HTTP/2.0' ) ) )
		$protocol = 'HTTP/1.0';

	header( "$protocol 304 Not Modified" );

	exit;
}

$expires_offset = 31536000; // 1 year

if( $etag )
	header( "Etag: $etag" );

header( 'Content-Type: application/javascript; charset=UTF-8' );
header( 'Vary: Accept-Encoding' );
header( 'Expires: ' . gmdate( 'D, d M Y H:i:s', time() + $expires_offset ) . ' GMT' );
header( "Cache-Control: public, max-age=$expires_offset" );

$debug_suffix = isset( $_GET['sd'] ) ? '' : '.min';
$file = dirname( dirname( dirname( dirname( __FILE__ ) ) ) ) . "/wp-includes/js/wplink{$debug_suffix}.js";
$out = get_file( $file );

if( ! $out )
	exit;

if( isset( $_GET['sd'] ) )
	echo str_replace( 'search.length > 2', 'search.length > 1', $out );
else
	echo str_replace( '.length>2){if', '.length>1){if', $out );

exit;
