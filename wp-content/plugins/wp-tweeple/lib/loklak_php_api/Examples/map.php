<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

$MapText = 'This is the sample,
Text On the Map';
$latitude='29.157176';
$longitude='48.125024';

$values = $l->map($MapText, $latitude , $longitude);

echo "<img src='data:image/png;base64,".$values."'</img>";

?>
