<?php
include('loklak.php');

$baseURL = 'http://localhost:9000';

$l = new Loklak($baseURL);

$values = $l->settings();
$settingsResponse = json_decode($values);

$settingsResponse = $settingsResponse->body;
$settingsResponse = json_decode($settingsResponse, true);

var_dump($settingsResponse);
?>
