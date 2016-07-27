jQuery(document).ready(function ($) {
	
	var timer, 
		lastResult;
	
	
	start();
	
	
	$('div[id*=widget-wptt_twittertweets]').on('ajaxComplete', function() {
		start();
	});
	
	$(document).on( "keyup", '.twitter_user_name', function() {
		clearTimeout(timer);
		var userInput = this.value,
			defaultValue =  $('.user-validator').prop("defaultValue"),
			delay = 500;
		if(userInput == '') {
			$('.user-validator')
				.removeClass('user-validator-valid user-validator-invalid')
				.html( defaultValue );
			
			return; 
		}
		$('.user-validator').html('checking...');

    	timer = setTimeout(function() {
    		validateScreenName( userInput );
    	}, delay);
    		
	});

	function validateScreenName( name ) {
			
		$.ajax({
			dataType: "json",
			url: ajaxurl,
			data: { screen_name: name ,action:'userValidate'},
			success: function(data) {
				setValidatorTo( data );
				lastResult = data;
			}
		});
	}
	
	function setValidatorTo( obj ) {
		$('.user-validator')
			.html(obj.data)
			.removeClass('user-validator-valid user-validator-invalid');
		$('.twitter_user_name').removeClass('user-validator-valid user-validator-invalid').addClass(obj.class);
	}
	
	function start() {
		
		if( lastResult )
			setValidatorTo( lastResult );
	}
});
