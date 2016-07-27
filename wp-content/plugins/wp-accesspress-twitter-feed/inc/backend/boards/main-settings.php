<div class="aptf-single-board-wrapper" id="aptf-settings-board">
    <h3><?php _e('Settings', 'accesspress-twitter-feed'); ?></h3>
     <div class="aptf-option-wrapper">
        <label>Loklak API</label>
        <div class="aptf-option-field">
            <input type="checkbox" name="loklak_api" value="1" <?php checked($aptf_settings['loklak_api'],true);?>/>
            <div class="aptf-option-note"><?php _e('Check to use anonymous API of <a href="http://loklak.org/">loklak.org</a> and get plugin data through loklak (no registration and authentication required). <a href="http://loklak.org/">Find out more</a> ', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label>Twitter Consumer Key</label>
        <div class="aptf-option-field">
            <input type="text" name="consumer_key" value="<?php echo esc_attr($aptf_settings['consumer_key']); ?>"/>
            <div class="aptf-option-note"><?php _e('Please create an app on Twitter through this link:', 'accesspress-twitter-feed'); ?><a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a><?php _e(' and get this information.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label>Twitter Consumer Secret</label>
        <div class="aptf-option-field">
            <input type="text" name="consumer_secret" value="<?php echo esc_attr($aptf_settings['consumer_secret']); ?>"/>
            <div class="aptf-option-note"><?php _e('Please create an app on Twitter through this link:', 'accesspress-twitter-feed'); ?><a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a><?php _e(' and get this information.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label>Twitter Access Token</label>
        <div class="aptf-option-field">
            <input type="text" name="access_token" value="<?php echo esc_attr($aptf_settings['access_token']); ?>"/>
            <div class="aptf-option-note"><?php _e('Please create an app on Twitter through this link:', 'accesspress-twitter-feed'); ?><a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a><?php _e(' and get this information.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label>Twitter Access Token Secret</label>
        <div class="aptf-option-field">
            <input type="text" name="access_token_secret" value="<?php echo esc_attr($aptf_settings['access_token_secret']); ?>"/>
            <div class="aptf-option-note"><?php _e('Please create an app on Twitter through this link:', 'accesspress-twitter-feed'); ?><a href="https://dev.twitter.com/apps" target="_blank">https://dev.twitter.com/apps</a><?php _e(' and get this information.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Twitter Username','accesspress-twitter-feed');?></label>
        <div class="aptf-option-field">
            <input type="text" name="twitter_username" value="<?php echo isset($aptf_settings['twitter_username']) ? $aptf_settings['twitter_username'] : ''; ?>" placeholder="e.g: @apthemes"/>
            <div class="aptf-option-note"><?php _e('Please enter the username of twitter account from which the feeds need to be fetched.For example:@apthemes', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Twitter Account Name','accesspress-twitter-feed');?></label>
        <div class="aptf-option-field">
            <input type="text" name="twitter_account_name" value="<?php echo isset($aptf_settings['twitter_account_name']) ? $aptf_settings['twitter_account_name'] : ''; ?>" placeholder="e.g: AccessPress Themes"/>
            <div class="aptf-option-note"><?php _e('Please enter the account name to be displayed.For example:AccessPress Themes', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label>Cache Period</label>
        <div class="aptf-option-field">
            <input type="text" name="cache_period" value="<?php echo esc_attr($aptf_settings['cache_period']); ?>" placeholder="e.g: 60"/>
            <div class="aptf-option-note"><?php _e('Please enter the time period in minutes in which the feeds should be fetched.Default is 60 Minutes', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Total Number of Feed', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <input type="number" name="total_feed" value="<?php echo isset($aptf_settings['total_feed']) ? esc_attr($aptf_settings['total_feed']) : ''; ?>" placeholder="e.g: 5"/>
            <div class="aptf-option-note"><?php _e('Please enter the number of feeds to be fetched.Default number of feeds is 5.And please don\'t forget to delete cache once you change the number of tweets using delete cache button below.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Feeds Template', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <?php for ($i = 1; $i <= 3; $i++) {
                ?>
                <label><input type="radio" name="feed_template" value="template-<?php echo $i; ?>" <?php checked($aptf_settings['feed_template'], 'template-' . $i); ?>/>Template <?php echo $i; ?></label>
                <?php
            }
            ?>

        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Time Format', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <label><input type="radio" name="time_format" value="full_date" <?php checked($aptf_settings['time_format'],'full_date');?>/><?php _e('Full Date and Time: <span>e.g March 10, 2001, 5:16 pm</span>', 'accesspress-twitter-feed'); ?></label>
            <label><input type="radio" name="time_format" value="date_only" <?php checked($aptf_settings['time_format'],'date_only');?>/><?php _e('Date only: <span>e.g March 10, 2001</span>', 'accesspress-twitter-feed'); ?></label>
            <label><input type="radio" name="time_format" value="elapsed_time" <?php checked($aptf_settings['time_format'],'elapsed_time');?>/><?php _e('Elapsed Time: <span>e.g 12 hours ago</span>', 'accesspress-twitter-feed'); ?></label>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Display Username', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <input type="checkbox" name="display_username" value="1" <?php checked($aptf_settings['display_username'],true);?>/>
            <div class="aptf-option-note"><?php _e('Check if you want to show your username in each tweet', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Display Twitter Actions(Reply, Retweet, Favorite)', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <input type="checkbox" name="display_twitter_actions" value="1" <?php checked($aptf_settings['display_twitter_actions'],true);?>/>
            <div class="aptf-option-note"><?php _e('Check if you want to display twitter actions', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Fallback Unavailable Message', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <input type="text" name="fallback_message" value="<?php echo isset($aptf_settings['fallback_message']) ? esc_attr($aptf_settings['fallback_message']) : ''; ?>"/>
            <div class="aptf-option-note"><?php _e('Please enter the message to display if the twitter API is unavailable sometime.', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php _e('Display Twitter Follow Button', 'accesspress-twitter-feed'); ?></label>
        <div class="aptf-option-field">
            <input type="checkbox" name="display_follow_button" value="1" <?php checked($aptf_settings['display_follow_button'],true);?>/>
            <div class="aptf-option-note"><?php _e('Check if you want to display twitter follow button at the end of the feeds', 'accesspress-twitter-feed'); ?></div>
        </div>
    </div>
    <div class="aptf-option-wrapper">
        <label><?php ?></label>
    </div>

</div>