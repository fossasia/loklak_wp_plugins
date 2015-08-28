<?php
$constant = strtoupper( str_replace( '-', '_', $prefix ) . '_BUCKET' );
$tr_class = ( isset( $tr_class ) ) ? $tr_class : '';
?>

<tr class="<?php echo $tr_class; ?>">
	<td><h3><?php _e( 'Bucket', 'as3cf' ); ?></h3></td>
	<td>
		<span id="<?php echo $prefix; ?>-active-bucket" class="as3cf-active-bucket"><?php echo $selected_bucket; // xss ok ?></span>
		<?php if ( ! defined( $constant ) ) { ?>
			<a href="#" class="as3cf-change-bucket" data-as3cf-modal=".as3cf-bucket-container"><?php _e( 'Change', 'as3cf' ); ?></a>
		<?php } else {
			_e( '(defined in wp-config.php)', 'as3cf' );
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
