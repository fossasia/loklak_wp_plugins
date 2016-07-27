<?php
settings_errors(); ?>
<p class="ctf-contents-links" id="general">
    <span>Quick links: </span>
    <?php
    $quick_links = array();
    $quick_links = apply_filters( 'ctf_admin_style_quick_links', $quick_links );

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
    <?php do_settings_sections( 'ctf_options_general_style' ); // matches the section name ?>
    <hr>
    <a id="header"></a>
    <?php do_settings_sections( 'ctf_options_header' ); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    <hr>
    <a id="date"></a>
    <?php do_settings_sections( 'ctf_options_date' ); // matches the section name ?>
    <hr>
    <a id="author"></a>
    <?php do_settings_sections( 'ctf_options_author' ); // matches the section name ?>
    <hr>
    <a id="text"></a>
    <?php do_settings_sections( 'ctf_options_text' ); // matches the section name ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
    <hr>
    <a id="links"></a>
    <?php do_settings_sections( 'ctf_options_links' ); // matches the section name ?>
    <hr>
    <a id="quoted"></a>
    <?php do_settings_sections( 'ctf_options_quoted' ); // matches the section name ?>
    <hr>
    <a id="actions"></a>
    <?php do_settings_sections( 'ctf_options_actions' ); // matches the section name ?>
    <hr>
    <a id="load"></a>
    <?php do_settings_sections( 'ctf_options_load' ); // matches the section name ?>
    <?php do_action( 'ctf_admin_add_settings_sections_to_style' ); ?>
    <p class="submit"><input class="button-primary" type="submit" name="save" value="<?php esc_attr_e( 'Save Changes' ); ?>" /></p>
</form>
<p><i class="fa fa-chevron-circle-right" aria-hidden="true"></i>&nbsp; <?php _e('Next Step: <a href="?page=custom-twitter-feeds&tab=display">Display your Feed</a>'); ?></p>
