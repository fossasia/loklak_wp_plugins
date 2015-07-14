<div class="error inline as3cf-can-write-error as3cf-error" style="<?php echo ( $can_write ) ? 'display: none;' : ''; // xss ok ?>">
	<p>
		<strong>
			<?php _e( 'Access Denied to Bucket', 'as3cf' ); ?>
		</strong>&mdash;
		<?php printf( __( 'This could indicate your S3 policy is Read-Only. You need to go to <a href="%s">Identity and Access Management</a> in your AWS console and manage the policy for the user you\'re using for this plugin. Your policy should look something like the following:', 'as3cf' ), 'https://console.aws.amazon.com/iam/home' ); ?>
	</p>
	<pre><code>{
  "Statement": [
    {
      "Effect": "Allow",
      "Action": [
        "s3:CreateBucket",
        "s3:DeleteObject",
        "s3:Put*",
        "s3:Get*",
        "s3:List*"
      ],
      "Resource": [
        "arn:aws:s3:::*"
      ]
    }
  ]
}</code></pre>
</div>