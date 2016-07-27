<div class="aptf-tweets-wrapper aptf-template-2"><?php
    if (is_array($tweets)) {

// to use with intents
        //echo '<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>';

        foreach ($tweets as $tweet) {
            //$this->print_array($tweet);
            $tweet = (object)$tweet;
            $loklak = $aptf_settings['loklak_api'];
            ?>

            <div class="aptf-single-tweet-wrapper">
                <div class="aptf-tweet-content">
                    <?php if ($aptf_settings['display_username'] == 1) { ?><a href="http://twitter.com/<?php echo $username; ?>" class="aptf-tweet-name" target="_blank"><?php echo $display_name; ?></a> <span class="aptf-tweet-username"><?php echo $username; ?></span> <?php } ?>
                    <div class="clear"></div>
                   <?php
                        if ($tweet->text) {
                            $the_tweet = ' '.$tweet->text . ' '; //adding an extra space to convert hast tag into links
                            
                            // i. User_mentions must link to the mentioned user's profile.
                            if ( isset($tweet->entities->user_mentions) && is_array($tweet->entities->user_mentions)) {
                                foreach ($tweet->entities->user_mentions as $key => $user_mention) {
                                    $the_tweet = preg_replace(
                                            '/@' . $user_mention->screen_name . '/i', '<a href="http://www.twitter.com/' . $user_mention->screen_name . '" target="_blank">@' . $user_mention->screen_name . '</a>', $the_tweet);
                                }
                            }
                            else if ( isset($tweet->mentions) && is_array($tweet->mentions)) {
                                foreach ($tweet->mentions as $user_mention) {
                                    $the_tweet = preg_replace(
                                            '/@' . $user_mention . '/i', '<a href="http://www.twitter.com/' . $user_mention . '" target="_blank">@' . $user_mention . '</a>', $the_tweet);
                                }
                            }

                            // ii. Hashtags must link to a twitter.com search with the hashtag as the query.
                            if ( isset($tweet->entities->hashtags) && is_array($tweet->entities->hashtags)) {
                                foreach ($tweet->entities->hashtags as $hashtag) {
                                    $the_tweet = str_replace(' #' . $hashtag->text . ' ', ' <a href="https://twitter.com/search?q=%23' . $hashtag->text . '&src=hash" target="_blank">#' . $hashtag->text . '</a> ', $the_tweet);
                                }
                            }

                            else if ( isset($tweet->hashtags) && is_array($tweet->hashtags)) {
                                foreach ($tweet->hashtags as $hashtag) {
                                    $the_tweet = str_replace(' #' . $hashtag . ' ', ' <a href="https://twitter.com/search?q=%23' . $hashtag . '&src=hash" target="_blank">#' . $hashtag . '</a> ', $the_tweet);
                                }
                            }

                            // iii. Links in Tweet text must be displayed using the display_url
                            //      field in the URL entities API response, and link to the original t.co url field.
                            
                            if ( isset($tweet->entities->urls) && is_array($tweet->entities->urls)) {
                                foreach ($tweet->entities->urls as $key => $link) {
                                    $the_tweet = preg_replace(
                                            '`' . $link->url . '`', '<a href="' . $link->url . '" target="_blank">' . $link->url . '</a>', $the_tweet);
                                }
                            }
                            else if ( isset($tweet->links) && is_array($tweet->links)) {
                                foreach ($tweet->links as $link) {
                                    $the_tweet = preg_replace(
                                            '`' . $link . '`', '<a href="' . $link . '" target="_blank">' . $link . '</a>', $the_tweet);
                                }
                            }

                            echo $the_tweet . ' ';
                            ?>
                    </div><!--tweet content-->
                    <div class="aptf-tweet-date">
                        <p class="aptf-timestamp">
                            <a href="https://twitter.com/<?php echo $username; ?>/status/<?php echo $tweet->id_str; ?>" target="_blank"> -
                                <?php echo $this->get_date_format($tweet->created_at, $aptf_settings['time_format']); ?>
                            </a>
                        </p>
                    </div><!--tweet_date-->
                        <?php
                    } else {
                        ?>

                        <p><a href="http://twitter.com/'<?php echo $username; ?> " target="_blank"><?php _e('Click here to read ' . $username . '\'S Twitter feed', 'accesspress-twitter-feed'); ?></a></p>
                        <?php
                    }
                    ?>
                
                <?php if (isset($aptf_settings['display_twitter_actions']) && $aptf_settings['display_twitter_actions'] == 1) { ?>
                    <!--Tweet Action -->
                    <?php include(plugin_dir_path(__FILE__) . '../tweet-actions.php'); ?>
                    <!--Tweet Action -->
                <?php } ?>
            </div><!-- single_tweet_wrap-->
            <?php
        }
    }
    ?>
</div>
<?php if(isset($aptf_settings['display_follow_button']) && $aptf_settings['display_follow_button']==1){
    include(plugin_dir_path(__FILE__) . '../follow-btn.php');
}
?>