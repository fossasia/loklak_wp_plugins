<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

/*
 * This API has a lot of possiblities in the way it can be used. It
 * takes the following parameters in order.
 * $l->geocode(place)
 *
 * place => string (name of a place)
 *
 */

$values = $l->geocode('Hyderabad');
$geocodeResponse = json_decode($values);


$geocodeResponse = $geocodeResponse->body;
$geocodeResponse = json_decode($geocodeResponse, true);

var_dump($geocodeResponse);
?>
