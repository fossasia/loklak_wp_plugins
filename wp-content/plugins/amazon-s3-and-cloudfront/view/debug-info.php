<div class="debug support-section">
	<h3><?php _e( 'Diagnostic Info', 'amazon-s3-and-cloudfront' ); ?></h3>
	<textarea class="debug-log-textarea" autocomplete="off" readonly><?php $this->output_diagnostic_info(); ?></textarea>
	<?php
	$args = array(
		'nonce'              => wp_create_nonce( 'as3cf-download-log' ),
		'as3cf-download-log' => '1',
		'hash'               => 'support',
	);
	$url = $this->get_plugin_page_url( $args, 'network', false ); ?>
	<a href="<?php echo esc_url( $url ); ?>" class="button"><?php _ex( 'Download', 'Download to your computer', 'amazon-s3-and-cloudfront' ); ?></a>
</div>