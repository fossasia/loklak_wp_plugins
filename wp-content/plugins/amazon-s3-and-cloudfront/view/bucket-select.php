<div class="as3cf-bucket-container <?php echo $prefix ;?>">
	<div class="as3cf-bucket-manual">
		<h3 data-modal-title="<?php _e( 'Change bucket', 'as3cf' ); ?>"><?php _e( 'What bucket would you like to use?', 'as3cf' ); ?></h3>
		<form method="post" class="as3cf-manual-save-bucket-form">
			<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Existing bucket name', 'as3cf' ); ?>" value="<?php echo $selected_bucket; ?>">
			<p class="bucket-actions actions manual">
				<button type="submit" class="bucket-action-save button button-primary" data-working="<?php _e( 'Saving...', 'as3cf' ); ?>"><?php _e( 'Save Bucket', 'as3cf' ); ?></button>
				<span><a href="#" class="bucket-action-browse"><?php _e( 'Browse existing buckets', 'as3cf' ); ?></a></span>
				<span><a href="#" class="bucket-action-create"><?php _e( 'Create new bucket', 'as3cf' ); ?></a></span>
			</p>
			<p class="bucket-actions actions select">
				<button type="submit" class="bucket-action-save button button-primary" data-working="<?php _e( 'Saving...', 'as3cf' ); ?>"><?php _e( 'Save Bucket', 'as3cf' ); ?></button>
				<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'as3cf' ); ?></a></span>
			</p>
		</form>
	</div>
	<div class="as3cf-bucket-select">
		<h3><?php _e( 'Select bucket', 'as3cf' ); ?></h3>
		<ul class="as3cf-bucket-list" data-working="<?php _e( 'Loading...', 'as3cf' ); ?>"></ul>
		<p class="bucket-actions actions manual">
			<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'as3cf' ); ?></a></span>
			<span class="right"><a href="#" class="bucket-action-refresh"><?php _e( 'Refresh', 'as3cf' ); ?></a></span>
		</p>
		<p class="bucket-actions actions select">
			<span><a href="#" class="bucket-action-manual"><?php _e( 'Enter bucket name', 'as3cf' ); ?></a></span>
			<span><a href="#" class="bucket-action-create"><?php _e( 'Create new bucket', 'as3cf' ); ?></a></span>
			<span class="right"><a href="#" class="bucket-action-refresh"><?php _e( 'Refresh', 'as3cf' ); ?></a></span>
		</p>
	</div>
	<div class="as3cf-bucket-create">
		<h3><?php _e( 'Create new bucket', 'as3cf' ); ?></h3>
		<form method="post" class="as3cf-create-bucket-form">
			<?php wp_nonce_field( 'as3cf-save-settings' ) ?>
			<table class="form-table">
				<tr>
					<td>
						<?php _e( 'Bucket Name:', 'as3cf' ); ?>
					</td>
					<td>
						<input type="text" class="as3cf-bucket-name" name="bucket_name" placeholder="<?php _e( 'Bucket Name', 'as3cf' ); ?>">
						<p class="as3cf-invalid-bucket-name"></p>
					</td>
				</tr>
				<tr>
					<td>
						<?php _e( 'Region:', 'as3cf' ); ?>
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
							printf( __( '%s (defined in wp-config.php)', 'as3cf' ), $region_name );
						} ?>
					</td>
				</tr>
			</table>
			<p class="bucket-actions actions">
				<button type="submit" class="button button-primary" data-working="<?php _e( 'Creating...', 'as3cf' ); ?>"><?php _e( 'Create New Bucket', 'as3cf' ); ?></button>
				<span><a href="#" class="bucket-action-cancel"><?php _e( 'Cancel', 'as3cf' ); ?></a></span>
			</p>
		</form>
	</div>
</div>