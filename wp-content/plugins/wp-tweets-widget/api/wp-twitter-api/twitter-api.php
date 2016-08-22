<?php
/**
 * Twitter API Wordpress library.
 * @author Tim Whitlock <@timwhitlock>
 */




/**
 * Call a Twitter API GET method.
 * 
 * @param string endpoint/method, e.g. "users/show"
 * @param array Request arguments, e.g. array( 'screen_name' => 'timwhitlock' )
 * @return array raw, deserialised data from Twitter
 * @throws TwitterApiException
 */ 
function twitter_api_get( $path, array $args = array() ){
    $Client = twitter_api_client();
    return $Client->call( $path, $args, 'GET' );
} 




/**
 * Call a Twitter API POST method.
 * 
 * @param string endpoint/method, e.g. "users/show"
 * @param array Request arguments, e.g. array( 'screen_name' => 'timwhitlock' )
 * @return array raw, deserialised data from Twitter
 * @throws TwitterApiException
 */ 
function twitter_api_post( $path, array $args = array() ){
    $Client = twitter_api_client();
    return $Client->call( $path, $args, 'POST' );
} 




/**
 * Enable caching of Twitter API responses using APC
 * @param int Cache lifetime in seconds
 * @return TwitterApiClient
 */
function twitter_api_enable_cache( $ttl ){
    $Client = twitter_api_client();
    return $Client->enable_cache( $ttl );
}




/**
 * Disable caching of Twitter API responses
 * @return TwitterApiClient
 */
function twitter_api_disable_cache(){
    $Client = twitter_api_client();
    return $Client->disable_cache();
}



 
/** 
 * Include a component from the lib directory.
 * @param string $component e.g. "core", or "admin"
 * @return void fatal error on failure
 */
function twitter_api_include(){
    foreach( func_get_args() as $component ){
        require_once twitter_api_basedir().'/lib/twitter-api-'.$component.'.php';
    }
} 



/**
 * Get plugin local base directory in case __DIR__ isn't available (php<5.3)
 */
function twitter_api_basedir(){
    static $dir;
    isset($dir) or $dir = dirname(__FILE__);
    return $dir;    
}    



/**
 * Test if system-configured client is authed and ready to use
 */
function twitter_api_configured(){
    function_exists('_twitter_api_config') or twitter_api_include('core');
    extract( _twitter_api_config() );
    return $consumer_key && $consumer_secret && $access_key && $access_secret;
} 



/**
 * Get fully configured and authenticated Twitter API client.
 * @return TwitterApiClient
 */ 
function twitter_api_client( $id = null ){
    static $clients = array();
    if( ! isset($clients[$id]) ){
        twitter_api_include('core');
        $clients[$id] = TwitterApiClient::create_instance( is_null($id) );
    }
    return $clients[$id];
}




/**
 * Contact Twitter for a request token, which will be exchanged for an access token later.
 * @return TwitterOAuthToken Request token
 */
function twitter_api_oauth_request_token( $consumer_key, $consumer_secret, $oauth_callback = 'oob' ){
    $Client = twitter_api_client('oauth');
    $Client->set_oauth( $consumer_key, $consumer_secret );     
    $params = $Client->oauth_exchange( TWITTER_OAUTH_REQUEST_TOKEN_URL, compact('oauth_callback') );
    return new TwitterOAuthToken( $params['oauth_token'], $params['oauth_token_secret'] );
}




/**
 * Exchange request token for an access token after authentication/authorization by user
 * @return TwitterOAuthToken Access token
 */
function twitter_api_oauth_access_token( $consumer_key, $consumer_secret, $request_key, $request_secret, $oauth_verifier ){
    $Client = twitter_api_client('oauth');
    $Client->set_oauth( $consumer_key, $consumer_secret, $request_key, $request_secret );     
    $params = $Client->oauth_exchange( TWITTER_OAUTH_ACCESS_TOKEN_URL, compact('oauth_verifier') );
    return new TwitterOAuthToken( $params['oauth_token'], $params['oauth_token_secret'] );
}




// Include application settings panel if in admin area
if( is_admin() ){
    twitter_api_include('core','admin');
}



/**
 * Enable localisation
 * @internal
 */
function twitter_api_load_textdomain( $locale = null, $domain = 'twitter-api' ){
    static $current_locale;
    if( is_null($locale) ){
        $locale = get_locale();
    }
    if( ! $locale || 0 === strpos($locale,'en') ){
        $current_locale and unload_textdomain( $domain );
        $locale = 'en_US';
    }
    else if( $current_locale !== $locale ){
        // purposefully not calling load_plugin_textdomain, due to symlinking 
        // and not knowing what plugin this could be called from.
        $mofile = realpath( twitter_api_basedir().'/lang/'.$domain.'-'.$locale.'.mo' );
        if( ! load_textdomain( $domain, $mofile ) ){
            $mofile = WP_LANG_DIR . '/plugins/'.$domain.'-'.$locale.'.mo';
            load_textdomain( $domain, $mofile );
        }
    }
    // detect changes in plugin locale, binding once only
    if( ! isset($current_locale) ){
        add_filter( 'plugin_locale', '_twitter_api_filter_plugin_locale', 10 , 2 );
    }
    $current_locale = $locale;
}



/**
 * Support locale switching mid execution
 * @internal
 */
function _twitter_api_filter_plugin_locale( $locale, $domain ){
    if( $domain === 'twitter-api' ){
        twitter_api_load_textdomain( $locale );
    }
    return $locale;
}



/**
 * legacy function call
 * @ignore
 */
function _twitter_api_init_l10n( $locale = null ){
    return twitter_api_load_textdomain( $locale );
}

