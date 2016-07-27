<div class="aptf-tweets-slider-wrapper aptf-slider-template-2" data-auto-slide ="<?php echo $auto_slide; ?>" data-slide-controls = "<?php echo $slide_controls; ?>" data-slide-duration="<?php echo $slide_duration; ?>"><?php
    if (is_array($tweets)) {

// to use with intents
        //echo '<script type="text/javascript" src="//platform.twitter.com/widgets.js"></script>';

        foreach ($tweets as $tweet) {
            //$this->print_array($tweet);
            ?>

            <div class="aptf-single-tweet-slide">
                <div class="aptf-tweet-content">
                    <?php if ($aptf_settings['display_username'] == 1) { ?><a href="http://twitter.com/<?php echo $username; ?>" class="aptf-tweet-name" target="_blank"><?php echo $username; ?></a><?php } ?>
                    <p class="aptf-timestamp">
                        <a href="https://twitter.com/<?php echo $username; ?>/status/<?php echo $tweet->id_str; ?>" class="aptf-tweet-name" target="_blank"> -
                            <?php echo $this->get_date_format($tweet->created_at, $aptf_settings['time_format']); ?>
                        </a>
                    </p>

                    <div class="clear"></div>
                    <?php
                        if ($tweet->text) {
                            $the_tweet = ' '.$tweet->text . ' '; //adding an extra space to convert hast tag into links
                            /*
                              Twitter Developer Display Requirements
                              https://dev.twitter.com/terms/display-requirements

                              2.b. Tweet Entities within the Tweet text must be properly linked to their appropriate home on Twitter. For example:
                              i. User_mentions must link to the mentioned user's profile.
                              ii. Hashtags must link to a twitter.com search with the hashtag as the query.
                              iii. Links in Tweet text must be displayed using the display_url
                              field in the URL entities API response, and link to the original t.co url field.
                             */

                            // i. User_mentions must link to the mentioned user's profile.
                            if (is_array($tweet->entities->user_mentions)) {
                                foreach ($tweet->entities->user_mentions as $key => $user_mention) {
                                    $the_tweet = preg_replace(
                                            '/@' . $user_mention->screen_name . '/i', '<a href="http://www.twitter.com/' . $user_mention->screen_name . '" target="_blank">@' . $user_mention->screen_name . '</a>', $the_tweet);
                                }
                            }

                            // ii. Hashtags must link to a twitter.com search with the hashtag as the query.
                            if (is_array($tweet->entities->hashtags)) {
                                foreach ($tweet->entities->hashtags as $hashtag) {
                                    $the_tweet = str_replace(' #' . $hashtag->text . ' ', ' <a href="https://twitter.com/search?q=%23' . $hashtag->text . '&src=hash" target="_blank">#' . $hashtag->text . '</a> ', $the_tweet);
                                }
                            }

                            // iii. Links in Tweet text must be displayed using the display_url
                            //      field in the URL entities API response, and link to the original t.co url field.
                            if (is_array($tweet->entities->urls)) {
                                foreach ($tweet->entities->urls as $key => $link) {
                                    $the_tweet = preg_replace(
                                            '`' . $link->url . '`', '<a href="' . $link->url . '" target="_blank">' . $link->url . '</a>', $the_tweet);
                                }
                            }

                            echo $the_tweet . ' ';
                            ?>
                    </div><!--tweet content-->
                    <?php 
                    } else {
                        ?>

                        <p><a href="http://twitter.com/'<?php echo $username; ?> " class="aptf-tweet-name" target="_blank"><?php _e('Click here to read ' . $username . '\'S Twitter feed', 'accesspress-twitter-feed'); ?></a></p>
                            <?php
                        }
                        ?>
                
                <!--Tweet Action -->
                <?php include(plugin_dir_path(__FILE__) . '../tweet-actions.php'); ?>
                <!--Tweet Action -->

            </div><!-- single_tweet_wrap-->
            <?php
        }
    }
    ?>
</div>
<?php if(isset($aptf_settings['display_follow_button']) && $aptf_settings['display_follow_button']==1){
    ?>
    <div class="aptf-seperator"></div>
    <?php 
    include(plugin_dir_path(__FILE__) . '../follow-btn.php');
}
?>