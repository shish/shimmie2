#!/bin/bash
#
# Set up the Travis-CI test environment for Shimmie.
#  (this script should be run as root via sudo)
#
# @copyright (c) 2014 jgen
# @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
#

# Exit immediately if a command exits with a non-zero status.
set -e

# Install the necessary packages
sudo apt-get install -y nginx realpath php5-fpm php5-mysql --fix-missing

# Stop the daemons
sudo service nginx stop
sudo /etc/init.d/php5-fpm stop

# shimmie needs to be able to create directories for images, etc.
#  (permissions of 777 are bad, but it definitely works)
sudo chmod -R 0777 $1

NGINX_CONF="/etc/nginx/sites-enabled/default"

# nginx configuration
echo "
server {
    listen        80;
    server_name   localhost 127.0.0.1 \"\";
    root          $1/;
    index         index.php;
    
	location ~ /_?(images|thumbs)/ {
			default_type image/jpeg;
	}
	
	# For the Nice URLs in Shimmie.
	#location / {
	#	if (!-e $request_filename) {
	#		rewrite  ^(.*)$  /index.php?q=$1  last;
	#		break;
	#	}
	#}
	
	location ~ \.php($|/) {
		fastcgi_pass          unix:/var/run/php5-fpm.sock;
		fastcgi_index         index.php;
		include               fastcgi_params;
	}
}
" | sudo tee $NGINX_CONF > /dev/null

# Start daemons
sudo /etc/init.d/php5-fpm start
sudo service nginx start
