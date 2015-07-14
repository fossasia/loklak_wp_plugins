<?php
$type        = ( isset( $type ) ) ? $type : 'notice-info';
$dismissible = ( isset( $dismissible ) ) ? $dismissible : false;
$inline      = ( isset( $inline ) ) ? $inline : false;
?>
<div class="notice <?php echo $type; ?><?php echo ( $dismissible ) ? ' is-dismissible' : ''; ?> as3cf-notice <?php echo ( $inline ) ? ' inline' : ''; ?>">
	<p><?php echo $message; // xss ok ?></p>
</div>