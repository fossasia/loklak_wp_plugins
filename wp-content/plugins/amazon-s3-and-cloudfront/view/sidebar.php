<div class="as3cf-sidebar">

	<div class="as3cf-banner"><img src="<?php echo esc_url( plugins_url( 'assets/img/snail.jpg', $this->plugin_file_path ) ); ?>" width="292" height="165" alt="" /></div>

	<form method="post" action="https://deliciousbrains.createsend.com/t/t/s/dlihik/" target="_blank" class="subscribe block">
		<h2><?php _e( 'Pro Version?', 'as3cf' ); ?></h2>

		<?php $user = wp_get_current_user(); ?>

		<p class="intro">
			<?php echo wptexturize( __( "We're working on a pro version that will include the following features:", 'as3cf' ) ); // xss ok ?>
		</p>

		<ul>
			<li><?php echo wptexturize( __( 'Copy existing Media Library to S3', 'as3cf' ) ); // xss ok ?></li>
			<li><?php echo wptexturize( __( 'Serve theme JS & CSS from S3/CloudFront', 'as3cf' ) ); // xss ok ?></li>
			<li><?php echo wptexturize( __( 'WooCommerce & <abbr title="Easy Digital Downloads">EDD</abbr> integration', 'as3cf' ) ); // xss ok ?></li>
			<li><?php echo wptexturize( __( 'Awesome email support', 'as3cf' ) ); // xss ok ?></li>
		</ul>

		<div class="field notify-name">
			<input type="text" name="cm-name" value="<?php echo esc_attr( trim( $user->first_name . ' ' . $user->last_name ) ); ?>" placeholder="<?php _e( 'Your Name', 'as3cf' ); ?>"/>
		</div>

		<div class="field notify-email">
			<input type="email" name="cm-dlihik-dlihik" value="<?php echo esc_attr( $user->user_email ); ?>" placeholder="<?php _e( 'Your Email', 'as3cf' ); ?>"/>
		</div>

		<div class="field submit-button">
			<input type="submit" class="button" value="<?php _e( 'Send me news about a pro version', 'as3cf' ); ?>"/>
		</div>

		<p class="promise">
			<?php _e( 'We promise we will not use your email for anything else and you can unsubscribe with 1-click anytime.', 'as3cf' ); ?>
		</p>
	</form>

	<div class="block credits">
		<h4>Created &amp; maintained by</h4>
		<ul>
			<li>
				<a href="http://profiles.wordpress.org/bradt/">
					<img src="//www.gravatar.com/avatar/e538ca4cb34839d4e5e3ccf20c37c67b?size=64" alt="" width="32" height="32">
					<span>Brad Touesnard</span>
				</a>
			</li>
			<li>
				<a href="https://deliciousbrains.com/?utm_source=insideplugin&utm_medium=web&utm_content=sidebar&utm_campaign=as3cf">
					<img src="//www.gravatar.com/avatar/e62fc2e9c8d9fc6edd4fea5339036a91?size=64" alt="" width="32" height="32">
					<span>Delicious Brains Inc.</span>
				</a>
			</li>
		</ul>
	</div>
</div>
