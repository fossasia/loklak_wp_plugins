<?php
function db_twitter_feed( $feed_config = NULL ) {
	$the_feed = new DB_Twitter_Feed( $feed_config );

	if ( ! $the_feed->is_cached ) {
		$the_feed->retrieve_feed_data();

		$the_feed->render_feed_html();

		$the_feed->cache_output( $the_feed->options['cache_hours'] );
	}

	if ( $the_feed->is_shortcode_called ) {
		return $the_feed->output;
	} else {
		echo $the_feed->output;
	}

}