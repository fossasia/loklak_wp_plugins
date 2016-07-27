jQuery(document).ready(function($){

    $('.ai-color-field').wpColorPicker();
    if($("#ai_loklak_api").prop('checked')){
		console.log("init");
		ai_update_twitter_auth(true);
	}

    $("#ai_loklak_api").live('change', function() {
    	if($(this).is(':checked')){
	    	ai_update_twitter_auth(true);
	    }
	    else {
	    	ai_update_twitter_auth(false);
	    }
	});

	function ai_update_twitter_auth(arg) {
	    $("#ai_consumer_key").prop('disabled', arg);
		$("#ai_consumer_secret").prop('disabled', arg);
		$("#ai_access_token").prop('disabled', arg);
		$("#ai_access_token_secret").prop('disabled', arg);
	}

});