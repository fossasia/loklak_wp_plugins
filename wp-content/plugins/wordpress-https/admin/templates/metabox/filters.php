<form name="<?php echo $this->getPlugin()->getSlug(); ?>_filters_form" id="<?php echo $this->getPlugin()->getSlug(); ?>_filters_form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field($this->getPlugin()->getSlug()); ?>
<input type="hidden" name="action" id="action" value="" />

<table class="form-table">
	<tr valign="top" id="secure_filter_row">
		<th scope="row">
			<?php _e('Secure Filters','wordpress-https'); ?>
			<p class="description"><?php printf( __("Example: If you have an E-commerce shop and all of the URL's begin with /store/, you could secure all store links by entering '/store/' on one line. You may use %s regular expressions %s",'wordpress-https'),'<a href="#TB_inline?height=155&width=350&inlineId=regex-help" class="thickbox" title="' . __('Regular Expressions Help','wordpress-https') . '">','</a>'); ?>.</p>
		</th>
		<td>
			<textarea name="secure_filter" id="secure_filter"><?php echo implode("\n", $this->getPlugin()->getSetting('secure_filter')); ?></textarea>
		</td>
	</tr>
</table>

<p class="button-controls">
	<input type="submit" name="filters-save" value="<?php _e('Save Changes','wordpress-https'); ?>" class="button-primary" id="filters-save" />
	<input type="submit" name="filters-reset" value="<?php _e('Reset','wordpress-https'); ?>" class="button-secondary" id="filters-reset" />
	<img alt="<?php _e('Waiting...','wordpress-https'); ?>" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting submit-waiting" />
</p>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
	var form = $('#<?php echo $this->getPlugin()->getSlug(); ?>_filters_form').first();
	$('#filters-save').click(function() {
		$(form).find('input[name="action"]').val('<?php echo $this->getPlugin()->getSlug(); ?>_filters_save');
	});
	$('#filters-reset').click(function() {
		$(form).find('input[name="action"]').val('<?php echo $this->getPlugin()->getSlug(); ?>_filters_reset');
	});
	$(form).submit(function(e) {
		e.preventDefault();
		$(form).find('.submit-waiting').show();
		$.post(ajaxurl, $(form).serialize(), function(response) {
			$(form).find('.submit-waiting').hide();
			$('#message-body').html(response).fadeOut(0).fadeIn().delay(5000).fadeOut();
		});
	});

	$('#filters-reset').click(function(e, el) {
	   if ( ! confirm('<?php _e('Are you sure you want to reset all WordPress HTTPS filters?','wordpress-https'); ?>') ) {
			e.preventDefault();
			return false;
	   }
	});
});
</script>