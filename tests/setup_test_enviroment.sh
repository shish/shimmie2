#!/bin/bash
#
# Set up the Travis-CI test enviroment for Shimmie.
#  (this script should be run as root via sudo)
#
# @copyright (c) 2014 jgen
# @license http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
#

# Exit immediately if a command exits with a non-zero status.
set -e

# Install the necessary packages
sudo apt-get install -y nginx realpath php5-fpm php-mysql --fix-missing

# Stop the daemons
sudo service nginx stop
sudo /etc/init.d/php5-fpm stop

SHIMMIE_ROOT=$(realpath "$0")

NGINX_CONF="/etc/nginx/sites-enabled/default"

# nginx configuration
echo "
server {
    listen   80 default;
    
    root        $SHIMMIE_ROOT/;

	index			index.php
    
	location ~ /_?(images|thumbs)/ {
			default_type image/jpeg;
	}

	location ~ \.php($|/) {
		fastcgi_pass   127.0.0.1:9000;
		fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
		fastcgi_param PATH_INFO $fastcgi_script_name;
		include        fastcgi_params;
	}
}
" | sudo tee $NGINX_CONF > /dev/null

# Start daemons
sudo /etc/init.d/php5-fpm start
sudo service nginx start
