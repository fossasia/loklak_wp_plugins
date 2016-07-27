<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$values = $l->peers();
$peersResponse = json_decode($values);


$peersResponse = $peersResponse->body;
$peersResponse = json_decode($peersResponse);

var_dump($peersResponse);
?>
