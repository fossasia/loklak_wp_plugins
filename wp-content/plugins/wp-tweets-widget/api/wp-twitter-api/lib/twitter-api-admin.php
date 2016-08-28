<?php
/**
 * Twitter API admin functions.
 * Configures and authenticates with the Twitter API.
 * @author Tim Whitlock <@timwhitlock>
 */
 
 
// register our admin page with the menu, and we're done.
add_action('admin_menu', 'twitter_api_admin_menu');


/**
 * Open admin page with header and message
 */
function twitter_api_admin_render_header( $subheader, $css = '' ){
    ?>
     <div>    
        <h1><?php echo esc_html__('Tweets Widget Settings','twitter-api')?></h1><hr/>
        <form action="<?php echo twitter_api_admin_base_uri()?>" method="post">
        <?php
            settings_fields( 'loklak-settings' );
            do_settings_sections( 'loklak-settings' );
        ?>
        <hr/>
        <h2>2. Twitter API</h2>
        <div class="wrap"><div class="<?php echo $css?>">
            <h3><?php echo esc_html($subheader)?></h3>
        </div></div>
    <?php
} 


/**
 * Close admin page
 */
function twitter_api_admin_render_footer(){
    ?>
    </div>
    <?php
}


/**
 * Render form for viewing/editing of application settings
 */
function twitter_api_admin_render_form(){
    extract( _twitter_api_config() );
    ?>   
    <p>
        <label for="twitter-api--consumer-key"><?php echo __('OAuth Consumer Key','twitter-api');?>:</label><br />
        <input type="text" size="64" name="saf_twitter[consumer_key]" id="twitter-api--consumer-key" value="<?php echo esc_html($consumer_key)?>" />
    </p>
    <p>
        <label for="twitter-api--consumer-secret"><?php echo __('OAuth Consumer Secret','twitter-api');?>:</label><br />
        <input type="text" size="64" name="saf_twitter[consumer_secret]" id="twitter-api--consumer-secret" value="<?php echo esc_html($consumer_secret)?>" />
    </p>
    <p>
        <label for="twitter-api--access-key"><?php echo __('OAuth Access Token','twitter-api');?>:</label><br />
        <input type="text" size="64" name="saf_twitter[access_key]" id="twitter-api--access-key" value="<?php echo esc_html($access_key)?>" />
    </p>
    <p>
        <label for="twitter-api--access-secret"><?php echo __('OAuth Access Secret','twitter-api');?>:</label><br />
        <input type="text" size="64" name="saf_twitter[access_secret]" id="twitter-api--access-secret" value="<?php echo esc_html($access_secret)?>" />
    </p>
    <hr/>
    <p class="submit">
        <input type="submit" class="button-primary" value="<?php echo esc_html__('Save settings','twitter-api')?>" />
    </p>
    <small>
        <?php echo esc_html__('These details are available in','twitter-api')?> 
        <a href="https://dev.twitter.com/apps"><?php echo esc_html__('your Twitter dashboard','twitter-api')?></a>
    </small>
    <?php
}



/**
 * Render "Connect" button for authenticating at twitter.com
 * @param string OAuth application Consumer Key
 * @param string OAuth application Consumer Secret
 */
function twitter_api_admin_render_login( $consumer_key, $consumer_secret ){
    try {
        $callback = twitter_api_admin_base_uri();
        $Token = twitter_api_oauth_request_token( $consumer_key, $consumer_secret, $callback );
    }
    catch( Exception $Ex ){
        echo '<div class="wrap"><div class="error"><p><strong>Error:</strong> ',esc_html( $Ex->getMessage() ),'</p></div></div>';
        return;
    }
    // Remember request token and render link to authorize
    // we're storing permanently - not using session here, because WP provides no session API.
    _twitter_api_config( array( 'request_secret' => $Token->secret ) );
    $href = $Token->get_authorization_url();
    echo '<p><a class="button-primary" href="',esc_html($href),'">'.esc_html__('Connect to Twitter','twitter-api').'</a></p>';
    echo '<p>&nbsp;</p>';
}
 
 
 
 
/**
 * Render full admin page
 */ 
function twitter_api_admin_render_page(){
    if ( ! current_user_can('manage_options') ){
        twitter_api_admin_render_header( __("You don't have permission to manage Twitter API settings",'twitter-api'),'error');
        twitter_api_admin_render_footer();
        return;
    }
    try {
        // update application settings if posted
        if( isset($_POST['saf_twitter']) && is_array( $update = $_POST['saf_twitter'] ) ){
            $conf = _twitter_api_config( $update );
        }

        // else get current settings
        else {
            $conf = _twitter_api_config();
        }

        loklak_settings_conf( );

        // check whether we have any OAuth params
        extract( $conf );
        if( ! $consumer_key || ! $consumer_secret ){
            throw new Exception( __('Twitter API not configured. Details are available in your <a href="https://dev.twitter.com/apps">Twitter dashboard</a>. The plugin is still functional if you use the loklak.org API.','twitter-api') );
        }

        // else exchange access token if callback // request secret saved as option
        if( isset($_GET['oauth_token']) && isset($_GET['oauth_verifier']) ){
            $Token = twitter_api_oauth_access_token( $consumer_key, $consumer_secret, $_GET['oauth_token'], $request_secret, $_GET['oauth_verifier'] );
            // have access token, update config and destroy request secret
            $conf = _twitter_api_config( array(
                'request_secret' => '',
                'access_key'     => $Token->key,
                'access_secret'  => $Token->secret,
            ) );
            extract( $conf );
            // fall through to verification of credentials
        }

        // else administrator needs to connect / authenticate with Twitter.
        if( ! $access_key || ! $access_secret ){
            twitter_api_admin_render_header( __('Plugin not yet authenticated with Twitter','twitter-api'), 'error' );
            twitter_api_admin_render_login( $consumer_key, $consumer_secret );
        }

        // else we have auth - verify that tokens are all still valid
        else {
            $me = twitter_api_get('account/verify_credentials');
            twitter_api_admin_render_header( sprintf( __('Authenticated as @%s','twitter-api'), $me['screen_name'] ), 'updated' );
        }

    }
    catch( TwitterApiException $Ex ){
        twitter_api_admin_render_header( $Ex->getStatus().': Error '.$Ex->getCode().', '.$Ex->getMessage(), 'error' );
        if( 401 === $Ex->getStatus() ){
            twitter_api_admin_render_login( $consumer_key, $consumer_secret );
        }
    }
    catch( Exception $Ex ){
        twitter_api_admin_render_header( $Ex->getMessage(), 'error' );
    }
    
    // end admin page with options form and close wrapper
    twitter_api_admin_render_form();
    echo '</form>';
    twitter_api_admin_render_footer();
}



/**
 * Calculate base URL for admin OAuth callbacks
 * @return string
 */
function twitter_api_admin_base_uri(){
    static $base_uri;
    if( ! isset($base_uri) ){
        $port = isset($_SERVER['HTTP_X_FORWARDED_PORT']) ? $_SERVER['HTTP_X_FORWARDED_PORT'] : $_SERVER['SERVER_PORT'];
        $prot = '443' === $port ? 'https:' : 'http:';
        $base_uri = $prot.'//'.$_SERVER['HTTP_HOST'].''.current( explode( '&', $_SERVER['REQUEST_URI'], 2 ) );
    }
    return $base_uri;
}





/**
 * Admin menu registration callback
 */
function twitter_api_admin_menu() {
    $title = __('Tweets Widget','twitter-api');
    add_options_page( $title, $title, 'manage_options', 'tweets-widget-settings', 'twitter_api_admin_render_page');
}


