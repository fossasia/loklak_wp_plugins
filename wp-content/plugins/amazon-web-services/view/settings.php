<div class="aws-content aws-settings">

	<h3>Access Keys</h3>

	<?php if ( $this->are_key_constants_set() ) : ?>

		<p>
			<?php _e( 'You&#8217;ve already defined your AWS access keys in your wp-config.php. If you&#8217;d prefer to manage them here and store them in the database (not recommended), simply remove the lines from your wp-config.', 'amazon-web-services' ); ?>
		</p>

	<?php else : ?>

		<p>
			<?php printf( __( 'If you don&#8217;t have an Amazon Web Services account yet, you need to <a href="%s">sign up</a>.', 'amazon-web-services' ), 'http://aws.amazon.com' ); ?>
		</p>
		<p>
			<?php printf( __( 'Once you&#8217;ve signed up, you will need to <a href="%s">create a new IAM user</a> and grant access to the specific services which this plugin will use (e.g. S3).', 'amazon-web-services' ), 'https://console.aws.amazon.com/iam/home?region=us-east-1#users' ); ?>
		</p>
		<p>
			<?php _e( 'Once the user has been created, you will be presented with a couple of keys. Copy the folowing code to your wp-config.php and replace the stars with the keys.', 'amazon-web-services' ); ?>
		</p>

		<pre>define( 'AWS_ACCESS_KEY_ID', '********************' );
define( 'AWS_SECRET_ACCESS_KEY', '****************************************' );</pre>

		<p class="reveal-form">
			<?php _e( 'If you&#8217;d rather not to edit your wp-config.php and are ok storing the keys in the database (not recommended), <a href="">click here to reveal a form.</a>', 'amazon-web-services' ); ?>
		</p>

		<form method="post" <?php echo ( ! $this->get_access_key_id() && ! $this->get_secret_access_key() ) ? 'style="display: none;"' : ''; // xss ok ?>>

			<?php if ( isset( $_POST['access_key_id'] ) ) { // input var okay ?>
				<div class="aws-updated updated">
					<p><strong>Settings saved.</strong></p>
				</div>
			<?php } ?>

			<input type="hidden" name="action" value="save" />
			<?php wp_nonce_field( 'aws-save-settings' ) ?>

			<table class="form-table">
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Access Key ID:', 'amazon-web-services' ); ?></th>
					<td>
						<input type="text" name="access_key_id" value="<?php echo $this->get_access_key_id() // xss ok; ?>" size="50" autocomplete="off" />
					</td>
				</tr>
				<tr valign="top">
					<th width="33%" scope="row"><?php _e( 'Secret Access Key:', 'amazon-web-services' ); ?></th>
					<td>
						<input type="text" name="secret_access_key" value="<?php echo $this->get_secret_access_key() ? '-- not shown --' : ''; // xss ok ?>" size="50" autocomplete="off" />
					</td>
				</tr>
				<tr valign="top">
					<th colspan="2" scope="row">
						<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'amazon-web-services' ); ?></button>
						<?php if ( $this->get_access_key_id() || $this->get_secret_access_key() ) : ?>
							&nbsp;
							<button class="button remove-keys"><?php _e( 'Remove Keys', 'amazon-web-services' ); ?></button>
						<?php endif; ?>
					</th>
				</tr>
			</table>

		</form>

	<?php endif; ?>

</div>