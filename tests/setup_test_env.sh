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

SHIMMIE_ROOT=$(realpath "$0")

# shimmie needs to be able to create directories for images, etc.
#  (permissions of 777 are bad, but it definitely works)
sudo chmod -R 0777 $SHIMMIE_ROOT

NGINX_CONF="/etc/nginx/sites-enabled/default"

sudo cat /etc/nginx/nginx.conf

# nginx configuration
echo "
server {
    listen   80 default;
    
    root        $SHIMMIE_ROOT/;
	index			index.php;
    
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
		fastcgi_pass          127.0.0.1:9000;
		include               fastcgi_params;
		fastcgi_index         index.php;
		fastcgi_read_timeout  600;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
	}
}
" | sudo tee $NGINX_CONF > /dev/null

# Start daemons
sudo /etc/init.d/php5-fpm start
sudo service nginx start
