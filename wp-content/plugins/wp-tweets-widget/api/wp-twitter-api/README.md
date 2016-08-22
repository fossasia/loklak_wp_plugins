# Twitter API WordPress Library

This library exposes a fully authenticated Twitter API client for developing WordPress plugins.

## Features

* Compatible with the new Twitter API 1.1
* OAuth flow connects your Twitter account via WordPress admin
* Access to a common Twitter API client that any plugin can use
* Caching of API responses
* Light-weight: uses WordPress utilities where possible
 

## Example plugin 

See the [Latest Tweets Widget](http://wordpress.org/extend/plugins/latest-tweets-widget/) for an example plugin using this library.


## Installation

Clone this repo to where you will develop your plugin. e.g. 

    git submodule add https://github.com/timwhitlock/wp-twitter-api.git \
      wp-content/plugins/my-twitter-plugin/api

To expose the library and its admin functions, bootstrap the library from your own plugin as follows:
```php
/*
 * Plugin Name: My Twitter Plugin
 */
if( ! function_exists('twitter_api_get') ){
    require dirname(__FILE__).'/api/twitter-api.php';
}
```

## Authentication

Once the plugin is installed and enabled, you can bind it to a Twitter account as follows:

* Register a Twitter application at https://dev.twitter.com/apps
* Note the Consumer key and Consumer secret under *OAuth settings*
* Log into WordPress admin and go to *Settings > Twitter API*
* Enter the consumer key and secret and click 'Save settings'
* Click the 'Connect to Twitter' button and follow the prompts.

Any WordPress plugin can now make fully authenticated calls to the Twitter API. The functions are documented below.


## Twitter Client

To check whether the user has authenticated the plugin and configured the oAuth tokens you can use the following function.

#### twitter_api_configured
`bool twitter_api_configured ()`
Returns True if the user has authenticated the plugin and configured oAuth tokens


The following functions are available from anywhere as soon as the plugin is authenticated.
They all operate as the Twitter account you connected in your admin area.

#### twitter_api_get
`array twitter_api_get ( string $path [, array $args ]  )`  
GETs data from the Twitter API, returning the raw unserialized data.

`$path` is any Twitter API method, e.g. `'users/show'` or `'statuses/user_timeline'`  
`$args` is an associative array of parameters, e.g. `array('screen_name'=>'timwhitlock')`

Note that neither the path nor the arguments are validated.

#### twitter_api_post
`array twitter_api_post ( string $path [, array $args ]  )`  
As above, but POSTs data to the Twitter API.

#### twitter_api_enable_cache
`TwitterApiClient twitter_api_enable_cache( int $ttl )`  
Enable caching of Twitter response data for `$ttl` seconds.

#### twitter_api_disable_cache
`TwitterApiClient twitter_api_disable_cache( )`  
Disables caching of responses. Caching is disabled by default.


## Custom OAuth flows

The above functions work with a single authenticated Twitter account.
If you want to authenticate multiple clients or create OAuth flows other than the one provided, you'll have to work directly with the `TwitterApiClient` class and roll your own OAuth user flows.

The following utility functions will do some lifting for you, but please see [Twitter's own documentation](https://dev.twitter.com/docs/auth/obtaining-access-tokens) if you're not familiar with the process.

#### twitter_api_oauth_request_token
`TwitterOAuthToken twitter_api_oauth_request_token ( string $consumer_key, string $consumer_secret, string $oauth_callback )`  
Fetches an OAuth request token from Twitter: e.g. `{ key: 'your request key', secret: 'your request secret' }`

#### twitter_api_oauth_access_token
`TwitterOAuthToken twitter_api_oauth_access_token ( $consumer_key, $consumer_secret, $request_key, $request_secret, $oauth_verifier )`
Exhanges a verified request token for an access token: e.g. `{ key: 'your access key', secret: 'your access secret' }`

### TwitterApiClient

Once you have your own authentication credentials you can work directly with the API client.
This example shows the main methods you might use:

```php
    try {
        if ( twitter_api_configured() ) {
            $Client = twitter_api_client('some client');
            $Client->set_oauth( 'my consumer key', 'my consumer secret', 'their access key', 'their access secret' );
            $user = $Client->call( 'users/show', array( 'screen_name' => 'timwhitlock' ), 'GET' );
            var_dump( $user );
        }
    }
    catch( TwitterApiRateLimitException $Ex ){
        $info = $Client->last_rate_limit();
        wp_die( 'Rate limit exceeded. Try again at '.date( 'H:i:s', $info['reset'] ) );
    }
    catch( TwitterApiException $Ex ){
        wp_die( 'Twitter responded with status '.$Ex->getStatus().', '.$Ex->getMessage() );
    }
    catch( Exception $Ex ){
        wp_die( 'Fatal error, '. $Ex->getMessage() );
    }
```
