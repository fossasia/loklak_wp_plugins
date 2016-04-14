<?php
$value    = ( isset( $value ) ) ? $value : $this->get_setting( $key );
$class    = ( isset( $class ) ) ? 'class="' . $class . '"' : '';
$disabled = ( isset( $disabled ) && $disabled ) ? ' disabled' : '';
$values   = ( isset( $values ) && is_array( $values ) && 2 === count( $values ) ) ? $values : array( 0, 1 );
?>
<div id="<?php echo $key; ?>-wrap" data-checkbox="<?php echo $key; ?>" class="as3cf-switch<?php echo $disabled . ( $value == $values[1] ? ' on' : '' ); ?>">
	<span class="on <?php echo $value == $values[1] ? 'checked' : ''; ?>">ON</span>
	<span class="off <?php echo $value == $values[0] ? 'checked' : ''; ?>">OFF</span>
	<input type="hidden" name="<?php echo $key; ?>" value="<?php echo $values[0]; ?>" />
	<input type="checkbox" name="<?php echo $key; ?>" value="<?php echo $values[1]; ?>" id="<?php echo $key; ?>" <?php echo $value == $values[1] ? 'checked="checked" ' : ''; ?> <?php echo $class ?>/>
</div>