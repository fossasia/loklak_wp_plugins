<?php

// checks $_GET to see if the nag variable is set and what it's value is
function ctf_check_nag_get( $get, $nag, $option, $transient ) {
    if ( isset( $_GET[$nag] ) && $get[$nag] == 1 ) {
        update_option( $option, 'dismissed' );
    } elseif ( isset( $_GET[$nag] ) && $get[$nag] == 'later' ) {
        $time = 2 * WEEK_IN_SECONDS;
        set_transient( $transient, 'waiting', $time );
        update_option( $option, 'pending' );
    }
}

// will set a transient if the notice hasn't been dismissed or hasn't been set yet
function ctf_maybe_set_transient( $transient, $option ) {
    $ctf_rating_notice_waiting = get_transient( $transient );
    $notice_status = get_option( $option, false );

    if ( ! $ctf_rating_notice_waiting && !( $notice_status === 'dismissed' || $notice_status === 'pending' ) ) {
        $time = 2 * WEEK_IN_SECONDS;
        set_transient( $transient, 'waiting', $time );
        update_option( $option, 'pending' );
    }
}

// generates the html for the admin notice
function ctf_rating_notice_html() {

    //Only show to admins
    if ( current_user_can( 'manage_options' ) ){

        global $current_user;
        $user_id = $current_user->ID;

        /* Check that the user hasn't already clicked to ignore the message */
        if ( ! get_user_meta( $user_id, 'ctf_ignore_rating_notice') ) {

            _e("
            <div class='ctf_notice ctf_review_notice'>
                <img src='". plugins_url( 'custom-twitter-feeds/img/ctf-icon.jpg' ) ."' alt='Custom Twitter Feeds'>
                <div class='ctf-notice-text'>
                    <p>It's great to see that you've been using the <strong>Custom Twitter Feeds</strong> plugin for a while now. Hopefully you're happy with it!&nbsp; If so, would you consider leaving a positive review? It really helps to support the plugin and helps others to discover it too!</p>
                    <p class='links'>
                        <a class='ctf_notice_dismiss' href='https://wordpress.org/support/view/plugin-reviews/custom-twitter-feeds' target='_blank'>Sure, I'd love to!</a>
                        &middot;
                        <a class='ctf_notice_dismiss' href='" .esc_url( add_query_arg( 'ctf_ignore_rating_notice_nag', '1' ) ). "'>No thanks</a>
                        &middot;
                        <a class='ctf_notice_dismiss' href='" .esc_url( add_query_arg( 'ctf_ignore_rating_notice_nag', '1' ) ). "'>I've already given a review</a>
                        &middot;
                        <a class='ctf_notice_dismiss' href='" .esc_url( add_query_arg( 'ctf_ignore_rating_notice_nag', 'later' ) ). "'>Ask Me Later</a>
                    </p>
                </div>
                <a class='ctf_notice_close' href='" .esc_url( add_query_arg( 'ctf_ignore_rating_notice_nag', '1' ) ). "'><i class='fa fa-close'></i></a>
            </div>
            ");

        }

    }
}

// variables to define certain terms
$transient = 'custom_twitter_feeds_rating_notice_waiting';
$option = 'ctf_rating_notice';
$nag = 'ctf_ignore_rating_notice_nag';

ctf_check_nag_get( $_GET, $nag, $option, $transient );
ctf_maybe_set_transient( $transient, $option );
$notice_status = get_option( $option, false );

// only display the notice if the time offset has passed and the user hasn't already dismissed it
if ( get_transient( $transient ) !== 'waiting' && $notice_status !== 'dismissed' ) {
    add_action( 'admin_notices', 'ctf_rating_notice_html' );
}
