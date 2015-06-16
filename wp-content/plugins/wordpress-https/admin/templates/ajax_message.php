<?php

if ( isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' ) {
	error_reporting(0);
	while(@ob_end_clean());
	if ( sizeof( $errors ) > 0 ) {
		echo "<div class=\"error below-h2 fade wphttps-message\" id=\"message\">\n\t<ul>\n";
		foreach ( $errors as $error ) {
			echo "\t\t<li><p>".$error."</p></li>\n";
		}
		echo "\t</ul>\n</div>\n";
	} else {
		echo "<div class=\"updated below-h2 fade wphttps-message\" id=\"message\"><p>" . $message . "</p></div>\n";
		if ( $logout || $reload ) {
			echo "<script type=\"text/javascript\">window.location.reload();</script>";
		}
	}
	exit();
}