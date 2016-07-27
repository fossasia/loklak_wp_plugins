<?php

/**
 * A class for rendering and managing an instance of
 * a Twitter feed
 *
 * This is the class to call at the top when scripting
 * the template tag. Be sure to offer a $feed_config
 * array as the parameter to properly initialise the
 * object instance.
 *
 * @version 1.3.0
 */
if ( ! class_exists( 'DB_Twitter_Feed' ) ) {

class DB_Twitter_Feed extends DB_Twitter_Feed_Base {

	/**
	 * @var array A holding place for parsed tweet data
	 * @since 1.1.0
	 */
	protected $tweet;

	/**
	 * @var object Holds an instance of the HTML rendering class
	 * @since 1.0.3
	 */
	public $html;

	/**
	 * @var string Main Twitter URL
	 * @since 1.0.0
	 */
	public $tw = 'https://twitter.com/';

	/**
	 * @var string Twitter search URL
	 * @since 1.0.0
	 */
	public $search = 'https://twitter.com/search?q=';

	/**
	 * @var string Twitter intent URL
	 * @since 1.0.0
	 */
	public $intent = 'https://twitter.com/intent/';

	/**
	 * @var string Twitter User endpoint URL
	 * @since 1.3.0
	 */
	protected $user_endpoint = 'https://api.twitter.com/1.1/statuses/user_timeline/';

	/**
	 * @var string Twtter Search endpoint URL
	 * @since 1.3.0
	 */
	protected $search_endpoint = 'https://api.twitter.com/1.1/search/tweets.json';

	/**
	 * @var string Twitter List endpoint URL
	 * @since 1.3.0
	 */
	protected $list_endpoint = 'https://api.twitter.com/1.1/lists/statuses.json';

	/**
	 * @var array If there are any errors after a check is made for such, they will be stored here
	 * @since 1.0.2
	 */
	public $errors;

	/**
	 * @var string The term the feed rendered is based on
	 * @since  1.1.0
	 */
	protected $feed_term;


	/**
	 * Constructor.
	 *
	 * @access public
	 * @since 1.0.0
	 */
	public function __construct( $feed_config ) {

		$this->set_main_admin_vars();
		$this->initialise_the_feed( $feed_config );

	}


	/**
	 * Configure data necessary for rendering the feed
	 *
	 * Get the feed configuration provided by the user
	 * and use defaults for options not provided, check
	 * for a cached version of the feed under the given
	 * user, initialise a Twitter API object.
	 *
	 * @access private
	 * @return void
	 * @since  1.0.4
	 */
	private function initialise_the_feed( $feed_config = null ) {

		/* Populate the $options property with the config options submitted
		   by the user. Should any of the options not be set, fall back on
		   stored values, then defaults */
		if ( ! is_array( $feed_config ) ) {
			$feed_config = array();
		}

		foreach ( $this->defaults as $option => $value ) {
			if ( ! array_key_exists( $option, $feed_config ) || empty( $feed_config[ $option ] ) ) {
				if ( $option === 'user' ) {
					$stored_value = $this->get_db_plugin_option( $this->options_name_main, 'twitter_username' );

				} elseif( $option === 'count' ) {
					$stored_value = $this->get_db_plugin_option( $this->options_name_main, 'result_count' );

				} else {
					$stored_value = $this->get_db_plugin_option( $this->options_name_main, $option );

				}

				if ( $stored_value !== FALSE ) {
					$this->options[ $option ] = $stored_value;
				} else {
					$this->options[ $option ] = $value;
				}

			} elseif ( array_key_exists( $option, $feed_config ) ) {
				$this->options[ $option ] = $feed_config[ $option ];
			}
		}


		/* The shortcode delivered feed config brings with it
		   the 'is_shortcode_called' option */

		/* As the above check is based on the items in the
		   $options property array, this option is ignored
		   by the check even if defined in the config by
		   the user because it isn't defined in $options */
		if ( isset( $feed_config['is_shortcode_called'] ) && $feed_config['is_shortcode_called'] === TRUE ) {
			$this->is_shortcode_called = TRUE;
		} else {
			$this->is_shortcode_called = FALSE;
		}


		/* Grab the term that is to be used to render the feed
		   and place it under once common variable. Saves one
		   having to check through each term type (user, search,
		   etc) to find the term in use */
		switch ( $this->options['feed_type'] ) {
			case 'user_timeline':
				$this->feed_term = $this->options['user'];
				break;

			case 'search':
				$this->feed_term = $this->options['search_term'];
				break;

			case 'list':
				$this->feed_term = $this->options[ $this->options['feed_type'] ];
				break;

			default:
				$this->feed_term = NULL;
				break;
		}


		/* Check to see if there is a cache available with the
		   username provided. Move into the output if so */

		/* However, we don't do this without first checking
		   whether or not a clearance of the cache has been
		   requested */
		if ( $this->options['clear_cache'] === 'yes' || (int) $this->options['cache_hours'] === 0 ) {
			$this->clear_cache_output( $this->feed_term );
		}

		$this->output = get_transient( $this->plugin_name . '_output_' . $this->feed_term );
		if ( $this->output !== FALSE ) {
			$this->is_cached = TRUE;

			/* Versions of the plugin prior to 3.1.0 didn't JSON encode
			   the output for caching so anyone updating will suffer
			   broken feeds when the plugin attempts to decode a cached
			   output that isn't JSON encoded. Here we place the JSON
			   decoded output in a placeholder */
			$this->output_ph = json_decode($this->output);

			// If the cached version is not legacy, we proceed as normal
			if ( $this->output_ph !== NULL ) {
				$this->output = $this->output_ph;
			}

		} else {
			$this->is_cached = FALSE;
		}


		// Load the bundled stylesheet if requested
		if ( $this->options['default_styling'] === 'yes' ) {
			$this->load_default_styling();
		}


		// Load the HTML rendering class
		$url_data = array(
			'tw'     => $this->tw,
			'search' => $this->search,
			'intent' => $this->intent
		);
		$this->html = new DB_Twitter_HTML( $this->options, $url_data );


		// Check for any NULL value auth options
		$auth_data = array(
			'oauth_access_token'        => $this->options['oauth_access_token'],
			'oauth_access_token_secret' => $this->options['oauth_access_token_secret'],
			'consumer_key'              => $this->options['consumer_key'],
			'consumer_secret'           => $this->options['consumer_secret']
		);

		if ( in_array( NULL, $auth_data ) ) {
			foreach ( $auth_data as $auth_name => $auth_value ) {
				if ( $auth_value === NULL ) {
					$msg  = 'Unable to authorise connection to Twitter, no ';
					$msg .= ucwords( str_replace( '_', ' ', $auth_name ) );
					$msg .= ' given';
					$this->register_feed_error( $msg );
				}
			}

		} else {
			// Get Twitter object
			$this->twitter = new TwitterAPIExchange( $auth_data );
		}

	}


	/**
	 * Perform a retrieval of feed data based on the options given
	 *
	 * @access public
	 * @since  1.3.0
	 * @return bool|mixed The raw data if successful, FALSE otherwise
	 *
	 * @param array $options Only `get_parameters` and `endpoint_url` are supported
	 */
	public function perform_retrieval( $options = array() ) {
		// We must query parameter provided
		if ( empty( $options['get_parameters'] ) || ! is_array( $options['get_parameters'] ) ) {
			return FALSE;
		} else {
			$get_field = '?' . http_build_query( $options['get_parameters'] );
		}

		// We must also have an endpoint URL
		if ( empty( $options['endpoint_url'] ) ) {
			return FALSE;
		} else {
			$endpoint = $options['endpoint_url'];
		}


		// Set request method to GET
		$request_method = 'GET';

		// Send data request
		$feed_data = $this->twitter->setGetfield( $get_field )
			->buildOauth( $endpoint, $request_method )
			->performRequest();

		// Decode the data
		$feed_data = json_decode( $feed_data );

		// Search feed data comes with an extra wrapper object around the tweet items
		if ( $this->options['feed_type'] === 'search' && ( isset( $feed_data->statuses ) && is_array(
					$feed_data->statuses ) ) ) {
			$feed_data = $feed_data->statuses;
		}

		return $feed_data;
	}


	/**
	 * Performs a full retrieval of data, iteratively retrieving more
	 * if the item count is lower the count option provided.
	 *
	 * This method also checks for errors.
	 *
	 * @access public
	 * @return bool TRUE on success, FALSE otherwise
	 * @since  1.0.0
	 */
	public function retrieve_feed_data() {

		// Skip this method if there are errors registered
		if ( $this->has_errors() ) {
			return FALSE;
		}

		$retrieval_params = array(
			'get_parameters' => array(
				'count' => $this->options['count']
			)
		);

		// Set retrieval parameters
		switch ( $this->options['feed_type'] ) {
			case 'user_timeline':
				$retrieval_params['endpoint_url'] = $this->user_endpoint . $this->options['user'] . '.json';
				$retrieval_params['get_parameters']['screen_name'] = $this->options['user'];

				if ( $this->options['exclude_replies'] === 'yes' ) {
					$retrieval_params['get_parameters']['exclude_replies'] = 'true';
				}

				if ( $this->options['exclude_retweets'] === 'yes' ) {
					$retrieval_params['get_parameters']['include_rts'] = '0';
				} else {
					$retrieval_params['get_parameters']['include_rts'] = '1';
				}
			break;

			case 'search':
				$retrieval_params['endpoint_url'] = $this->search_endpoint;
				$retrieval_params['get_parameters']['q'] = $this->options['search_term'];
				$retrieval_params['get_parameters']['result_type'] = 'recent';
			break;

			case 'list':
				$list_data = $this->get_list_term_data( $this->options['list'] );
				if ( $list_data === FALSE ) {
					return FALSE;
				}

				$retrieval_params['endpoint_url'] = $this->list_endpoint;
				$retrieval_params['get_parameters']['slug'] = $list_data[0];
				$retrieval_params['get_parameters']['owner_screen_name'] = $list_data[1];
				if ( $this->options['exclude_retweets'] === 'yes' ) {
					$retrieval_params['get_parameters']['include_rts'] = 'false';
				}
				break;

			default:
				return FALSE;
				break;
		}

		// Retrieve the data
		$this->feed_data = $this->perform_retrieval( $retrieval_params );

		// Retrieval was unsuccessful
		if ( $this->feed_data === FALSE ) {
			return FALSE;
		}

		// Register any errors. Return false where errors exist
		if ( is_object( $this->feed_data ) && is_array( $this->feed_data->errors ) ) {
			foreach ( $this->feed_data->errors as $error ) {
				$this->register_feed_error( $error->message, $error->code );
			}

			return FALSE;

		}


		// Grab the end tweet ID
		$feed_end_tweet = end( $this->feed_data );
		$cont_id = $feed_end_tweet->id_str;

		// Create an array of nicely parsed feed data
		foreach ( $this->feed_data as $tweet ) {
			$parsed_tweet = $this->parse_tweet_data( $tweet );
			if ( is_array( $parsed_tweet ) ) {
				$this->parsed_feed_data[] = $parsed_tweet;
				$this->item_count++;
			}
		}

		/* Attempt to honour the given tweet count where settings
		   result in a returned data set that falls short */
		$iteration = 0;
		while ( $this->item_count < (int) $this->options['count'] ) {
			$iteration++;

			if ( $iteration <= 3 ) {
				// Figure out how many tweets left to get
				$retrieval_count = $this->options['count'] - $this->item_count;

				/* This retrieval includes the last tweet from the last retrieval
				   as a result of using the last tweet's ID as the starting point.
				   Here, we +1 the retrieval count to account for that. We'll
				   remove the repeated tweet after retrieval. */
				$retrieval_count++;

				// Update retrieval parameters
				$retrieval_params['get_parameters']['count']  = $retrieval_count;
				$retrieval_params['get_parameters']['max_id'] = $cont_id;

				// Retrieve more data
				$more_data = $this->perform_retrieval($retrieval_params);

				if ( empty( $more_data ) ) {
					break;
				}

				// Check for errors
				/* If we got to this point, fundamental functionality is fine
				   so we don't punish rendering the feed because of errors at
				   this point. We simply cease trying to fill tweet gaps */
				if ( is_object( $more_data ) && is_array( $more_data->errors ) ) {
					/* But we register errors anyway, just in case it does cause
					   undesired behaviour */
					foreach ( $more_data->errors as $error ) {
						$this->register_feed_error( $error->message, $error->code );
					}

					break;
				}

				/* Remove the first tweet as it will be the same as the last tweet
				   from the last retrieval */
				unset( $more_data[0] );

				// Update the raw feed data property
				$this->feed_data = $this->feed_data + $more_data;

				// Grab the end tweet ID
				$feed_end_tweet = end( $more_data );
				$cont_id = $feed_end_tweet->id_str;

				foreach ( $more_data as $tweet ) {
					$parsed_tweet = $this->parse_tweet_data( $tweet );
					if ( is_array( $parsed_tweet ) ) {
						$this->parsed_feed_data[] = $parsed_tweet;
						$this->item_count++;
					}
				}

			} else {
				break;
			}
		}

		// All is well
		return TRUE;

	}


	/**
	 * Return the feed term for the current feed rendered
	 *
	 * @access public
	 * @return mixed Returns the term as a string, or FALSE if none has yet been set
	 * @since  1.1.0
	 */
	public function get_feed_term() {
		return $this->feed_term;
	}


	/**
	 * Check that the timeline queried actually has tweets
	 *
	 * @access public
	 * @return bool An indication of whether or not the returned feed data has any renderable entries
	 * @since  1.0.1
	 */
	public function is_empty() {

		if ( is_array( $this->feed_data ) && count( $this->feed_data ) === 0 ) {
			return TRUE;

		} elseif ( empty( $this->feed_data ) ) {
			return TRUE;

		} else {
			return FALSE;

		}

	}


	/**
	 * Check to see if any errors have been returned during the process of
	 * handling feed data
	 *
	 * @access public
	 * @return bool
	 * @since  1.0.2
	 */
	public function has_errors() {

		if ( ! empty ( $this->errors ) ) {
			return TRUE;

		} else {
			return FALSE;

		}

	}


	/**
	 * Register an error and its message for this particular feed instance
	 *
	 * @access public
	 * @return void
	 * @since  1.2.0
	 *
	 * @param string $msg  Error details
	 * @param mixed  $code A code that represents the error that you're registering
	 */
	public function register_feed_error( $msg, $code = 'NA (internal error)' ) {

		if ( empty ( $this->errors ) ) {
			$this->errors = array();
		}

		$error = new stdClass();
		$error->message = $msg;
		$error->code    = $code;

		$this->errors[] = $error;

	}


	/**
	 * Parse and return useful tweet data from an individual tweet in an array
	 *
	 * This is best utilised within an iteration loop that iterates through a
	 * populated $feed_data.
	 *
	 * @access public
	 * @return mixed Tweet data from the tweet item given, FALSE if invalid data given
	 * @since  1.0.2
	 */
	public function parse_tweet_data( $t ) {

		// Check that data given hasn't already been parsed
		if ( is_array( $t ) && ! empty( $t['is_parsed'] ) ) {
			return $t;
		}

		// Check that data given is valid tweet
		if ( ! is_object( $t ) || ( is_object( $t ) && empty( $t->id_str ) ) ) {
			return false;
		}

		$tweet = array();

		$tweet['is_parsed'] = true;

		$tweet['is_retweet'] = ( isset( $t->retweeted_status ) ) ? TRUE : FALSE;


	/*	User data */
	/************************************************/
		if ( ! $tweet['is_retweet'] ) {
			$tweet['user_id']               = $t->user->id_str;
			$tweet['user_display_name']     = $t->user->name;
			$tweet['user_screen_name']      = $t->user->screen_name;
			$tweet['user_description']      = $t->user->description;
			$tweet['profile_img_url']       = $t->user->profile_image_url;
			$tweet['profile_img_url_https'] = $t->user->profile_image_url_https;

			if ( isset( $t->user->entities->url->urls ) ) {
				$tweet['user_urls'] = array();
				foreach ( $t->user->entities->url->urls as $url_data ) {
					$tweet['user_urls']['short_url']   = $url_data->url;
					$tweet['user_urls']['full_url']    = $url_data->expanded_url;
					$tweet['user_urls']['display_url'] = $url_data->display_url;
				}
			}


		/*	When Twitter shows a retweet, the account that has
			been retweeted is shown rather than the retweeter's */

		/*	To emulate this we need to grab the necessary data
			that Twitter has thoughtfully made available to us */
		} elseif ( $tweet['is_retweet'] ) {
			$tweet['retweeter_display_name'] = $t->user->name;
			$tweet['retweeter_screen_name']  = $t->user->screen_name;
			$tweet['retweeter_description']  = $t->user->description;
			$tweet['user_id']                = $t->retweeted_status->user->id_str;
			$tweet['user_display_name']      = $t->retweeted_status->user->name;
			$tweet['user_screen_name']       = $t->retweeted_status->user->screen_name;
			$tweet['user_description']       = $t->retweeted_status->user->description;
			$tweet['profile_img_url']        = $t->retweeted_status->user->profile_image_url;
			$tweet['profile_img_url_https']  = $t->retweeted_status->user->profile_image_url_https;

			if ( isset( $t->retweeted_status->url ) ) {
				$tweet['user_urls'] = array();
				foreach ( $t->retweeted_status->user->entities->url->urls as $url_data ) {
					$tweet['user_urls']['short_url']   = $url_data->url;
					$tweet['user_urls']['full_url']    = $url_data->expanded_url;
					$tweet['user_urls']['display_url'] = $url_data->display_url;
				}
			}
		}


	/*	Tweet data */
	/************************************************/
		$tweet['id']   = $t->id_str;
		$tweet['text'] = $t->text;

		if ( $this->options['relative_times'] === 'yes' ) {
			$tweet['date'] = $this->formatify_date( $t->created_at );
		} else {
			$tweet['date'] = $this->formatify_date( $t->created_at, FALSE );
		}

		$tweet['user_replied_to']	= $t->in_reply_to_screen_name;

		$tweet['hashtags'] = array();
		foreach ( $t->entities->hashtags as $ht_data ) {
			$tweet['hashtags']['text'][]    = $ht_data->text;
			$tweet['hashtags']['indices'][] = $ht_data->indices;
		}

		$tweet['mentions'] = array();
		foreach ( $t->entities->user_mentions as $mention_data ) {
			$tweet['mentions'][] = array(
				'screen_name' => $mention_data->screen_name,
				'name'        => $mention_data->name,
				'id'          => $mention_data->id_str
			);
		}

		$tweet['urls'] = array();
		foreach ( $t->entities->urls as $url_data ) {
			$tweet['urls'][] = array(
				'short_url'    => $url_data->url,
				'expanded_url' => $url_data->expanded_url,
				'display_url'  => $url_data->display_url
			);
		}

		if ( isset( $t->entities->media ) ) {
			$tweet['media'] = array();
			foreach ( $t->entities->media as $media_data ) {
				$tweet['media'][] = array(
					'id'              => $media_data->id_str,
					'type'            => $media_data->type,
					'sizes'           => $media_data->sizes,
					'short_url'       => $media_data->url,
					'media_url'       => $media_data->media_url,
					'display_url'     => $media_data->display_url,
					'expanded_url'    => $media_data->expanded_url,
					'media_url_https' => $media_data->media_url_https
				);
			}
		}


	/*	Clean up and format the tweet text */
	/************************************************/
		if ( $tweet['is_retweet'] ) {
			// Shave unnecessary "RT [@screen_name]: " from the tweet text
			$char_count  = strlen( '@' . $tweet['user_screen_name'] ) + 5;
			$shave_point = ( 0 - strlen( $tweet['text'] ) ) + $char_count;
			$tweet['text']  = substr( $tweet['text'], $shave_point );
		}

		$tweet['text'] =
		( isset( $tweet['hashtags']['text'] ) ) ? $this->linkify_hashtags( $tweet['text'], $tweet['hashtags']['text'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['mentions'] ) ) ? $this->linkify_mentions( $tweet['text'], $tweet['mentions'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['urls'] ) ) ? $this->linkify_links( $tweet['text'], $tweet['urls'] ) : $tweet['text'];

		$tweet['text'] =
		( isset( $tweet['media'] ) ) ? $this->linkify_media( $tweet['text'], $tweet['media'] ) : $tweet['text'];

		return $tweet;

	}


	/**
	 * Loop through the feed data and render the HTML of the feed
	 *
	 * The output is stored in the $output property
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function render_feed_html() {
		// The holding element
		$this->output .= '<div class="tweets">';

		/* If Twitter's having none of it (most likely due to
		   bad config) then we get the errors and display them
		   to the user */
		if ( $this->has_errors() ) {
			$this->output .= '<p>' . __( 'Twitter has returned errors:', 'devbuddy-twitter-feed' ) . '</p>';
			$this->output .= '<ul>';

			foreach ( $this->errors as $error ) {
				$this->output .= '<li>&ldquo;' . $error->message . ' [error code: ' . $error->code . ']&rdquo;</li>';
			}

			$this->output .= '</ul>';

			$this->output .= '<p>';
			$this->output .= sprintf(
				__( 'More information on errors that have codes %shere%s.', 'devbuddy-twitter-feed' ),
				sprintf(
					'<a href="https://dev.twitter.com/docs/error-codes-responses" target="_blank" title="%s">',
					esc_attr__( 'Twitter API Error Codes and Responses', 'devbuddy-twitter-feed' )
				),
				'</a>'
			);
			$this->output .= '</p>';

		/* If the result set returned by the request is
		   empty we let the user know */
		} elseif ( $this->is_empty() ) {
			switch ( $this->options['feed_type'] ) {
				case 'user_timeline':
					$this->output .= '<p>' . __( 'Looks like this timeline is completely empty.', 'devbuddy-twitter-feed' ) . '</p>';
				break;

				case 'search':
					$this->output .= '<p>';
					$this->output .= sprintf(
						__( 'Your search for %s doesn&rsquo;t have any recent results. %sPerform a full search on Twitter%s to see all results.', 'devbuddy-twitter-feed' ),
						'<strong>' . $this->options['search_term'] . '</strong>',
						sprintf(
							' <a href="%s" title="%s" target="_blank">',
							$this->search . urlencode($this->options['search_term']),
							sprintf(
								esc_attr__( 'Search Twitter for %s', 'devbuddy-twitter-feed' ),
								$this->options['search_term']
							)
						),
						'</a>'
					);
					$this->output .= '</p>';
				break;

				default:
					$this->output .= '<p>' . __( 'There are no tweets to display.', 'devbuddy-twitter-feed' ) . '</p>';
				break;
			}

		// If all is well, we get on with it
		} else {
			foreach ( $this->parsed_feed_data as $tweet ) {
				$this->render_tweet_html( $tweet );
			}

		}

		$this->output .= '</div>';

	}


	/**
	 * Takes a tweet object and renders the HTML for that tweet
	 *
	 * The output is stored in the $output property
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 */
	public function render_tweet_html( $the_tweet ) {

		$this->tweet = $this->parse_tweet_data( $the_tweet );

		$this->html->set( $this->tweet );

		// START Rendering the Tweet's HTML (outer tweet wrapper)
		$this->output .= $this->html->open_tweet();


			// START Tweet content (inner tweet wrapper)
			$this->output .= $this->html->open_tweet_content();


				// START Tweeter's display picture
				$this->output .= $this->html->tweet_display_pic();
				// END Tweeter's display picture


				// START Tweet user info
				$this->output .= $this->html->open_tweet_primary_meta();
					$this->output .= $this->html->tweet_display_name_link();
				$this->output .= $this->html->close_tweet_primary_meta();
				// END Tweet user info


				// START Actual tweet
				$this->output .= $this->html->tweet_text();
				$this->output .= $this->html->tweet_media();
				// END Actual tweet


				// START Tweet meta data
				$this->output .= $this->html->open_tweet_secondary_meta();
					$this->output .= $this->html->tweet_date();
					$this->output .= $this->html->tweet_retweeted();
					$this->output .= $this->html->tweet_intents();
				$this->output .= $this->html->close_tweet_secondary_meta();
				// END Tweet meta data


			$this->output .= $this->html->close_tweet_content();
			// END Tweet content


		$this->output .= $this->html->close_tweet();
		// END Rendering Tweet's HTML

	}


	/* The following "linkify" functions look for
	   specific components within the tweet text
	   and converts them to links using the data
	   provided by Twitter */

	/* @Mouthful
	   Each function accepts an array holding arrays.
	   Each array held within the array represents
	   an instance of a linkable item within that
	   particular tweet and has named keys
	   representing useful data to do with that
	   instance of the linkable item */
	/************************************************/

	/**
	 * Transform hastags within a tweet into active links
	 *
	 * @access public
	 * @return string The tweet with the hashtags transformed into links
	 * @since  1.0.0
	 *
	 * @param $tweet    string The existing tweet text
	 * @param $hashtags array  An array containing the hashtag data returned by twitter in its entities values
	 */
	public function linkify_hashtags( $tweet, $hashtags ) {

		if ( $hashtags !== NULL ) {
			foreach ( $hashtags as $hashtag ) {
				$title_attr = sprintf(
					esc_attr__( 'Search Twitter for %s' ),
					esc_attr( '#' . $hashtag )
				);
				$tweet = str_replace(
					'#' . $hashtag,
					'<a href="' . $this->search . urlencode( '#' . $hashtag ) . '" target="_blank" title="' . $title_attr . '">#' . $hashtag . '</a>',
					$tweet
				);
			}

			return $tweet;

		} else {
			return $tweet;

		}

	}

	/**
	 * Transform mentions within a tweet into active links
	 *
	 * @access public
	 * @return string The tweet with the mentions transformed into links
	 * @since  1.0.0
	 *
	 * @param $tweet    string The existing tweet text
	 * @param $mentions array  An array containing the mention data returned by twitter in its entities values
	 */
	public function linkify_mentions( $tweet, $mentions ) {

		if ( is_array( $mentions ) && count( $mentions ) !== 0 ) {
			foreach ( $mentions as $mention ) {
				$count = count( $mentions );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = preg_replace(
						'|@' . $mentions[ $i ]['screen_name'] . '|',
						'<a href="' . $this->tw . $mentions[ $i ]['screen_name'] . '" target="_blank" title="' . $mentions[ $i ]['name'] . '">@'.$mentions[ $i ]['screen_name'] . '</a>',
						$tweet
					);
				}

				return $tweet;
			}

		} else {
			return $tweet;

		}

	}

	/**
	 * Transform URLs within a tweet into active links
	 *
	 * @access public
	 * @return string The tweet with the URLs transformed into active links
	 * @since  1.0.0
	 *
	 * @param $tweet string The existing tweet text
	 * @param $urls  array  An array containing the URL data returned by twitter in its entities values
	 */
	public function linkify_links( $tweet, $urls ) {

		if ( is_array( $urls ) && count( $urls ) !== 0 ) {
			foreach ( $urls as $url ) {
				$count = count( $urls );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = str_replace(
						$urls[ $i ]['short_url'],
						'<a href="' . $urls[ $i ]['short_url'] . '" target="_blank">' . $urls[ $i ]['display_url'] . '</a>',
						$tweet
					);
				}

				return $tweet;
			}

		} else {
			return $tweet;

		}

	}

	/**
	 * Transform media data within a tweet into active links
	 *
	 * @access public
	 * @return string The tweet with any media transformed into active links
	 * @since  1.0.0
	 *
	 * @param $tweet string The existing tweet text
	 * @param $media array  An array containing the media data returned by twitter in its entities values
	 */
	public function linkify_media( $tweet, $media ) {

		if ( is_array( $media ) && count( $media ) !== 0 ) {
			foreach ( $media as $item ) {
				$count = count( $media );

				for ( $i = 0; $i < $count; $i++ ) {
					$tweet = str_replace(
						$media[ $i ]['short_url'],
						'<a href="' . $media[ $i ]['short_url'] . '" target="_blank">' . $media[ $i ]['display_url'] . '</a>',
						$tweet
					);
				}

				return $tweet;
			}

		} else {
			return $tweet;

		}

	}


	/**
	 * Echo whatever is currently stored in the DB_Twitter_Feed::output property to the page
	 *
	 * This method also calls the DevBuddy_Feed_Plugin::cache_output() method
	 *
	 * @access public
	 * @return void
	 * @since  1.0.0
	 * @uses   DevBuddy_Feed_Plugin::cache_output() to cache the output before it's echoed
	 */
	public function echo_output() {

		$this->cache_output( $this->options['cache_hours'] );
		echo $this->output;

	}

} // END class

} // END class_exists