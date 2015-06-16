jQuery(function($){
	$('#wp-sendgrid-test-settings').click(function(event){
		event.preventDefault();

		var $responseMsg = $('#wp-sendgrid-test-settings-response');
		var $spinner = $('#setting-error-wp_sendgrid_settings_updated').find('.spinner');

		$spinner.css( 'display', 'inline-block');

		$.post(
			ajaxurl,
			{
				'action': 'wp_sendgrid_check_settings'
			},
			function(response) {
				if ( response.error ) {
					$responseMsg.removeClass('success').addClass('error').text(response.error);
				} else {
					$responseMsg.removeClass('error').addClass('success').text(response.success).show();
				}
				$spinner.hide();
			},
			'json'
		);

		return false;
	})
});