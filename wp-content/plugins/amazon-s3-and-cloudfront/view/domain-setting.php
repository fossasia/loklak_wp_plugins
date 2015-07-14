<?php
$tr_class = ( isset( $tr_class ) ) ? $tr_class : ''; ?>
<tr class="<?php echo $tr_class; ?>">
	<td>
		<h4><?php _e( 'Domain:', 'as3cf' ) ?></h4>
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
				<?php _e( 'Bucket name as subdomain', 'as3cf' ); ?>
				<p>http://bucket-name.s3.amazon.com/&hellip;</p>
			</label>
			<label>
				<input type="radio" name="domain" value="path" <?php checked( $domain, 'path' ); ?>>
				<?php _e( 'Bucket name in path', 'as3cf' ); ?>
				<p>http://s3.amazon.com/bucket-name/&hellip;</p>
			</label>
			<label>
				<input type="radio" name="domain" value="virtual-host" <?php checked( $domain, 'virtual-host' ); ?>>
				<?php _e( 'Bucket name as domain', 'as3cf' ); ?>
				<p>http://bucket-name/&hellip;</p>
			</label>
			<label>
				<input id="cloudfront" type="radio" name="domain" value="cloudfront" <?php checked( $domain, 'cloudfront' ); ?>>
				<?php _e( 'CloudFront or custom domain', 'as3cf' ); ?>
				<p class="as3cf-setting cloudfront <?php echo ( 'cloudfront' == $domain ) ? '' : 'hide'; // xss ok ?>">
					<input type="text" name="cloudfront" value="<?php echo esc_attr( $this->get_setting( 'cloudfront' ) ); ?>" size="30" />
				</p>
			</label>
		</div>
	</td>
</tr>