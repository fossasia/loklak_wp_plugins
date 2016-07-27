<?php

class wptt_TwitterTweets extends WP_Widget {

    /**
     * Widget IDs using Slider display
     */
    var $widgetid;
    static $slider_ids = array();

    function form($instance) {
        $defaults = $this->get_defaults();
        $instance = wp_parse_args((array) $instance, $defaults);
        $widget_title = $instance['title'];
        $name = $instance['name'];
        $tweets_count = $instance['tweets_cnt'];
        $loklak_api = $instance['loklak_api'];
        $accessTokenSecret = trim($instance['accessTokenSecret']);
        $replies_excl = $instance['replies_excl'];
        $consumerSecret = trim($instance['consumerSecret']);
        $accessToken = trim($instance['accessToken']);
        $cache_transient = $instance['timeRef'];
        $alter_ago_time = $instance['timeAgo'];
        $twitterIntents = $instance['twitterIntents'];
        //$dataShowCount        = $instance['dataShowCount'];
        $disp_screen_name = $instance['disp_scr_name'];
        $timeto_store = $instance['store_time'];
        $consumerKey = trim($instance['consumerKey']);
        $intents_text = $instance['twitterIntentsText'];
        $color_intents = $instance['intentColor'];
        $slide_style = $instance['slide_style'];
        $showAvatar = $instance['showAvatar'];
        $border_rad_avatar = $instance['border_rad'];
        $tweet_border = $instance['tweet_border'];
        $tweet_theme = $instance['tweet_theme'];
        if (!in_array('curl', get_loaded_extensions())) {
            echo '<p style="background-color:pink;padding:10px;border:1px solid red;"><strong>cURL is not installed!</strong></p>';
        }
        include('widget_html.php');
    }

    function get_defaults() {
        $data = array(
            'title' => 'Latest Tweets'
            , 'name' => ''
            , 'tweets_cnt' => 3
            , 'tweet_theme' => 'light'
            , 'tweet_border' => 'true'
            , 'store_time' => 4
            , 'replies_excl' => true
            , 'disp_scr_name' => false
            , 'loklak_api' => true
            , 'consumerKey' => ''
            , 'consumerSecret' => ''
            , 'accessToken' => ''
            , 'accessTokenSecret' => ''
            , 'dataLang' => 'en'
            , 'timeRef' => false
            , 'timeAgo' => true
            , 'twitterIntents' => false
            , 'twitterIntentsText' => false
            , 'intentColor' => "#999999"
            , 'showAvatar' => false
            , 'border_rad' => false
            , 'slide_style' => 'list'
        );
        return $data;
    }

    function wpltf_enqueue_js($hook) {
        if ($hook != 'widgets.php')
            return;


        global $wp_version;
        if (3.5 <= $wp_version) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        } else {
            wp_enqueue_style('farbtastic');
            wp_enqueue_script('farbtastic');
        }
        //wp_enqueue_script('admin_js', plugins_url( '/js/admin_script.js' , dirname(__FILE__) ), array('jquery'));
        wp_enqueue_script('user_validate', plugins_url('/js/validate.js', dirname(__FILE__)), array('jquery'));
        wp_enqueue_script('twitter_auth_disable', plugins_url('/js/twitter_auth_disable.js', dirname(__FILE__)), array('jquery'));
    }

    function sanitize_links($tweet) {
        if (isset($tweet->retweeted_status)) {
            $rt_section = current(explode(":", $tweet->text));
            $text = $rt_section . ": ";
            $text .= $tweet->retweeted_status->text;
        } else {
            $text = $tweet->text;
        }
        $text = preg_replace('/((http)+(s)?:\/\/[^<>\s]+)/i', '<a href="$0" target="_blank" rel="nofollow">$0</a>', $text);
        $text = preg_replace('/[@]+([A-Za-z0-9-_]+)/', '<a href="https://twitter.com/$1" target="_blank" rel="nofollow">@$1</a>', $text);
        $text = preg_replace('/[#]+([A-Za-z0-9-_]+)/', '<a href="https://twitter.com/search?q=%23$1" target="_blank" rel="nofollow">$0</a>', $text);
        return $text;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;

        $instance['title'] = strip_tags($new_instance['title']);
        $instance['name'] = strip_tags($new_instance['name']);
        $instance['tweets_cnt'] = $new_instance['tweets_cnt'];
        $instance['store_time'] = $new_instance['store_time'];
        //$instance['dataShowCount']        = $new_instance['dataShowCount'];
        $instance['disp_scr_name'] = $new_instance['disp_scr_name'];
        $instance['timeAgo'] = $new_instance['timeAgo'];
        $instance['twitterIntents'] = $new_instance['twitterIntents'];
        $instance['twitterIntentsText'] = $new_instance['twitterIntentsText'];
        $instance['intentColor'] = strip_tags($new_instance['intentColor']);
        $instance['slide_style'] = $new_instance['slide_style'];
        $instance['loklak_api'] = $new_instance['loklak_api'];
        $instance['consumerKey'] = trim($new_instance['consumerKey']);
        $instance['consumerSecret'] = trim($new_instance['consumerSecret']);
        $instance['accessToken'] = trim($new_instance['accessToken']);
        $instance['accessTokenSecret'] = trim($new_instance['accessTokenSecret']);
        $instance['replies_excl'] = $new_instance['replies_excl'];
        $instance['timeRef'] = $new_instance['timeRef'];
        $instance['showAvatar'] = $new_instance['showAvatar'];
        $instance['border_rad'] = $new_instance['border_rad'];
        $instance['tweet_border'] = $new_instance['tweet_border'];
        $instance['tweet_theme'] = $new_instance['tweet_theme'];
        return $instance;
    }

    function __construct() {
        add_action('admin_enqueue_scripts', array(&$this, 'wpltf_enqueue_js'));
        if (!is_admin())
            add_action('wp_enqueue_scripts', array(&$this, 'wpltf_register_styles'));
        $widget_data = array('classname' => 'wptt_TwitterTweets', 'description' => 'A easy widget which lets you add your latest tweets in just a few clicks on your website.');
        parent::__construct('wptt_TwitterTweets', 'WP Twitter Feeds', $widget_data);
    }

    function wpltf_register_styles() {
        if (!is_admin()) {
            wp_register_style('wptt_front', plugins_url('wp-twitter-feeds/css/admin_style.min.css'));
            wp_enqueue_style('wptt_front');
        }
    }

    function widget($args, $instance) {
        extract($args, EXTR_SKIP);
        //  print_r($args);
        echo $before_widget;
        $this->widgetid = $args['widget_id'];
        $wpltf_wdgt_title = empty($instance['title']) ? ' ' : apply_filters('widget_title', $instance['title']);
        $wpltf_wdgt_name = $instance['name'];
        $wpltf_wdgt_loklakApi = $instance['loklak_api'];
        $wpltf_wdgt_consumerSecret = trim($instance['consumerSecret']);
        $wpltf_wdgt_accessTokenSecret = trim($instance['accessTokenSecret']);
        $widget_replies_excl = isset($instance['replies_excl']) ? $instance['replies_excl'] : false;
        $wpltf_wdgt_accessToken = trim($instance['accessToken']);
        $wpltf_wdgt_tweets_cnt = $instance['tweets_cnt'];
        $wpltf_wdgt_store_time = $instance['store_time'];
        $wpltf_wdgt_consumerKey = trim($instance['consumerKey']);
        //$wpltf_wdgt_dataShowCount         = isset( $instance['dataShowCount'] ) ? $instance['dataShowCount'] : false;
        $wpltf_wdgt_disp_scr_name = isset($instance['disp_scr_name']) ? $instance['disp_scr_name'] : false;
        $wpltf_wdgt_timeRef = isset($instance['timeRef']) ? $instance['timeRef'] : false;
        $wpltf_wdgt_timeAgo = isset($instance['timeAgo']) ? $instance['timeAgo'] : false;
        $wpltf_wdgt_twitterIntents = isset($instance['twitterIntents']) ? $instance['twitterIntents'] : false;
        $wpltf_wdgt_twitterIntentsText = isset($instance['twitterIntentsText']) ? $instance['twitterIntentsText'] : false;
        $wpltf_wdgt_intentColor = $instance['intentColor'];
        $wpltf_wdgt_slide_style = isset($instance['slide_style']) ? $instance['slide_style'] : 'list';
        $wpltf_wdgt_showAvatar = isset($instance['showAvatar']) ? $instance['showAvatar'] : false;
        $wpltf_wdgt_border_rad = isset($instance['border_rad']) ? $instance['border_rad'] : false;
        $wpltf_wdgt_tewwt_border = isset($instance['tweet_border']) ? $instance['tweet_border'] : 'false';
        $wpltf_wdgt_tweet_theme = isset($instance['tweet_theme']) ? $instance['tweet_theme'] : 'light';
        if (!empty($wpltf_wdgt_title))
            echo $before_title . $wpltf_wdgt_title . $after_title;
        if( !$wpltf_wdgt_loklakApi && ( $wpltf_wdgt_consumerKey=='' || $wpltf_wdgt_consumerSecret =='' || $wpltf_wdgt_accessTokenSecret=='' || $wpltf_wdgt_accessToken=='' )) {
            echo '<div class="isa_error">Bad Authentication data.<br/>Please enter valid API Keys.</div>';
        } else {
            $class = 'light';
            if (isset($wpltf_wdgt_tweet_theme) && $wpltf_wdgt_tweet_theme == 'dark')
                $class = 'dark';
            if (isset($wpltf_wdgt_tewwt_border) && $wpltf_wdgt_tewwt_border == 'true') {
                echo '<style>
                .fetched_tweets.light > li{border-color: rgb(238, 238, 238) rgb(221, 221, 221) rgb(187, 187, 187);
                border-width: 1px;
                border-style: solid;}
                .fetched_tweets.dark > li{
                border-color: #444;
                border-width: 1px;
                border-style: solid;}</style>';
            }
            ?>          

            <ul class="fetched_tweets <?php echo $class; ?>">
                <?php
                $tweets_count = $wpltf_wdgt_tweets_cnt;
                $name = $wpltf_wdgt_name;
                $timeto_store = $wpltf_wdgt_store_time;
                $loklak_api = $wpltf_wdgt_loklakApi;
                $consumerSecret = trim($wpltf_wdgt_consumerSecret);
                $accessToken = trim($wpltf_wdgt_accessToken);
                $accessTokenSecret = trim($wpltf_wdgt_accessTokenSecret);
                $replies_excl = $widget_replies_excl;
                $consumerKey = trim($wpltf_wdgt_consumerKey);
                //$dataShowCount        = ($wpltf_wdgt_dataShowCount != "true") ? "false" : "true";
                $disp_screen_name = ($wpltf_wdgt_disp_scr_name != "true") ? "false" : "true";
                $intents_text = $wpltf_wdgt_twitterIntentsText;
                $color_intents = $wpltf_wdgt_intentColor;
                $slide_style = $wpltf_wdgt_slide_style;
                $cache_transient = $wpltf_wdgt_timeRef;
                $alter_ago_time = $wpltf_wdgt_timeAgo;
                $twitterIntents = $wpltf_wdgt_twitterIntents;
                $showAvatar = $wpltf_wdgt_showAvatar;
                $border_rad_avatar = $wpltf_wdgt_border_rad;
                $transName = 'list-tweets-' . $name;
                $backupName = $transName . '-backup';
                $totalToFetch = ($replies_excl) ? max(50, $tweets_count * 3) : $tweets_count;

                if (false === ($tweets = get_transient($transName) ) ) :
                    if (false === $loklak_api) :
                        require_once 'twitteroauth/twitteroauth.php';

                        $api_call = new TwitterOAuth(
                                $consumerKey, $consumerSecret, $accessToken, $accessTokenSecret
                        );

                        $fetchedTweets = $api_call->get(
                            'statuses/user_timeline', array(
                            'screen_name' => $name,
                            'count' => $totalToFetch,
                            'replies_excl' => $replies_excl
                            )
                        );
                    else : 
                        if(!class_exists('Loklak')) :
                            require_once('loklak_php_api/loklak.php');
                        endif;
                        $api_call = new Loklak();
                        $fetchedTweets = $api_call->search('', null, null, $name, $totalToFetch);
                        $fetchedTweets = json_decode($fetchedTweets, true);
                        $fetchedTweets = json_decode($fetchedTweets['body'], true);
                        $fetchedTweets = $fetchedTweets['statuses'];
                    endif;

                    if( false === $loklak_api && $api_call->http_code != 200 ) :
                        $tweets = get_option($backupName);

                    else :
                        $limitToDisplay = min($tweets_count, count($fetchedTweets));
                        
                        for($i = 0; $i < $limitToDisplay; $i++) :
                            $tweet = $fetchedTweets[$i];
                            $tweet = (object)$tweet;
                            $tweet->user = (object)($tweet->user);

                            $name = $tweet->user->name;
                            $screen_name = $tweet->user->screen_name;
                            $permalink = 'http://twitter.com/'. $name .'/status/'. $tweet->id_str;
                            $tweet_id = $tweet->id_str;
                            $fav_count = ($loklak_api ? $tweet->favourites_count : $tweet->favorite_count);
                            $image = ($loklak_api ? $tweet->user->profile_image_url_https : $tweet->user->profile_image_url);
                            $text = $this->sanitize_links($tweet);
                            $time = $tweet->created_at;
                            $time = date_parse($time);
                            $uTime = mktime($time['hour'], $time['minute'], $time['second'], $time['month'], $time['day'], $time['year']);

                            $tweets[] = array(
                                'text' => $text,
                                'scr_name'=>$screen_name,
                                'favourite_count'=>$fav_count,
                                'retweet_count'=>$tweet->retweet_count,
                                'name' => $name,
                                'permalink' => $permalink,
                                'image' => $image,
                                'time' => $uTime,
                                'tweet_id' => $tweet_id
                                );
                        endfor;
                        set_transient($transName, $tweets, 60 * $timeto_store);
                        update_option($backupName, $tweets);
                    endif;
                endif;
                if (!function_exists('twitter_time_diff')) {

                    function twitter_time_diff($from, $to = '') {
                        $diff = human_time_diff($from, $to);
                        $replace = array(
                            ' hour' => 'h',
                            ' hours' => 'h',
                            ' day' => 'd',
                            ' days' => 'd',
                            ' minute' => 'm',
                            ' minutes' => 'm',
                            ' second' => 's',
                            ' seconds' => 's',
                        );
                        return strtr($diff, $replace);
                    }

                }
                if ($tweets) :                  
                    ?>
                    <?php foreach ($tweets as $t) : ?>
                        <li class="tweets_avatar">
                            <?php
                            echo '<div class="tweet_wrap"><div class="wdtf-user-card ltr">';
                            if ($showAvatar) {

                                echo '<img ';
                                echo 'width="45px" height="45px"';
                                //echo 'src="'.$t['image'].'" alt="Tweet Avatar" class="';
                                echo 'src="' . str_replace('http://', '//', $t['image']) . '" alt="Tweet Avatar" class="';
                                echo ($border_rad_avatar) ? 'circular' : '';
                                echo '"/>';
                            }
                            if (!isset($screen_name)) {
                                $screen_name = $name;
                            }

                            if ($disp_screen_name != 'false') {
                                echo '<div class="wdtf-screen-name">';
                                echo "<span class=\"screen_name\">{$t['name']}</span><br>";
                                echo "<a href=\"https://twitter.com/{$screen_name}\" target=\"_blank\" dir=\"ltr\">@{$screen_name}</a></div>";
                            }
                            echo '<div class="clear"></div></div>';
                            ?>
                            <div class="tweet_data">
                                <?php echo $t['text']; ?>
                            </div>
                            <br/>
                            <div class="clear"></div>
                            <div class="times">
                                <em>

                                    <a href="https://www.twitter.com/<?php echo $screen_name; ?>" target="_blank" title="Follow <?php echo $name; ?> on Twitter [Opens new window]">
                                        <?php
                                        if ($cache_transient == "true") {
                                            $timeDisplay = twitter_time_diff($t['time'], current_time('timestamp'));
                                        } else {
                                            $timeDisplay = human_time_diff($t['time'], current_time('timestamp'));
                                        }
                                        if ($alter_ago_time == "true") {
                                            $displayAgo = " ago";
                                        }
                                        printf(__('%1$s%2$s'), $timeDisplay, $displayAgo);
                                        ?>
                                    </a>
                                </em>
                            </div>
                            <?php if ($twitterIntents == "true") {
                                ?>       
                                <div class="tweets-intent-data">
                                    <?php if ($t['favourite_count'] != 0 || $t['retweet_count'] != 0) { ?>
                                        <span class="stats-narrow customisable-border"><span class="stats" data-scribe="component:stats">
                                                <?php if ($t['retweet_count'] != 0) {
                                                    ?>
                                                    <a href="https://twitter.com/<?php echo $screen_name; ?>/statuses/<?php echo $t['tweet_id']; ?>" title="View Tweet on Twitter" data-scribe="element:favorite_count" target="_blank">
                                                        <span class="stats-favorites">
                                                            <strong><?php echo $t['retweet_count']; ?></strong> retweet<?php if ($t['retweet_count'] > 1) echo's'; ?>
                                                        </span>
                                                    </a>
                                                <?php } ?>
                                                <?php if ($t['favourite_count'] != 0) {
                                                    ?>
                                                    <a href="https://twitter.com/<?php echo $screen_name; ?>/statuses/<?php echo $t['tweet_id']; ?>" title="View Tweet on Twitter" data-scribe="element:favorite_count" target="_blank">
                                                        <span class="stats-favorites">
                                                            <strong><?php echo $t['favourite_count']; ?></strong> Favorite<?php if ($t['favourite_count'] > 1) echo's'; ?>
                                                        </span>
                                                    </a>
                                                <?php } ?>

                                            </span>
                                        </span>
                                        <div class="clear"></div>
                                        <div class="seperator_wpltf"></div>
                                    <?php } ?>
                                    <ul class="tweet-actions " role="menu" >
                                        <li><a href="https://twitter.com/intent/tweet?in_reply_to=<?php echo $t['tweet_id']; ?>" data-lang="en" class="in-reply-to" title="Reply" target="_blank"><span aria-hidden="true" data-icon="&#xf079;" <?php echo ($color_intents) ? 'style="color:' . $color_intents . ';"' : ''; ?>></span></a></li>
                                        <li><a href="https://twitter.com/intent/retweet?tweet_id=<?php echo $t['tweet_id']; ?>" data-lang="en" class="retweet" title="Retweet" target="_blank"><span aria-hidden="true" data-icon="&#xf112;" <?php echo ($color_intents) ? 'style="color:' . $color_intents . ';"' : ''; ?>></span></a></li>
                                        <li><a href="https://twitter.com/intent/favorite?tweet_id=<?php echo $t['tweet_id']; ?>" data-lang="en" class="favorite" title="Favorite" target="_blank"><span aria-hidden="true" data-icon="&#xf005;" <?php echo ($color_intents) ? 'style="color:' . $color_intents . ';"' : ''; ?>></span></a></li>
                                    </ul>
                                </div>
                            <?php } ?>
                            <div class="clear"></div>
                        </div><div class="clear"></div>
                        </li>
                    <?php endforeach; ?>

                <?php else : ?>
                    <li>Waiting for twitter.com...Try reloading the page again </li>
                <?php endif; ?>
            </ul>

            <?php
            if (isset($wpltf_wdgt_slide_style) && $wpltf_wdgt_slide_style == 'slider') {
                wp_register_script('ticker_script', plugins_url('/js/jquery.newsTicker.js', dirname(__FILE__)));
                if (!wp_script_is('ticker_script')) {
                    wp_print_scripts('ticker_script');
                }
                add_action('wp_footer', array($this, 'add_script_footer'));
            }
        }
        echo $after_widget;
    }

    /*
     * Outputs Slider Javascript
     * Shows a single tweet at a time, fading between them.
     */

    public function twitter_slider_script() {
        
    }

    function add_script_footer() {
        ?>  
        <?php //echo $this->widgetid; ?>
        <script type="text/javascript">
            jQuery(document).ready(function () {
                jQuery(".fetched_tweets").removeClass("light");

            });



            var height_li = jQuery(".fetched_tweets li").height();

            height_li = height_li + 15;
            var nt_example1 = jQuery('.fetched_tweets').newsTicker({
                row_height: height_li,
                max_rows: 2,
                duration: 10000,
            });</script> <?php
    }

}
?>
