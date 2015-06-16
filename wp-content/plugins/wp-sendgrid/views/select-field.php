<select id="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '-' . $id ); ?>"
	name="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '[' . $id . ']' ); ?>">
	<?php foreach ( $options as $option_value => $title ) : ?>
		<option value="<?php echo esc_attr( $option_value ); ?>"<?php selected( $value, $option_value ); ?>><?php echo $title; ?></option>
	<?php endforeach; ?>
</select>
<?php if ( !empty( $description ) ) : ?>
	<span class="description"><?php echo $description; ?></span>
<?php endif; ?>