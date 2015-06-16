<?php
require_once('../includes/template.php'); // WordPress Dashboard Functions
?>

<div class="wphttps-message-wrap" id="message-wrap"><div id="message-body"></div></div>

<div class="wrap" id="wphttps-main">
	<div id="icon-options-https" class="icon32"><br /></div>
	<h2><?php _e('HTTPS','wordpress-https'); ?></h2>

<?php
	wp_nonce_field('closedpostboxes', 'closedpostboxesnonce', false );
	wp_nonce_field('meta-box-order', 'meta-box-order-nonce', false );
?>
	<div id="poststuff" class="columns metabox-holder">
		<div class="postbox-container">
<?php do_meta_boxes('toplevel_page_' . $this->getPlugin()->getSlug() . '_network', 'main', $this); ?>
		</div>
	</div>
</div>