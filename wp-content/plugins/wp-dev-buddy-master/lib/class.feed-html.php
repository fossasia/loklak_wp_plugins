<?php

/**
* This class takes care of rendering chunks of the feed's HTML
*
* When using this class, it's important that it is
* set up with a tweet array using DB_Twitter_HTML::set()
* if you want any of it's methods to work
*
* @version 1.1.0
*/
if ( ! class_exists( 'DB_Twitter_HTML' ) ) {

class DB_Twitter_HTML {

	/**
	* @var array Used to hold an individual tweet's data
	* @since 1.0.0
	*/
	public $tweet;

	/**
	* @var array Holds the options for the feed instance
	* @since 1.0.0
	*/
	public $options;

	/**
	* @var string Main Twitter URL
	* @since 1.0.0
	*/
	public $tw;

	/**
	* @var string Twitter search URL
	* @since 1.0.0
	*/
	public $search;

	/**
	* @var string Twitter intent URL
	* @since 1.0.0
	*/
	public $intent;


	/**
	* Grab and assign data essential for the rendered output
	*
	* @access public
	* @return void
	*
	* @param $options  array The feed configuration for a feed instance
	* @param $url_data array The URL that will be necessary within the feed
	*/
	public function __construct( $options, $url_data ) {
		$this->options = $options;
		$this->tw      = $url_data['tw'];
		$this->search  = $url_data['search'];
		$this->intent  = $url_data['intent'];
	}


	/**
	* Sets the tweet data to be used while rendering
	*
	* @access public
	* @return void
	*
	* @param $tweet array The array of data to be used
	*/
	public function set( $tweet ) {
		$this->tweet = $tweet;
	}


	/**
	* Returns the opening element for a tweet
	*
	* @access public
	* @return string
	*/
	public function open_tweet() {
		$output = '<article class="tweet">';

		return $output;
	}


	/**
	* Returns the closing element for a tweet
	*
	* @access public
	* @return string
	*/
	public function close_tweet() {
		$output = '</article>';

		return $output;
	}


	/**
	* Returns the opening element for a tweet's content
	*
	* @access public
	* @return string
	*/
	public function open_tweet_content() {
		$output = '<div class="tweet_content">';

		return $output;
	}


	/**
	* Returns the closing element for a tweet's content
	*
	* @access public
	* @return string
	*/
	public function close_tweet_content() {
		$output = '</div>';

		return $output;
	}


	/**
	* Returns the opening element for a generic div used to wrap around a tweet's meta information
	*
	* @access public
	* @return string
	*/
	public function open_tweet_primary_meta() {
		$output = '<div class="tweet_primary_meta">';

		return $output;
	}


	/**
	* Returns the closing element for a generic div used to wrap around a tweet's meta information
	*
	* @access public
	* @return string
	*/
	public function close_tweet_primary_meta() {
		$output = '</div>';

		return $output;
	}


	/**
	* Returns the opening element for a generic div used to wrap around a tweet's meta information
	*
	* @access public
	* @return string
	*/
	public function open_tweet_secondary_meta() {
		$output = '<div class="tweet_secondary_meta">';

		return $output;
	}


	/**
	* Returns the closing element for a generic div used to wrap around a tweet's meta information
	*
	* @access public
	* @return string
	*/
	public function close_tweet_secondary_meta() {
		$output = '</div>';

		return $output;
	}


	/**
	* Returns the display name as a link along with the Twitter handle, which is left unlinked
	*
	* @access public
	* @return string
	*/
	public function tweet_display_name_link() {
		$output  = '<a href="' . $this->tw . $this->tweet['user_screen_name'] . '" target="_blank" class="tweet_user" title="' . $this->tweet['user_description'] . '">' . $this->tweet['user_display_name'] . '</a>';
		$output .= ' <span class="tweet_screen_name">@' . $this->tweet['user_screen_name'] . '</span>';

		return $output;
	}


	/**
	* Returns the display picture
	*
	* @access public
	* @return string
	*/
	public function tweet_display_pic() {
		$output  = '<figure class="tweet_profile_img">';
		$output .= '<a href="' . $this->tw . $this->tweet['user_screen_name'] . '" target="_blank" title="' . $this->tweet['user_display_name'] . '"><img src="' . $this->tweet_media_src( 'profile_img' ) . '" alt="' . $this->tweet['user_display_name'] . '" /></a>';
		$output .= '</figure>';

		return $output;
	}


	/**
	* Returns the tweet text
	*
	* @access public
	* @return string
	*/
	public function tweet_text() {
		$output = '<div class="tweet_text">' . $this->tweet['text'] . '</div>';

		return $output;
	}


	/**
	 * Returns the HTML for the media item associated with the tweet
	 *
	 * @access public
	 * @return string
	 */
	public function tweet_media() {
		$output = '';

		if ( isset( $this->tweet['media'] ) ) {
			$output .= '<div class="tweet_media">';

			switch ( $this->tweet['media'][0]['type'] ) {
				case 'photo':
					if ( isset( $this->options['show_images'] ) && $this->options['show_images'] === 'yes' ) {
						$output .= '<a href="' . $this->tweet['media'][0]['expanded_url'] . '" target="_blank">';
						$output .= '<img src="' . $this->tweet_media_src( 'embedded_img' ) . '" alt="" />';
						$output .= '</a>';
					}
					break;

				default:
					$output .= '';
					break;
			}

			$output .= '</div>';
		}

		return $output;
	}


	/**
	* Returns the date at which the tweet was posted as a hyperlink to the post itself
	*
	* @access public
	* @return string
	*/
	public function tweet_date() {
		$output = '<a href="' . $this->url_to_tweet().'" target="_blank" title="' . __( 'View this tweet in Twitter', 'devbuddy-twitter-feed' ) . '" class="tweet_date">' . $this->tweet['date'] . '</a>';

		return $output;
	}


	/**
	* Returns the retweet information should the tweet indeed be a retweet
	*
	* @access public
	* @return string
	*/
	public function tweet_retweeted() {
		$output = '';

		if ( $this->tweet['is_retweet'] ) {
			$output .= '<span class="tweet_retweet">';
			$output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
			$output .= __( 'Retweeted by ', 'devbuddy-twitter-feed' );
			$output .= '<a href="' . $this->tw . $this->tweet['retweeter_screen_name'] . '" target="_blank" title="' . $this->tweet['retweeter_display_name'] . '">' . $this->tweet['retweeter_display_name'] . '</a>';
			$output .= '</span>';
		}

		return $output;
	}


	/**
	* Returns actions that end users can use to interact with the tweet: reply, retweet, and favourite
	*
	* @access public
	* @return string
	*/
	public function tweet_intents() {
		$output  = '<div class="tweet_intents">';

		// START Reply intent
		$output .= '<a href="' . $this->intent.'tweet?in_reply_to=' . $this->tweet['id'] . '" title="' . __( 'Reply to this tweet', 'devbuddy-twitter-feed' ) . '" target="_blank" class="tweet_intent tweet_intent_reply">';
		$output .= '<span class="tweet_icon tweet_icon_reply"></span>';
		$output .= '<b class="tweet_intent_txt">' . __( 'Reply', 'devbuddy-twitter-feed' ) . '</b></a>';
		// END Reply intent

		// START Retweet intent
		$output .= '<a href="' . $this->intent.'retweet?tweet_id=' . $this->tweet['id'] . '" title="' . __( 'Retweet this tweet', 'devbuddy-twitter-feed' ) . '" target="_blank" class="tweet_intent tweet_intent_retweet">';
		$output .= '<span class="tweet_icon tweet_icon_retweet"></span>';
		$output .= '<b class="tweet_intent_txt">' . __( 'Retweet', 'devbuddy-twitter-feed' ) . '</b></a>';
		// END Retweet intent

		// START Favourite intent
		$output .= '<a href="' . $this->intent.'favorite?tweet_id=' . $this->tweet['id'] . '" title="' . __( 'Favourite this tweet', 'devbuddy-twitter-feed' ) . '" target="_blank" class="tweet_intent tweet_intent_favourite">';
		$output .= '<span class="tweet_icon tweet_icon_favourite"></span>';
		$output .= '<b class="tweet_intent_txt">' . __( 'Favourite', 'devbuddy-twitter-feed' ) . '</b></a>';
		// END Favourite intent

		$output .= '</div>';
		// END Tweet intents

		return $output;
	}


	/**
	* Returns the URL to the tweet in Twitter
	*
	* @access public
	* @return string
	*/
	public function url_to_tweet() {
		$output = $this->tw . $this->tweet['user_screen_name'] . '/status/' . $this->tweet['id'];

		return $output;
	}


	/**
	 * Returns the URL for the media item given. Checks for HTTPS
	 *
	 * @access public
	 * @return string
	 */
	public function tweet_media_src( $media_item ) {
		$media_item = strtolower($media_item);

		$media_src = '';
		$https     = '';

		if ( isset( $this->options['https'] ) && $this->options['https'] === 'yes' ) {
			$https = '_https';
		}

		switch ( $media_item ) {
			case 'profile_img':
				$media_src = $this->tweet['profile_img_url' . $https];
				break;

			case 'embedded_img':
				$media_src = $this->tweet['media'][0]['media_url' . $https];
				break;
		}

		return $media_src;
	}

} // END class

} // END class_exists