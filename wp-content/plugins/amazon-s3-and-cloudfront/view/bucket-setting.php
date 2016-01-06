<?php
$constant = strtoupper( str_replace( '-', '_', $prefix ) . '_BUCKET' );
$tr_class = ( isset( $tr_class ) ) ? $tr_class : '';
?>

<tr class="<?php echo $tr_class; ?>">
	<td><h3><?php _e( 'Bucket', 'amazon-s3-and-cloudfront' ); ?></h3></td>
	<td>
		<span id="<?php echo $prefix; ?>-active-bucket" class="as3cf-active-bucket">
			<?php echo $selected_bucket; // xss ok ?>
		</span>
		<a id="<?php echo $prefix; ?>-view-bucket" target="_blank" class="as3cf-view-bucket" href="<?php echo $this->get_aws_bucket_link( $selected_bucket, $selected_bucket_prefix ); ?>" title="<?php _e( 'View in S3 console', 'amazon-s3-and-cloudfront' ); ?>">
			<span class="dashicons dashicons-external"></span>
		</a>
		<?php if ( ! defined( $constant ) ) { ?>
			<a href="#" class="as3cf-change-bucket" data-as3cf-modal=".as3cf-bucket-container"><?php _e( 'Change', 'amazon-s3-and-cloudfront' ); ?></a>
		<?php } else {
			_e( '(defined in wp-config.php)', 'amazon-s3-and-cloudfront' );
		} ?>
		<input id="<?php echo $prefix; ?>-bucket" type="hidden" class="no-compare" name="bucket" value="<?php echo esc_attr( $selected_bucket ); ?>">
		<?php
		$region = $this->get_setting( 'region' );
		if ( is_wp_error( $region ) ) {
			$region = '';
		} ?>
		<input id="<?php echo $prefix; ?>-region" type="hidden" class="no-compare" name="region" value="<?php echo esc_attr( $region ); ?>">
		<?php $bucket_select = $this->get_setting( 'manual_bucket' ) ? 'manual' : ''; ?>
		<input id="<?php echo $prefix; ?>-bucket-select" type="hidden" class="no-compare"  value="<?php echo esc_attr( $bucket_select ); ?>">
		<?php
		if ( isset( $after_bucket_content ) ) {
			echo $after_bucket_content;
		}
		?>
	</td>
</tr>
