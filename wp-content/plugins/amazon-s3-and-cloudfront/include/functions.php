<?php
/**
 * API function to generate a link to download a file from Amazon S3 using
 * query string authentication, expiring after a set amount of time.
 *
 * @param mixed   $post_id Post ID of the attachment or null to use the loop
 * @param int     $expires Seconds for the link to live
 * @param mixed   $size    Size of the image to get
 */
function as3cf_get_secure_attachment_url( $post_id, $expires = 900, $size = null ) {
	global $as3cf;

	return $as3cf->get_secure_attachment_url( $post_id, $expires, $size );
}