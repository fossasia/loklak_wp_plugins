<?php
/**
 * tmhOAuth
 *
 * An OAuth library written in PHP.
 * The library supports file uploading using multipart/form as well as general
 * REST requests. OAuth authentication is sent using an Authorization Header.
 *
 * @author themattharris
 * @version 0.8.3
 *
 * 19 August 2013
 */
defined('__DIR__') or define('__DIR__', dirname(__FILE__));

class tmhOAuth {
  const VERSION = '0.8.3';
  var $response = array();

  /**
   * Creates a new tmhOAuth object
   *
   * @param string $config, the configuration to use for this request
   * @return void
   */
  public function __construct($config=array()) {
    $this->buffer = null;
    $this->reconfigure($config);
    $this->reset_request_settings();
    $this->set_user_agent();
  }

  public function reconfigure($config=array()) {
    // default configuration options
    $this->config = array_merge(
      array(
        // leave 'user_agent' blank for default, otherwise set this to
        // something that clearly identifies your app
        'user_agent'                 => '',
        'host'                       => 'api.twitter.com',

        'consumer_key'               => '',
        'consumer_secret'            => '',
        'token'                      => '',
        'secret'                     => '',

        // OAuth2 bearer token. This should already be URL encoded
        'bearer'                     => '',

        // oauth signing variables that are not dynamic
        'oauth_version'              => '1.0',
        'oauth_signature_method'     => 'HMAC-SHA1',

        // you probably don't want to change any of these curl values
        'curl_http_version'          => CURL_HTTP_VERSION_1_1,
        'curl_connecttimeout'        => 30,
        'curl_timeout'               => 10,

        // for security this should always be set to 2.
        'curl_ssl_verifyhost'        => 2,
        // for security this should always be set to true.
        'curl_ssl_verifypeer'        => true,
        // for security this should always be set to true.
        'use_ssl'                    => true,

        // you can get the latest cacert.pem from here http://curl.haxx.se/ca/cacert.pem
        // if you're getting HTTP 0 responses, check cacert.pem exists and is readable
        // without it curl won't be able to create an SSL connection
        'curl_cainfo'                => __DIR__ . DIRECTORY_SEPARATOR . 'cacert.pem',
        'curl_capath'                => __DIR__,

        'curl_followlocation'        => false, // whether to follow redirects or not

        // support for proxy servers
        'curl_proxy'                 => false, // really you don't want to use this if you are using streaming
        'curl_proxyuserpwd'          => false, // format username:password for proxy, if required
        'curl_encoding'              => '',    // leave blank for all supported formats, else use gzip, deflate, identity etc

        // streaming API configuration
        'is_streaming'               => false,
        'streaming_eol'              => "\r\n",
        'streaming_metrics_interval' => 10,

        // header or querystring. You should always use header!
        // this is just to help me debug other developers implementations
        'as_header'                  => true,
        'force_nonce'                => false, // used for checking signatures. leave as false for auto
        'force_timestamp'            => false, // used for checking signatures. leave as false for auto
      ),
      $config
    );
  }

  private function reset_request_settings($options=array()) {
    $this->request_settings = array(
      'params'    => array(),
      'headers'   => array(),
      'with_user' => true,
      'multipart' => false,
    );

    if (!empty($options))
      $this->request_settings = array_merge($this->request_settings, $options);
  }

  /**
   * Sets the useragent for PHP to use
   * If '$this->config['user_agent']' already has a value it is used instead of one
   * being generated.
   *
   * @return void value is stored to the config array class variable
   */
  private function set_user_agent() {
    if (!empty($this->config['user_agent']))
      return;

    $ssl = ($this->config['curl_ssl_verifyhost'] && $this->config['curl_ssl_verifypeer'] && $this->config['use_ssl']) ? '+' : '-';
    $ua = 'tmhOAuth ' . self::VERSION . $ssl . 'SSL - //github.com/themattharris/tmhOAuth';
    $this->config['user_agent'] = $ua;
  }

  /**
   * Generates a random OAuth nonce.
   * If 'force_nonce' is false a nonce will be generated, otherwise the value of '$this->config['force_nonce']' will be used.
   *
   * @param string $length how many characters the nonce should be before MD5 hashing. default 12
   * @param string $include_time whether to include time at the beginning of the nonce. default true
   * @return $nonce as a string
   */
  private function nonce($length=12, $include_time=true) {
    if ($this->config['force_nonce'] === false) {
      $prefix = $include_time ? microtime() : '';
      return md5(substr($prefix . uniqid(), 0, $length));
    } else {
      return $this->config['force_nonce'];
    }
  }

  /**
   * Generates a timestamp.
   * If 'force_timestamp' is false a timestamp will be generated, otherwise the value of '$this->config['force_timestamp']' will be used.
   *
   * @return $time as a string
   */
  private function timestamp() {
    if ($this->config['force_timestamp'] === false) {
      $time = time();
    } else {
      $time = $this->config['force_timestamp'];
    }
    return (string) $time;
  }

  /**
   * Encodes the string or array passed in a way compatible with OAuth.
   * If an array is passed each array value will will be encoded.
   *
   * @param mixed $data the scalar or array to encode
   * @return $data encoded in a way compatible with OAuth
   */
  private function safe_encode($data) {
    if (is_array($data)) {
      return array_map(array($this, 'safe_encode'), $data);
    } else if (is_scalar($data)) {
      return str_ireplace(
        array('+', '%7E'),
        array(' ', '~'),
        rawurlencode($data)
      );
    } else {
      return '';
    }
  }

  /**
   * Decodes the string or array from it's URL encoded form
   * If an array is passed each array value will will be decoded.
   *
   * @param mixed $data the scalar or array to decode
   * @return string $data decoded from the URL encoded form
   */
  private function safe_decode($data) {
    if (is_array($data)) {
      return array_map(array($this, 'safe_decode'), $data);
    } else if (is_scalar($data)) {
      return rawurldecode($data);
    } else {
      return '';
    }
  }

  /**
   * Prepares OAuth1 signing parameters.
   *
   * @return void all required OAuth parameters, safely encoded, are stored to the class variable '$this->request_settings['oauth1_params']'
   */
  private function prepare_oauth1_params() {
    $defaults = array(
      'oauth_nonce'            => $this->nonce(),
      'oauth_timestamp'        => $this->timestamp(),
      'oauth_version'          => $this->config['oauth_version'],
      'oauth_consumer_key'     => $this->config['consumer_key'],
      'oauth_signature_method' => $this->config['oauth_signature_method'],
    );

    // include the user token if it exists
    if ( $oauth_token = $this->token() )
      $defaults['oauth_token'] = $oauth_token;

    $this->request_settings['oauth1_params'] = array();

    // safely encode
    foreach ($defaults as $k => $v) {
      $this->request_settings['oauth1_params'][$this->safe_encode($k)] = $this->safe_encode($v);
    }
  }

  private function token() {
    if ( $this->request_settings['with_user'] ) {
      if (isset($this->config['token']) && !empty($this->config['token'])) return $this->config['token'];
      elseif (isset($this->config['user_token'])) return $this->config['user_token'];
    }
    return '';
  }

  private function secret() {
    if ( $this->request_settings['with_user'] ) {
      if (isset($this->config['secret']) && !empty($this->config['secret'])) return $this->config['secret'];
      elseif (isset($this->config['user_secret'])) return $this->config['user_secret'];
    }
    return '';
  }

  /**
   * Extracts and decodes OAuth parameters from the passed string
   *
   * @param string $body the response body from an OAuth flow method
   * @return array the response body safely decoded to an array of key => values
   */
  public function extract_params($body) {
    $kvs = explode('&', $body);
    $decoded = array();
    foreach ($kvs as $kv) {
      $kv = explode('=', $kv, 2);
      $kv[0] = $this->safe_decode($kv[0]);
      $kv[1] = $this->safe_decode($kv[1]);
      $decoded[$kv[0]] = $kv[1];
    }
    return $decoded;
  }

  /**
   * Prepares the HTTP method for use in the base string by converting it to
   * uppercase.
   *
   * @return void value is stored to the class variable '$this->request_settings['method']'
   */
  private function prepare_method() {
    $this->request_settings['method'] = strtoupper($this->request_settings['method']);
  }

  /**
   * Prepares the URL for use in the base string by ripping it apart and
   * reconstructing it.
   *
   * Ref: 3.4.1.2
   *
   * @return void value is stored to the class array variable '$this->request_settings['url']'
   */
  private function prepare_url() {
    $parts = parse_url($this->request_settings['url']);

    $port   = isset($parts['port']) ? $parts['port'] : false;
    $scheme = $parts['scheme'];
    $host   = $parts['host'];
    $path   = isset($parts['path']) ? $parts['path'] : false;

    $port or $port = ($scheme == 'https') ? '443' : '80';

    if (($scheme == 'https' && $port != '443') || ($scheme == 'http' && $port != '80')) {
      $host = "$host:$port";
    }

    // the scheme and host MUST be lowercase
    $this->request_settings['url'] = strtolower("$scheme://$host");
    // but not the path
    $this->request_settings['url'] .= $path;
  }

  /**
   * If the request uses multipart, and the parameter isn't a file path, prepend a space
   * otherwise return the original value. we chose a space here as twitter whitespace trims from
   * the beginning of the tweet. we don't use \0 here because it's the character for string
   * termination.
   *
   * @param the parameter value
   * @return string the original or modified string, depending on the request and the input parameter
   */
  private function multipart_escape($value) {
    if (! $this->request_settings['multipart'] || strpos($value, '@') !== 0)
      return $value;

    // see if the parameter is a file.
    // we split on the semi-colon as it's the delimiter used on media uploads
    // for fields with semi-colons this will return the original string
    list($file) = explode(';', substr($value, 1), 2);
    if (file_exists($file))
      return $value;

    return " $value";
  }


  /**
   * Prepares all parameters for the base string and request.
   * Multipart parameters are ignored as they are not defined in the specification,
   * all other types of parameter are encoded for compatibility with OAuth.
   *
   * @param array $params the parameters for the request
   * @return void prepared values are stored in the class array variable '$this->request_settings'
   */
  private function prepare_params() {
    $doing_oauth1 = false;
    $this->request_settings['prepared_params'] = array();
    $prepared = &$this->request_settings['prepared_params'];
    $prepared_pairs = array();
    $prepared_pairs_with_oauth = array();

    if (isset($this->request_settings['oauth1_params'])) {
      $oauth1  = &$this->request_settings['oauth1_params'];
      $doing_oauth1 = true;
      $params = array_merge($oauth1, $this->request_settings['params']);

      // Remove oauth_signature if present
      // Ref: Spec: 9.1.1 ("The oauth_signature parameter MUST be excluded.")
      unset($params['oauth_signature']);

      // empty the oauth1 array. we reset these values later in this method
      $oauth1 = array();
    } else {
      $params = $this->request_settings['params'];
    }

    // Parameters are sorted by name, using lexicographical byte value ordering.
    // Ref: Spec: 9.1.1 (1)
    uksort($params, 'strcmp');

    // encode params unless we're doing multipart
    foreach ($params as $k => $v) {
      $k = $this->request_settings['multipart'] ? $k : $this->safe_encode($k);

      if (is_array($v))
        $v = implode(',', $v);

      $v = $this->request_settings['multipart'] ? $this->multipart_escape($v) : $this->safe_encode($v);

      // split parameters for the basestring and authorization header, and recreate the oauth1 array
      if ($doing_oauth1) {
        // if we're doing multipart, only store the oauth_* params, ignore the users request params
        if ((strpos($k, 'oauth') === 0) || !$this->request_settings['multipart'])
          $prepared_pairs_with_oauth[] = "{$k}={$v}";

        if (strpos($k, 'oauth') === 0) {
          $oauth1[$k] = $v;
          continue;
        }
      }
      $prepared[$k] = $v;
      $prepared_pairs[] = "{$k}={$v}";
    }

    if ($doing_oauth1) {
      $this->request_settings['basestring_params'] = implode('&', $prepared_pairs_with_oauth);
    }

    // setup params for GET/POST method handling
    if (!empty($prepared_pairs)) {
      $content = implode('&', $prepared_pairs);

      switch ($this->request_settings['method']) {
        case 'POST':
          $this->request_settings['postfields'] = $this->request_settings['multipart'] ? $prepared : $content;
          break;
        default:
          $this->request_settings['querystring'] = $content;
          break;
      }
    }
  }

  /**
   * Prepares the OAuth signing key
   *
   * @return void prepared signing key is stored in the class variable 'signing_key'
   */
  private function prepare_signing_key() {
    $left = $this->safe_encode($this->config['consumer_secret']);
    $right = $this->safe_encode($this->secret());
    $this->request_settings['signing_key'] = $left . '&' . $right;
  }

  /**
   * Prepare the base string.
   * Ref: Spec: 9.1.3 ("Concatenate Request Elements")
   *
   * @return void prepared base string is stored in the class variable 'base_string'
   */
  private function prepare_base_string() {
    $url = $this->request_settings['url'];

    # if the host header is set we need to rewrite the basestring to use
    # that, instead of the request host. otherwise the signature won't match
    # on the server side
    if (!empty($this->request_settings['headers']['Host'])) {
      $url = str_ireplace(
        $this->config['host'],
        $this->request_settings['headers']['Host'],
        $url
      );
    }

    $base = array(
      $this->request_settings['method'],
      $url,
      $this->request_settings['basestring_params']
    );
    $this->request_settings['basestring'] = implode('&', $this->safe_encode($base));
  }

  /**
   * Signs the OAuth 1 request
   *
   * @return void oauth_signature is added to the parameters in the class array variable '$this->request_settings'
   */
  private function prepare_oauth_signature() {
    $this->request_settings['oauth1_params']['oauth_signature'] = $this->safe_encode(
      base64_encode(
        hash_hmac(
          'sha1', $this->request_settings['basestring'], $this->request_settings['signing_key'], true
    )));
  }

  /**
   * Prepares the Authorization header
   *
   * @return void prepared authorization header is stored in the class variable headers['Authorization']
   */
  private function prepare_auth_header() {
    if (!$this->config['as_header'])
      return;

    // oauth1
    if (isset($this->request_settings['oauth1_params'])) {
      // sort again as oauth_signature was added post param preparation
      uksort($this->request_settings['oauth1_params'], 'strcmp');
      $encoded_quoted_pairs = array();
      foreach ($this->request_settings['oauth1_params'] as $k => $v) {
        $encoded_quoted_pairs[] = "{$k}=\"{$v}\"";
      }
      $header = 'OAuth ' . implode(', ', $encoded_quoted_pairs);
    } elseif (!empty($this->config['bearer'])) {
      $header = 'Bearer ' . $this->config['bearer'];
    }

    if (isset($header))
      $this->request_settings['headers']['Authorization'] = $header;
  }

  /**
   * Create the bearer token for OAuth2 requests from the consumer_key and consumer_secret.
   *
   * @return string the bearer token
   */
  public function bearer_token_credentials() {
    $credentials = implode(':', array(
      $this->safe_encode($this->config['consumer_key']),
      $this->safe_encode($this->config['consumer_secret'])
    ));
    return base64_encode($credentials);
  }

  /**
   * Make an HTTP request using this library. This method doesn't return anything.
   * Instead the response should be inspected directly.
   *
   * @param string $method the HTTP method being used. e.g. POST, GET, HEAD etc
   * @param string $url the request URL without query string parameters
   * @param array $params the request parameters as an array of key=value pairs. Default empty array
   * @param string $useauth whether to use authentication when making the request. Default true
   * @param string $multipart whether this request contains multipart data. Default false
   * @param array $headers any custom headers to send with the request. Default empty array
   * @return int the http response code for the request. 0 is returned if a connection could not be made
   */
  public function request($method, $url, $params=array(), $useauth=true, $multipart=false, $headers=array()) {
    $options = array(
      'method'    => $method,
      'url'       => $url,
      'params'    => $params,
      'with_user' => true,
      'multipart' => $multipart,
      'headers'   => $headers
    );
    $options = array_merge($this->default_options(), $options);

    if ($useauth) {
      return $this->user_request($options);
    } else {
      return $this->unauthenticated_request($options);
    }
  }

  public function apponly_request($options=array()) {
    $options = array_merge($this->default_options(), $options, array(
      'with_user' => false,
    ));
    $this->reset_request_settings($options);
    if ($options['without_bearer']) {
      return $this->oauth1_request();
    } else {
      $this->prepare_method();
      $this->prepare_url();
      $this->prepare_params();
      $this->prepare_auth_header();
      return $this->curlit();
    }
  }

  public function user_request($options=array()) {
    $options = array_merge($this->default_options(), $options, array(
      'with_user' => true,
    ));
    $this->reset_request_settings($options);
    return $this->oauth1_request();
  }

  public function unauthenticated_request($options=array()) {
    $options = array_merge($this->default_options(), $options, array(
      'with_user' => false,
    ));
    $this->reset_request_settings($options);
    $this->prepare_method();
    $this->prepare_url();
    $this->prepare_params();
    return $this->curlit();
  }

  /**
   * Signs the request and adds the OAuth signature. This runs all the request
   * parameter preparation methods.
   *
   * @param string $method the HTTP method being used. e.g. POST, GET, HEAD etc
   * @param string $url the request URL without query string parameters
   * @param array $params the request parameters as an array of key=value pairs
   * @param boolean $with_user whether to include the user credentials when making the request.
   * @return void
   */
  private function oauth1_request() {
    $this->prepare_oauth1_params();
    $this->prepare_method();
    $this->prepare_url();
    $this->prepare_params();
    $this->prepare_base_string();
    $this->prepare_signing_key();
    $this->prepare_oauth_signature();
    $this->prepare_auth_header();
    return $this->curlit();
  }

  private function default_options() {
    return array(
      'method'         => 'GET',
      'params'         => array(),
      'with_user'      => true,
      'multipart'      => false,
      'headers'        => array(),
      'without_bearer' => false,
    );
  }

  /**
   * Make a long poll HTTP request using this library. This method is
   * different to the other request methods as it isn't supposed to disconnect
   *
   * Using this method expects a callback which will receive the streaming
   * responses.
   *
   * @param string $method the HTTP method being used. e.g. POST, GET, HEAD etc
   * @param string $url the request URL without query string parameters
   * @param array $params the request parameters as an array of key=value pairs
   * @param string $callback the callback function to stream the buffer to.
   * @return void
   */
  public function streaming_request($method, $url, $params=array(), $callback='') {
    if ( ! empty($callback) ) {
      if ( ! is_callable($callback) ) {
        return false;
      }
      $this->config['streaming_callback'] = $callback;
    }
    $this->metrics['start']          = time();
    $this->metrics['interval_start'] = $this->metrics['start'];
    $this->metrics['messages']       = 0;
    $this->metrics['last_messages']  = 0;
    $this->metrics['bytes']          = 0;
    $this->metrics['last_bytes']     = 0;
    $this->config['is_streaming']    = true;
    $this->request($method, $url, $params);
  }

  /**
   * Handles the updating of the current Streaming API metrics.
   *
   * @return array the metrics for the streaming api connection
   */
  private function update_metrics() {
    $now = time();
    if (($this->metrics['interval_start'] + $this->config['streaming_metrics_interval']) > $now)
      return null;

    $this->metrics['mps'] = round( ($this->metrics['messages'] - $this->metrics['last_messages']) / $this->config['streaming_metrics_interval'], 2);
    $this->metrics['bps'] = round( ($this->metrics['bytes'] - $this->metrics['last_bytes']) / $this->config['streaming_metrics_interval'], 2);

    $this->metrics['last_bytes'] = $this->metrics['bytes'];
    $this->metrics['last_messages'] = $this->metrics['messages'];
    $this->metrics['interval_start'] = $now;
    return $this->metrics;
  }

  /**
   * Utility function to create the request URL in the requested format.
   * If a fully-qualified URI is provided, it will be returned.
   * Any multi-slashes (except for the protocol) will be replaced with a single slash.
   *
   *
   * @param string $request the API method without extension
   * @param string $extension the format of the response. Default json. Set to an empty string to exclude the format
   * @return string the concatenation of the host, API version, API method and format, or $request if it begins with http
   */
  public function url($request, $extension='json') {
    // remove multi-slashes
    $request = preg_replace('$([^:])//+$', '$1/', $request);

    if (stripos($request, 'http') === 0 || stripos($request, '//') === 0) {
      return $request;
    }

    $extension = strlen($extension) > 0 ? ".$extension" : '';
    $proto  = $this->config['use_ssl'] ? 'https:/' : 'http:/';

    // trim trailing slash
    $request = ltrim($request, '/');

    $pos = strlen($request) - strlen($extension);
    if (substr($request, $pos) === $extension)
      $request = substr_replace($request, '', $pos);

    return implode('/', array(
      $proto,
      $this->config['host'],
      $request . $extension
    ));
  }

  /**
   * Public access to the private safe decode/encode methods
   *
   * @param string $text the text to transform
   * @param string $mode the transformation mode. either encode or decode
   * @return string $text transformed by the given $mode
   */
  public function transformText($text, $mode='encode') {
    return $this->{"safe_$mode"}($text);
  }

  /**
   * Utility function to parse the returned curl headers and store them in the
   * class array variable.
   *
   * @param object $ch curl handle
   * @param string $header the response headers
   * @return string the length of the header
   */
  private function curlHeader($ch, $header) {
    $this->response['raw'] .= $header;

    list($key, $value) = array_pad(explode(':', $header, 2), 2, null);

    $key = trim($key);
    $value = trim($value);

    if ( ! isset($this->response['headers'][$key])) {
      $this->response['headers'][$key] = $value;
    } else {
      if (!is_array($this->response['headers'][$key])) {
        $this->response['headers'][$key] = array($this->response['headers'][$key]);
      }
      $this->response['headers'][$key][] = $value;
    }

    return strlen($header);
  }

  /**
    * Utility function to parse the returned curl buffer and store them until
    * an EOL is found. The buffer for curl is an undefined size so we need
    * to collect the content until an EOL is found.
    *
    * This function calls the previously defined streaming callback method.
    *
    * @param object $ch curl handle
    * @param string $data the current curl buffer
    * @return int the length of the data string processed in this function
    */
  private function curlWrite($ch, $data) {
    $l = strlen($data);
    if (strpos($data, $this->config['streaming_eol']) === false) {
      $this->buffer .= $data;
      return $l;
    }

    $buffered = explode($this->config['streaming_eol'], $data);
    $content = $this->buffer . $buffered[0];

    $this->metrics['messages']++;
    $this->metrics['bytes'] += strlen($content);

    if ( ! is_callable($this->config['streaming_callback']))
      return 0;

    $metrics = $this->update_metrics();
    $stop = call_user_func(
      $this->config['streaming_callback'],
      $content,
      strlen($content),
      $metrics
    );
    $this->buffer = $buffered[1];
    if ($stop)
      return 0;

    return $l;
  }

  /**
   * Makes a curl request. Takes no parameters as all should have been prepared
   * by the request method
   *
   * the response data is stored in the class variable 'response'
   *
   * @return int the http response code for the request. 0 is returned if a connection could not be made
   */
  private function curlit() {
    $this->response = array(
      'raw' => ''
    );

    // configure curl
    $c = curl_init();
    switch ($this->request_settings['method']) {
      case 'GET':
        if (isset($this->request_settings['querystring']))
          $this->request_settings['url'] = $this->request_settings['url'] . '?' . $this->request_settings['querystring'];
        break;
      case 'POST':
        curl_setopt($c, CURLOPT_POST, true);
        if (isset($this->request_settings['postfields']))
          $postfields = $this->request_settings['postfields'];
        else
          $postfields = array();

        curl_setopt($c, CURLOPT_POSTFIELDS, $postfields);
        break;
      default:
        if (isset($this->request_settings['postfields']))
          curl_setopt($c, CURLOPT_CUSTOMREQUEST, $this->request_settings['postfields']);
    }

    curl_setopt_array($c, array(
      CURLOPT_HTTP_VERSION   => $this->config['curl_http_version'],
      CURLOPT_USERAGENT      => $this->config['user_agent'],
      CURLOPT_CONNECTTIMEOUT => $this->config['curl_connecttimeout'],
      CURLOPT_TIMEOUT        => $this->config['curl_timeout'],
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_SSL_VERIFYPEER => $this->config['curl_ssl_verifypeer'],
      CURLOPT_SSL_VERIFYHOST => $this->config['curl_ssl_verifyhost'],

      CURLOPT_FOLLOWLOCATION => $this->config['curl_followlocation'],
      CURLOPT_PROXY          => $this->config['curl_proxy'],
      CURLOPT_ENCODING       => $this->config['curl_encoding'],
      CURLOPT_URL            => $this->request_settings['url'],
      // process the headers
      CURLOPT_HEADERFUNCTION => array($this, 'curlHeader'),
      CURLOPT_HEADER         => false,
      CURLINFO_HEADER_OUT    => true,
    ));

    if ($this->config['curl_cainfo'] !== false)
      curl_setopt($c, CURLOPT_CAINFO, $this->config['curl_cainfo']);

    if ($this->config['curl_capath'] !== false)
      curl_setopt($c, CURLOPT_CAPATH, $this->config['curl_capath']);

    if ($this->config['curl_proxyuserpwd'] !== false)
      curl_setopt($c, CURLOPT_PROXYUSERPWD, $this->config['curl_proxyuserpwd']);

    if ($this->config['is_streaming']) {
      // process the body
      $this->response['content-length'] = 0;
      curl_setopt($c, CURLOPT_TIMEOUT, 0);
      curl_setopt($c, CURLOPT_WRITEFUNCTION, array($this, 'curlWrite'));
    }

    if ( ! empty($this->request_settings['headers'])) {
      foreach ($this->request_settings['headers'] as $k => $v) {
        $headers[] = trim($k . ': ' . $v);
      }
      curl_setopt($c, CURLOPT_HTTPHEADER, $headers);
    }

    if (isset($this->config['block']) && (true === $this->config['block']))
      return 0;

    // do it!
    $response = curl_exec($c);
    $code = curl_getinfo($c, CURLINFO_HTTP_CODE);
    $info = curl_getinfo($c);
    $error = curl_error($c);
    $errno = curl_errno($c);
    curl_close($c);

    // store the response
    $this->response['code'] = $code;
    $this->response['response'] = $response;
    $this->response['info'] = $info;
    $this->response['error'] = $error;
    $this->response['errno'] = $errno;

    if (!isset($this->response['raw'])) {
      $this->response['raw'] = '';
    }
    $this->response['raw'] .= $response;

    return $code;
  }
}