<div class="as3cf-bucket-container <?php echo $prefix ;?>">
	<div class="as3cf-bucket-manual">
		<h3 data-modal-title="<?php _e( 'Change bucket', 'amazon-s3-and-cloudfront' ); ?>"><?php _e( 'What bucket would you like to use?', 'amazon-s3-and-cloudfront' ); ?></h3>
		<form method="post" class="as3cf-manual-save-bucket-form">
			<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Existing bucket name', 'amazon-s3-and-cloudfront' ); ?>" value="<?php echo $selected_bucket; ?>">
			<p class="bucket-actions actions manual">
				<button type="submit" class="bucket-action-save button button-primary" data-working="<?php _e( 'Saving...', 'amazon-s3-and-cloudfront' ); ?>"><?php _e( 'Save Bucket', 'amazon-s3-and-cloudfront' ); ?></button>
				<span><a href="#" class="bucket-action-browse"><?php _e( 'Browse existing buckets', 'amazon-s3-and-cloudfront' ); ?></a></span>
				<span><a href="#" class="bucket-action-create"><?php _e( 'Create new bucket', 'amazon-s3-and-cloudfront' ); ?></a></span>
			</p>
			<p class="bucket-actions actions select">
				<button type="submit" class="bucket-action-save button button-primary" data-working="<?php _e( 'Saving...', 'amazon-s3-and-cloudfront' ); ?>"><?php _e( 'Save Bucket', 'amazon-s3-and-cloudfront' ); ?></button>
				<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'amazon-s3-and-cloudfront' ); ?></a></span>
			</p>
		</form>
	</div>
	<div class="as3cf-bucket-select">
		<h3><?php _e( 'Select bucket', 'amazon-s3-and-cloudfront' ); ?></h3>
		<ul class="as3cf-bucket-list" data-working="<?php _e( 'Loading...', 'amazon-s3-and-cloudfront' ); ?>"></ul>
		<p class="bucket-actions actions manual">
			<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'amazon-s3-and-cloudfront' ); ?></a></span>
			<span class="right"><a href="#" class="bucket-action-refresh"><?php _e( 'Refresh', 'amazon-s3-and-cloudfront' ); ?></a></span>
		</p>
		<p class="bucket-actions actions select">
			<span><a href="#" class="bucket-action-manual"><?php _e( 'Enter bucket name', 'amazon-s3-and-cloudfront' ); ?></a></span>
			<span><a href="#" class="bucket-action-create"><?php _e( 'Create new bucket', 'amazon-s3-and-cloudfront' ); ?></a></span>
			<span class="right"><a href="#" class="bucket-action-refresh"><?php _e( 'Refresh', 'amazon-s3-and-cloudfront' ); ?></a></span>
		</p>
	</div>
	<div class="as3cf-bucket-create">
		<h3><?php _e( 'Create new bucket', 'amazon-s3-and-cloudfront' ); ?></h3>
		<form method="post" class="as3cf-create-bucket-form">
			<?php wp_nonce_field( 'as3cf-save-settings' ) ?>
			<table class="form-table">
				<tr>
					<td>
						<?php _e( 'Bucket Name:', 'amazon-s3-and-cloudfront' ); ?>
					</td>
					<td>
						<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Bucket Name', 'amazon-s3-and-cloudfront' ); ?>">
						<p class="as3cf-invalid-bucket-name"></p>
					</td>
				</tr>
				<tr>
					<td>
						<?php _e( 'Region:', 'amazon-s3-and-cloudfront' ); ?>
					</td>
					<td>
						<?php
						$aws_regions = $this->get_aws_regions();
						if ( ! defined( 'AS3CF_REGION' ) ) { ?>
							<select class="bucket-create-region" name="region_name">
								<?php foreach ( $aws_regions as $value => $label ) : ?>
									<option value="<?php echo $value; ?>"> <?php echo $label; ?></option>
								<?php endforeach; ?>
							</select>
						<?php } else {
							$region      = AS3CF_REGION;
							$region_name = isset( $aws_regions[ $region ] ) ? $aws_regions[ $region ] : $region;
							printf( __( '%s (defined in wp-config.php)', 'amazon-s3-and-cloudfront' ), $region_name );
						} ?>
					</td>
				</tr>
			</table>
			<p class="bucket-actions actions">
				<button type="submit" class="button button-primary" data-working="<?php _e( 'Creating...', 'amazon-s3-and-cloudfront' ); ?>"><?php _e( 'Create New Bucket', 'amazon-s3-and-cloudfront' ); ?></button>
				<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'amazon-s3-and-cloudfront' ); ?></a></span>
			</p>
		</form>
	</div>
</div>