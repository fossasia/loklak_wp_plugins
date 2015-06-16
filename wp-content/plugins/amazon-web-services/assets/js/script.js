(function($) {

	$(document).ready(function() {

		$('.aws-settings').each(function() {
			var $container = $(this);

			$('.reveal-form a', $container).click(function() {
				var $form = $('form', $container);
				if ('block' == $form.css('display')) {
					$form.hide();
				}
				else {
					$form.show();
				}
				return false;
			});
		});

		$('.button.remove-keys').click(function() {
			$('input[name=secret_access_key],input[name=access_key_id]').val('');
		});

	});

})(jQuery);