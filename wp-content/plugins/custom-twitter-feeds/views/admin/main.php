<div id="ctf-admin" class="wrap">
    <h1>Custom Twitter Feeds</h1>
    <?php
    // this controls which view is included based on the selected tab
    if ( ! isset ( $tab ) ) {
        $tab = isset( $_GET["tab"] ) ? $_GET["tab"] : '';
    }
    $active_tab = CtfAdmin::get_active_tab( $tab );
    ?>

    <!-- Display the tabs along with styling for the 'active' tab -->
    <h2 class="nav-tab-wrapper">
        <a href="admin.php?page=custom-twitter-feeds&tab=configure" class="nav-tab <?php if ( $active_tab == 'configure' ){ echo 'nav-tab-active'; } ?>"><?php _e( '1. Configure', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=customize" class="nav-tab <?php if ( $active_tab == 'customize' ){ echo 'nav-tab-active'; } ?>"><?php _e( '2. Customize', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=style" class="nav-tab <?php if ( $active_tab == 'style' ){ echo 'nav-tab-active'; } ?>"><?php _e( '3. Style', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=display" class="nav-tab <?php if ( $active_tab == 'display' ){ echo 'nav-tab-active'; } ?>"><?php _e( '4. Display Your Feed', 'ctf' ); ?></a>
        <a href="admin.php?page=custom-twitter-feeds&tab=support" class="nav-tab <?php if ( $active_tab == 'support' ){ echo 'nav-tab-active'; } ?>"><?php _e( 'Support', 'ctf' ); ?></a>
    </h2>
    <?php

    if ( isset( $active_tab ) ) {
        if ( $active_tab === 'customize' ) {
            require_once CTF_URL . 'views/admin/customize.php';
        } elseif ( $active_tab === 'style' ) {
            require_once CTF_URL . 'views/admin/style.php';
        }  elseif ( $active_tab === 'configure' ) {
            require_once CTF_URL . 'views/admin/configure.php';
        } elseif ( $active_tab === 'display' ) {
            require_once CTF_URL .'views/admin/display.php';
        } elseif ( $active_tab === 'support' ) {
            require_once CTF_URL .'views/admin/support.php';
        }
    }
    ?>

    <p><i class="fa fa-life-ring" aria-hidden="true"></i>&nbsp; <?php _e('Need help setting up the plugin? Check out our <a href="https://smashballoon.com/custom-twitter-feeds/free/" target="_blank">setup directions</a>', 'custom-twitter-feeds'); ?></p>

    <div class="ctf-quick-start">
        <h3><i class="fa fa-rocket" aria-hidden="true"></i>&nbsp; <?php _e( 'Display your feed', 'custom-twitter-feeds'); ?></h3>
        <p><?php _e( "Copy and paste this shortcode directly into the page, post or widget where you'd like to display the feed:", "custom-twitter-feeds" ); ?>
        <input type="text" value="[custom-twitter-feeds]" size="18" readonly="readonly" style="text-align: center;" onclick="this.focus();this.select()" title="<?php _e( 'To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac).', 'custom-twitter-feeds' ); ?>" /></p>
        <p><?php _e( "Find out how to display <a href='?page=custom-twitter-feeds&tab=display'>multiple feeds</a>.", "custom-twitter-feeds" ); ?></p>
    </div>

    <a href="https://smashballoon.com/custom-twitter-feeds/" target="_blank" class="ctf-pro-notice">
        <img src="<?php echo plugins_url( '../../img/pro-notice.png' , __FILE__ ) ?>" alt="Custom Twitter Feeds Pro" />
    </a>
    
</div>