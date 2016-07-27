<?php

/* Copy all of this into your theme, making sure
   to tweak the name of the shortcode to taste
*********************************************************/
add_shortcode(
	'my_twitter_feed_shortcode',         // The name of your shortcode
	'register_my_twitter_feed_shortcode' // This should match the function name below
);


/* For simplicity, the shortcode simply passes data
   to the template tag, nothing more

   If a new option is made available in the future
   you'll need to add it to the $default_atts array
   and the $feed_config array.
*********************************************************/
function register_my_twitter_feed_shortcode( $given_atts ) {

	/* Default values here will always be NULL, defaults
	   are checked and set in the plugin itself */
	$default_atts =
	array(
		'feed_type'                 => NULL,
		'user'                      => NULL,
		'search_term'               => NULL,
		'list'                      => NULL,
		'count'                     => NULL,
		'exclude_replies'           => NULL,
		'exclude_retweets'          => NULL,
		'relative_times'            => NULL,
		'show_images'               => NULL,
		'https'                     => NULL,
		'default_styling'           => NULL,
		'cache_hours'               => NULL,
		'clear_cache'               => NULL,
		'oauth_access_token'        => NULL,
		'oauth_access_token_secret' => NULL,
		'consumer_key'              => NULL,
		'consumer_secret'           => NULL
	);

	extract(
		shortcode_atts( $default_atts, $given_atts )
	);

	$feed_config =
	array(
		'feed_type'                 => $feed_type,
		'user'                      => $user,
		'search_term'               => $search_term,
		'list'                      => $list,
		'count'                     => $count,
		'exclude_replies'           => $exclude_replies,
		'exclude_retweets'          => $exclude_retweets,
		'relative_times'            => $relative_times,
		'show_images'               => $show_images,
		'https'                     => $https,
		'default_styling'           => $default_styling,
		'cache_hours'               => $cache_hours,
		'clear_cache'               => $clear_cache,
		'oauth_access_token'        => $oauth_access_token,
		'oauth_access_token_secret' => $oauth_access_token_secret,
		'consumer_key'              => $consumer_key,
		'consumer_secret'           => $consumer_secret,
		'is_shortcode_called'       => TRUE
	);


	/* If you've created your own template tag, change the
	   tag here to match it. Otherwise, leave it as is */
	return db_twitter_feed( $feed_config );

}