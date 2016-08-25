<?php
include('loklak.php');

$baseURL = 'http://loklak.org'; 

$l = new Loklak($baseURL);

$lookupAccount = 'test';
$values = $l->account($lookupAccount);
$accountResponse = json_decode($values);
echo $accountResponse->body;

echo "<br><br><br><hr><br><br>";

$arr = array('screen_name'=>'test', 'oauth_token'=>'thisisanoauthtoken', 'oauth_token_secret'=>'thisisasampleoauthsecret');

$values = $l->account('','update', $arr);
$accountResponse = json_decode($values);

echo $accountResponse->body;

?>
