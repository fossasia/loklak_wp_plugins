<form name="<?php echo $this->getPlugin()->getSlug(); ?>_network_settings_form" id="<?php echo $this->getPlugin()->getSlug(); ?>_network_settings_form" action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
<?php wp_nonce_field($this->getPlugin()->getSlug()); ?>
<input type="hidden" name="action" id="action" value="" />

<table id="blog-table">
	<thead>
	<tr>
		<th class="blog-blog"><?php _e('Blog','wordpress-https'); ?></th>
		<th class="blog-host"><?php _e('SSL Host','wordpress-https'); ?></th>
		<th class="blog-ssl_admin"><?php _e('Force SSL Admin','wordpress-https'); ?></th>
		<th class="blog-exclusive_https"><?php _e('Force SSL Exclusively','wordpress-https'); ?></th>
		<th class="blog-remove_unsecure"><?php _e('Remove Unsecure Elements','wordpress-https'); ?></th>
		<th class="blog-debug"><?php _e('Debug Mode','wordpress-https'); ?></th>
		<th class="blog-proxy"><?php _e('Proxy','wordpress-https'); ?></th>
		<th class="blog-admin_menu"><?php _e('Admin Menu Location','wordpress-https'); ?></th>
	</tr>
	</thead>
	<tbody>
<?php
	global $wpdb;
	$blogs = $wpdb->get_col($wpdb->prepare("SELECT blog_id FROM " . $wpdb->blogs, NULL));
	foreach($blogs as $blog_id) {
		$ssl_host = ($this->getPlugin()->getSetting('ssl_host', $blog_id) != '' ?  $this->getPlugin()->getSetting('ssl_host', $blog_id) : get_site_url($blog_id, '', 'https'));
		$ssl_host = preg_replace('/http[s]?:\/\//', '', $ssl_host);
		$ssl_host = rtrim(str_replace(parse_url(get_site_url($blog_id, ''), PHP_URL_PATH), '', $ssl_host), '/');
?>
	<tr>
		<td class="blog-blog"><strong><?php echo preg_replace('/http[s]?:\/\//', '', get_site_url($blog_id)); ?></strong></td>
		<td class="blog-host"><input name="blog[<?php echo $blog_id; ?>][ssl_host]" type="text" class="regular-text code" value="<?php echo $ssl_host; ?>" /></td>
		<td class="blog-ssl_admin"><input type="hidden" name="blog[<?php echo $blog_id; ?>][ssl_admin]" value="<?php echo ((force_ssl_admin() && $this->getPlugin()->getSetting('ssl_admin', $blog_id) == 1) ? 1 : 0); ?>" /><input name="blog[<?php echo $blog_id; ?>][ssl_admin]" type="checkbox" value="1"<?php echo ((force_ssl_admin()) ? ' disabled="disabled" title="FORCE_SSL_ADMIN is true in wp-config.php"' : '') . ($this->getPlugin()->getSetting('ssl_admin', $blog_id) ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-exclusive_https"><input type="hidden" name="blog[<?php echo $blog_id; ?>][exclusive_https]" value="0" /><input name="blog[<?php echo $blog_id; ?>][exclusive_https]" type="checkbox" value="1"<?php echo (($this->getPlugin()->getSetting('exclusive_https', $blog_id)) ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-remove_unsecure"><input type="hidden" name="blog[<?php echo $blog_id; ?>][remove_unsecure]" value="0" /><input name="blog[<?php echo $blog_id; ?>][remove_unsecure]" type="checkbox" value="1"<?php echo (($this->getPlugin()->getSetting('remove_unsecure', $blog_id)) ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-debug"><input type="hidden" name="blog[<?php echo $blog_id; ?>][debug]" value="0" /><input name="blog[<?php echo $blog_id; ?>][debug]" type="checkbox" value="1"<?php echo (($this->getPlugin()->getSetting('debug', $blog_id)) ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-proxy">
			<select name="blog[<?php echo $blog_id; ?>][ssl_proxy]">
				<option value="0"<?php echo ((! $this->getPlugin()->getSetting('ssl_proxy', $blog_id)) ? ' selected="selected"' : ''); ?>><?php _e('No','wordpress-https'); ?></option>
				<option value="auto"<?php echo (($this->getPlugin()->getSetting('ssl_proxy', $blog_id) === 'auto') ? ' selected="selected"' : ''); ?>><?php _e('Auto','wordpress-https'); ?></option>
				<option value="1"<?php echo (($this->getPlugin()->getSetting('ssl_proxy', $blog_id) == 1) ? ' selected="selected"' : ''); ?>><?php _e('Yes','wordpress-https'); ?></option>
			</select>
		</td>
		<td class="blog-admin_menu">
			<select name="blog[<?php echo $blog_id; ?>][admin_menu]">
				<option value="side"<?php echo (($this->getPlugin()->getSetting('admin_menu', $blog_id) === 'side') ? ' selected="selected"' : ''); ?>><?php _e('Sidebar','wordpress-https'); ?></option>
				<option value="settings"<?php echo (($this->getPlugin()->getSetting('admin_menu', $blog_id) === 'settings') ? ' selected="selected"' : ''); ?>><?php _e('Settings','wordpress-https'); ?></option>
			</select>
		</td>
	</tr>
<?php
	}

	$defaults = $this->getPlugin()->getSetting('network_defaults');
	if ( sizeof($defaults) == 0 ) {
		foreach( $this->getPlugin()->getSettings() as $setting => $default ) {
			$defaults[$setting] = $default;
		}
	}
?>

	<tr>
		<td class="blog-blog"><strong>New Site Defaults</strong></td>
		<td class="blog-host"><input name="blog_default[ssl_host]" type="text" class="regular-text code" value="<?php echo $defaults['ssl_host']; ?>" /></td>
		<td class="blog-ssl_admin"><input type="hidden" name="blog_default[ssl_admin]" value="0" /><input name="blog_default[ssl_admin]" type="checkbox" value="1"<?php echo ($defaults['ssl_admin'] ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-exclusive_https"><input type="hidden" name="blog_default[exclusive_https]" value="0" /><input name="blog_default[exclusive_https]" type="checkbox" value="1"<?php echo ($defaults['exclusive_https'] ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-remove_unsecure"><input type="hidden" name="blog_default[remove_unsecure]" value="0" /><input name="blog_default[remove_unsecure]" type="checkbox" value="1"<?php echo ($defaults['remove_unsecure'] ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-debug"><input type="hidden" name="blog_default[debug]" value="0" /><input name="blog_default[debug]" type="checkbox" value="1"<?php echo ($defaults['debug'] ? ' checked="checked"' : ''); ?> /></td>
		<td class="blog-proxy">
			<select name="blog_default[ssl_proxy]">
				<option value="0"<?php echo (! $defaults['ssl_proxy'] ? ' selected="selected"' : ''); ?>><?php _e('No','wordpress-https'); ?></option>
				<option value="auto"<?php echo ($defaults['ssl_proxy'] === 'auto' ? ' selected="selected"' : ''); ?>><?php _e('Auto','wordpress-https'); ?></option>
				<option value="1"<?php echo ($defaults['ssl_proxy'] === 1 ? ' selected="selected"' : ''); ?>><?php _e('Yes','wordpress-https'); ?></option>
			</select>
		</td>
		<td class="blog-admin_menu">
			<select name="blog_default[admin_menu]">
				<option value="side"<?php echo ($defaults['admin_menu'] === 'side' ? ' selected="selected"' : ''); ?>><?php _e('Sidebar','wordpress-https'); ?></option>
				<option value="settings"<?php echo ($defaults['admin_menu'] === 'settings' ? ' selected="selected"' : ''); ?>><?php _e('Settings','wordpress-https'); ?></option>
			</select>
		</td>
	</tr>
	</tbody>

</table>

<p class="button-controls">
	<input type="submit" name="network-settings-save" value="<?php _e('Save Changes','wordpress-https'); ?>" class="button-primary" id="network-settings-save" />
	<img alt="<?php _e('Waiting...','wordpress-https'); ?>" src="<?php echo admin_url('/images/wpspin_light.gif'); ?>" class="waiting submit-waiting" />
</p>
</form>
<script type="text/javascript">
jQuery(document).ready(function($) {
	var form = $('#<?php echo $this->getPlugin()->getSlug(); ?>_network_settings_form').first();
	$('#network-settings-save').click(function() {
		$(form).find('input[name="action"]').val('<?php echo $this->getPlugin()->getSlug(); ?>_network_settings_save');
	});
	$(form).submit(function(e) {
		e.preventDefault();
		$(form).find('.submit-waiting').show();
		$.post(ajaxurl, $(form).serialize(), function(response) {
			$(form).find('.submit-waiting').hide();
			$('#message-body').html(response).fadeOut(0).fadeIn().delay(5000).fadeOut();
		});
	});
});
</script>