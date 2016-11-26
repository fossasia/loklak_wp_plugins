function twp_update_twitter_auth(arg) {
	if (arg == true) {
    	jQuery("#twitter-widget-pro-general-settings").addClass("closed");
    	jQuery(".twp_username_input").show();
    	jQuery(".twp_username_select").hide();
	}
    else {
    	jQuery("#twitter-widget-pro-general-settings").removeClass("closed");
    	jQuery(".twp_username_input").hide();
    	jQuery(".twp_username_select").show();
    }

    jQuery("#twitter-widget-pro-general-settings .handlediv").attr('aria-expanded', arg);
}


jQuery(function() {
	if(jQuery("#twp_loklak_api").prop('checked')){
		jQuery(".twp_username_input").show();
		twp_update_twitter_auth(true);
	}

    jQuery("#twp_loklak_api").live('change', function() {
    	if(jQuery(this).is(':checked')){
	    	twp_update_twitter_auth(true);
	    }
	    else {
	    	twp_update_twitter_auth(false);
	    }
	});
	
});