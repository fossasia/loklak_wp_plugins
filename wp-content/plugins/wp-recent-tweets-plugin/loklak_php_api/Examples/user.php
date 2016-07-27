<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$values = $l->user('Daminisatya');
$userResponse = json_decode($values);

$userResponse = $userResponse->body;
$userResponse = json_decode($userResponse, true);

var_dump($userResponse);
?>
