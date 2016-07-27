<?php $ok_url=plugins_url( '../images/rsz_11button_ok.png',__FILE__);
$cancel_url=plugins_url( '../images/rsz_1onebit_33.png',__FILE__);?>
<style>input.user-validator-valid {
background-color: #CFFAD7;
background-image:url('<?php echo $ok_url?>');
background-position:right;
background-repeat:no-repeat;
color: #2C823C;
font-weight:bold;
}
input.user-validator-invalid {
background-color: #FCCDC5;
background-image:url('<?php echo $cancel_url?>');
background-position:right;
color: #660011;
background-repeat:no-repeat;
font-weight:bold;
}
</style>


		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Widget Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($widget_title); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('name'); ?>">Twitter User Name: <input class="widefat twitter_user_name" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" /></label>
			<span class="widefat user-validator">Start entering your user name</span>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('store_time'); ?>">Tweets Cache Time (in minutes): <input class="widefat" id="<?php echo $this->get_field_id('store_time'); ?>" name="<?php echo $this->get_field_name('store_time'); ?>" type="text" value="<?php echo esc_attr($timeto_store); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tweets_cnt'); ?>">Number of Tweets to Fetch: <input class="widefat" id="<?php echo $this->get_field_id('tweets_cnt'); ?>" name="<?php echo $this->get_field_name('tweets_cnt'); ?>" type="text" value="<?php echo esc_attr($tweets_count); ?>" /></label>
		</p>
		<h4  style="width:100%; text-align:center;">Loklak API Settings</h4>
			<div style="padding:10px;">
				<p>
					<input class="checkbox loklak_api" type="checkbox" value="true" <?php checked( (isset( $instance['loklak_api']) && ($instance['loklak_api'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'loklak_api' ); ?>" name="<?php echo $this->get_field_name( 'loklak_api' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'loklak_api' ); ?>">Use anonymous <a href="http://loklak.org/">loklak.org</a> API instead of Twitter. <a href="http://loklak.org/">Find out more</a></label>
				</p>			
			</div>	
		<h4  style="width:100%; text-align:center;">Twitter API Settings</h4>
			<div style="padding:10px;">
				<p>
					<label for="<?php echo $this->get_field_id('consumerKey'); ?>">API key: <input class="widefat consumerKey" id="<?php echo $this->get_field_id('consumerKey'); ?>" name="<?php echo $this->get_field_name('consumerKey'); ?>" type="text" value="<?php echo esc_attr($consumerKey); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('consumerSecret'); ?>">API secret: <input class="widefat consumerSecret" id="<?php echo $this->get_field_id('consumerSecret'); ?>" name="<?php echo $this->get_field_name('consumerSecret'); ?>" type="text" value="<?php echo esc_attr($consumerSecret); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessToken'); ?>">Access Token: <input class="widefat accessToken" id="<?php echo $this->get_field_id('accessToken'); ?>" name="<?php echo $this->get_field_name('accessToken'); ?>" type="text" value="<?php echo esc_attr($accessToken); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessTokenSecret'); ?>">Access Token Secret: <input class="widefat accessTokenSecret" id="<?php echo $this->get_field_id('accessTokenSecret'); ?>" name="<?php echo $this->get_field_name('accessTokenSecret'); ?>" type="text" value="<?php echo esc_attr($accessTokenSecret); ?>" /></label>
				</p>
				
			</div>
			<h4  style="width:100%; text-align:center;">Advanced Options</h4>
			<div style="padding:10px;">
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['twitterIntents']) && ($instance['twitterIntents'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'twitterIntents' ); ?>" name="<?php echo $this->get_field_name( 'twitterIntents' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'twitterIntents' ); ?>">Show Twitter Intents</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['twitterIntentsText']) && ($instance['twitterIntentsText'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'twitterIntentsText' ); ?>" name="<?php echo $this->get_field_name( 'twitterIntentsText' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'twitterIntentsText' ); ?>">Hide Twitter Intents Text</label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('intentColor'); ?>">Colour for Intent icons: </label><input class="intentColor widefat" id="<?php echo $this->get_field_id('intentColor'); ?>" name="<?php echo $this->get_field_name('intentColor'); ?>" type="text" value="<?php echo esc_attr($color_intents); ?>" />
					<div id="colorpicker"></div>
				</p>
                                <p>
                                  <label for="<?php echo $this->get_field_id( 'slide_style' ); ?>">Style:</label>
					<select name="<?php echo $this->get_field_name( 'slide_style' ); ?>" id="<?php echo $this->get_field_id( 'slide_style' ); ?>" style="width: 100%;">
					    <option value="list" <?php if(isset($slide_style) && $slide_style=='list'){echo 'selected';} ?>>List</option>
					    <option value="slider" <?php if(isset($slide_style) && $slide_style=='slider'){echo 'selected';} ?>>Slider</option>
					</select>       
                                </p> 
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( ( isset( $instance['border_rad']) && ($instance['showAvatar'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'border_rad' ); ?>" name="<?php echo $this->get_field_name( 'border_rad' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'border_rad' ); ?>">Circular Avatar image</label>
				</p>  
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( ( isset( $instance['showAvatar']) && ($instance['showAvatar'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'showAvatar' ); ?>" name="<?php echo $this->get_field_name( 'showAvatar' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'showAvatar' ); ?>">Display avatar image</label>
				</p>
				
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( isset( $instance['replies_excl']), true ); ?> id="<?php echo $this->get_field_id( 'replies_excl' ); ?>" name="<?php echo $this->get_field_name( 'replies_excl' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'replies_excl' ); ?>">Exclude @replies</label>
				</p>
					<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeAgo']) && ($instance['timeAgo'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeAgo' ); ?>" name="<?php echo $this->get_field_name( 'timeAgo' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'timeAgo' ); ?>">Show "ago" after the time</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeRef']) && ($instance['timeRef'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeRef' ); ?>" name="<?php echo $this->get_field_name( 'timeRef' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'timeRef' ); ?>">Set Twitter like short time</label>
				</p>	
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( (isset( $instance['disp_scr_name']) && ($instance['disp_scr_name'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>" name="<?php echo $this->get_field_name( 'disp_scr_name' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>">Show Twitter Screen Name</label>
				</p>
				<p>
				    <label for="<?php echo $this->get_field_id( 'tweet_border' ); ?>">Show Twitter Widget Border:</label><br/>
					<select name="<?php echo $this->get_field_name( 'tweet_border' ); ?>" id="<?php echo $this->get_field_id( 'tweet_border' ); ?>" style="width: 100%;">
					<option value="true" <?php if(isset($tweet_border) && $tweet_border=='true'){echo 'selected';} ?>>Yes</option>
					<option value="false" <?php if(isset($tweet_border) && $tweet_border=='false'){echo 'selected';} ?>>No</option>
					</select>
				</p>
				<p>
				<label for="<?php echo $this->get_field_id( 'tweet_theme' ); ?>">Twitter Widget Theme:</label><br/>
					<select name="<?php echo $this->get_field_name( 'tweet_theme' ); ?>" id="<?php echo $this->get_field_id( 'tweet_theme' ); ?>" style="width: 100%;">
					<option value="light" <?php if(isset($tweet_theme) && $tweet_theme=='light'){echo 'selected';} ?>>Light</option>
					<option value="dark" <?php if(isset($tweet_theme) && $tweet_theme=='dark'){echo 'selected';} ?>>Dark</option>
					</select>
				</p>
			</div>
			
