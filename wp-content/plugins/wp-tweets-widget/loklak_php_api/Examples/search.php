<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

/*
 * This API has a lot of possiblities in the way it can be used. It
 * takes the following parameters in order.
 * $l->search(query, sinceDate, UntilDate, from_user, count)
 *
 * query => string
 * sinceDate => string formatted as YYYY-MM-DD
 * untilDate => string formatted as YYYY-MM-DD
 * from_user => twitter username from whom the results are needed
 * count => numeric value
 *
 */

$values1 = $l->search("fossasia");
$values2 = $l->search("fossasia","2015-01-01");
$values3 = $l->search("fossasia","2015-01-01", "2016-01-01");
$values4 = $l->search("fossasia","2015-01-01", "2016-01-01", "sudheesh001");
$values5 = $l->search("fossasia","2015-01-01", "2016-01-01", "sudheesh001", 10);
$values6 = $l->search("fossasia","", "", "", 10);
$values7 = $l->search("fossasia","2015-01-01", "", "sudheesh001", 10);
$values8 = $l->search("fossasia","2015-01-01", "2016-01-01", "", 10);
$values9 = $l->search("fossasia","", "", "", 10);

$searchResponse1 = json_decode($values1);
$searchResponse2 = json_decode($values2);
$searchResponse3 = json_decode($values3);
$searchResponse4 = json_decode($values4);
$searchResponse5 = json_decode($values5);
$searchResponse6 = json_decode($values6);
$searchResponse7 = json_decode($values7);
$searchResponse8 = json_decode($values8);
$searchResponse9 = json_decode($values9);

$searchResponse1 = $searchResponse->body;
$searchResponse1 = json_decode($searchResponse1, true);

$searchResponse2 = $searchResponse->body;
$searchResponse2 = json_decode($searchResponse2, true);

$searchResponse3 = $searchResponse->body;
$searchResponse3 = json_decode($searchResponse3, true);

$searchResponse4 = $searchResponse->body;
$searchResponse4 = json_decode($searchResponse4, true);

$searchResponse5 = $searchResponse->body;
$searchResponse5 = json_decode($searchResponse5, true);

$searchResponse6 = $searchResponse->body;
$searchResponse6 = json_decode($searchResponse6, true);

$searchResponse7 = $searchResponse->body;
$searchResponse7 = json_decode($searchResponse7, true);

$searchResponse8 = $searchResponse->body;
$searchResponse8 = json_decode($searchResponse8, true);

$searchResponse9 = $searchResponse->body;
$searchResponse9 = json_decode($searchResponse9, true);

var_dump($searchResponse1);
var_dump($searchResponse2);
var_dump($searchResponse3);
var_dump($searchResponse4);
var_dump($searchResponse5);
var_dump($searchResponse6);
var_dump($searchResponse7);
var_dump($searchResponse8);
var_dump($searchResponse9);
?>
