/*
 Script to cycle the rotating tweets
*/
function rotatingtweets_update_twitter_auth(arg) {
    jQuery("#rotatingtweets_api_key_input").prop('disabled', arg);
	jQuery("#rotatingtweets_api_secret_input").prop('disabled', arg);
	jQuery("#rotatingtweets_api_token_input").prop('disabled', arg);
	jQuery("#rotatingtweets_api_token_secret_input").prop('disabled', arg);
}
jQuery(document).ready(function() {
	if(jQuery("#rotatingtweets_api_loklak_api_input").prop('checked')){
		rotatingtweets_update_twitter_auth(true);
	}
	console.log("init");
    jQuery("#rotatingtweets_api_loklak_api_input").live('change', function() {
    	if(jQuery(this).is(':checked')){
	    	rotatingtweets_update_twitter_auth(true);
	    }
	    else {
	    	rotatingtweets_update_twitter_auth(false);
	    }
	});
	jQuery('.rotatingtweets').each(function() {
		/* Get the ID of the rotating tweets div - and parse it to get rotation speed and rotation fx */
		var rotate_id = "#"+this.id,
			rotate_class = "."+this.id,
			rotate_timeout = jQuery(this).data('cycle-timeout'),
			rotate_fx = jQuery(this).data('cycle-fx'),
			rotate_speed = jQuery(this).data('cycle-speed'),
			rotate_pager = jQuery(this).data('cycle-pager'),
			rotate_pager_template = jQuery(this).data('cycle-pager-template'),
			rotate_wp_debug = jQuery(this).hasClass('wp_debug');
		/* Handling old versions of jQuery that handle .data differently */
		if ( rotate_timeout === undefined ) {
			var rotate_id_split = rotate_id.split('_');
			rotate_timeout = rotate_id_split[1];
			rotate_fx = rotate_id_split[2];
			rotate_speed = rotate_id_split[3];
		}
		if( typeof console === "undefined" || typeof console.log === "undefined" ) {
			rotate_wp_debug = false;
		}
		/* If the rotation type has not been set - then set it to scrollUp */
		if(rotate_fx == null){rotate_fx = 'scrollUp'};
		var rt_height_px = 'auto';
		/* Now find the widget container width */
		// Take the smaller of the parent and grandparent
		var rt_parent = jQuery(rotate_id).parent(),
			rt_grandparent = jQuery(rotate_id).parent().parent();
		var rt_target_container_width = Math.min (
				rt_parent.innerWidth() - parseFloat(rt_parent.css('padding-left')) - parseFloat(rt_parent.css('padding-right')),
				rt_grandparent.innerWidth() - parseFloat(rt_grandparent.css('padding-left')) - parseFloat(rt_grandparent.css('padding-right'))  - parseFloat(rt_parent.css('padding-left')) - parseFloat(rt_parent.css('padding-right')) - parseFloat(rt_parent.css('margin-left')) - parseFloat(rt_parent.css('margin-right'))
			);
		// Get the size of the parent box and subtract any padding
		var rt_target_width = rt_target_container_width - parseFloat(jQuery(this).css('padding-left')) - parseFloat(jQuery(this).css('padding-right'))  - parseFloat(jQuery(this).css('margin-left')) - parseFloat(jQuery(this).css('margin-right'))  - parseFloat(jQuery(this).css('border-left')) - parseFloat(jQuery(this).css('border-right') ) ;
		var rt_fit = 1;
		if( rt_target_width == null ) {
			rt_fit = 0;
		}
		if(rotate_wp_debug) {
			console.log('============================');
			console.log('self_width = '+jQuery(rotate_id).innerWidth());
			console.log('parent_width = '+rt_parent.innerWidth());
			console.log('grandparent_width = '+rt_grandparent.innerWidth());
			console.log('rt_target_container_width = '+rt_target_container_width);
			console.log('rt_target_width = '+rt_target_width);
			console.log('rotate_timeout = '+rotate_timeout);
			console.log('rotate_speed = '+rotate_speed);
			console.log('rotate_fx = '+rotate_fx);
			console.log('rotate_pager = '+rotate_pager);
			console.log('rotate_pager_template = '+rotate_pager_template);
		}
		/* If we're displaying an 'official' tweet, reset all the heights - this option is currently switched off! */
//		var rt_official_child = rotate_id + ' .twitter-tweet';
//		var rt_official_num = jQuery(rt_official_child).length;
//		if (rt_official_num > 0) rt_height_px = '211px';
		var rotate_vars = {	
			pause: 1,
			height: rt_height_px,
			timeout: rotate_timeout,
			cleartypeNoBg: true,
			width: rt_target_width,
			prev: rotate_class + '_rtw_prev',
			next: rotate_class + '_rtw_next',
			fx: rotate_fx,
			fit: rt_fit,
			speed: rotate_speed
		}
		if( rotate_timeout > 0) {
			rotate_vars.timeout = rotate_timeout;
		} else {
			rotate_vars.continuous = true;
			rotate_vars.easing = 'linear';
		}
		if(typeof rotate_pager !== "undefined" ) {
			rotate_vars.pager = rotate_id + '_rtw_pager';
			if(typeof rotate_pager_template !== "undefined") {
				rotate_vars.pagerAnchorBuilder = function(idx, slide) { 
					return rotate_pager_template; 
				} 
			}
		}
		if(rotate_wp_debug) {
			console.log(rotate_vars);
		}
		/* Call the rotation */
		jQuery(rotate_id).cycle(rotate_vars);
		/* If the height of the rotating tweet box is zero - kill the box and start again */
		var rt_height = jQuery(rotate_id).height();
		if(rotate_wp_debug) {
			console.log('Initial height: '+rt_height );
		}
		if( rt_height < 1 ) {	
			var rt_children_id = rotate_id + ' .rotatingtweet';
			var rt_height = 0;
			/* Go through the tweets - get their height - and set the minimum height */
			jQuery(rt_children_id).each(function() {
				var rt_tweet_height = jQuery(this).height();
				if(rt_tweet_height > rt_height) {
					rt_height = rt_tweet_height;
				}
			});
			rt_height = rt_height + 20;
			rt_height_px = rt_height + 'px';
			rotate_vars.height = rt_height_px;
			if(rotate_wp_debug) {
				console.log('Resetting height to rt_height_px '+rt_height_px);
			}
			jQuery(rotate_id).cycle('destroy');
			jQuery(rotate_id).cycle(rotate_vars);
		}

		/* Only do this if we're showing the official tweets - the first select is the size of the info box at the top of the tweet */
		var rt_children_id = rotate_id + ' .rtw_info';
		/* This shows the width of the icon on 'official version 2' - i.e. the one where the whole tweet is indented */
		var rt_icon_id = rotate_id + ' .rtw_wide_icon a img';
		/* This shows the width of the block containing the icon on 'official version 2' - i.e. the one where the whole tweet is indented */
		var rt_block_id = rotate_id + ' .rtw_wide_block';
		var rt_official_num = jQuery(rt_children_id).length;
		var rt_children_meta_id = rotate_id + ' .rtw_meta';
		if(rt_official_num > 0) {
			/* Now run through and make sure all the boxes are the right size */
			if(jQuery(rt_icon_id).length > 0) {
				if(rotate_wp_debug) {
					console.log('Adjusting widths for \'Official Twitter Version 2\'');
					console.log('- Width of Rotating Tweets container: ' + jQuery(this).width());
					console.log('- Width of the icon container: ' + jQuery(rt_icon_id).show().width());
				};
				var rt_icon_width = 0;
				jQuery(rt_icon_id).each( function() {
					newiconsize = jQuery(this).width();
					if(newiconsize>rt_icon_width) {
						rt_icon_width = newiconsize;
					}
				});
				if(rotate_wp_debug) {
					console.log('- Width of the icon: '+rt_icon_width);
				};
				if(rt_icon_width > 0) {
/*
					jQuery(rt_block_id).each( function() {
						jQuery(this).css('padding-left', ( rt_icon_width + 10 ) + 'px');
					});
*/
					jQuery(rt_block_id).css('padding-left', ( rt_icon_width + 10 ) + 'px');
				}
			}
			/* Now get the padding-left dimension (if it exists) and subtract it from the max width	*/
			if(rotate_wp_debug) {
				console.log ('Now check for \'padding-left\'');
				console.log ('- leftpadding - text : '+ jQuery(rt_block_id).css('padding-left') + ' and value: ' +parseFloat(jQuery(rt_block_id).css('padding-left')));
			};
			var rt_max_width = jQuery(rotate_id).width();
			if( typeof jQuery(rt_block_id).css('padding-left') != 'undefined' ) {
				rt_max_width = rt_max_width - parseFloat(jQuery(rt_block_id).css('padding-left')) - 1 ;
				if(rotate_wp_debug) {
					console.log('- Padding is not undefined');
				};
			} else if(rotate_wp_debug) {
 				console.log('- Padding IS undefined - leave width unchanged');
			}
			if(rotate_wp_debug) {
				console.log('- rt_max_width: ' + rt_max_width);
			};
			/* Go through the tweets - and set the minimum width */
			jQuery(rt_children_id).width(rt_max_width);
			/* Go through the tweets - and set the minimum width */
			jQuery(rt_children_meta_id).width(rt_max_width);
		};
		// Now the responsiveness code
		// First get the measures we will use to track change
		var rt_resize_width_old_parent = rt_parent.innerWidth(),
			rt_resize_width_old_grandparent = rt_grandparent.innerWidth(),
			rt_resize_width_new_parent = rt_resize_width_old_parent,
			rt_resize_width_new_grandparent = rt_resize_width_old_grandparent,
			rt_resize_parent_change = 0,
			rt_resize_grandparent_change = 0;
		// Now get the starting measures
		var rt_resize_target_width = jQuery(rotate_id).width(),
			rt_resize_target_main = jQuery(rotate_id + ' .rtw_main').width(),
			rt_resize_target_tweet = jQuery(rotate_id + ' .rotatingtweet').width(),
			rt_resize_target_meta = jQuery(rotate_id + ' .rtw_meta').width();
		jQuery(window).resize(function() {
			if(rotate_wp_debug) {
				console.log("== Window Resize Detected ==");
			}
			rt_parent = jQuery(rotate_id).parent();
			rt_grandparent = rt_parent.parent();
			rt_resize_width_new_parent = rt_parent.innerWidth();
			rt_resize_width_new_grandparent = rt_grandparent.innerWidth();
			
			// Now calculate the largest and smallest change in size
			rt_resize_parent_change = rt_resize_width_new_parent - rt_resize_width_old_parent;
			rt_resize_grandparent_change = rt_resize_width_new_grandparent - rt_resize_width_old_grandparent;		
			
			// Now decide how much to change things
			rt_resize_change = rt_resize_parent_change;
			if(rt_resize_change == 0) {
				rt_resize_change = rt_resize_grandparent_change;
			}			
			if(rotate_wp_debug) {
				console.log('Parent change: '+rt_resize_parent_change);
				console.log('Grandparent change: '+rt_resize_grandparent_change);
				console.log('Old box width: '+rt_resize_target_width);
				console.log('New target width: '+ (rt_resize_target_width + rt_resize_change));
				console.log('rt_max_width: '+ (rt_resize_target_width + rt_resize_change));
			}
			if(rt_max_width == null) {
				rt_max_width = rt_resize_target_tweet;
			}
			if(rt_resize_change != 0) {
				var rt_oldheight = 0;
				var rt_oldcontainerheight = jQuery(rotate_id).height();
				jQuery(rotate_id + ' .rotatingtweet').height('auto');
				jQuery(rotate_id + ' .rotatingtweet').each( function() {
					var rt_test_height = jQuery(this).height();
					if(rotate_wp_debug) {
						console.log('Old tweet height: '+ rt_test_height);
					}
					if(rt_test_height > rt_oldheight ) {
						rt_oldheight = rt_test_height;
					};
				});
				if(rotate_wp_debug) {
					console.log('Old container height: '+ rt_oldcontainerheight);
					console.log('Old height: '+ rt_oldheight);
				}
				var rt_old_box_height = jQuery(rotate_id).height();
				if(rotate_wp_debug) {
					console.log('Old container height' + rt_old_box_height )
				}
				jQuery(rt_children_id).width(rt_max_width + rt_resize_change );
				jQuery(rt_children_meta_id).width(rt_max_width  + rt_resize_change );
				jQuery(rotate_id + ' .rtw_main').width(rt_resize_target_main  + rt_resize_change );
				jQuery(rotate_id + ' .rotatingtweet').width(rt_resize_target_tweet  + rt_resize_change );
				jQuery(rotate_id + ' .rtw_meta').width(rt_resize_target_meta  + rt_resize_change );
				jQuery(rotate_id).width(rt_resize_target_width + rt_resize_change );
				// Now update the variables
				rt_resize_target_width = rt_resize_target_width + rt_resize_change;
				rt_resize_target_main = rt_resize_target_main +  rt_resize_change;
				rt_resize_target_tweet = rt_resize_target_tweet  + rt_resize_change;
				rt_max_width = rt_max_width   + rt_resize_change;
				rt_resize_target_meta = rt_resize_target_meta   + rt_resize_change;
				rt_resize_width_old_parent = rt_parent.innerWidth();
				rt_resize_width_old_grandparent = rt_grandparent.innerWidth();
				// Now we need to fix the heights
				var rt_newheight = 0;
				jQuery(rotate_id + ' .rotatingtweet').height('auto');
				jQuery(rotate_id + ' .rotatingtweet').each( function() {
					var rt_test_height = jQuery(this).height();
					if(rotate_wp_debug) {
						console.log('New tweet height: '+ rt_test_height);
					}
					if(rt_test_height > rt_newheight ) {
						rt_newheight = rt_test_height;
					};
				});
				if(rotate_wp_debug) {
					console.log('New height: '+ rt_newheight);
				}
				if(rt_newheight > 0) {
					jQuery(rotate_id).height( Math.max( rt_oldcontainerheight + rt_newheight - rt_oldheight,rt_newheight) );
				}
			}
		});
	});
	// Script to show mouseover effects when going over the Twitter intents
	jQuery('.rtw_intents a').hover(function() {
		var rtw_src = jQuery(this).find('img').attr('src');
		var clearOutHovers = /_hover.png$/;
		jQuery(this).find('img').attr('src',rtw_src.replace(clearOutHovers,".png"));
		var rtw_src = jQuery(this).find('img').attr('src');
		var srcReplacePattern = /.png$/;
		jQuery(this).find('img').attr('src',rtw_src.replace(srcReplacePattern,"_hover.png"));
	},function() {
		var rtw_src = jQuery(this).find('img').attr('src');
		var clearOutHovers = /_hover.png/;
		jQuery(this).find('img').attr('src',rtw_src.replace(clearOutHovers,".png"));
	});
	jQuery('.rtw_wide .rtw_intents').hide();
	jQuery('.rtw_expand').show();
	jQuery('.rotatingtweets').has('.rtw_wide').hover(function() {
		jQuery(this).find('.rtw_intents').show();
	},function() {
		jQuery(this).find('.rtw_intents').hide();
	});
});
/* And call the Twitter script while we're at it! */
/* Standard script to call Twitter */
!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");