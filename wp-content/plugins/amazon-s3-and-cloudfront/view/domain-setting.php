<?php
$tr_class = ( isset( $tr_class ) ) ? $tr_class : ''; ?>
<tr class="<?php echo $tr_class; ?>">
	<td>
		<h4><?php _e( 'Domain:', 'amazon-s3-and-cloudfront' ) ?></h4>
	</td>
	<td>
		<?php
		$domain             = $this->get_setting( 'domain' );
		$subdomain_disabled = '';
		$subdomain_class    = '';
		if ( 'https' == $this->get_setting( 'ssl' ) ) {
			if ( 'subdomain' == $domain ) {
				$domain = 'path';
			}
			$subdomain_disabled = 'disabled="disabled"';
			$subdomain_class    = 'disabled';
		}
		?>
		<div class="as3cf-domain as3cf-radio-group">
			<label class="subdomain-wrap <?php echo $subdomain_class; // xss ok?>">
				<input type="radio" name="domain" value="subdomain" <?php checked( $domain, 'subdomain' ); ?> <?php echo $subdomain_disabled; // xss ok ?>>
				<?php _e( 'Bucket name as subdomain', 'amazon-s3-and-cloudfront' ); ?>
				<p>http://bucket-name.s3.amazon.com/&hellip;</p>
			</label>
			<label>
				<input type="radio" name="domain" value="path" <?php checked( $domain, 'path' ); ?>>
				<?php _e( 'Bucket name in path', 'amazon-s3-and-cloudfront' ); ?>
				<p>http://s3.amazon.com/bucket-name/&hellip;</p>
			</label>
			<label>
				<input type="radio" name="domain" value="virtual-host" <?php checked( $domain, 'virtual-host' ); ?>>
				<?php _e( 'Bucket name as domain', 'amazon-s3-and-cloudfront' ); ?>
				<p>http://bucket-name/&hellip;</p>
			</label>
			<label>
				<input id="cloudfront" type="radio" name="domain" value="cloudfront" <?php checked( $domain, 'cloudfront' ); ?>>
				<?php _e( 'CloudFront or custom domain', 'amazon-s3-and-cloudfront' ); ?>
				<p class="as3cf-setting cloudfront <?php echo ( 'cloudfront' == $domain ) ? '' : 'hide'; // xss ok ?>">
					<input type="text" name="cloudfront" value="<?php echo esc_attr( $this->get_setting( 'cloudfront' ) ); ?>" size="30" />
					<span class="as3cf-validation-error" style="display: none;">
						<?php _e( 'Invalid character. Letters, numbers, periods and hyphens are allowed.', 'amazon-s3-and-cloudfront' ); ?>
					</span>
				</p>
			</label>
		</div>
	</td>
</tr>