jQuery(document).ready(function ($) {
	if($(".loklak_api").prop('checked')) {
		wpltf_update_twitter_auth(true);
	}

    $(".loklak_api").live('change', function() {
    	if($(this).is(':checked')){
	    	wpltf_update_twitter_auth(true);
	    }
	    else {
	    	wpltf_update_twitter_auth(false);
	    }
	});

	function wpltf_update_twitter_auth(arg) {
		if (arg == true) {
	    	$(".consumerKey").prop('disabled', arg);
			$(".consumerSecret").prop('disabled', arg);
			$(".accessToken").prop('disabled', arg);
			$(".accessTokenSecret").prop('disabled', arg);
		}
	    else {
	    	$(".consumerKey").prop('disabled', arg);
			$(".consumerSecret").prop('disabled', arg);
			$(".accessToken").prop('disabled', arg);
			$(".accessTokenSecret").prop('disabled', arg);
	    }
	}
	
});