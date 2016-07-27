<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$values = $l->hello();
$helloResponse = json_decode($values);


$helloResponse = $helloResponse->body;
$helloResponse = json_decode($helloResponse);

var_dump($helloResponse);
?>
