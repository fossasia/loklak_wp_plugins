<?php
$updated_class = '';
if ( isset( $_GET['updated'] ) ) { // input var okay
	$updated_class = 'show';
}
$prefix = $this->get_plugin_prefix_slug();
?>
<div class="notice is-dismissible as3cf-updated updated inline <?php echo $updated_class; // xss ok ?>">
	<p>
		<?php _e( 'Settings saved.', 'amazon-s3-and-cloudfront' ); ?>
	</p>
</div>
<?php
$selected_bucket        = $this->get_setting( 'bucket' );
$selected_bucket_prefix = $this->get_object_prefix(); ?>
<div id="tab-media" data-prefix="as3cf" class="as3cf-tab aws-content<?php echo ( $selected_bucket ) ? ' as3cf-has-bucket' : ''; // xss ok ?>">
	<div class="error inline as3cf-bucket-error as3cf-error" style="display: none;">
		<p>
			<span class="title"></span>
			<span class="message"></span>
		</p>
	</div>

	<?php
	do_action( 'as3cf_pre_tab_render', 'media' );
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
						'prefix'                 => $prefix,
						'selected_bucket'        => $selected_bucket,
						'selected_bucket_prefix' => $selected_bucket_prefix,
						'tr_class'               => 'as3cf-border-bottom as3cf-bucket-setting',
					)
				); ?>

				<?php $args = $this->get_setting_args( 'copy-to-s3' ); ?>
				<tr class="as3cf-setting-title">
					<td colspan="2"><h3><?php _e( 'Enable/Disable the Plugin', 'amazon-s3-and-cloudfront' ); ?></h3></td>
				</tr>
				<tr class="<?php echo $args['tr_class']; ?>">
					<td>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Copy Files to S3', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p>
							<?php _e( 'When a file is uploaded to the Media Library, copy it to S3. Existing files are <em>not</em> copied to S3.', 'amazon-s3-and-cloudfront' ); ?>
							<?php echo $this->settings_more_info_link( 'copy-to-s3' ); ?>
						</p>

					</td>
				</tr>
				<?php $args = $this->get_setting_args( 'serve-from-s3' ); ?>
				<tr class="as3cf-border-bottom <?php echo $args['tr_class']; ?>">
					<td>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Rewrite File URLs', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p>
							<?php _e( 'For Media Library files that have been copied to S3, rewrite the URLs so that they are served from S3/CloudFront instead of your server.', 'amazon-s3-and-cloudfront' ); ?>
							<?php echo $this->settings_more_info_link( 'serve-from-s3' ); ?>
						</p>

					</td>
				</tr>
				<tr class="configure-url as3cf-setting-title">
					<td colspan="2"><h3><?php _e( 'Configure File URLs', 'amazon-s3-and-cloudfront' ); ?></h3></td>
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
				<?php $this->render_view( 'domain-setting' ); ?>
				<?php $args = $this->get_setting_args( 'enable-object-prefix' ); ?>
				<tr class="configure-url url-preview <?php echo $args['tr_class']; ?>">
					<td>
						<?php $args['class'] = 'sub-toggle'; ?>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Path', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p class="object-prefix-desc">
							<?php _e( 'By default the path is the same as your local WordPress files.', 'amazon-s3-and-cloudfront' ); ?>
							<?php echo $this->settings_more_info_link( 'object-prefix' ); ?>
						</p>
						<p class="as3cf-setting enable-object-prefix <?php echo ( $this->get_setting( 'enable-object-prefix' ) ) ? '' : 'hide'; // xss ok ?>">
							<?php
							$args = $this->get_setting_args( 'object-prefix' );
							if ( false === $this->get_defined_setting( 'object-prefix', false ) ) {
								$placeholder = 'placeholder="placeholder"';
							} else {
								$placeholder = '';
							}
							?>
							<input type="text" name="object-prefix" value="<?php echo esc_attr( $this->get_setting( 'object-prefix' ) ); ?>" size="30" placeholder="<?php echo $placeholder; ?>" <?php echo $args['disabled_attr']; ?> />
						</p>
					</td>
				</tr>

				<?php $args = $this->get_setting_args( 'use-yearmonth-folders' ); ?>
				<tr class="configure-url url-preview <?php echo $args['tr_class']; ?>">
					<td>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Year/Month', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p>
							<?php _e( 'Add the Year/Month in the URL.' ); ?>
							<?php echo $this->settings_more_info_link( 'use-yearmonth-folders' ); ?>
						</p>
					</td>
				</tr>

				<?php $args = $this->get_setting_args( 'ssl' ); ?>
				<tr class="configure-url as3cf-border-bottom url-preview <?php echo $args['tr_class']; ?>">
					<td>
						<h4><?php _e( 'SSL', 'amazon-s3-and-cloudfront' ); ?></h4>
					</td>
					<td>
						<p>
							<?php _e( 'Controls the protocol of the S3 URLs.' ); ?>
							<?php echo $this->settings_more_info_link( 'ssl' ); ?>
						</p>
						<?php
						$ssl = $this->get_setting( 'ssl' );
						echo $args['setting_msg'];
						?>
						<div class="as3cf-ssl as3cf-radio-group">
							<label>
								<input type="radio" name="ssl" value="request" <?php checked( $ssl, 'request' ); ?> <?php echo $args['disabled_attr']; ?>>
								<?php _e( 'Same as request', 'amazon-s3-and-cloudfront' ); ?>
								<p><?php _e( 'When the request is https://, use https:// for the file URL as well.', 'amazon-s3-and-cloudfront' ); ?></p>
							</label>
							<label>
								<input type="radio" name="ssl" value="https" <?php checked( $ssl, 'https' ); ?> <?php echo $args['disabled_attr']; ?>>
								<?php _e( 'Always SSL', 'amazon-s3-and-cloudfront' ); ?>
								<p><?php _e( 'Forces https:// to be used.', 'amazon-s3-and-cloudfront' ); ?></p>
								<?php if ( $this->show_deprecated_domain_setting() ) : ?>
									<p><?php _e( 'You cannot use the "Bucket as a subdomain" domain option when using SSL.', 'amazon-s3-and-cloudfront' ); ?></p>
								<?php endif; ?>
								</label>
							<label>
								<input type="radio" name="ssl" value="http" <?php checked( $ssl, 'http' ); ?> <?php echo $args['disabled_attr']; ?>>
								<?php _e( 'Always non-SSL', 'amazon-s3-and-cloudfront' ); ?>
								<p><?php _e( 'Forces http:// to be used.', 'amazon-s3-and-cloudfront' ); ?></p>
							</label>
						</div>
					</td>
				</tr>
				<tr class="advanced-options as3cf-setting-title">
					<td colspan="2"><h3><?php _e( 'Advanced Options', 'amazon-s3-and-cloudfront' ); ?></h3></td>
				</tr>
				<?php $args = $this->get_setting_args( 'remove-local-file' ); ?>
				<tr class="advanced-options <?php echo $args['tr_class']; ?>">
					<td>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Remove Files From Server', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p><?php _e( 'Once a file has been copied to S3, remove it from the local server.', 'amazon-s3-and-cloudfront' ); ?>
							<?php echo $this->settings_more_info_link( 'remove-local-file' ); ?>
						</p>
						<?php
						$lost_files_msg  = apply_filters( 'as3cf_lost_files_notice', __( '<strong>Broken URLs</strong> &mdash; There will be broken URLs for files that don\'t exist locally. You can fix this by enabling <strong>Rewrite File URLs</strong> to use the S3 URLs.', 'amazon-s3-and-cloudfront' ) );
						$lost_files_args = array(
							'message' => $lost_files_msg,
							'id'      => 'as3cf-lost-files-notice',
							'inline'  => true,
							'type'    => 'error',
							'style'   => 'display: none',
						);
						$this->render_view( 'notice', $lost_files_args );

						$remove_local_link = $this->more_info_link( 'https://deliciousbrains.com/wp-offload-s3/doc/compatibility-with-other-plugins/' );
						$remove_local_msg  = apply_filters( 'as3cf_remove_local_notice', sprintf( __( '<strong>Warning</strong> &mdash; Some plugins depend on the file being present on the local server and may not work when the file is removed. %s', 'amazon-s3-and-cloudfront' ), $remove_local_link ) );
						$remove_local_args = array(
							'message' => $remove_local_msg,
							'id'      => 'as3cf-remove-local-notice',
							'inline'  => true,
							'type'    => 'notice-warning',
							'style'   => 'display: none',
						);
						$this->render_view( 'notice', $remove_local_args ); ?>
					</td>
				</tr>
				<?php $args = $this->get_setting_args( 'object-versioning' ); ?>
				<tr class="advanced-options url-preview as3cf-border-bottom <?php echo $args['tr_class']; ?>">
					<td>
						<?php $this->render_view( 'checkbox', $args ); ?>
					</td>
					<td>
						<?php echo $args['setting_msg']; ?>
						<h4><?php _e( 'Object Versioning', 'amazon-s3-and-cloudfront' ) ?></h4>
						<p>
							<?php _e( 'Append a timestamp to the S3 file path. Recommended when using CloudFront so you don\'t have to worry about cache invalidation.' ); ?>
							<?php echo $this->settings_more_info_link( 'object-versioning' ); ?>
						</p>
					</td>
				</tr>
			</table>
			<p>
				<button type="submit" class="button button-primary" <?php echo $this->maybe_disable_save_button(); ?>><?php _e( 'Save Changes', 'amazon-s3-and-cloudfront' ); ?></button>
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