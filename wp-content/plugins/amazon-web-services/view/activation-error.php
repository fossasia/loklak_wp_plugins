<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" <?php if ( function_exists( 'language_attributes' ) && function_exists( 'is_rtl' ) ) language_attributes(); else echo "dir='ltr'"; ?>>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php _e( 'Activation Error', 'amazon-web-services' ); ?></title>
	<style type="text/css">
		body {
			background: #fff;
			color: #444;
			font-family: "Open Sans", sans-serif;
			margin: 0;
			padding: 0 2px;
			font-size: 13px;
			max-width: 700px;
		}

		<?php if ( function_exists( 'is_rtl' ) && is_rtl() ) : ?>
		body { font-family: Tahoma, Arial; }
		<?php endif; ?>
	</style>
</head>
<body>
<?php echo $error_msg; // xss ok ?>
</body>
</html>
