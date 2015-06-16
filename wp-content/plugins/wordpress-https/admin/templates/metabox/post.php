<?php
global $post;

if ( $post->ID ) {
	$force_ssl = get_post_meta($post->ID, 'force_ssl', true);
	$force_ssl_children = get_post_meta($post->ID, 'force_ssl_children', true);
	$postParent = $post;
	while ( $postParent->post_parent ) {
		$postParent = get_post( $postParent->post_parent );
		if ( get_post_meta($postParent->ID, 'force_ssl_children', true) == 1 ) {
			$parent_force_ssl_children = get_post($postParent->ID);
			break;
		}
	}
}

wp_nonce_field($this->getPlugin()->getSlug(), $this->getPlugin()->getSlug());
?>

<div class="misc-pub-section">
<?php if ( isset($parent_force_ssl_children) ) { ?>
	<input type="hidden" value="<?php echo ( $force_ssl  ? 1 : 0 ); ?>" name="force_ssl" />
<?php } ?>
	<label<?php echo ( isset($parent_force_ssl_children)  ? ' title="' . printf( __("This post's parent page '%s' has 'Secure child posts' enabled.",'wordpress-https'), $parent_force_ssl_children->post_title) . '"' : '' ); ?>><input type="checkbox" value="1" name="force_ssl" <?php echo ( $force_ssl  ? ' checked="checked"' : '' ); ?><?php echo ( isset($parent_force_ssl_children)  ? ' disabled="disabled="' : '' ); ?> /> <?php _e('Secure post','wordpress-https'); ?></label>
</div>
<div class="misc-pub-section misc-pub-section-last">
	<label><input type="checkbox" value="1" name="force_ssl_children" <?php echo ( $force_ssl_children  ? ' checked="checked"' : '' ); ?> /> <?php _e('Secure child posts','wordpress-https'); ?></label>
</div>