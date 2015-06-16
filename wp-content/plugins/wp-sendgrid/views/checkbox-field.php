<input type="checkbox" id="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '-' . $id ); ?>"
       name="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '[' . $id . ']' ); ?>"
       value="1" <?php checked( $value ); ?> />
<?php if ( !empty( $label ) ) :?>
	<label for="<?php echo esc_attr( WP_SendGrid_Settings::SETTINGS_OPTION_NAME . '-' . $id ); ?>"><?php echo $label; ?></label>
<?php endif; ?>
<?php if ( !empty( $description ) ) : ?>
	<span class="description"><?php echo $description; ?></span>
<?php endif; ?>