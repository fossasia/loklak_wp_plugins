<p>
			<label for="<?php echo $this->get_field_id('title'); ?>">Widget Title: <input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($widget_title); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('name'); ?>">Twitter Name (excluding @ symbol): <input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('store_time'); ?>">Tweets Cache Time (in minutes): <input class="widefat" id="<?php echo $this->get_field_id('store_time'); ?>" name="<?php echo $this->get_field_name('store_time'); ?>" type="text" value="<?php echo esc_attr($timeto_store); ?>" /></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('tweets_cnt'); ?>">Number of Tweets to Fetch: <input class="widefat" id="<?php echo $this->get_field_id('tweets_cnt'); ?>" name="<?php echo $this->get_field_name('tweets_cnt'); ?>" type="text" value="<?php echo esc_attr($tweets_count); ?>" /></label>
		</p>
		
<h4  style="width:100%; text-align:center;">Twitter API Options</h4>
			<div style="padding:10px;">
				<p>
					<label for="<?php echo $this->get_field_id('consumerKey'); ?>">API Key: <input class="widefat" id="<?php echo $this->get_field_id('consumerKey'); ?>" name="<?php echo $this->get_field_name('consumerKey'); ?>" type="text" value="<?php echo esc_attr($consumerKey); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('consumerSecret'); ?>">API Secret: <input class="widefat" id="<?php echo $this->get_field_id('consumerSecret'); ?>" name="<?php echo $this->get_field_name('consumerSecret'); ?>" type="text" value="<?php echo esc_attr($consumerSecret); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessToken'); ?>">Access Token: <input class="widefat" id="<?php echo $this->get_field_id('accessToken'); ?>" name="<?php echo $this->get_field_name('accessToken'); ?>" type="text" value="<?php echo esc_attr($accessToken); ?>" /></label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('accessTokenSecret'); ?>">Access Token Secret: <input class="widefat" id="<?php echo $this->get_field_id('accessTokenSecret'); ?>" name="<?php echo $this->get_field_name('accessTokenSecret'); ?>" type="text" value="<?php echo esc_attr($accessTokenSecret); ?>" /></label>
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
					<label for="<?php echo $this->get_field_id('intentColor'); ?>">Colour for Intent icons: <input class="intentColor" id="<?php echo $this->get_field_id('intentColor'); ?>" name="<?php echo $this->get_field_name('intentColor'); ?>" type="text" value="<?php echo esc_attr($color_intents); ?>" /></label>
					<div id="colorpicker"></div>
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
					<label for="<?php echo $this->get_field_id('avatarSize'); ?>">Size of Avatar (default: 48): <input class="widefat" id="<?php echo $this->get_field_id('avatarSize'); ?>" name="<?php echo $this->get_field_name('avatarSize'); ?>" type="text" value="<?php echo esc_attr($avatarSize); ?>" /><br><em>only numeric values allowed</em></label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( isset( $instance['replies_excl']), true ); ?> id="<?php echo $this->get_field_id( 'replies_excl' ); ?>" name="<?php echo $this->get_field_name( 'replies_excl' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'replies_excl' ); ?>">Exclude_@replies</label>
				</p>
					<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeAgo']) && ($instance['timeAgo'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeAgo' ); ?>" name="<?php echo $this->get_field_name( 'timeAgo' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'timeAgo' ); ?>">Show "ago" after the time</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" value="true" <?php checked( (isset( $instance['timeRef']) && ($instance['timeRef'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'timeRef' ); ?>" name="<?php echo $this->get_field_name( 'timeRef' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'timeRef' ); ?>">Change to Twitter style short time</label>
				</p>	
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( (isset( $instance['disp_scr_name']) && ($instance['disp_scr_name'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>" name="<?php echo $this->get_field_name( 'disp_scr_name' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'disp_scr_name' ); ?>">Show Twitter Screen Name</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( (isset( $instance['twitterFollow']) && ($instance['twitterFollow'] == "on") ), true ); ?> id="<?php echo $this->get_field_id( 'twitterFollow' ); ?>" name="<?php echo $this->get_field_name( 'twitterFollow' ); ?>" />
				    <label for="<?php echo $this->get_field_id( 'twitterFollow' ); ?>">Show Twitter Follow Button</label>
				</p>
				<p>
				    <input class="checkbox" type="checkbox" <?php checked( (isset( $instance['dataShowCount']) && ($instance['dataShowCount'] == "true") ), true ); ?> id="<?php echo $this->get_field_id( 'dataShowCount' ); ?>" name="<?php echo $this->get_field_name( 'dataShowCount' ); ?>" value="true" />
				    <label for="<?php echo $this->get_field_id( 'dataShowCount' ); ?>">Show Twitter Followers Count</label>
				</p>
				<p>
					<label for="<?php echo $this->get_field_id('dataLang'); ?>">Tweets Language: <input class="widefat" id="<?php echo $this->get_field_id('dataLang'); ?>" name="<?php echo $this->get_field_name('dataLang'); ?>" type="text" value="<?php echo esc_attr($dataLang); ?>" /></label>
				</p>
			</div>
