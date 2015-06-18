if [ ! -e /app/.htpasswd ]; then
	echo $ADMINAUTH > /app/.htpasswd
fi
vendor/bin/heroku-php-apache2

