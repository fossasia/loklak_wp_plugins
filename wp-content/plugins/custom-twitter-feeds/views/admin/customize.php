<?php
settings_errors(); ?>
<p class="ctf-contents-links" id="general">
    <span>Quick links: </span>
    <?php
    $quick_links = array();
    $quick_links = apply_filters( 'ctf_admin_customize_quick_links', $quick_links );

    foreach ( $quick_links as $quick_link ) {
        echo '<a href="#' . $quick_link[0] . '">' . $quick_link[1] . '</a>';
    }
    //echo '<pre>';
    //var_dump( get_option('ctf_options'));
    //echo '</pre>';
    ?>
</p>
<form method="post" action="options.php">
    <?php settings_fields( 'ctf_options' ); // matches the options name ?>
    <?php do_settings_sections( 'ctf_options_general' ); // matches the section name ?>
    <hr>
    <a id="showhide"></a>
    <?php do_settings_sections( 'ctf_options_showandhide' ); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    <hr>
    <?php do_action( 'ctf_admin_add_settings_sections_to_customize' ); ?>
    <a id="misc"></a>
    <?php do_settings_sections( 'ctf_options_misc' ); // matches the section name ?>
    <hr>
    <a id="advanced"></a>
    <?php do_settings_sections('ctf_options_advanced'); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
</form>
<p><i class="fa fa-chevron-circle-right" aria-hidden="true"></i>&nbsp; <?php _e('<b>Next Step:</b> <a href="?page=custom-twitter-feeds&tab=style">Style your Feed</a>'); ?></p>
