<?php
$updated_class = '';
if ( isset( $_GET['updated'] ) ) { // input var okay
	$updated_class = 'show';
}
$prefix = $this->get_plugin_prefix_slug();
?>
<div class="notice is-dismissible as3cf-updated updated inline <?php echo $updated_class; // xss ok ?>">
	<p>
		<?php _e( 'Settings saved.', 'as3cf' ); ?>
	</p>
</div>
<?php
$selected_bucket = $this->get_setting( 'bucket' ); ?>
<div id="tab-media" data-prefix="as3cf" class="as3cf-tab aws-content<?php echo ( $selected_bucket ) ? ' as3cf-has-bucket' : ''; // xss ok ?>">
	<div class="error inline as3cf-bucket-error as3cf-error" style="display: none;">
		<p>
			<span class="title"></span>
			<span class="message"></span>
		</p>
	</div>

	<?php
	do_action( 'as3cf_media_pre_tab_render' );
	$this->render_bucket_permission_errors(); ?>

	<div class="as3cf-main-settings">
		<form method="post">
			<input type="hidden" name="action" value="save" />
			<input type="hidden" name="plugin" value="<?php echo $this->get_plugin_slug(); ?>" />
			<?php wp_nonce_field( $this->get_settings_nonce_key() ) ?>
			<?php do_action( 'as3cf_form_hidden_fields' ); ?>

			<table class="form-table">
				<?php
				$this->render_view( 'bucket-setting',
					array(
						'prefix'          => $prefix,
						'selected_bucket' => $selected_bucket,
						'tr_class'        => 'as3cf-border-bottom',
					)
				); ?>
				<tr class="as3cf-setting-title">
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
				<tr class="configure-url as3cf-setting-title">
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
				<?php $this->render_view( 'domain-setting', array( 'tr_class' => 'configure-url url-preview' ) ); ?>
				<tr class="configure-url url-preview">
					<td>
						<?php $this->render_view( 'checkbox', array( 'key' => 'enable-object-prefix', 'class' => 'sub-toggle' ) ); ?>
					</td>
					<td>
						<h4><?php _e( 'Path', 'as3cf' ) ?></h4>
						<p class="object-prefix-desc">
							<?php _e( 'By default the path is the same as your local WordPress files:', 'as3cf' ); ?>
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
						<h4><?php _e( 'SSL:', 'as3cf' ) ?></h4>
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
				<tr class="advanced-options as3cf-setting-title">
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
				<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'as3cf' ); ?></button>
			</p>
		</form>
	</div>

	<?php $this->render_view( 'bucket-select', array( 'prefix' => $prefix, 'selected_bucket' => $selected_bucket ) ); ?>
</div>

<?php $this->render_view( 'support' ); ?>

<?php do_action( 'as3cf_after_settings' ); ?>

<?php
if ( ! $this->is_pro() ) {
	$this->render_view( 'sidebar' );
}
?>