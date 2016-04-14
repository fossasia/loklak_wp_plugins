<?php
$domain = $this->get_setting( 'domain' );
$args   = $this->get_setting_args( 'domain' );
$args['tr_class'] = $args['tr_class'] . ' configure-url url-preview';
if ( $this->show_deprecated_domain_setting( $domain ) ) {
	return $this->render_view( 'deprecated-domain-setting', $args );
}
?>
<tr class="<?php echo $args['tr_class']; ?>">
	<td>
		<?php
		$args['values'] = array( 'path', 'cloudfront' );
		$args['class']  = 'sub-toggle';
		$this->render_view( 'checkbox', $args );
		?>
	</td>
	<td>
		<?php echo $args['setting_msg']; ?>
		<h4><?php _e( 'CloudFront or Custom Domain', 'amazon-s3-and-cloudfront' ) ?></h4>
		<p class="domain-desc">
			<?php _e( 'Replace the default S3 domain and path with your CloudFront domain or any domain.', 'amazon-s3-and-cloudfront' ); ?>
			<?php echo $this->settings_more_info_link( 'domain' ); ?>
		</p>
		<?php
		$args = $this->get_setting_args( 'cloudfront' );
		$args['domain'] = $domain;
		$this->render_view( 'cloudfront-setting', $args );
		?>
	</td>
</tr>