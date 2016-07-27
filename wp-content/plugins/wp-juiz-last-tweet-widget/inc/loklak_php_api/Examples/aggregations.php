<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

/*
 * This API has a lot of possiblities in the way it can be used. It
 * takes the following parameters in order.
 * $l->aggregations(query, sinceDate, UntilDate, fields, limit, count)
 *
 * query => string
 * sinceDate => string formatted as YYYY-MM-DD
 * untilDate => string formatted as YYYY-MM-DD
 * fields => array of strings ["mentions", "hashtags"]
 * limit => numeric value
 * count => numeric value
 *
 */

$values1 = $l->aggregations("spacex", "2016-04-01", "2016-04-06", array("mentions","hashtags"), 10, 6);
$values2 = $l->aggregations("fossasia", "2015-01-10", "2015-10-21", array("mentions","hashtags"), 10);
$values3 = $l->aggregations("fossasia", "", "", "hashtags");

$aggregationsResponse1 = json_decode($values1);
$aggregationsResponse2 = json_decode($values2);
$aggregationsResponse3 = json_decode($values3);

$aggregationsResponse1 = $aggregationsResponse1->body;
$aggregationsResponse1 = json_decode($aggregationsResponse1, true);

$aggregationsResponse2 = $aggregationsResponse2->body;
$aggregationsResponse2 = json_decode($aggregationsResponse2, true);

$aggregationsResponse3 = $aggregationsResponse3->body;
$aggregationsResponse3 = json_decode($aggregationsResponse3, true);

var_dump($aggregationsResponse1);
?>
