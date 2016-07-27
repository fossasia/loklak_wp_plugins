<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$values = $l->status();
$statusResponse = json_decode($values);

$statusResponse = $statusResponse->body;
$statusResponse = json_decode($statusResponse, true);

var_dump($statusResponse);
?>
