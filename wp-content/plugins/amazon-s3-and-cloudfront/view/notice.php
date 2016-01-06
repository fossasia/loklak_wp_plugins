<?php
$type        = ( isset( $type ) ) ? $type : 'notice-info';
$dismissible = ( isset( $dismissible ) ) ? $dismissible : false;
$inline      = ( isset( $inline ) ) ? $inline : false;
$id          = ( isset( $id ) ) ? 'id="' . $id . '"' : '';
$style       = ( isset( $style ) ) ? $style : '';
$auto_p      = ( isset( $auto_p ) ) ? $auto_p : 'true';
$class       = ( isset( $class ) ) ? $class : '';
?>
<div <?php echo $id; ?> class="notice <?php echo $type; ?><?php echo ( $dismissible ) ? ' is-dismissible' : ''; ?> as3cf-notice <?php echo ( $inline ) ? ' inline' : ''; ?> <?php echo ( '' !== $class ) ? ' ' . $class : ''; ?>" style="<?php echo $style; ?>">
<?php if ( $auto_p ) : ?>
	<p>
<?php endif; ?>
		<?php echo $message; // xss ok ?>
<?php if ( $auto_p ) : ?>
	</p>
<?php endif; ?>
</div>