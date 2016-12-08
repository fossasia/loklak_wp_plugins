<?php
include('loklak.php');

$baseURL = 'http://loklak.org';

$l = new Loklak($baseURL);

// markdown API returns the base 64 string for the image converted.

// $lookupAccount = 'test';
// $values = $l->markdown($lookupAccount);

// echo "<img src='data:image/png;base64,".$values."'</img>";

// $lookupAccount = 'test';
// $color = 'ffffff';
// $values = $l->markdown($lookupAccount, $color);

// echo "<img src='data:image/png;base64,".$values."'</img>";

$markdownText = 'This is the sample,
Text On the Markdown';
$color = 'ffffff';
$background = '444444';

$values = $l->markdown($markdownText, $color, $background);

echo "<img src='data:image/png;base64,".$values."'</img>";

?>
