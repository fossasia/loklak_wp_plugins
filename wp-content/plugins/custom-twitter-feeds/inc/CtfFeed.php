<?php
/**
 * Class CtfFeed
 *
 * Creates the settings for the feed and outputs the html
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfFeed
{
    /**
     * @var array
     */
    public $errors = array();

    /**
     * @var array
     */
    protected $atts;

    /**
     * @var string
     */
    protected $last_id_data;

    private $num_needed_input;

    /**
     * @var mixed|void
     */
    protected $db_options;

    /**
     * @var array
     */
    public $feed_options = array();

    /**
     * @var mixed|void
     */
    public $missing_credentials;

    /**
     * @var string
     */
    public $transient_name;

    /**
     * @var bool
     */
    protected $transient_data = false;

    /**
     * @var int
     */
    private $num_tweets_needed;

    /**
     * @var array
     */
    public $tweet_set;

    /**
     * @var object
     */
    public $api_obj;

    /**
     * @var string
     */
    public $feed_html;

    /**
     * retrieves and sets options that apply to the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     */
    public function __construct( $atts, $last_id_data, $num_needed_input )
    {
        $this->atts = $atts;
        $this->last_id_data = $last_id_data;
        $this->num_needed_input = $num_needed_input;
        $this->db_options = get_option( 'ctf_options', array() );
    }

    /**
     * creates and returns all of the data needed to generate the output for the feed
     *
     * @param array $atts           data from the shortcode
     * @param string $last_id_data  the last visible tweet on the feed, empty string if first set
     * @param int $num_needed_input this number represents the number left to retrieve after the first set
     * @return CtfFeed                 the complete object for the feed
     */
    public static function init( $atts, $last_id_data = '', $num_needed_input = 0  )
    {
        $feed = new CtfFeed( $atts, $last_id_data, $num_needed_input );
        $feed->setFeedOptions();
        $feed->setTweetSet();
        return $feed;
    }

    /**
     * creates all of the feed options with shortcode settings having the highest priority
     */
    protected function setFeedOptions()
    {
        $this->setFeedTypeAndTermOptions();

        $bool_false = array (
            'have_own_tokens',
            'includereplies',
            'ajax_theme',
            'width_mobile_no_fixed',
            'disablelinks',
            'linktexttotwitter',
            'creditctf',
            'showheader'
        );
        $this->setStandardBoolOptions( $bool_false, false );

        $this->setAccessTokenAndSecretOptions();
        $this->setConsumerKeyAndSecretOptions();

        $db_only =  array(
            'request_method'
        );
        $this->setDatabaseOnlyOptions( $db_only );

        $this->setStandardTextOptions( 'num', 5 );

        $standard_text = array(
            'class',
            'headertext',
            'dateformat',
            'datecustom',
            'mtime',
            'htime',
            'nowtime'
        );
        $this->setStandardTextOptions( $standard_text, '' );

        $this->setStandardTextOptions( 'retweetedtext', 'Retweeted' );

        $this->setStandardTextOptions( 'multiplier', 1.25 );
        $this->setStandardTextOptions( 'twitterlinktext', 'Twitter' );

        $this->setStandardTextOptions( 'buttontext', 'Load More...' );

        $text_size = array(
            'authortextsize',
            'tweettextsize',
            'datetextsize',
            'quotedauthorsize',
            'iconsize'
        );
        $this->setTextSizeOptions( $text_size );

        $text_weight = array(
            'authortextweight',
            'tweettextweight',
            'datetextweight',
            'quotedauthorweight'
        );
        $this->setStandardStyleProperty( $text_weight, 'font-weight' );

        $text_color = array(
            'headertextcolor',
            'textcolor',
            'linktextcolor',
            'iconcolor',
            'buttontextcolor'
        );
        $this->setStandardStyleProperty( $text_color, 'color' );

        $bg_color = array(
            'bgcolor',
            'tweetbgcolor',
            'headerbgcolor',
            'buttoncolor'
        );
        $this->setStandardStyleProperty( $bg_color, 'background-color' );

        $bool_true = array(
            'showbutton',
            'showbio'
        );
        $this->setStandardBoolOptions( $bool_true, true );

        $this->setDimensionOptions();
        $this->setCacheTimeOptions();
        $this->setIncludeExcludeOptions();
    }

    /**
     * uses the feed options to set the the tweets in the feed by using
     * an existing set in a cache or by retrieving them from Twitter
     */
    protected function setTweetSet()
    {
        $this->setTransientName();
        $success = $this->maybeSetTweetsFromCache();

        if ( ! $success ) {
            $this->maybeSetTweetsFromTwitter();
        }

        $this->num_tweets_needed = $this->numTweetsNeeded();
    }

    /**
     * the access token and secret must be set in order for the feed to work
     * this function processes the user input and sets a flag if none are entered
     */
    private function setAccessTokenAndSecretOptions()
    {
        $this->feed_options['access_token'] = isset( $this->db_options['access_token'] ) && strlen( $this->db_options['access_token'] ) > 30 ? $this->db_options['access_token'] : 'missing';
        $this->feed_options['access_token_secret'] = isset( $this->db_options['access_token_secret'] ) && strlen( $this->db_options['access_token_secret'] ) > 30 ? $this->db_options['access_token_secret'] : 'missing';

        // verify that access token and secret have been entered
        $this->setMissingCredentials();
    }

    /**
     * generates the flag if there are missing access tokens
     */
    private function setMissingCredentials() {
        if ( $this->feed_options['access_token'] == 'missing' || $this->feed_options['access_token_secret'] == 'missing' ) {
            $this->missing_credentials = true;
        } else {
            $this->missing_credentials = false;
        }
    }

    /**
     * processes the consumer key and secret options
     */
    protected function setConsumerKeyAndSecretOptions()
    {
        if ( $this->feed_options['have_own_tokens'] ) {
            $this->feed_options['consumer_key'] = isset( $this->db_options['consumer_key'] ) && strlen( $this->db_options['consumer_key'] ) > 15 ? $this->db_options['consumer_key'] : 'FPYSYWIdyUIQ76Yz5hdYo5r7y';
            $this->feed_options['consumer_secret'] = isset( $this->db_options['consumer_secret'] ) && strlen( $this->db_options['consumer_secret'] ) > 30 ? $this->db_options['consumer_secret'] : 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
        } else {
            $this->feed_options['consumer_key'] ='FPYSYWIdyUIQ76Yz5hdYo5r7y';
            $this->feed_options['consumer_secret'] = 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param $options_page string  options page this setting is set on
     * @param string $default       default value to use if there is no user input
     */
    public function setDatabaseOnlyOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->db_options[$option] ) && ! empty( $this->db_options[$option] ) ? $this->db_options[$option] : $default;
            }
        } else {
            $this->feed_options[$options] = isset( $this->db_options[$options] ) && ! empty( $this->db_options[$options] ) ? $this->db_options[$options] : $default;
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param $options_page string  options page this setting is set on
     * @param string $default       default value to use if there is no user input
     */
    public function setStandardTextOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? esc_attr( $this->atts[$option] ) : ( isset( $this->db_options[$option] ) ?  esc_attr( $this->db_options[$option] )  : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? esc_attr( $this->atts[$options] ) : ( isset( $this->db_options[$options] ) ?  esc_attr( $this->db_options[$options] )  : $default );
        }
    }

    /**
     * creates the appropriate style attribute string for the text size setting
     *
     * @param $value mixed  pixel size or other that the user has selected
     * @return string       string for the style attribute
     */
    public static function processTextSizeStyle( $value )
    {
        if ( $value == '' ) {
            return '';
        }
        $processed_value = $value == 'inherit' ? '' : 'font-size: ' . $value . 'px;';

        return $processed_value;
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param string $default       default value to use if there is no user input
     */
    public function setTextSizeOptions( $options, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? $this->processTextSizeStyle( esc_attr( $this->atts[$option] ) ) : ( isset( $this->db_options[$option] ) ? $this->processTextSizeStyle( esc_attr( $this->db_options[$option] ) ) : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? $this->processTextSizeStyle( esc_attr( $this->atts[$options] ) ) : ( isset( $this->db_options[$options] ) ? $this->processTextSizeStyle( esc_attr( $this->db_options[$options] ) ) : $default );
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed    the key or array of keys to be set
     * @param $property string  name of the property to be set
     * @param string $default   default value to use if there is no user input
     */
    public function setStandardStyleProperty( $options, $property, $default = '' )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) && $this->atts[$option] != 'inherit' ? $property . ': ' . esc_attr( $this->atts[$option] ) . ';'  : ( isset( $this->db_options[$option] ) && $this->db_options[$option] != '#' && $this->db_options[$option] != '' && $this->db_options[$option] != 'inherit' ? $property . ': ' . esc_attr( $this->db_options[$option] ) . ';' : $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) && $this->atts[$options] != 'inherit' ? $property . ': ' . esc_attr( $this->atts[$options] ) . ';'  : ( isset( $this->db_options[$options] ) && $this->db_options[$options] != '#' && $this->db_options[$options] != '' && $this->db_options[$options] != 'inherit' ? $property . ': ' . esc_attr( $this->db_options[$options] ) . ';' : $default );
        }
    }

    /**
     * determines what value to use and saves it for the appropriate key in the feed_options array
     *
     * @param $options mixed        the key or array of keys to be set
     * @param bool|true $default    default value to use if there is no user input
     */
    public function setStandardBoolOptions( $options, $default = true )
    {
        if ( is_array( $options ) ) {
            foreach ( $options as $option ) {
                $this->feed_options[$option] = isset( $this->atts[$option] ) ? ( $this->atts[$option] === 'true'  ) : ( isset( $this->db_options[$option] ) ? (bool) $this->db_options[$option] : (bool) $default );
            }
        } else {
            $this->feed_options[$options] = isset( $this->atts[$options] ) ? esc_attr( $this->atts[$options] ) : ( isset( $this->db_options[$options] ) ?  esc_attr( $this->db_options[$options] )  : $default );
        }
    }

    /**
     * sets the width and height of the feed based on user input
     */
    public function setDimensionOptions()
    {
        $this->feed_options['width'] = isset( $this->atts['width'] ) ? 'width: '. esc_attr( $this->atts['width'] ) .';' : ( ( isset( $this->db_options['width'] ) && $this->db_options['width'] != '' ) ? 'width: '. esc_attr( $this->db_options['width'] ) . ( isset( $this->db_options['width_unit'] ) ? esc_attr( $this->db_options['width_unit'] ) : '%' ) . ';' : '' );
        $this->feed_options['height'] = isset( $this->atts['height'] ) ? 'height: '. esc_attr( $this->atts['height'] ) .';' : ( ( isset( $this->db_options['height'] ) && $this->db_options['height'] != '' ) ? 'height: '. esc_attr( $this->db_options['height'] ) . ( isset( $this->db_options['height_unit'] ) ? esc_attr( $this->db_options['height_unit'] ) : 'px' ) . ';' : '' );
    }

    /**
     * sets the cache time based on user input
     */
    public function setCacheTimeOptions()
    {
        $user_cache = isset( $this->db_options['cache_time'] ) ? ( $this->db_options['cache_time'] * $this->db_options['cache_time_unit'] ) : HOUR_IN_SECONDS;
        $this->feed_options['cache_time'] = max( $user_cache, 1);
    }


    /**
     * sets the number of tweets to retrieve
     */
    public function setTweetsToRetrieve()
    {
        $min_tweets_to_retrieve = 10;

        if ( $this->num_needed_input < 1 ) {
            if ( $this->feed_options['includereplies'] ) {
                $this->feed_options['count'] = $this->feed_options['num'];
            } else {
                if ( $this->feed_options['num'] < 10 ) {
                    $this->feed_options['count'] = max( round( $this->feed_options['num'] * $this->feed_options['multiplier'] * 1.6 ), $min_tweets_to_retrieve );
                } elseif ( $this->feed_options['num'] < 30 ) {
                    $this->feed_options['count'] = round( $this->feed_options['num'] * $this->feed_options['multiplier'] * 1.2 );
                } else {
                    $this->feed_options['count'] = round( $this->feed_options['num'] * $this->feed_options['multiplier'] );
                }
            }
        } else {
            $this->feed_options['count'] = max( $this->num_needed_input, 50 );
            $this->feed_options['num'] = $this->num_needed_input;
        }

    }

    /**
     * sets the feed type and associated parameter
     */
    public function setFeedTypeAndTermOptions()
    {
        $this->feed_options['type'] = '';
        $this->feed_options['feed_term'] = '';
        $this->feed_options['screenname'] = isset( $this->db_options['usertimeline_text'] ) ? $this->db_options['usertimeline_text'] : '';

        if ( isset( $this->atts['home'] ) && $this->atts['home'] == 'true' ) {
            $this->feed_options['type'] = 'hometimeline';
        }
        if ( isset( $this->atts['screenname'] ) ) {
            $this->feed_options['type'] = 'usertimeline';
            $this->feed_options['feed_term'] = isset( $this->atts['screenname'] ) ? ctf_validate_usertimeline_text( $this->atts['screenname'] ) : ( ( isset( $this->db_options['usertimeline_text'] ) ) ? $this->db_options['usertimeline_text'] : '' );
            $this->feed_options['screenname'] = $this->feed_options['feed_term'];
        }
        if ( isset( $this->atts['search'] ) || isset( $this->atts['hashtag'] ) ) {
            $this->feed_options['type'] = 'search';
            $this->working_term = isset( $this->atts['hashtag'] ) ? $this->atts['hashtag'] : ( isset( $this->atts['search'] ) ? $this->atts['search'] : '' );
            $this->feed_options['feed_term'] = isset( $this->working_term ) ? ctf_validate_search_text( $this->working_term ) : ( ( isset( $this->db_options['search_text'] ) ) ? $this->db_options['search_text'] : '' );
        }

        if ( $this->feed_options['type'] == '' ) {
            $this->feed_options['type'] = isset( $this->db_options['type'] ) ? $this->db_options['type'] : 'usertimeline';
            switch ( $this->feed_options['type'] ) {
                case 'usertimeline':
                    $this->feed_options['feed_term'] = isset( $this->db_options['usertimeline_text'] ) ? $this->db_options['usertimeline_text'] : '';
                    break;
                case 'hometimeline':
                    $this->feed_options['type'] = 'hometimeline';
                    break;
                case 'search':
                    $this->feed_options['feed_term'] = isset( $this->db_options['search_text'] ) ? $this->db_options['search_text'] : '';
                    break;
            }
        }
    }

    /**
     * sets the visible parts of each tweet for the feed
     */
    public function setIncludeExcludeOptions()
    {
        $this->feed_options['tweet_includes'] = array();
        $this->feed_options['tweet_excludes'] = array();
        $this->feed_options['tweet_includes'] = isset( $this->atts['include'] ) ? explode( ',', str_replace( ', ', ',', esc_attr( $this->atts['include'] ) ) ) : '';
        if ( $this->feed_options['tweet_includes'] == '' ) {
            $this->feed_options['tweet_excludes'] = isset( $this->atts['exclude'] ) ? explode( ',', str_replace( ', ', ',', esc_attr( $this->atts['exclude'] ) ) ) : '';
        }
        if ( $this->feed_options['tweet_excludes'] == '' ) {
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_retweeter'] ) && $this->db_options['include_retweeter'] == false ? null : 'retweeter';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_avatar'] ) && $this->db_options['include_avatar'] == false ? null : 'avatar';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_author'] ) && $this->db_options['include_author'] == false ? null : 'author';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_text'] ) && $this->db_options['include_text'] == false ? null : 'text';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_date'] ) && $this->db_options['include_date'] == false ? null : 'date';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_actions'] ) && $this->db_options['include_actions'] == false ? null : 'actions';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_twitterlink'] ) && $this->db_options['include_twitterlink'] == false ? null : 'twitterlink';
            $this->feed_options['tweet_includes'][] = isset( $this->db_options['include_linkbox'] ) && $this->db_options['include_linkbox'] == false ? null : 'linkbox';
        }
    }

    /**
     * sets the transient name for the caching system
     */
    public function setTransientName()
    {
        $last_id_data = $this->last_id_data;
        $num = isset( $this->feed_options['num'] ) ? $this->feed_options['num'] : '';

        switch ( $this->feed_options['type'] ) {
            case 'hometimeline' :
                $this->transient_name = 'ctf_' . $last_id_data . 'hometimeline'. $num;
                break;
            case 'usertimeline' :
                $screenname = isset( $this->feed_options['feed_term'] ) ? $this->feed_options['feed_term'] : '';
                $this->transient_name = substr( 'ctf__' . $last_id_data . $screenname . $num, 0, 45 );
                break;
            case 'search' :
                $hashtag = isset( $this->feed_options['feed_term'] ) ? $this->feed_options['feed_term'] : '';
                $this->transient_name = substr( 'ctf_' . $last_id_data . $hashtag . $num, 0, 45 );
                break;
        }
    }

    /**
     * checks the data available in the cache to make sure it seems to be valid
     *
     * @return bool|string  false if the cache is valid, error otherwise
     */
    private function validateCache()
    {
        if ( isset( $this->transient_data[0] ) ) {
            return false;
        } else {
            return 'invalid cache';
        }
    }

    /**
     * will use the cached data in the feed if data seems to be valid and user
     * wants to use caching
     *
     * @return bool|mixed   false if none is set, tweet set otherwise
     */
    public function maybeSetTweetsFromCache()
    {
        $this->transient_data = get_transient( $this->transient_name );

        if ( $this->feed_options['cache_time'] <= 0 ) {
            return $this->tweet_set = false;
        }
        // validate the transient data
        if ( $this->transient_data ) {
            $this->errors['cache_status'] = $this->validateCache();
            if ( $this->errors['cache_status'] === false ) {
                return $this->tweet_set = $this->transient_data;
            } else {
                return $this->tweet_set = false;
            }
        } else {
            $this->errors['cache_status'] = 'none found';
            return $this->tweet_set = false;
        }
    }

    /**
     *  will attempt to connect to the api to retrieve current tweets
     */
    public function maybeSetTweetsFromTwitter()
    {
        $this->setTweetsToRetrieve();
        $this->api_obj = $this->apiConnect( $this->feed_options['type'], $this->feed_options['feed_term'] );
        $this->tweet_set = json_decode( $this->api_obj->json , $assoc = true );

        // check for errors/tweets present
        if ( isset( $this->tweet_set['errors'][0] ) ) {
            $this->api_obj->api_error_no = $this->tweet_set['errors'][0]['code'];
            $this->api_obj->api_error_message = $this->tweet_set['errors'][0]['message'];
            $this->tweet_set = false;
        }

        $tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;

        if ( empty( $tweets ) ) {
            $this->errors['error_message'] = 'No Tweets returned';
            $this->tweet_set = false;
        }
    }


    /**
     * calculates how many tweets short the feed is so more can be retrieved via ajax
     *
     * @return int number of tweets needed
     */
    protected function numTweetsNeeded() {
        $tweet_count = isset( $this->tweet_set['statuses'] ) ? count( $this->tweet_set['statuses'] ) : count( $this->tweet_set );

        return $this->feed_options['num'] - $tweet_count;
    }

    /**
     * trims the unused data retrieved for more efficient caching
     */
    protected function trimTweetData()
    {
        $is_pagination = !empty( $this->last_id_data ) ? 1 : 0;
        $tweets = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $len = min( $this->feed_options['num'] + $is_pagination, count( $tweets ) );
        $trimmed_tweets = array();

        // for header
        if ( $this->last_id_data == '' ) { // if this is the first set of tweets
            $trimmed_tweets[0]['user']['name']= $tweets[0]['user']['name'];
            $trimmed_tweets[0]['user']['description']= $tweets[0]['user']['description'];
            $trimmed_tweets[0]['user']['statuses_count']= $tweets[0]['user']['statuses_count'];
            $trimmed_tweets[0]['user']['followers_count']= $tweets[0]['user']['followers_count'];
        }

        for ( $i = 0; $i < $len; $i++ ) {
            $trimmed_tweets[$i]['user']['name'] = $tweets[$i]['user']['name'];
            $trimmed_tweets[$i]['user']['screen_name'] = $tweets[$i]['user']['screen_name'];
            $trimmed_tweets[$i]['user']['verified'] = $tweets[$i]['user']['verified'];
            $trimmed_tweets[$i]['user']['profile_image_url_https'] = $tweets[$i]['user']['profile_image_url_https'];
            $trimmed_tweets[$i]['user']['utc_offset']= $tweets[$i]['user']['utc_offset'];
            $trimmed_tweets[$i]['text']= $tweets[$i]['text'];
            $trimmed_tweets[$i]['id_str']= $tweets[$i]['id_str'];
            $trimmed_tweets[$i]['created_at']= $tweets[$i]['created_at'];
            $trimmed_tweets[$i]['retweet_count']= $tweets[$i]['retweet_count'];
            $trimmed_tweets[$i]['favorite_count']= $tweets[$i]['favorite_count'];

            if ( isset( $tweets[$i]['retweeted_status'] ) ) {
                $trimmed_tweets[$i]['retweeted_status']['user']['name'] = $tweets[$i]['retweeted_status']['user']['name'];
                $trimmed_tweets[$i]['retweeted_status']['user']['screen_name'] = $tweets[$i]['retweeted_status']['user']['screen_name'];
                $trimmed_tweets[$i]['retweeted_status']['user']['verified'] = $tweets[$i]['retweeted_status']['user']['verified'];
                $trimmed_tweets[$i]['retweeted_status']['user']['profile_image_url_https'] = $tweets[$i]['retweeted_status']['user']['profile_image_url_https'];
                $trimmed_tweets[$i]['retweeted_status']['user']['utc_offset']= $tweets[$i]['retweeted_status']['user']['utc_offset'];
                $trimmed_tweets[$i]['retweeted_status']['text'] = $tweets[$i]['retweeted_status']['text'];
                $trimmed_tweets[$i]['retweeted_status']['id_str'] = $tweets[$i]['retweeted_status']['id_str'];
                $trimmed_tweets[$i]['retweeted_status']['created_at']= $tweets[$i]['retweeted_status']['created_at'];
                $trimmed_tweets[$i]['retweeted_status']['retweet_count']= $tweets[$i]['retweeted_status']['retweet_count'];
                $trimmed_tweets[$i]['retweeted_status']['favorite_count']= $tweets[$i]['retweeted_status']['favorite_count'];
            }

            if ( isset( $tweets[$i]['quoted_status'] ) ) {
                $trimmed_tweets[$i]['quoted_status']['user']['name'] = $tweets[$i]['quoted_status']['user']['name'];
                $trimmed_tweets[$i]['quoted_status']['user']['screen_name'] = $tweets[$i]['quoted_status']['user']['screen_name'];
                $trimmed_tweets[$i]['quoted_status']['user']['verified'] = $tweets[$i]['quoted_status']['user']['verified'];
                $trimmed_tweets[$i]['quoted_status']['text'] = $tweets[$i]['quoted_status']['text'];
                $trimmed_tweets[$i]['quoted_status']['id_str'] = $tweets[$i]['quoted_status']['id_str'];
            }

            $trimmed_tweets[$i] = $this->filterTrimmedTweets( $trimmed_tweets[$i], $tweets[$i] );
        }

        $this->tweet_set = $trimmed_tweets;
    }

    /**
     * method to be overridden by pro
     *
     * @param $trimmed current trimmed tweet araray
     * @param $tweet current tweet data to be trimmed
     * @return mixed final trimmed tweet
     */
    protected function filterTrimmedTweets( $trimmed, $tweet )
    {
        return $trimmed;
    }

    /**
     * will create a transient with the tweet cache if one doesn't exist, the data seems valid, and caching is active
     */
    public function maybeCacheTweets()
    {
        if ( ( ! $this->transient_data || $this->errors['cache_status'] ) && $this->feed_options['cache_time'] > 0 ) {
            $this->trimTweetData();
            set_transient( $this->transient_name, $this->tweet_set, $this->feed_options['cache_time'] );
        }
    }

    /**
     * returns a JSON string to be used in the data attribute that contains the shortcode data
     */
    public function getShortCodeJSON()
    {
        $json_data = '{';
        $i = 0;
        $len = count( $this->atts );

        if ( ! empty( $this->atts ) ) {
            foreach ( $this->atts as $key => $value) {
                if ( $i == $len - 1 ) {
                    $json_data .= '&quot;' . $key . '&quot;: &quot;' . $value . '&quot;';
                } else {
                    $json_data .= '&quot;' . $key . '&quot;: &quot;' . $value . '&quot;, ';
                }
                $i++;
            }
        }

        $json_data .= '}';

        return $json_data;
    }

    /**
     * uses the endpoint to determing what get fields need to be set
     *
     * @param $end_point api endpoint needed
     * @param $feed_term term associated with the endpoint, user name or search term
     * @return array the get fields for the request
     */
    protected function setGetFieldsArray( $end_point, $feed_term )
    {
        $get_fields = array();
        if ( $end_point === 'usertimeline' ) {
            if ( ! empty ( $feed_term ) ) {
                $get_fields['screen_name'] = $feed_term;
            }
            $get_fields['exclude_replies'] = 'true';
        }
        if ( $end_point === 'hometimeline' ) {
            $get_fields['exclude_replies'] = 'true';
        }
        if ( $end_point === 'search' ) {
            $get_fields['q'] = $feed_term;
        }

        return $get_fields;
    }

    /**
     * attempts to connect and retrieve tweets from the Twitter api
     *
     * @return mixed|string object containing the response
     */
    public function apiConnect( $end_point, $feed_term )
    {
        // Only can be set in the options page
        $request_settings = array(
            'consumer_key' => $this->feed_options['consumer_key'],
            'consumer_secret' => $this->feed_options['consumer_secret'],
            'access_token' => $this->feed_options['access_token'],
            'access_token_secret' => $this->feed_options['access_token_secret'],
        );

        // For pagination, an extra post needs to be retrieved since the last post is
        // included in the next set
        $count = $this->feed_options['count'];

        $get_fields = $this->setGetFieldsArray( $end_point, $feed_term );

        // if the last id is present, that means this is not the first set of tweets
        // retrieve only tweets made after the last tweet using it's id
        if ( ! empty( $this->last_id_data ) ) {
            $count++;
            $max_id = $this->last_id_data;
        }
        $get_fields['count'] = $count;

        // max_id parameter should only be included for the second set of posts
        if ( isset( $max_id ) ) {
            $get_fields['max_id'] = $max_id;
        }

        include_once( CTF_URL . '/inc/CtfOauthConnect.php' );

        // actual connection
        $twitter_connect = new CtfOauthConnect( $request_settings, $end_point );
        $twitter_connect->setUrlBase();
        $twitter_connect->setGetFields( $get_fields );
        $twitter_connect->setRequestMethod( $this->feed_options['request_method'] );

        return $twitter_connect->performRequest();
    }

    /**
     * If the feed runs out of tweets to display for some reason,
     * this function creates a graceful failure message
     *
     * @param $feed_options
     * @return string html for "out of tweets" message
     */
    protected function getOutOfTweetsHtml( $feed_options )
    {
        $html = '';

        $html .= '<div class="ctf-out-of-tweets">';
        $html .= '<p>That\'s all! No more Tweets to load</p>';
        $html .= '<p>';
        $html .= '<a class="twitter-share-button" href="https://twitter.com/share" target="_blank" data-size="large" data-url="<?php echo get_home_url(); ?>">Share</a>';
        if ( isset( $feed_options['screenname'] ) ) {
            $html .= '<a class="twitter-follow-button" href="https://twitter.com/' . $feed_options['screenname'] . '" target="_blank" data-show-count="false" data-size="large" data-dnt="true">Follow</a>';
        }
        $html .= '</p>';
        $html .= "<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>";
        $html .= '</div>';

        return $html;
    }

    /**
     * creates opening html for the feed
     *
     * @return string opening html that creates the feed
     */
    public function getFeedOpeningHtml()
    {
        $feed_options = $this->feed_options;
        $ctf_data_disablelinks = ($feed_options['disablelinks'] == 'true') ? ' data-ctfdisablelinks="true"' : '';
        $ctf_data_linktextcolor = $feed_options['linktextcolor'] != '' ? ' data-ctflinktextcolor="'.$feed_options['linktextcolor'].'"' : '';
        $ctf_data_needed = $this->num_tweets_needed;
        $ctf_feed_type = ! empty ( $feed_options['type'] ) ? esc_attr( $feed_options['type'] ) : 'multiple';
        $ctf_feed_classes = 'ctf ctf-type-' . $ctf_feed_type;
        $ctf_feed_classes .= ' ' . $feed_options['class'] . ' ctf-styles';
        $ctf_feed_classes .= $feed_options['width_mobile_no_fixed'] ? ' ctf-width-resp' : '';
        $ctf_feed_classes = apply_filters( 'ctf_feed_classes', $ctf_feed_classes ); //add_filter( 'ctf_feed_classes', function( $ctf_feed_classes ) { return $ctf_feed_classes . ' new-class'; }, 10, 1 );
        $ctf_feed_html = '';

        $ctf_feed_html .= '<!-- Custom Twitter Feeds by Smash Balloon -->';
        $ctf_feed_html .= '<div id="ctf" class="' . $ctf_feed_classes . '" style="' . $feed_options['width'] . $feed_options['height'] . $feed_options['bgcolor'] . '" data-ctfshortcode="' . $this->getShortCodeJSON() . '"' .$ctf_data_disablelinks . $ctf_data_linktextcolor . ' data-ctfneeded="'. $ctf_data_needed .'">';
        $tweet_set = $this->tweet_set;

        // dynamically include header
        if ( $feed_options['showheader'] ) {
            $ctf_feed_html .= $this->getFeedHeaderHtml( $tweet_set, $this->feed_options );
        }

        $ctf_feed_html .= '<div class="ctf-tweets">';

        return $ctf_feed_html;
    }

    /**
     * creates opening html for the feed
     *
     * @return string opening html that creates the feed
     */
    public function getFeedClosingHtml()
    {
        $feed_options = $this->feed_options;
        $ctf_feed_html = '';

        $ctf_feed_html .= '</div>'; // closing div for ctf-tweets

        if ( $feed_options['showbutton'] ) {
            $ctf_feed_html .= '<a href="javascript:void(0);" id="ctf-more" class="ctf-more" style="' . $feed_options['buttoncolor'] . $feed_options['buttontextcolor'] . '"><span>' . $feed_options['buttontext'] . '</span></a>';
        }

        if ( $feed_options['creditctf'] ) {
            $ctf_feed_html .= '<div class="ctf-credit-link"><a href="https://smashballoon.com/custom-twitter-feeds" target="_blank"><i class="fa fa-twitter" aria-hidden="true"></i>Custom Twitter Feeds Plugin</a></div>';
        }

        $ctf_feed_html .= '</div>'; // closing div tag for #ctf

        if ( $feed_options['ajax_theme'] ) {
            $ctf_feed_html .= '<script type="text/javascript" src="' . CTF_JS_URL . '"></script>';
        }

        return $ctf_feed_html;
    }

    /**
     * creates html for header of the feed
     *
     * @param $tweet_set string     trimmed tweets to be added to the feed
     * @param $feed_options         options for the feed
     * @return string html that creates the header of the feed
     */
    protected function getFeedHeaderHtml( $tweet_set, $feed_options )
    {
        $ctf_header_html = '';
        $ctf_no_bio = $feed_options['showbio'] ? '' : ' ctf-no-bio';

        // temporary workaround for cached http images
        $tweet_set[0]['user']['profile_image_url_https'] = isset( $tweet_set[0]['user']['profile_image_url_https'] ) ? $tweet_set[0]['user']['profile_image_url_https'] : $tweet_set[0]['user']['profile_image_url'];


        if ( $feed_options['type'] === 'usertimeline' ) {
            $ctf_header_html .= '<div class="ctf-header' . $ctf_no_bio . '" style="' . $feed_options['headerbgcolor'] . '">';
            $ctf_header_html .= '<a href="http://twitter.com/' . $tweet_set[0]['user']['screen_name'] . '" target="_blank" title="@' . $tweet_set[0]['user']['screen_name'] . '" class="ctf-header-link">';
            $ctf_header_html .= '<div class="ctf-header-text">';
            $ctf_header_html .= '<p class="ctf-header-user" style="' . $feed_options['headertextcolor'] . '">';
            $ctf_header_html .= '<span class="ctf-header-name">';

            if ( $feed_options['headertext'] != '' ) {
                $ctf_header_html .= esc_html( $feed_options['headertext'] );
            } else {
                $ctf_header_html .= esc_html( $tweet_set[0]['user']['name'] );
            }

            $ctf_header_html .= '</span>';

            if ( $tweet_set[0]['user']['verified'] == 1 ) {
                $ctf_header_html .= '<span class="ctf-verified"><i class="fa fa-check-circle"></i></span>';
            }

            $ctf_header_html .= '<span class="ctf-header-follow"><i class="fa fa-twitter" aria-hidden="true"></i>Follow</span>';
            $ctf_header_html .= '</p>';

            if ( $feed_options['showbio'] ) {
                $ctf_header_html .= '<p class="ctf-header-bio" style="' . $feed_options['headertextcolor'] . '">' . $tweet_set[0]['user']['description'] . '</p>';
            }

            $ctf_header_html .= '</div>';
            $ctf_header_html .= '<div class="ctf-header-img">';
            $ctf_header_html .= '<div class="ctf-header-img-hover"><i class="fa fa-twitter"></i></div>';
            $ctf_header_html .= '<img src="' . $tweet_set[0]['user']['profile_image_url_https'] . '" alt="' . $tweet_set[0]['user']['name'] . '" width="48" height="48">';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</a>';
            $ctf_header_html .= '</div>';
        } else {

            if ( $feed_options['type'] === 'search' ) {
                $default_header_text = $feed_options['headertext'] != '' ? esc_html($feed_options['headertext']) : $feed_options['feed_term'];
                $url_part = 'hashtag/' . str_replace("#", "", $feed_options['feed_term']);
            } else {
                $default_header_text = 'Twitter';
                $url_part = $feed_options['screenname']; //Need to get screenname here
            }

            $ctf_header_html .= '<div class="ctf-header ctf-header-type-generic" style="' . $feed_options['headerbgcolor'] . '">';
            $ctf_header_html .= '<a href="https://twitter.com/' . $url_part . '" target="_blank" class="ctf-header-link">';
            $ctf_header_html .= '<div class="ctf-header-text">';
            $ctf_header_html .= '<p class="ctf-header-no-bio" style="' . $feed_options['headertextcolor'] . '">' . $default_header_text . '</p>';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '<div class="ctf-header-img">';
            $ctf_header_html .= '<div class="ctf-header-generic-icon">';
            $ctf_header_html .= '<i class="fa fa-twitter"></i>';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</div>';
            $ctf_header_html .= '</a>';
            $ctf_header_html .= '</div>';
        }

        return $ctf_header_html;
    }

    /**
     * outputs the html for a set of tweets to be used in the feed
     *
     * @param int $is_pagination    1 or 0, used to differentiate between the first set and subsequent tweet sets
     *
     * @return string $tweet_html
     */
    public function getTweetSetHtml( $is_pagination = 0 )
    {
        $tweet_set = isset( $this->tweet_set['statuses'] ) ? $this->tweet_set['statuses'] : $this->tweet_set;
        $len = min( $this->feed_options['num'] + $is_pagination, count( $tweet_set ) );
        $i = $is_pagination; // starts at index "1" to offset duplicate tweet
        $feed_options = $this->feed_options;
        $tweet_html = $this->feed_html;

        if ( $is_pagination && ( ! isset ( $tweet_set[1]['id_str'] ) ) ) {
            $tweet_html .= $this->getOutOfTweetsHtml( $this->feed_options );
        } else {
            while ( $i < $len ) {

                // run a check to accommodate the "search" endpoint as well
                $post = $tweet_set[$i];

                // temporary workaround for cached http images
                $post['user']['profile_image_url_https'] = isset( $post['user']['profile_image_url_https'] ) ? $post['user']['profile_image_url_https'] : $post['user']['profile_image_url'];

                // save the original tweet data in case it's a retweet
                $post_id = $post['id_str'];
                $author = strtolower( $post['user']['screen_name'] );

                // creates a string of classes applied to each tweet
                $tweet_classes = 'ctf-item ctf-author-' . $author .' ctf-new';
                if ( !ctf_show( 'avatar', $feed_options ) ) $tweet_classes .= ' ctf-hide-avatar';
                $tweet_classes = apply_filters( 'ctf_tweet_classes', $tweet_classes ); // add_filter( 'ctf_tweet_classes', function( $tweet_classes ) { return $ctf_feed_classes . ' new-class'; }, 10, 1 );

                // check for retweet
                if ( isset( $post['retweeted_status'] ) ) {
                    $retweeter = array(
                        'name' => $post['user']['name'],
                        'screen_name' => $post['user']['screen_name']
                    );
                    $post = $post['retweeted_status'];

                    // temporary workaround for cached http images
                    $post['user']['profile_image_url_https'] = isset( $post['user']['profile_image_url_https'] ) ? $post['user']['profile_image_url_https'] : $post['user']['profile_image_url'];
                    $tweet_classes .= ' ctf-retweet';
                } else {
                    unset( $retweeter );
                }

                // check for quoted
                if ( isset( $post['quoted_status'] ) ) {
                    $tweet_classes .= ' ctf-quoted';
                    $quoted = $post['quoted_status'];
                } else {
                    unset( $quoted );
                }

                // include tweet view
                $tweet_html .= '<div class="'. $tweet_classes . '" id="' . $post_id . '" style="' . $feed_options['tweetbgcolor'] .'">';

                if ( isset( $retweeter ) && ctf_show( 'retweeter', $feed_options ) ) {
                    $tweet_html .= '<div class="ctf-context">';
                    $tweet_html .= '<a href="https://twitter.com/intent/user?screen_name=' . $retweeter['screen_name'] . '" target="_blank" class="ctf-retweet-icon"><i class="fa fa-retweet"></i></a>';
                    $tweet_html .= '<a href="https://twitter.com/' . $retweeter['screen_name'] . '" target="_blank" class="ctf-retweet-text" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">' . $retweeter['name'] . ' ' . $feed_options['retweetedtext'] . '</a>';
                    $tweet_html .= '</div>';
                }

                $tweet_html .= '<div class="ctf-author-box">';
                $tweet_html .= '<div class="ctf-author-box-link" target="_blank" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] .  $feed_options['textcolor'] . '">';
                if ( ctf_show( 'avatar', $feed_options ) ) {
                    $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] .'" class="ctf-author-avatar" target="_blank" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] . $feed_options['textcolor'] . '">';
                    $tweet_html .= '<img src="' . $post['user']['profile_image_url_https'] . '" width="48" height="48">';
                    $tweet_html .= '</a>';
                }

                if ( ctf_show( 'author', $feed_options ) ) {
                    $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '" target="_blank" class="ctf-author-name" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] .  $feed_options['textcolor'] . '">' . $post['user']['name'] . '</a>';
                    if ( $post['user']['verified'] == 1 ) {
                        $tweet_html .= '<span class="ctf-verified" ><i class="fa fa-check-circle" ></i ></span>';
                    }
                    $tweet_html .= '<a href="https://twitter.com/' . $post['user']['screen_name'] . '" class="ctf-author-screenname" target="_blank" style="' . $feed_options['authortextsize'] . $feed_options['authortextweight'] .  $feed_options['textcolor'] . '">@' . $post['user']['screen_name'] . '</a>';
                    $tweet_html .= '<span class="ctf-screename-sep">&middot;</span>';
                }

                if ( ctf_show( 'date', $feed_options ) ) {
                    $tweet_html .= '<div class="ctf-tweet-meta">';
                    $tweet_html .= '<a href="https://twitter.com/statuses/' . $post['id_str'] . '" class="ctf-tweet-date" target="_blank" style="' . $feed_options['datetextsize'] . $feed_options['datetextweight'] .  $feed_options['textcolor'] . '">' . ctf_get_formatted_date( $post['created_at'] , $feed_options, $post['user']['utc_offset'] ) . '</a>';
                    $tweet_html .= '</div>';
                } // show date
                $tweet_html .= '</div> <!-- end .ctf-author-box-link -->';
                $tweet_html .= '</div>';

                if ( ctf_show( 'text', $feed_options ) ) {
                    $tweet_html .= '<div class="ctf-tweet-content">';

                    if ( $feed_options['linktexttotwitter'] ) {
                        $tweet_html .= '<a href="https://twitter.com/statuses/' . $post['id_str'] . '" target="_blank">';
                        $tweet_html .= '<p class="ctf-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . $post['text'] . '</p>';
                        $tweet_html .= '</a>';
                    } else {
                        $tweet_html .= '<p class="ctf-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . $post['text'] . '</p>';
                    } // link text to twitter option is selected

                    $tweet_html .= '</div>';
                } // show tweet text

                if ( ctf_show( 'linkbox', $feed_options ) && isset( $quoted ) ) {
                    $tweet_html .= '<a href="https://twitter.com/statuses/' . $quoted['id_str'] . '" class="ctf-quoted-tweet" style="' . $feed_options['quotedauthorsize'] . $feed_options['quotedauthorweight'] . $feed_options['textcolor'] . '" target="_blank">';
                    $tweet_html .= '<span class="ctf-quoted-author-name">' . $quoted['user']['name'] . '</span>';

                    if ($quoted['user']['verified'] == 1) {
                        $tweet_html .= '<span class="ctf-quoted-verified"><i class="fa fa-check-circle" ></i></span>';
                    } // user is verified

                    $tweet_html .= '<span class="ctf-quoted-author-screenname">@' . $quoted['user']['screen_name'] . '</span>';
                    $tweet_html .= '<p class="ctf-quoted-tweet-text" style="' . $feed_options['tweettextsize'] . $feed_options['tweettextweight'] . $feed_options['textcolor'] . '">' . $quoted['text'] . '</p>';
                    $tweet_html .= '</a>';
                }// show link box

                $tweet_html .= '<div class="ctf-tweet-actions">';
                if ( ctf_show( 'actions', $feed_options ) ) {
                    $tweet_html .= '<a href="https://twitter.com/intent/tweet?in_reply_to=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-reply" target="_blank" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"><i class="fa fa-reply"></i></a>';
                    $tweet_html .= '<a href="https://twitter.com/intent/retweet?tweet_id=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-retweet" target="_blank" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"><i class="fa fa-retweet"></i><span class="ctf-action-count ctf-retweet-count">';
                    if ( $post['retweet_count'] > 0 ) {
                        $tweet_html .= $post['retweet_count'];
                    }
                    $tweet_html .= '</span></a>';
                    $tweet_html .= '<a href="https://twitter.com/intent/like?tweet_id=' . $post['id_str'] . '&related=' . $post['user']['screen_name'] . '" class="ctf-like" target="_blank" style="' . $feed_options['iconsize'] . $feed_options['iconcolor'] . '"><i class="fa fa-heart"></i><span class="ctf-action-count ctf-favorite-count">';
                    if ( $post['favorite_count'] > 0 ) {
                        $tweet_html .= $post['favorite_count'];
                    }
                    $tweet_html .= '</span></a>';
                }
                if ( ctf_show( 'twitterlink', $feed_options ) ) {
                    $tweet_html .= '<a href="https://twitter.com/statuses/' . $post['id_str'] . '" class="ctf-twitterlink" style="' . $feed_options['textcolor'] . '" target="_blank">' . $feed_options['twitterlinktext'] . '</a>';
                } // show twitter link or actions
                $tweet_html .= '</div>';
                $tweet_html .= '</div>';

                $i++;
            }
        }
        return $tweet_html;
    }

    /**
     * displays a message if there is an error in the feed
     *
     * @return string error html
     */
    public function getErrorHtml()
    {
        $error_html = '';
        $error_html .= '<div id="ctf" class="ctf">';
        $error_html .= '<div class="ctf-error">';
        $error_html .= '<div class="ctf-error-user">';
        $error_html .= '<p>Unable to load Tweets</p>';
        $error_html .= '<a class="twitter-share-button"';
        $error_html .= 'href="https://twitter.com/share"';
        $error_html .= 'data-size="large"';
        $error_html .= 'data-url="' . get_the_permalink() . '"';
        $error_html .= 'data-text="Check out this website">';
        $error_html .= '</a>';

        if ( isset( $this->feed_options['screenname'] ) ) {
            $error_html .= '<a class="twitter-follow-button"';
            $error_html .= 'href="https://twitter.com/' . $this->feed_options['screenname'] . '"';
            $error_html .= 'data-show-count="false"';
            $error_html .= 'data-size="large"';
            $error_html .= 'data-dnt="true">Follow</a>';
        }
        $error_html .= '</div>';

        if ( current_user_can( 'manage_options' ) ) {
            $error_html .= '<div class="ctf-error-admin">';

            $error_html .= '<p><b>This message is only visible to admins:</b><br />';
            $error_html .= 'An error has occurred with your feed.<br />';
            if ( $this->missing_credentials ) {
                $error_html .= 'There is a problem with your access token, access token secret, consumer token, or consumer secret<br />';
            }
            if ( isset( $this->errors['error_message'] ) ) {
                $error_html .= $this->errors['error_message'] . '<br />';
            }
            if( ! empty( $this->api_obj->api_error_no ) ) {
                $error_html .= 'The error response from the Twitter API is the following:<br />';
                $error_html .= '<code>Error number: ' . $this->api_obj->api_error_no . '<br />';
                $error_html .= 'Message: ' . $this->api_obj->api_error_message . '</code>';
            }
            $error_html .= '<a href="https://smashballoon.com/custom-twitter-feeds/docs/errors/" target="_blank">Click here to troubleshoot</a></p>';

            $error_html .= '</div>';
        }
        $error_html .= '</div>'; // end .ctf-error
        $error_html .= '</div>'; // end #ctf

        return $error_html;
    }
}