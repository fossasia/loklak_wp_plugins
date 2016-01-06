<div class="error inline as3cf-can-write-error as3cf-error" style="<?php echo ( $can_write ) ? 'display: none;' : ''; // xss ok ?>">
	<p>
		<strong>
			<?php _e( 'Access Denied to Bucket', 'amazon-s3-and-cloudfront' ); ?>
		</strong>&mdash;
		<?php echo $this->get_access_denied_notice_message(); ?>
	</p>
</div>