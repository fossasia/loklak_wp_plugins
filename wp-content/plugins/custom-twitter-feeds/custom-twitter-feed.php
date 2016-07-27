<?php
/*
Plugin Name: Custom Twitter Feeds
Plugin URI: http://smashballoon.com/custom-twitter-feeds
Description: Customizable Twitter feeds for your website
Version: 1.1.2
Author: Smash Balloon
Author URI: http://smashballoon.com/
Text Domain: custom-twitter-feeds
*/
/*
Copyright 2016  Smash Balloon LLC (email : hey@smashballoon.com)
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define( 'CTF_URL', plugin_dir_path( __FILE__ )  );
define( 'CTF_VERSION', '1.1.2' );
define( 'CTF_TITLE', 'Custom Twitter Feeds' );
define( 'CTF_JS_URL', plugins_url( '/js/ctf-scripts.js?ver=' . CTF_VERSION , __FILE__ ) );
define( 'OAUTH_PROCESSOR_URL', 'https://smashballoon.com/ctf-at-retriever/?return_uri=' );

require_once( CTF_URL . '/inc/widget.php' );

require_once( CTF_URL . '/inc/admin-hooks.php' );

function ctf_update_settings() {
    $existing_deprecated_options = get_option( 'ctf_configure' );
    $existing_options = get_option( 'ctf_options' );

    update_option( 'ctf_version', CTF_VERSION );

    if ( ! empty( $existing_deprecated_options ) && empty( $existing_options ) ) {
        $merged_options = $existing_deprecated_options;
        $merged_options = array_merge( $merged_options, get_option( 'ctf_customize', array() ) );
        $merged_options = array_merge( $merged_options, get_option( 'ctf_style', array() ) );

        update_option( 'ctf_options', $merged_options );
    }
}

/**
 * include the admin files only if in the admin area
 */
if ( is_admin() ) {

    $ctf_version = get_option( 'ctf_version', false );

    if ( ! $ctf_version ) {
        ctf_update_settings();
    }
    require_once( CTF_URL . '/inc/CtfAdmin.php' );
    require_once( CTF_URL . '/inc/notices.php' );

    $admin = new CtfAdmin;
}

/**
 * Generates the Twitter feed wherever the shortcode is placed
 *
 * @param $atts array shortcode arguments
 * 
 * @return string
 */
function ctf_init( $atts ) {

    include_once( CTF_URL . '/inc/CtfFeed.php' );

    $twitter_feed = CtfFeed::init( $atts );

    // if there is an error, display the error html, otherwise the feed
    if ( ! $twitter_feed->tweet_set || $twitter_feed->missing_credentials ) {
        return $twitter_feed->getErrorHtml();
    } else {
        $twitter_feed->maybeCacheTweets();
        
        $feed_html = $twitter_feed->getFeedOpeningHtml();
        $feed_html .= $twitter_feed->getTweetSetHtml();
        $feed_html .= $twitter_feed->getFeedClosingHtml();

        return $feed_html;
    }
}
add_shortcode( 'custom-twitter-feed', 'ctf_init' );
add_shortcode( 'custom-twitter-feeds', 'ctf_init' );

/**
 * Called via ajax to get more posts after the "load more" button is clicked
 */
function ctf_get_more_posts() {
    $shortcode_data = json_decode( str_replace( '\"', '"', sanitize_text_field( $_POST['shortcode_data'] ) ), true ); // necessary to unescape quotes
    $last_id_data = isset( $_POST['last_id_data'] ) ? sanitize_text_field( $_POST['last_id_data'] ) : '';
    $num_needed = isset( $_POST['num_needed'] ) ? (int)$_POST['num_needed'] : 0;
    $is_pagination = empty( $last_id_data ) ? 0 : 1;

    include_once( CTF_URL . '/inc/CtfFeed.php' );

    $twitter_feed = CtfFeed::init( $shortcode_data, $last_id_data, $num_needed );

    $twitter_feed->maybeCacheTweets();

    echo $twitter_feed->getTweetSetHtml( $is_pagination );

    die();
}
add_action( 'wp_ajax_nopriv_ctf_get_more_posts', 'ctf_get_more_posts' );
add_action( 'wp_ajax_ctf_get_more_posts', 'ctf_get_more_posts' );

/**
 * the html output is controlled by the user selecting which portions of tweets to show
 *
 * @param $part string          part of the feed in the html
 * @param $feed_options array   options that contain what parts of the tweet to show
 * @return bool                 whether or not to show the tweet
 */
function ctf_show( $part, $feed_options ) {
    $tweet_excludes = isset( $feed_options['tweet_excludes'] ) ? $feed_options['tweet_excludes'] : '';
    $tweet_includes = isset( $feed_options['tweet_includes'] ) ? $feed_options['tweet_includes'] : '';

    // if part is in the array of excluded parts or not in the array of included parts, don't show
    if ( ! empty( $tweet_excludes ) ) {
        return ( in_array( $part, $tweet_excludes ) === false );
    } else {
        return ( in_array( $part, $tweet_includes ) === true );
    }
}

/**
 * this function returns the properly formatted date string based on user input
 *
 * @param $raw_date string      the date from the Twitter api
 * @param $feed_options array   options for the feed that contain date formatting settings
 * @param $utc_offset int       offset in seconds for the time display based on timezone
 * @return string               formatted date
 */
function ctf_get_formatted_date( $raw_date, $feed_options, $utc_offset ) {
    include_once( CTF_URL . '/inc/CtfDateTime.php' );
    
    $options = get_option( 'ctf_configure' );
    $timezone = isset( $options['timezone'] ) ? $options['timezone'] : 'default';
    // use php DateTimeZone class to handle the date formatting and offsets
    $date_obj = new CtfDateTime( $raw_date, new DateTimeZone( "UTC" ) );

    if( $timezone != 'default' ) {
        $date_obj->setTimeZone( new DateTimeZone( $timezone ) );
        $utc_offset = $date_obj->getOffset();
    }

    $tz_offset_timestamp = $date_obj->getTimestamp() + $utc_offset;

    // use the custom date format if set, otherwise use from the selected defaults
    if ( ! empty( $feed_options['datecustom'] ) ){
        $date_str = date_i18n( $feed_options['datecustom'], $tz_offset_timestamp );
    } else {

        switch ( $feed_options['dateformat'] ) {

            case '2':
                $date_str = date_i18n( 'F j', $tz_offset_timestamp );
                break;
            case '3':
                $date_str = date_i18n( 'F j, Y', $tz_offset_timestamp );
                break;
            case '4':
                $date_str = date_i18n( 'm.d', $tz_offset_timestamp );
                break;
            case '5':
                $date_str = date_i18n( 'm.d.y', $tz_offset_timestamp );
                break;
            default:

                // default format is similar to Twitter
                $ctf_minute = ! empty( $feed_options['mtime'] ) ? $feed_options['mtime'] : 'm';
                $ctf_hour = ! empty( $feed_options['htime'] ) ? $feed_options['htime'] : 'h';
                $ctf_now_str = ! empty( $feed_options['nowtime'] ) ? $feed_options['nowtime'] : 'now';

                $now = time() + $utc_offset;

                $difference = $now - $tz_offset_timestamp;

                if ( $difference < 60 ) {
                    $date_str = $ctf_now_str;
                } elseif ( $difference < 60*60 ) {
                    $date_str = round( $difference/60 ) . $ctf_minute;
                } elseif ( $difference < 60*60*24 ) {
                    $date_str = round( $difference/3600 ) . $ctf_hour;
                } else  {
                    $one_year_from_date = new CtfDateTime( $raw_date, new DateTimeZone( "UTC" ) );
                    $one_year_from_date->modify('+1 year');
                    $one_year_from_date_timestamp = $one_year_from_date->getTimestamp();
                    if ( $now > $one_year_from_date_timestamp ) {
                        $date_str = date_i18n( 'j M Y', $tz_offset_timestamp );
                    } else {
                        $date_str = date_i18n( 'j M', $tz_offset_timestamp );
                    }
                }
                break;
        }

    }

    return $date_str;
}

/**
 * Called via ajax to automatically save access token and access token secret
 * retrieved with the big blue button
 */
function ctf_auto_save_tokens() {
    if ( current_user_can( 'edit_posts' ) ) {
        wp_verify_nonce( 'ctf-smash-balloon' );

        $options = get_option( 'ctf_configure' );
        $options['access_token'] = sanitize_text_field( $_POST['access_token'] );
        $options['access_token_secret'] = sanitize_text_field( $_POST['access_token_secret'] );

        update_option( 'ctf_configure', $options );
    } else {
        return false;
    }
}
add_action( 'wp_ajax_ctf_auto_save_tokens', 'ctf_auto_save_tokens' );

/**
 * manually clears the cached tweets in case of error or user preference
 *
 * @return mixed bool whether or not it was successful
 */
function ctf_clear_cache() {
    if ( current_user_can( 'edit_posts' ) ) {
        //Delete all transients
        global $wpdb;
        $table_name = $wpdb->prefix . "options";
        $result = $wpdb->query("
        DELETE
        FROM $table_name
        WHERE `option_name` LIKE ('%\_transient\_ctf\_%')
        ");
        $wpdb->query("
        DELETE
        FROM $table_name
        WHERE `option_name` LIKE ('%\_transient\_timeout\_ctf\_%')
        ");
        return $result;
    } else {
        return false;
    }
}
add_action( 'ctf_cron_job', 'ctf_clear_cache' );
add_action( 'wp_ajax_ctf_clear_cache', 'ctf_clear_cache' );

/**
 * clear the cache and unschedule an cron jobs when deactivated
 */
function ctf_deactivate() {
    ctf_clear_cache();

    wp_clear_scheduled_hook( 'ctf_cron_job' );
}
register_deactivation_hook( __FILE__, 'ctf_deactivate' );

/**
 * Loads the javascript for the plugin front-end. Also localizes the admin-ajax file location for use in ajax calls
 */
function ctf_scripts_and_styles() {
    wp_enqueue_style( 'ctf_styles', plugins_url( '/css/ctf-styles.css', __FILE__ ), array(), CTF_VERSION );
    wp_enqueue_script( 'ctf_twitter_intents', 'https://platform.twitter.com/widgets.js' );
    wp_enqueue_script( 'ctf_scripts', plugins_url( '/js/ctf-scripts.js', __FILE__ ), array( 'jquery' ), CTF_VERSION, true );
    wp_localize_script( 'ctf_scripts', 'ctf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' )
        )
    );
}
add_action( 'wp_enqueue_scripts', 'ctf_scripts_and_styles' );

/**
 * outputs the custom js from the "Customize" tab on the Settings page
 */
function ctf_custom_js() {
    $options = get_option( 'ctf_customize' );
    $ctf_custom_js = isset( $options[ 'custom_js' ] ) ? $options[ 'custom_js' ] : '';

    if ( ! empty( $ctf_custom_js ) ) {
        ?>
        <!-- Custom Twitter Feeds JS -->
        <script type="text/javascript">
            <?php echo "window.ctf_custom_js = function($){" . stripslashes( $ctf_custom_js ) . "}\r\n"; ?>
        </script>
        <?php
    }
}
add_action( 'wp_footer', 'ctf_custom_js' );

/**
 * outputs the custom css from the "Customize" tab on the Settings page
 */
function ctf_custom_css() {
    $options = get_option( 'ctf_customize' );
    $ctf_custom_css = isset( $options[ 'custom_css' ] ) ? $options[ 'custom_css' ] : '';

    if ( ! empty( $ctf_custom_css ) ) {
        ?>
        <!-- Custom Twitter Feeds CSS -->
        <style type="text/css">
            <?php echo stripslashes( $ctf_custom_css ) . "\r\n"; ?>
        </style>
        <?php
    }
}
add_action( 'wp_head', 'ctf_custom_css' );

/**
 * Some CSS and JS needed in the admin area as well
 */
function ctf_admin_scripts_and_styles() {
    wp_enqueue_style( 'ctf_admin_styles', plugins_url( '/css/ctf-admin-styles.css', __FILE__ ), array(), CTF_VERSION );
    wp_enqueue_script( 'ctf_admin_scripts', plugins_url( '/js/ctf-admin-scripts.js', __FILE__ ) , array( 'jquery' ), CTF_VERSION, false );
    wp_localize_script( 'ctf_admin_scripts', 'ctf', array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'sb_nonce' => wp_create_nonce( 'ctf-smash-balloon' )
        )
    );
    wp_enqueue_style( 'wp-color-picker' );
    wp_enqueue_script(array('wp-color-picker'));
}
add_action( 'admin_enqueue_scripts', 'ctf_admin_scripts_and_styles' );


