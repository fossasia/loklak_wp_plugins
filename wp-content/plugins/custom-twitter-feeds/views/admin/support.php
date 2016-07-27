<h3><?php _e( 'Need help?', 'custom-twitter-feeds' ); ?></h3>

<p><i class="fa fa-life-ring" aria-hidden="true"></i>&nbsp; <?php _e( 'Check out our ', 'custom-twitter-feeds'); ?><a href="https://smashballoon.com/custom-twitter-feeds/docs/" target="_blank"><?php _e( 'setup directions', 'custom-twitter-feeds' ); ?></a> <?php _e( 'for a step-by-step guide on how to setup and use the plugin', 'custom-twitter-feeds' ); ?>.</p>

<p><i class="fa fa-envelope" aria-hidden="true"></i>&nbsp; <?php _e( 'Have a problem? Submit a ', 'custom-twitter-feeds' ); ?><a href="https://smashballoon.com/custom-twitter-feeds/support/" target="_blank"><?php _e( 'support ticket', 'custom-twitter-feeds' ); ?></a> <?php _e( 'on our website', 'custom-twitter-feeds' ); ?>.  <?php _e( 'Please include your <b>System Info</b> below with all support requests.', 'custom-twitter-feeds'  ); ?></p>

<br />
<h3><?php _e('System Info', 'custom-twitter-feeds' ); ?> &nbsp; <i style="color: #666; font-size: 11px; font-weight: normal;"><?php _e( 'Click the text below to select all', 'custom-twitter-feeds' ); ?></i></h3>

<textarea readonly="readonly" onclick="this.focus();this.select()" title="To copy, click the field then press Ctrl + C (PC) or Cmd + C (Mac)." style="width: 70%; height: 500px; white-space: pre; font-family: Menlo,Monaco,monospace;">
## SITE/SERVER INFO: ##
Plugin Version:           <?php echo CTF_TITLE . ' v' . CTF_VERSION. "\n"; ?>
Site URL:                 <?php echo site_url() . "\n"; ?>
Home URL:                 <?php echo home_url() . "\n"; ?>
WordPress Version:        <?php echo get_bloginfo( 'version' ) . "\n"; ?>
PHP Version:              <?php echo PHP_VERSION . "\n"; ?>
Web Server Info:          <?php echo $_SERVER['SERVER_SOFTWARE'] . "\n"; ?>
PHP allow_url_fopen:      <?php echo ini_get( 'allow_url_fopen' ) ? "Yes" . "\n" : "No" . "\n"; ?>
PHP cURL:                 <?php echo is_callable('curl_init') ? "Yes" . "\n" : "No" . "\n"; ?>
JSON:                     <?php echo function_exists("json_decode") ? "Yes" . "\n" : "No" . "\n" ?>
SSL Stream:               <?php echo in_array('https', stream_get_wrappers()) ? "Yes" . "\n" : "No" . "\n" //extension=php_openssl.dll in php.ini ?>

## ACTIVE PLUGINS: ##
<?php
$plugins = get_plugins();
$active_plugins = get_option( 'active_plugins', array() );

foreach ( $plugins as $plugin_path => $plugin ) {
    // If the plugin isn't active, don't show it.
    if ( in_array( $plugin_path, $active_plugins ) ) {
        echo $plugin['Name'] . ': ' . $plugin['Version'] ."\n";
    }
}
?>

## OPTIONS: ##
<?php
$options = get_option( 'ctf_options' );
foreach ( $options as $key => $val ) {
    $label = $key . ':';
    $value = isset( $val ) ? esc_attr( $val ) : 'unset';
    echo str_pad( $label, 24 ) . $value ."\n";
}

$options = get_option( 'ctf_options' );
$consumer_key = ! empty( $options['consumer_key'] ) && $options['have_own_tokens'] ? $options['consumer_key'] : 'FPYSYWIdyUIQ76Yz5hdYo5r7y';
$consumer_secret = ! empty( $options['consumer_secret'] ) && $options['have_own_tokens'] ? $options['consumer_secret'] : 'GqPj9BPgJXjRKIGXCULJljocGPC62wN2eeMSnmZpVelWreFk9z';
$request_settings = array(
    'consumer_key' => $consumer_key,
    'consumer_secret' => $consumer_secret,
    'access_token' => $options['access_token'],
    'access_token_secret' => $options['access_token_secret']
);

$request_method = $options['request_method'];

include_once( CTF_URL . '/inc/CtfOauthConnect.php' );
$twitter_api = new CtfOauthConnect( $request_settings, 'usertimeline' );
$twitter_api->setUrlBase();
$get_fields = array( 'count' => '1' );
$twitter_api->setGetFields( $get_fields );
$twitter_api->setRequestMethod( $request_method );

$twitter_api->performRequest();
$response = json_decode( $twitter_api->json , $assoc = true );
$screen_name = isset( $response[0] ) ? $response[0]['user']['screen_name'] : 'error';
if ( $screen_name == 'error' ) {
    if ( isset( $response['errors'][0] ) ) {
        $twitter_api->api_error_no = $response['errors'][0]['code'];
        $twitter_api->api_error_message = $response['errors'][0]['message'];
    }
}
?>

## Twitter API RESPONSE: ##
<?php
echo 'Screen Name:   ' . $screen_name."\n";
echo 'Error No:      ' . $twitter_api->api_error_no."\n";
echo 'Error Message: ' . $twitter_api->api_error_message."\n";
?>

</textarea>