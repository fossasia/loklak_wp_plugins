<?php
$selected_bucket = $this->get_setting( 'bucket' ); ?>
<div class="aws-content as3cf-settings<?php echo ( $selected_bucket ) ? ' as3cf-has-bucket' : ''; // xss ok ?>">

	<div class="error as3cf-bucket-error" style="display: none;">
		<p>
			<span class="title"></span>
			<span class="message"></span>
		</p>
	</div>
	<?php
	$updated_class = '';
if ( isset( $_GET['updated'] ) ) { // input var okay
	$updated_class = 'show';
}
	?>
	<div class="updated <?php echo $updated_class; // xss ok ?>">
		<p>
			<?php _e( 'Settings saved.', 'as3cf' ); ?>
		</p>
	</div>

	<?php
	$can_write = $this->check_write_permission();
	// catch any file system issues
if ( is_wp_error( $can_write ) ) {
	$this->render_view( 'error-fatal', array( 'message' => $can_write->get_error_message() ) );
	$can_write = true;
}
// display a error message if the user does not have write permission to S3
?>
	<div class="error as3cf-can-write-error" style="<?php echo ( $can_write ) ? 'display: none;' : ''; // xss ok ?>">
		<p>
			<strong>
				<?php _e( 'S3 Policy is Read-Only', 'as3cf' ); ?>
			</strong>&mdash;
			<?php printf( __( 'You need to go to <a href="%s">Identity and Access Management</a> in your AWS console and manage the policy for the user you\'re using for this plugin. Your policy should look something like the following:', 'as3cf' ), 'https://console.aws.amazon.com/iam/home' ); ?>
		</p>
		<pre><code>{
  "Version": "2012-10-17",
  "Statement": [
	{
	  "Effect": "Allow",
	  "Action": "s3:*",
	  "Resource": "*"
	}
  ]
}</code></pre>
	</div>
	<?php $bucket_toggle_class = $this->get_setting( 'manual_bucket' ) ? 'manual' : ''; ?>
	<div class="as3cf-bucket-select <?php echo $bucket_toggle_class; ?>">
		<h3><?php _e( 'Select an existing S3 bucket to use:', 'as3cf' ); ?></h3>
		<div class="as3cf-bucket-actions">
			<span class="as3cf-cancel-bucket-select-wrap">
				<a href="#" class="as3cf-cancel-bucket-select"><?php _e( 'Cancel', 'as3cf' ); ?></a>
			</span>
			<a href="#" class="as3cf-refresh-buckets"><?php _e( 'Refresh', 'as3cf' ); ?></a>
		</div>
		<p>
			<?php _e( 'You can enter a bucket manually to avoid listing the buckets available. This can be helpful if you have an IAM policy that does not allow bucket listing or you have a large amount of buckets to load.', 'as3cf' ); ?>
		</p>
		<p>
			<a href="#" class="as3cf-manual-bucket-toggle"> <?php _e( 'Enter Bucket', 'as3cf' ); ?></a>
			<a href="#" class="as3cf-bucket-list-toggle"><?php _e( 'Select Bucket', 'as3cf' ); ?></a>
		</p>
		<div class="as3cf-manual-save-bucket-wrapper">
			<form method="post" class="as3cf-manual-save-bucket-form">
				<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Bucket Name', 'as3cf' ); ?>" value="<?php echo $selected_bucket; ?>">
				<button type="submit" class="button" data-working="<?php _e( 'Saving...', 'as3cf' ); ?>"><?php _e( 'Save', 'as3cf' ); ?></button>
			</form>
		</div>
		<div class="as3cf-bucket-list-wrapper">
			<ul class="as3cf-bucket-list" data-working="<?php _e( 'Loading...', 'as3cf' ); ?>">
			</ul>
		</div>
		<h3><?php _e( 'Or create a new bucket:', 'as3cf' ); ?></h3>
		<form method="post" class="as3cf-create-bucket-form">
			<?php wp_nonce_field( 'as3cf-save-settings' ) ?>
			<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Bucket Name', 'as3cf' ); ?>">
			<button type="submit" class="button" data-working="<?php _e( 'Creating...', 'as3cf' ); ?>"><?php _e( 'Create', 'as3cf' ); ?></button>
		</form>
	</div>

	<div class="as3cf-main-settings">
		<form method="post">
			<input type="hidden" name="action" value="save" />
			<?php wp_nonce_field( 'as3cf-save-settings' ) ?>

			<table class="form-table">
				<tr class="as3cf-border-bottom">
					<td><h3><?php _e( 'Bucket', 'as3cf' ); ?></h3></td>
					<td>
						<span class="as3cf-active-bucket"><?php echo $selected_bucket; // xss ok ?></span>
						<?php if ( ! defined( 'AS3CF_BUCKET' ) ) : ?>
							<a href="#" class="as3cf-change-bucket"><?php _e( 'Change', 'as3cf' ); ?></a>
						<?php endif; ?>
						<input id="as3cf-bucket" type="hidden" class="no-compare" name="bucket" value="<?php echo esc_attr( $selected_bucket ); ?>">
						<input id="as3cf-region" type="hidden" class="no-compare" name="region" value="<?php echo esc_attr( $this->get_setting( 'region' ) ); ?>">
					</td>
				</tr>
				<tr>
					<td colspan="2"><h3><?php _e( 'Enable/Disable the Plugin', 'as3cf' ); ?></h3></td>
				</tr>
				<tr>
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'copy-to-s3' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Copy Files to S3', 'as3cf' ) ?></h4>
						<p><?php _e( 'When a file is uploaded to the Media Library, copy it to S3. Existing files are <em>not</em> copied to S3.', 'as3cf' ) ?></p>
					</td>
				</tr>
				<tr class="as3cf-border-bottom">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'serve-from-s3' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Rewrite File URLs', 'as3cf' ) ?></h4>
						<p><?php _e( 'For Media Library files that have been copied to S3, rewrite the URLs so that they are served from S3/CloudFront instead of your server.', 'as3cf' ) ?></p>
					</td>
				</tr>
				<tr class="configure-url">
					<td colspan="2"><h3><?php _e( 'Configure File URLs', 'as3cf' ); ?></h3></td>
				</tr>
				<tr class="configure-url">
					<td colspan="2">
						<div class="as3cf-url-preview-wrap">
							<span>Preview</span>
							<div class="as3cf-url-preview">
								<?php echo $this->get_url_preview(); // xss ok ?>
							</div>
						</div>
					</td>
				</tr>
				<tr class="configure-url url-preview">
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
								Bucket name as subdomain
								<p>http://bucket-name.s3.amazon.com/&hellip;</p>
							</label>
							<label>
								<input type="radio" name="domain" value="path" <?php checked( $domain, 'path' ); ?>>
								Bucket name in path
								<p>http://s3.amazon.com/bucket-name/&hellip;</p>
							</label>
							<label>
								<input type="radio" name="domain" value="virtual-host" <?php checked( $domain, 'virtual-host' ); ?>>
								Bucket name as domain
								<p>http://bucket-name/&hellip;</p>
							</label>
							<label>
								<input id="cloudfront" type="radio" name="domain" value="cloudfront" <?php checked( $domain, 'cloudfront' ); ?>>
								CloudFront or custom domain
								<p class="as3cf-setting cloudfront <?php echo ( 'cloudfront' == $domain ) ? '' : 'hide'; // xss ok ?>">
									<input type="text" name="cloudfront" value="<?php echo esc_attr( $this->get_setting( 'cloudfront' ) ); ?>" size="40" />
								</p>
							</label>
						</div>
					</td>
				</tr>
				<tr class="configure-url url-preview">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'enable-object-prefix', 'class' => 'sub-toggle' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Path', 'as3cf' ) ?></h4>
						<p class="object-prefix-desc">
							<?php _e( 'By default the path is the same as your local WordPress files:' ); ?>
							<em><?php echo $this->get_default_object_prefix(); // xss ok ?></em>
						</p>
						<p class="as3cf-setting enable-object-prefix <?php echo ( $this->get_setting( 'enable-object-prefix' ) ) ? '' : 'hide'; // xss ok ?>">
							<input type="text" name="object-prefix" value="<?php echo esc_attr( $this->get_setting( 'object-prefix' ) ); ?>" size="30" />
						</p>
					</td>
				</tr>
				<tr class="configure-url url-preview">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'use-yearmonth-folders' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Year/Month', 'as3cf' ) ?></h4>
						<p>
							<?php _e( 'Add the Year/Month in the URL.' ); ?>
						</p>
					</td>
				</tr>
				<tr class="configure-url as3cf-border-bottom url-preview">
					<td>
						<h4><?php _e( 'SSL', 'as3cf' ) ?></h4>
					</td>
					<td>
						<?php
						$ssl = $this->get_setting( 'ssl' ); ?>
						<div class="as3cf-ssl as3cf-radio-group">
							<label>
								<input type="radio" name="ssl" value="request" <?php checked( $ssl, 'request' ); ?>>
								<?php _e( 'Same as request', 'as3cf' ); ?>
								<p><?php _e( 'When the request is https://, use https:// for the file URL as well.', 'as3cf' ); ?></p>
							</label>
							<label>
								<input type="radio" name="ssl" value="https" <?php checked( $ssl, 'https' ); ?>>
								<?php _e( 'Always SSL', 'as3cf' ); ?>
								<p><?php _e( 'Forces https:// to be used.', 'as3cf' ); ?></p>
								<p><?php _e( 'You cannot use the "Bucket as a subdomain" domain option when using SSL.', 'as3cf' ); ?></p>
							</label>
							<label>
								<input type="radio" name="ssl" value="http" <?php checked( $ssl, 'http' ); ?>>
								<?php _e( 'Always non-SSL', 'as3cf' ); ?>
								<p><?php _e( 'Forces http:// to be used.', 'as3cf' ); ?></p>
							</label>
						</div>
					</td>
				</tr>
				<tr class="advanced-options">
					<td colspan="2"><h3><?php _e( 'Advanced Options', 'as3cf' ); ?></h3></td>
				</tr>
				<tr class="advanced-options">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'remove-local-file' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Remove Files From Server', 'as3cf' ) ?></h4>
						<p><?php _e( 'Once a file has been copied to S3, remove it from the local server.', 'as3cf' ) ?></p>
					</td>
				</tr>
				<tr class="advanced-options url-preview">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'object-versioning' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Object Versioning', 'as3cf' ) ?></h4>
						<p>
							<?php _e( 'Append a timestamp to the S3 file path. Recommended when using CloudFront so you don\'t have to worry about cache invalidation.' ); ?>
							<a href="http://docs.aws.amazon.com/AmazonCloudFront/latest/DeveloperGuide/ReplacingObjects.html">
								<?php _e( 'More info', 'as3cf' ) ?> &raquo;
							</a>
						</p>
					</td>
				</tr>
				<tr class="advanced-options">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'expires' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Far Future Expiration Header', 'as3cf' ) ?></h4>
						<p><?php _e( 'Implements a "Never Expire" caching policy for browsers by setting an Expires header for 10 years in the future. Should be used in conjunction with object versioning above.' ); ?>
							<a href="http://developer.yahoo.com/performance/rules.html#expires">
								<?php _e( 'More info', 'as3cf' ) ?> &raquo;
							</a>
						</p>
					</td>
				</tr>
				<tr class="advanced-options as3cf-border-bottom">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'hidpi-images' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Copy HiDPI (@2x) Images', 'as3cf' ) ?></h4>
						<p> <?php printf( __( 'When uploading a file to S3, checks if there\'s a file of the same name with an @2x suffix and copies it to S3 as well. Works with the <a href="%s">WP Retina 2x</a> plugin.', 'as3cf' ), 'https://wordpress.org/plugins/wp-retina-2x/' ); ?></p>
					</td>
				</tr>

			</table>
			<p>
				<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'amazon-web-services' ); ?></button>
			</p>
		</form>
	</div>

	<?php $this->render_view( 'sidebar' ); ?>

</div>