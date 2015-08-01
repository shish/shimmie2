#!/bin/bash
#
# Set up the Travis-CI test environment for Shimmie.
#  (this script should be run as root via sudo)
#
# @author jgen <jeffgenovy@gmail.com>
# @license http://opensource.org/licenses/GPL-2.0 GNU General Public License v2
#

# Exit immediately if a command exits with a non-zero status.
set -e

# Install the necessary packages
sudo apt-get install -y nginx php5-fpm php5-mysql php5-pgsql php5-sqlite --fix-missing

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
    server_tokens off;
    root          $1;
    index         index.php;

	location / {
		index	index.php;
		# For the Nice URLs in Shimmie.
		if (!-e \$request_filename) {
			rewrite  ^(.*)\$  /index.php?q=\$1  last;
			break;
		}
	}

	location ~ \.php\$ {
		try_files \$uri =404;
		fastcgi_index         index.php;
		fastcgi_pass          127.0.0.1:9000;
		include               fastcgi_params;
	}
}
" | sudo tee $NGINX_CONF > /dev/null

# Start daemons
sudo /etc/init.d/php5-fpm start
sudo service nginx start
