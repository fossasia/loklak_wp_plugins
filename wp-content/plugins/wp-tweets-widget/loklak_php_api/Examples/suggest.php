<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$values = $l->suggest('fossasia', 10, 'asc');
$suggestResponse = json_decode($values);

$suggestResponse = $suggestResponse->body;
$suggestResponse = json_decode($suggestResponse, true);

var_dump($suggestResponse);
?>
