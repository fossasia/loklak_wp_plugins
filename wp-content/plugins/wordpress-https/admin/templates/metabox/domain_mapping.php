<form name="<?php echo $this->getPlugin()->getSlug(); ?>_domain_mapping_form" id="<?php echo $this->getPlugin()->getSlug(); ?>_domain_mapping_form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field($this->getPlugin()->getSlug()); ?>
<input type="hidden" name="action" id="action" value="" />

<p><?php printf( __('Domain mapping allows you to map external domains that host their HTTPS content on a different domain. You may use %s regular expressions %s','wordpress-https'),'<a href="#TB_inline?height=155&width=350&inlineId=regex-help&" class="thickbox" title="' . __('Regular Expressions Help','wordpress-https') . '">', '</a>') ; ?>.</p>

<table class="form-table" id="domain_mapping">
	<thead>
	</thead>
<?php
	$ssl_host_mapping = ( is_array($this->getPlugin()->getSetting('ssl_host_mapping')) ? $this->getPlugin()->getSetting('ssl_host_mapping') : array() );
	foreach( $ssl_host_mapping as $http_domain => $https_domain ) {
?>
	<tr valign="top" class="domain_mapping_row">
		<td class="http_scheme">
			<span class="label">http://</span>
		</td>
		<td class="http_domain">
			<input type="text" name="http_domain[]" value="<?php echo $http_domain; ?>" />
		</td>
		<td class="arrow">
			<span class="label">&gt;</span>
		</td>
		<td class="https_scheme">
			<span class="label">https://</span>
		</td>
		<td class="https_domain">
			<input type="text" name="https_domain[]" value="<?php echo $https_domain; ?>" />
		</td>
		<td class="controls">
			<a class="remove" href="#" title="<?php _e('Remove URL Filter','wordpress-https'); ?>"><?php _e('Remove','wordpress-https'); ?></a>
			<a class="add" href="#" title="<?php _e('Add URL Filter','wordpress-https'); ?>"><?php _e('Add','wordpress-https'); ?></a>
		</td>
	</tr>

<?php } ?>
	<tr valign="top" class="domain_mapping_row">
		<td class="http_scheme">
			<span class="label">http://</span>
		</td>
		<td class="http_domain">
			<input type="text" name="http_domain[]" value="" />
		</td>
		<td class="arrow">
			<span class="label">&gt;</span>
		</td>
		<td class="https_scheme">
			<span class="label">https://</span>
		</td>
		<td class="https_domain">
			<input type="text" name="https_domain[]" value="" />
		</td>
		<td class="controls">
			<a class="remove" href="#" title="<?php _e('Remove URL Filter','wordpress-https'); ?>"><?php _e('Remove','wordpress-https'); ?></a>
			<a class="add" href="#" title="<?php _e('Add URL Filter','wordpress-https'); ?>"><?php _e('Add','wordpress-https'); ?></a>
		</td>
	</tr>
</table>

<p class="button-controls">
	<input type="submit" name="domain-mapping-save" value="<?php _e('Save Changes','wordpress-https'); ?>" class="button-primary" id="domain-mapping-save" />
	<input type="submit" name="domain-mapping-reset" value="<?php _e('Reset','wordpress-https'); ?>" class="button-secondary" id="domain-mapping-reset" />
	<img alt="<?php _e('Waiting...','wordpress-https'); ?>" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting submit-waiting" />
</p>
</form>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var form = $('#<?php echo $this->getPlugin()->getSlug(); ?>_domain_mapping_form').first();
	$('#domain-mapping-save').click(function() {
		$(form).find('input[name="action"]').val('<?php echo $this->getPlugin()->getSlug(); ?>_domain_mapping_save');
	});
	$('#domain-mapping-reset').click(function() {
		$(form).find('input[name="action"]').val('<?php echo $this->getPlugin()->getSlug(); ?>_domain_mapping_reset');
	});
	$(form).submit(function(e) {
		e.preventDefault();
		$(form).find('.submit-waiting').show();
		$.post(ajaxurl, $(form).serialize(), function(response) {
			$(form).find('.submit-waiting').hide();
			$('#message-body').html(response).fadeOut(0).fadeIn().delay(5000).fadeOut();
		});
	});

	if ( $('#domain_mapping tr').length <= 1 ) {
		$('#domain_mapping .remove').hide();
	} else {
		$('#domain_mapping .remove').show();
		$('#domain_mapping .add').hide();
		$('#domain_mapping tr:last-child .add').show();
	}

	$('.domain_mapping_row .add').live('click', function(e) {
		e.preventDefault();
		var row = $(this).parents('tr').clone();
		row.find('input').val('');
		$(this).parents('table').append(row);
		$(this).hide();
		$('#domain_mapping .remove').show();
		return false;
	});

	$('.domain_mapping_row .remove').live('click', function(e) {
		e.preventDefault();
		$(this).parents('tr').remove();
		if ( $('#domain_mapping tr').length <= 1 ) {
			$('#domain_mapping .remove').hide();
		} else {
			$('#domain_mapping .remove').show();
		}
		$('#domain_mapping .add').hide();
		$('#domain_mapping tr:last-child .add').show();
		return false;
	});

	$('#domain_mapping-reset').click(function(e, el) {
	   if ( ! confirm('<?php _e('Are you sure you want to reset all WordPress HTTPS domain mappings?','wordpress-https'); ?>') ) {
			e.preventDefault();
			return false;
	   }
	});
});
</script>