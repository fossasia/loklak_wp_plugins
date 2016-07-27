<?php

//$this->print_array($_POST);
/**
 * [action] => aptf_form_action
  [consumer_key] => Roo0zrWHjCUrB13fNvsLmBOZN
  [consumer_secret] => 8aU1sfjKaDRK7rmZJ3JXC5cC0zNcunV4CmVYDl8NiuaXtt0NRq
  [access_token] => 256050616-psaikzDyzWQ1tFDNRQzIDpBLSnxNiPB7ieYUKaUG
  [access_token_secret] => 2Rjcetsnc0dYbd8TZlEoUo6Sn51bT1Qa2c9ia8JQUn5g4
  [twitter_username] => @apthemes
  [cache_period] => 60
  [total_feed] => 5
  [feed_template] => template-
  [aptf_nonce_field] => 6f8a90d5c4
  [_wp_http_referer] => /accesspress-twitter-feed/wp-admin/admin.php?page=ap-twitter-feed
  [aptf_settings_submit] => Save Settings
 */
foreach ($_POST as $key => $val) {
    $$key = sanitize_text_field($val);
}

$aptf_settings = array('loklak_api' => $loklak_api,
    'consumer_key' => $consumer_key,
    'consumer_secret' => $consumer_secret,
    'access_token' => $access_token,
    'access_token_secret' => $access_token_secret,
    'twitter_username' => $twitter_username,
    'twitter_account_name'=>$twitter_account_name,
    'cache_period' => $cache_period,
    'total_feed' => $total_feed,
    'feed_template' => $feed_template,
    'time_format' => $time_format,
    'display_username' => isset($display_username)?1:0,
    'display_twitter_actions'=>isset($display_twitter_actions)?1:0,
    'fallback_message'=>$fallback_message,
    'display_follow_button'=>isset($display_follow_button)?1:0
);
update_option('aptf_settings', $aptf_settings);
$_SESSION['aptf_msg'] = __('Settings Saved Successfully','accesspress-twitter-feed');
wp_redirect(admin_url().'admin.php?page=ap-twitter-feed');

