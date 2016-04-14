<p class="as3cf-setting domain cloudfront <?php echo ( 'cloudfront' == $domain ) ? '' : 'hide'; // xss ok ?>">
	<input type="text" name="cloudfront" value="<?php echo esc_attr( $this->get_setting( 'cloudfront' ) ); ?>" size="30" <?php echo $disabled_attr; ?> />
	<span class="as3cf-validation-error" style="display: none;">
		<?php _e( 'Invalid character. Letters, numbers, periods and hyphens are allowed.', 'amazon-s3-and-cloudfront' ); ?>
	</span>
</p>