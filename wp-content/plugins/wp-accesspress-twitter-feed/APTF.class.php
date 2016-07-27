<?php
/*
* Version 2.2.1
* The base class for the storm twitter feed for developers.
* This class provides all the things needed for the wordpress plugin, but in theory means you don't need to use it with wordpress.
* What could go wrong?
*/


if (!class_exists('TwitterOAuth')) {
  require_once('oauth/twitteroauth.php');
} else {
  define('TFD_USING_EXISTING_LIBRARY_TWITTEROAUTH',true);
}

class APTF_Twitter_Class {

  private $defaults = array(
    'directory' => '',
    'key' => '',
    'secret' => '',
    'token' => '',
    'token_secret' => '',
    'screenname' => '',
    'cache_expire' => 1      
  );
  
  public $st_last_error = false;
  
  function __construct($args = array()) {
    $this->defaults = array_merge($this->defaults, $args);
  }
  
  function __toString() {
    return print_r($this->defaults, true);
  }
  
  function getTweets($screenname = false,$count = 20,$options = false) {
    // BC: $count used to be the first argument
    if (is_int($screenname)) {
      list($screenname, $count) = array($count, $screenname);
    }
    
    if ($count > 20) $count = 20;
    if ($count < 1) $count = 1;
    
    $default_options = array('trim_user'=>true, 'exclude_replies'=>true, 'include_rts'=>false);
    
    if ($options === false || !is_array($options)) {
      $options = $default_options;
    } else {
      $options = array_merge($default_options, $options);
    }
    
    if ($screenname === false || $screenname === 20) $screenname = $this->defaults['screenname'];
  
    
    //If we're here, we need to load.
    $result = $this->oauthGetTweets($screenname,$options);
    
    if (is_array($result) && isset($result['errors'])) {
      if (is_array($result) && isset($result['errors'][0]) && isset($result['errors'][0]['message'])) {
        $last_error = $result['errors'][0]['message'];
      } else {
        $last_error = $result['errors'];
      }
      return array('error'=>__('Twitter said: ',APTF_TD).json_encode($last_error));
    } else {
      if (is_array($result)) {
        return $this->cropTweets($result,$count);
        
      } else {
        $last_error = __('Something went wrong with the twitter request: ',APTF_TD).json_encode($result);
        return array('error'=>$last_error);
      }
    }
    
  }
  
  private function cropTweets($result,$count) {
    return array_slice($result, 0, $count);
  }
  
  
  
  private function getOptionsHash($options) {
    $hash = md5(serialize($options));
    return $hash;
  }
  
  
  
  private function oauthGetTweets($screenname,$options) {
    $key = $this->defaults['key'];
    $secret = $this->defaults['secret'];
    $token = $this->defaults['token'];
    $token_secret = $this->defaults['token_secret'];
    
    $cachename = $screenname."-".$this->getOptionsHash($options);
    
    $options = array_merge($options, array('screen_name' => $screenname, 'count' => 20));
    
    if (empty($key)) return array('error'=>__('Missing Consumer Key - Check Settings',APTF_TD));
    if (empty($secret)) return array('error'=>__('Missing Consumer Secret - Check Settings',APTF_TD));
    if (empty($token)) return array('error'=>__('Missing Access Token - Check Settings',APTF_TD));
    if (empty($token_secret)) return array('error'=>__('Missing Access Token Secret - Check Settings',APTF_TD));
    if (empty($screenname)) return array('error'=>__('Missing Twitter Feed Screen Name - Check Settings',APTF_TD));
    
    $connection = new TwitterOAuth($key, $secret, $token, $token_secret);
    $result = $connection->get('statuses/user_timeline', $options);
    
    if (isset($result['errors'])) {
        if (is_array($results) && isset($result['errors'][0]) && isset($result['errors'][0]['message'])) {
        $last_error = '['.date('r').'] Twitter error: '.$result['errors'][0]['message'];
        $this->st_last_error = $last_error;
      } else {
        $last_error = '['.date('r').'] Twitter returned an invalid response. It is probably down.';
        $this->st_last_error = $last_error;
      }
    } 
    
    return $result;
  
  }
}
