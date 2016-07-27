<?php
$aptf_settings = $this->aptf_settings;
$username = $aptf_settings['twitter_username'];
$display_name = $aptf_settings['twitter_account_name'];
//$tweets = $this->get_tweets($username, $aptf_settings['total_feed']);
$tweets = $this->get_twitter_tweets($username, $aptf_settings['total_feed']);
//$this->print_array($tweets);
//die();
if(isset($atts['template'])){
    $aptf_settings['feed_template'] = $atts['template'];
}
if(isset($atts['follow_button'])){
    if($atts['follow_button']=='true'){
        $aptf_settings['display_follow_button'] = 1;
    }
    else{
        $aptf_settings['display_follow_button'] = 0;
}
    
}
if(isset($tweets->errors)){
    $fallback_message = ($aptf_settings['fallback_message']=='')?__('Something went wrong with the twitter.','accesspress-twitter-feed'):$aptf_settings['fallback_message'];
    ?>
<p><?php echo $fallback_message;?></p>
        <?php
}
else
{
    
//var_dump($tweets);
$template = $aptf_settings['feed_template'].'.php';
//$this->print_array($tweets);
include('templates/default/'.$template);
}
?>

