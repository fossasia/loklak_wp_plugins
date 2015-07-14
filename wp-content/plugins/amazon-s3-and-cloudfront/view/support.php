<div id="tab-support" class="aws-content as3cf-tab">
<?php
if ( ! $this->is_pro() ) {
	$this->render_view( 'wordpress-org-support' );
}

do_action( 'as3cf_support_pre_debug' );

$this->render_view( 'debug-info' );

do_action( 'as3cf_support_post_debug' );

?>
</div>
