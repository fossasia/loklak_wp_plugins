<?php
$classes[] = 'slider';
$classes[] = $widget_id;
?>
<ul class="fetched_tweets <?php echo $class; ?> <?php echo implode(' ', $classes); ?>" data-timeout="10000" data-speed="1000" data-animation="fade">
    <?php
			$tweets_count 			= $wpltf_wdgt_tweets_cnt; 		
			$name 				= $wpltf_wdgt_name;			
			$timeto_store 			= $wpltf_wdgt_store_time; 	
			$consumerSecret 	= trim($wpltf_wdgt_consumerSecret);
			$accessToken 		= trim($wpltf_wdgt_accessToken);
			$accessTokenSecret 	= trim($wpltf_wdgt_accessTokenSecret);
			$replies_excl 	= $widget_replies_excl;
			$consumerKey 		= trim($wpltf_wdgt_consumerKey);
			//$dataShowCount 		= ($wpltf_wdgt_dataShowCount != "true") ? "false" : "true";
			$disp_screen_name	= ($wpltf_wdgt_disp_scr_name != "true") ? "false" : "true";
			$intents_text = $wpltf_wdgt_twitterIntentsText; 
			$color_intents 		= $wpltf_wdgt_intentColor;
                        $slide_style 		= $wpltf_wdgt_slide_style; 
			$cache_transient 			= $wpltf_wdgt_timeRef;
			$alter_ago_time 			= $wpltf_wdgt_timeAgo;
			$twitterIntents		= $wpltf_wdgt_twitterIntents;
			$showAvatar 		= $wpltf_wdgt_showAvatar;
			$border_rad_avatar 		= $wpltf_wdgt_border_rad;
			$transName = 'list-tweets-'.$name; 
			$backupName = $transName . '-backup'; 
			if(false === ($tweets = get_transient($transName) ) ) :
                        $twitter_outh_path = WP_PLUGIN_DIR. '/wp-twitter-feeds/controller/twitteroauth/twitteroauth.php';
			require_once ($twitter_outh_path);

			$api_call = new TwitterOAuth(
				$consumerKey,   		
				$consumerSecret,   
				$accessToken,   	
				$accessTokenSecret
			);
			$totalToFetch = ($replies_excl) ? max(50, $tweets_count * 3) : $tweets_count;
			
			$fetchedTweets = $api_call->get(
				'statuses/user_timeline',
				array(
					'screen_name'     => $name,
					'count'           => $totalToFetch,
					'replies_excl' => $replies_excl
				)
			);
			
			if($api_call->http_code != 200) :
				$tweets = get_option($backupName);

			else :
				$limitToDisplay = min($tweets_count, count($fetchedTweets));
				
				for($i = 0; $i < $limitToDisplay; $i++) :
					$tweet = $fetchedTweets[$i];
			    	$name = $tweet->user->name;
			    	$screen_name = $tweet->user->screen_name;
			    	$permalink = 'http://twitter.com/'. $name .'/status/'. $tweet->id_str;
			    	$tweet_id = $tweet->id_str;
			    	$image = $tweet->user->profile_image_url;
					$text = $this->sanitize_links($tweet);
			    	$time = $tweet->created_at;
			    	$time = date_parse($time);
			    	$uTime = mktime($time['hour'], $time['minute'], $time['second'], $time['month'], $time['day'], $time['year']);
			    	$tweets[] = array(
			    		'text' => $text,
			    		'scr_name'=>$screen_name,
			    		'favourite_count'=>$tweet->favorite_count,
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
			if(!function_exists('twitter_time_diff'))
			{
				function twitter_time_diff( $from, $to = '' ) {
				    $diff = human_time_diff($from,$to);
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
				    return strtr($diff,$replace);
				}
			}
			if($tweets) : ?>
			    <?php foreach($tweets as $t) : ?>
			        <li class="tweets_avatar">
			        	<?php
			        	echo '<div class="tweet_wrap"><div class="wdtf-user-card ltr">';
			        		if ($showAvatar){
			        			
			        			echo '<img ';
			        			echo 'width="45px" height="45px"';
			        			echo 'src="'.$t['image'].'" alt="Tweet Avatar" class="';
		        				echo ($border_rad_avatar) ? 'circular':'';
			        			echo '"/>';
			        		}
			        		if(!isset($screen_name)){$screen_name = $name;}
			        	
			        		if($disp_screen_name!='false')
			        		{
			        			echo '<div class="wdtf-screen-name">';
			        			echo "<span class=\"screen_name\">{$t['name']}</span><br>";
			        			echo "<a href=\"https://twitter.com/{$screen_name}\" target=\"_blank\" dir=\"ltr\">@{$screen_name}</a></div>";
			        			
			        		}
			        	echo '<div class="clear"></div></div>';	
			        	?>
			       		<div class="tweet_data">
			        	<?php 
			        		
			        		//echo $t['text']; ?>
			        	</div>
			            <br/>
			            <div class="clear"></div>
			            <div class="times">
			            <em>
			            
						<a href="http://www.twitter.com/<?php echo $screen_name; ?>" target="_blank" title="Follow <?php echo $name; ?> on Twitter [Opens new window]">
							<?php
								if($cache_transient == "true"){
									$timeDisplay = twitter_time_diff($t['time'], current_time('timestamp'));
								}else{
									$timeDisplay = human_time_diff($t['time'], current_time('timestamp'));
								}
								if($alter_ago_time == "true"){
									$displayAgo = " ago";
								}
								printf(__('%1$s%2$s'), $timeDisplay, $displayAgo);

							?>
							</a>
			            </em>
			            </div>
						<?php if($twitterIntents == "true"){
						?>       
<div class="tweets-intent-data">
<?php if($t['favourite_count']!=0 || $t['retweet_count']!=0){?>
<span class="stats-narrow customisable-border"><span class="stats" data-scribe="component:stats">
 <?php if($t['retweet_count']!=0)
	{?>
  <a href="https://twitter.com/<?php echo $screen_name; ?>/statuses/<?php echo $t['tweet_id']; ?>" title="View Tweet on Twitter" data-scribe="element:favorite_count" target="_blank">
    <span class="stats-favorites">
      <strong><?php echo $t['retweet_count'];?></strong> retweet<?php if($t['retweet_count']>1)echo's';?>
    </span>
  </a>
  <?php } ?>
<?php if($t['favourite_count']!=0)
	{?>
  <a href="https://twitter.com/<?php echo $screen_name; ?>/statuses/<?php echo $t['tweet_id']; ?>" title="View Tweet on Twitter" data-scribe="element:favorite_count" target="_blank">
    <span class="stats-favorites">
      <strong><?php echo $t['favourite_count'];?></strong> Favorite<?php if($t['favourite_count']>1)echo's';?>
    </span>
  </a>
  <?php }?>
  
</span>
</span>
<div class="clear"></div>
<div class="seperator_wpltf"></div>
<?php }?>
      <ul class="tweet-actions " role="menu" >
  <li><a href="http://twitter.com/intent/tweet?in_reply_to=<?php echo $t['tweet_id']; ?>" data-lang="en" class="in-reply-to" title="Reply" target="_blank"><span aria-hidden="true" data-icon="&#xf079;" <?php echo ($color_intents) ? 'style="color:'.$color_intents.';"' :''; ?>></span></a></li>
  <li><a href="http://twitter.com/intent/retweet?tweet_id=<?php echo $t['tweet_id']; ?>" data-lang="en" class="retweet" title="Retweet" target="_blank"><span aria-hidden="true" data-icon="&#xf112;" <?php echo ($color_intents) ? 'style="color:'.$color_intents.';"' :''; ?>></span></a></li>
  <li><a href="http://twitter.com/intent/favorite?tweet_id=<?php echo $t['tweet_id']; ?>" data-lang="en" class="favorite" title="Favorite" target="_blank"><span aria-hidden="true" data-icon="&#xf005;" <?php echo ($color_intents) ? 'style="color:'.$color_intents.';"' :''; ?>></span></a></li>
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
