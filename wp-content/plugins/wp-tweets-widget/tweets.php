<?php
/*
Plugin Name: Tweets Widget
Plugin URI: http://wordpress.org/extend/plugins/tweets-widget/
Description: Provides a sidebar widget showing latest tweets - compatible with the new Twitter API 1.1
Author: fossasia
Version: 1.0
Author URI: http://fossasia.org/
Domain Path: /api/lang/
*/



/**
 * Pull latest tweets with some caching of raw data.
 * @param string account whose tweets we're pulling
 * @param int number of tweets to get and display
 * @param bool whether to show retweets
 * @param bool whether to show at replies
 * @return array blocks of html expected by the widget
 */
function tweets_render( $screen_name, $count, $rts, $ats, $pop = 0){
    try {
        $loklak_api = get_option('loklak-settings[loklak_api]');
        if( $loklak_api ){
            if(!class_exists('Loklak')) {
                require_once dirname(__FILE__).'/loklak_php_api/loklak.php';
            }
            $loklak = new Loklak();
            $favorite_count = 'favourites_count';
        }
        else{
            if( ! function_exists('twitter_api_get') ){
                require_once dirname(__FILE__).'/api/wp-twitter-api/twitter-api.php';
                twitter_api_load_textdomain();
                $favorite_count = 'favorite_count';
                // caching full data set, not just twitter api caching
                // caching is disabled by default in debug mode, but still filtered.
                $cachettl = (int) apply_filters('fa_tweets_cache_seconds', WP_DEBUG ? 0 : 300 );
                if( $cachettl ){
                    $arguments = func_get_args();
                    $cachekey = 'tweets_'.implode('_', $arguments );
                    if( ! function_exists('_twitter_api_cache_get') ){
                        twitter_api_include('core');
                    }
                    if( $rendered = _twitter_api_cache_get($cachekey) ){
                        return $rendered;
                    }
                }
                // Check configuration before use
                if( ! twitter_api_configured() ){
                    throw new Exception( __('Plugin not fully configured','twitter-api') );
                }
            }
        }
            // Build API params for "search" for lolak.org/api/search.json?q=from:$user
        $trim_user = false;
        $include_rts = ! empty($rts);
        $exclude_replies = empty($ats);
        $params = compact('exclude_replies','include_rts','trim_user','screen_name');
        // Stripping tweets means we may get less than $count tweets.
        // we'll keep going until we get the amount we need, but may as well get more each time.
        if( $exclude_replies || ! $include_rts || $pop ){
            $params['count'] = 100;
        }
        else {
            $params['count'] = max( $count, 2 );
        }
        // pull tweets until we either have enough, or there are no more
        if( $loklak ){
            $screen_name = explode('@', $screen_name)[1];
            $batch = $loklak->search('', null, null, $screen_name, $count);
            $batch = json_decode($batch, true);
            $batch = json_decode($batch['body'], true); 
            $batch = $batch['statuses'];
        }
        else{
            $batch = twitter_api_get('statuses/user_timeline', $params );
        }
        $tweets = array();
        while( $batch ){
            $max_id = null;
            foreach( $batch as $tweet ){
                if( isset($params['max_id']) && $tweet['id_str'] === $params['max_id'] ){
                    // previous max included in results, even though docs say it won't be
                    continue;
                }
                $max_id = $tweet['id_str'];
                if( ! $include_rts && preg_match('/^(?:RT|MT)[ :\-]*@/i', $tweet['text']) ){
                    // skipping manual RT
                    continue;
                }
                if( $pop > ( $tweet['retweet_count'] + $tweet[$favorite_count] ) ){
                    // skipping tweets not deemed popular enough
                    continue;
                }
                $tweets[] = $tweet;
            }
            if( isset($tweets[$count]) ){
                $tweets = array_slice( $tweets, 0, $count );
                break;
            }
            if( ! $max_id ){
                // infinite loop would occur if user had only tweeted once, ever.
                break;
            }
            $params['max_id'] = $max_id;
        }
        // Fix Wordpress's broken timezone implementation
        $os_timezone = date_default_timezone_get() or $os_timezone = 'UTC';
        $wp_timezone = get_option('timezone_string') or $wp_timezone = $os_timezone;
        if( $os_timezone !== $wp_timezone ){
            date_default_timezone_set( $wp_timezone );
        }
        // Let theme disable or override emoji rendering
        $emoji_callback = apply_filters('tweets_emoji_callback', 'twitter_api_replace_emoji_callback' );
        // render each tweet as a block of html for the widget list items
        $rendered = array();
        foreach( $tweets as $tweet ){
            extract( $tweet );
            $handle = $user['screen_name'] or $handle = $screen_name;
            $link = esc_html( 'http://twitter.com/'.$handle.'/status/'.$id_str);
            // render nice datetime, unless theme overrides with filter
            $date = apply_filters( 'tweets_render_date', $created_at );
            if( $date === $created_at ){
                function_exists('twitter_api_relative_date') or twitter_api_include('utils');
                $time = strtotime( $created_at );
                $date = esc_html( twitter_api_relative_date($time) );
                $date = '<time datetime="'.date_i18n( 'Y-m-d H:i:sO', $time ).'">'.$date.'</time>';
            }
            // handle original retweet text as RT may be truncated
            if( $include_rts && isset($retweeted_status) && preg_match('/^RT\s+@[a-z0-9_]{1,15}[\s:]+/i', $text, $prefix ) ){
                $text = $prefix[0].$retweeted_status['text'];
                unset($retweeted_status);
            }
            // render and linkify tweet, unless theme overrides with filter
            $html = apply_filters('tweets_render_text', $text );
            if( $html === $text ){
                if( ! function_exists('twitter_api_html') ){
                    twitter_api_include('utils');
                }
                // htmlify tweet, using entities if we can
                if( isset($entities) && is_array($entities) ){
                    $html = twitter_api_html_with_entities( $text, $entities );
                    unset($entities);
                }
                else {
                    $html = twitter_api_html( $text );
                }
                // render emoji, unless filtered out
                if( $emoji_callback ){
                    $html = twitter_api_replace_emoji( $html, $emoji_callback );
                }
                
                // strip characters that will choke mysql cache.
                if( !$loklak && $cachettl && ! TWITTER_CACHE_APC ){
                    $html = twitter_api_strip_quadruple_bytes( $html );
                }
                
            }
            // piece together the whole tweet, allowing override
            $final = apply_filters('tweets_render_tweet', $html, $date, $link, $tweet );
            if( $final === $html ){
                $final = '<p class="tweet-text">'.$html.'</p>'.
                         '<p class="tweet-details"><a href="'.$link.'" target="_blank">'.$date.'</a></p>';
            }
            $rendered[] = $final;
        } 
        // cache rendered tweets
        if( !$loklak && $cachettl ){
            _twitter_api_cache_set( $cachekey, $rendered, $cachettl );
        }
        // put altered timezone back
        if( $os_timezone !== $wp_timezone ){
            date_default_timezone_set( $os_timezone );
        }
        return $rendered;
    }
    catch( Exception $Ex ){
        return array( '<p class="tweet-text"><strong>Error:</strong> '.esc_html($Ex->getMessage()).'</p>' );
    }
} 



/**
 * Render tweets as HTML anywhere
 * @param string $screen_name Twitter handle
 * @param int $num Number of tweets to show, defaults to 5
 * @param bool $rts Whether to show Retweets, defaults to true
 * @param bool $ats Whether to show 'at' replies, defaults to true
 * @return string HTML <div> element containing a list
 */
function tweets_render_html( $screen_name = '', $num = 5, $rts = true, $ats = true, $pop = 0 ){
    $items = tweets_render( $screen_name, $num, $rts, $ats, $pop );
    $list  = apply_filters('tweets_render_list', $items, $screen_name );
    if( is_array($list) ){
        $list = '<ul><li>'.implode('</li><li>',$items).'</li></ul>';
    }
    return 
        '<div class="tweets">'. 
            apply_filters( 'tweets_render_before', '' ).
            $list.
            apply_filters( 'tweets_render_after', '' ).
        '</div>';
}

 
  
/**
 * tweets widget class
 */
class Tweets_Widget extends WP_Widget {
    
    /** @see WP_Widget::__construct */
    public function __construct( $id_base = false, $name = '', $widget_options = array(), $control_options = array() ){

        if(!class_exists('Loklak')) {
            require_once dirname(__FILE__).'/loklak_php_api/loklak.php';
            $loklak = new Loklak();
        }

        if( ! function_exists('twitter_api_load_textdomain') ){
            require_once dirname(__FILE__).'/api/wp-twitter-api/twitter-api.php';
        }
        twitter_api_load_textdomain();
        $this->options = array(
            array (
                'name'  => 'title',
                'label' => __('Widget title'),
                'type'  => 'text'
            ),
            array (
                'name'  => 'screen_name',
                'label' => __('Twitter handle','twitter-api'),
                'type'  => 'text'
            ),
            array (
                'name'  => 'num',
                'label' => __('Number of tweets','twitter-api'),
                'type'  => 'number'
            ),
            array (
                'name'  => 'pop',
                'label' => __('Minimum popularity','twitter-api'),
                'type'  => 'number'
            ),
            array (
                'name'  => 'rts',
                'label' => __('Show Retweets','twitter-api'),
                'type'  => 'bool'
            ),
            array (
                'name'  => 'ats',
                'label' => __('Show Replies','twitter-api'),
                'type'  => 'bool'
            ),
            /*array (
                'name'  => 'loklak',
                'label' => __('Use anonymous API from loklak.org','twitter-api'),
                'type'  => 'bool'
            ),*/
        );
        $name or $name = __('Tweets','twitter-api');
        parent::__construct( $id_base, $name, $widget_options, $control_options );  
    }    
    
    /* ensure no missing keys in instance params */
    private function check_instance( $instance ){
        if( ! is_array($instance) ){
            $instance = array();
        }
        $instance += array (
            'title' => __('Tweets','twitter-api'),
            'screen_name' => '',
            'num' => 5,
            'pop' => 0,
            'rts' => '',
            'ats' => '',
            //'loklak' => '',
        );
        return $instance;
    }
    
    /** @see WP_Widget::form */
    public function form( $instance ) {
        $instance = $this->check_instance( $instance );
        foreach ( $this->options as $val ) {
            $elmid = $this->get_field_id( $val['name'] );
            $fname = $this->get_field_name($val['name']);
            $value = isset($instance[ $val['name'] ]) ? $instance[ $val['name'] ] : '';
            $label = '<label for="'.$elmid.'">'.$val['label'].'</label>';
            if( 'bool' === $val['type'] ){
                 $checked = $value ? ' checked="checked"' : '';
                 echo '<p><input type="checkbox" value="1" id="'.$elmid.'" name="'.$fname.'"'.$checked.' /> '.$label.'</p>';
            }
            else {
                $attrs = '';
                echo '<p>'.$label.'<br /><input class="widefat" type="',$val['type'],'" value="'.esc_attr($value).'" id="'.$elmid.'" name="'.$fname.'" /></p>';
            }
        }
    }

    /** @see WP_Widget::widget */
    public function widget( $args, $instance ) {
        extract( $this->check_instance($instance) );
        // title is themed via Wordpress widget theming techniques
        $title = $args['before_title'] . apply_filters('widget_title', $title, $instance, $this->id_base ) . $args['after_title'];
        // by default tweets are rendered as an unordered list
        $items = tweets_render( $screen_name, $num, $rts, $ats, $pop);
        $list  = apply_filters('tweets_render_list', $items, $screen_name );
        if( is_array($list) ){
            $list = '<ul><li>'.implode('</li><li>',$items).'</li></ul>';
        }
        // output widget applying filters to each element
        echo 
        $args['before_widget'], 
            $title,
            '<div class="tweets">', 
                apply_filters( 'tweets_render_before', '' ),
                $list,
                apply_filters( 'tweets_render_after', '' ),
            '</div>',
        $args['after_widget'];
    }
    
}
 


function tweets_register_widget(){
    return register_widget('Tweets_Widget');
}

add_action( 'widgets_init', 'tweets_register_widget' );



function tweets_shortcode( $atts ){
    $screen_name = isset($atts['user']) ? trim($atts['user'],' @') : '';
    $num = isset($atts['max']) ? (int) $atts['max'] : 5;
    return tweets_render_html( $screen_name, $num, true, false );
}

add_shortcode( 'tweets', 'tweets_shortcode' );



if( is_admin() ){

    require_once dirname(__FILE__).'/loklak_php_api/Lib/loklak-api-admin.php';

    if( ! function_exists('twitter_api_get') ){
        require_once dirname(__FILE__).'/api/wp-twitter-api/twitter-api.php';
    }
    // extra visibility of API settings link
    function tweets_plugin_row_meta( $links, $file ){
        if( false !== strpos($file,'/tweets.php') ){
            $links[] = '<a href="options-general.php?page=twitter-api-admin"><strong>'.esc_attr__('Connect to Twitter','twitter-api').'</strong></a>';
        } 
        return $links;
    }
    add_action('plugin_row_meta', 'tweets_plugin_row_meta', 10, 2 );
}

