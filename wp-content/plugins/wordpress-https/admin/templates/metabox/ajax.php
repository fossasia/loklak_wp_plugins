<?php 
	$nonce = wp_create_nonce($this->getPlugin()->getSlug());
?><script type="text/javascript">
jQuery(document).ready(function($) {
	var loading = $('<img alt="Loading..." src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="loading" />');

	$('#<?php echo $metabox['id']; ?> .handlediv').append( loading );
	$('#<?php echo $metabox['id']; ?> .handlediv .loading').fadeIn('fast');

	$.post(ajaxurl, {
		action : '<?php echo $this->getPlugin()->getSlug(); ?>_ajax_metabox',
		id : '<?php echo $metabox['id']; ?>',
		url : '<?php echo $metabox['args']['url']; ?>',
		_nonce : '<?php echo $nonce; ?>'
	}, function(response) {
		$('#<?php echo $metabox['id']; ?> .inside').html(response);
		$('#<?php echo $metabox['id']; ?> .handlediv .loading').fadeIn(0).fadeOut('fast');
	});
});
</script>