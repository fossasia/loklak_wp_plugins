<?php
/**
 * Class OauthConnect
 *
 * Simple, lightweight class to make a connection to the Twitter API
 * Supports home timeline, user timeline, and search endpoints
 */

// Don't load directly
if ( ! defined( 'ABSPATH' ) ) {
    die( '-1' );
}

class CtfOauthConnect
{
    /**
     * @var string
     */
    protected $base_url;

    /**
     * @var string
     */
    private $get_fields;

    /**
     * @var string
     */
    private $request_method;

    /**
     * @var array
     */
    private $oauth;

    /**
     * @var string
     */
    private $header;

    /**
     * @var bool
     */
    public $api_error_no = false;

    /**
     * @var bool
     */
    public $api_error_message = false;

    /**
     * @var string
     */
    public $json;

    /**
     * @param array $request_settings   all necessary tokens for OAuth connection
     * @param $feed_type string         type of Twitter feed
     */
    public function __construct( array $request_settings, $feed_type )
    {
        $this->consumer_key = $request_settings['consumer_key'];
        $this->consumer_secret = $request_settings['consumer_secret'];
        $this->access_token = $request_settings['access_token'];
        $this->access_token_secret = $request_settings['access_token_secret'];
        $this->feed_type = $feed_type;
    }

    /**
     * Sets the complete url for our API endpoint. GET fields will be added later
     */
    public function setUrlBase()
    {
        switch ( $this->feed_type ) {
            case "hometimeline":
                $this->base_url = 'https://api.twitter.com/1.1/statuses/home_timeline.json';
                break;
            case "search":
                $this->base_url = 'https://api.twitter.com/1.1/search/tweets.json';
                break;
            default:
                $this->base_url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';
        }
    }

    /**
     * Encodes an array of GET field data into html characters for including in a URL
     *
     * @param array $get_fields array of GET fields that are compatible with the Twitter API
     */
    public function setGetFields( array $get_fields )
    {
        $this->get_fields = '?' . http_build_query( $get_fields );
    }

    /**
     * Users can manually set the request method if there is an uncatchable error in
     * the other methods
     *
     * @param string $request_method
     */
    public function setRequestMethod( $request_method = 'auto' )
    {
        $this->request_method = $request_method;
    }

    /**
     * Uses the OAuth data to build the base string needed to create the
     * OAuth signature to be used in the header of the request
     *
     * @param $oauth array  oauth data without the signature
     * @return string       the base string for needed to construct the oauth signature
     */
    private function buildBaseString( $oauth )
    {
        $base_string = array();
        ksort( $oauth );

        // start forming the header string by creating a numeric index array with
        // each part of the header string it's own element in the array
        foreach ( $oauth as $key => $value ) {
            $base_string[] = rawurlencode( $key ) . '=' . rawurlencode( $value );
        }

        // convert the array of values into a single encoded string and return
        return 'GET&' . rawurlencode( $this->base_url ) . '&' . rawurlencode( implode( '&', $base_string ) );
    }

    /**
     * Builds the OAuth data array that is used to authenticate the connection
     * to the Twitter API
     */
    public function buildOauth()
    {
        $oauth = array(
            'oauth_consumer_key' => $this->consumer_key,
            'oauth_nonce' => time(),
            'oauth_signature_method' => 'HMAC-SHA1',
            'oauth_token' => $this->access_token,
            'oauth_timestamp' => time(),
            'oauth_version' => '1.0'
        );

        $getfields = str_replace( '?', '', explode( '&', $this->get_fields ) );

        // add the get fields to the oauth associative array to be
        // formed into the header string eventually
        foreach ( $getfields as $getfield ) {
            $split = explode( '=', $getfield );

            if ( isset( $split[1] ) ) {
                $oauth[$split[0]] = urldecode( $split[1] );
            }
        }

        // the OAuth signature for Twitter is a hashed, encoded version of the base url, 4 different keys
        $base_string = $this->buildBaseString( $oauth );
        $composite_key = rawurlencode( $this->consumer_secret ) . '&' . rawurlencode( $this->access_token_secret );
        $oauth_signature = base64_encode( hash_hmac( 'sha1', $base_string, $composite_key, true ) );
        $oauth['oauth_signature'] = $oauth_signature;

        $this->oauth = $oauth;
    }

    /**
     * Since the OAuth data is passed in a url, special characters need to be encoded
     */
    private function encodeHeader()
    {
        $header = 'Authorization: OAuth ';
        $values = array();

        // each element of the header needs to have it's special characters encoded for
        // passing through a url
        foreach ( $this->oauth as $key => $value ) {
            if ( in_array( $key, array( 'oauth_consumer_key', 'oauth_nonce', 'oauth_signature',
                'oauth_signature_method', 'oauth_timestamp', 'oauth_token', 'oauth_version' ) ) ){
                $values[] = "$key=\"" . rawurlencode( $value ) . "\"";
            }
        }

        $header .= implode( ', ', $values );
        $this->header = $header;
    }

    /**
     * Attempts to connect to the Twitter api using curl
     *
     * @param $url      string the complete api endpoint url
     * @return mixed    json string retrieved in the request
     */
    private function curlRequest( $url )
    {
        $br = curl_init( $url );

        curl_setopt( $br, CURLOPT_HTTPHEADER, array( $this->header ) ); // must pass in array
        curl_setopt( $br, CURLOPT_URL, $url );
        curl_setopt( $br, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $br, CURLOPT_TIMEOUT, 10 );
        curl_setopt( $br, CURLOPT_SSL_VERIFYPEER, false ); // must be false to connect without signed certificate
        curl_setopt( $br, CURLOPT_ENCODING, '' );

        $json = curl_exec( $br );

        if ( curl_errno( $br ) ){
            $this->api_error_no = curl_errno( $br );
            $this->api_error_message = curl_error( $br );
        }

        curl_close( $br );

        return $json;
    }

    /**
     * Attempts to connect to the Twitter api using file get contents
     *
     * @param $url      string the complete api endpoint url
     * @return mixed    json string retrieved in the request
     */
    public function fileGetContentsRequest( $url )
    {
        $opts = array(
            'http' => array(
                'method' => 'GET',
                'header' => $this->header
            )
        );

        $context = stream_context_create( $opts );

        return file_get_contents( $url, false, $context );
    }

    /**
     * Attempts to connect to the Twitter api using WP_HTTP class
     *
     * @param $url      string the complete api endpoint url
     * @return mixed    json string retrieved in the request
     */
    private function wpHttpRequest( $url )
    {
        $args = array(
            'headers' => $this->header,
            'timeout' => 60,
            'sslverify' => false
        );
        $result = wp_remote_get( $url, $args );

        return $result['body']; // just need the body to keep everything simple
    }

    /**
     * Uses the data created and gathered up to this point to make the actual connection
     * to the Twitter API. It first tests whether or not a curl connection is possible,
     * followed by file_get_contents connection, then defaults to the WordPress WP_HTTP object
     *
     * @return mixed|string raw json data retrieved from the API request
     */
    public function performRequest()
    {
        $url = $this->base_url . $this->get_fields;
        $this->buildOauth();
        $this->encodeHeader();

        switch ( $this->request_method ) {
            case 'curl':
                $this->json = $this->curlRequest( $url );
                break;
            case 'file_get_contents':
                $this->json = $this->fileGetContentsRequest( $url );
                break;
            case 'wp_http':
                $this->json = $this->wpHttpRequest( $url );
                break;
            default:
                if ( is_callable( 'curl_init' ) ) {
                    $this->json = $this->curlRequest( $url );

                    if ( $this->api_error_no ){
                        $this->json = $this->fileGetContentsRequest( $url );
                    }
                } elseif ( ( ini_get( 'allow_url_fopen' ) == 1 ||
                        ini_get( 'allow_url_fopen' ) === TRUE ) &&
                        in_array( 'https', stream_get_wrappers() ) ) {
                    $this->json = $this->fileGetContentsRequest( $url );
                } else {
                    $this->json = $this->wpHttpRequest( $url );
                }
        }

        return $this;
    }
}