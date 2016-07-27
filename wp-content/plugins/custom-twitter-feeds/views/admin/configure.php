<?php
settings_errors(); ?>
<form method="post" action="options.php">
    <?php settings_fields('ctf_options'); // matches the options name ?>
    <?php do_settings_sections('ctf_options_connect'); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    <hr>
    <?php do_settings_sections('ctf_options_feed_settings'); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
</form>
<p><i class="fa fa-chevron-circle-right" aria-hidden="true"></i>&nbsp; <?php _e('Next Step: <a href="?page=custom-twitter-feeds&tab=customize">Customize your Feed</a>'); ?></p>
