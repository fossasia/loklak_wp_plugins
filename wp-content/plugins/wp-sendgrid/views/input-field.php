<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '-' . $id ); ?>"
       name="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '[' . $id . ']' ); ?>"
       value="<?php echo esc_attr( $value ); ?>" />
<?php if ( !empty( $description ) ) : ?>
	<span class="description"><?php echo $description; ?></span>
<?php endif; ?>
