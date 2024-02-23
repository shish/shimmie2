#!/bin/sh

set -eux

DATABASE=$1

mkdir -p data/config

if [ "$DATABASE" = "pgsql" ]; then
    psql --version
    sudo systemctl start postgresql
    sudo -u postgres psql -c "SELECT set_config('log_statement', 'all', false);" -U postgres
    sudo -u postgres psql -c "CREATE USER shimmie WITH PASSWORD 'shimmie';" -U postgres
    sudo -u postgres psql -c "CREATE DATABASE shimmie WITH OWNER shimmie;" -U postgres
    export TEST_DSN="pgsql:user=shimmie;password=shimmie;host=127.0.0.1;dbname=shimmie"
fi
if [ "$DATABASE" = "mysql" ]; then
    mysql --version
    sudo systemctl start mysql
    mysql -e "SET GLOBAL general_log = 'ON';" -uroot -proot
    mysql -e "CREATE DATABASE shimmie;" -uroot -proot
    export TEST_DSN="mysql:user=root;password=root;host=127.0.0.1;dbname=shimmie"
fi
if [ "$DATABASE" = "sqlite" ]; then
    sqlite3 --version
    export TEST_DSN="sqlite:data/shimmie.sqlite"
fi

if [ -n "$GITHUB_ENV" ]; then
    echo "Setting DSN for $DATABASE: $TEST_DSN"
    echo "TEST_DSN=$TEST_DSN" >> $GITHUB_ENV
    echo "INSTALL_DSN=$TEST_DSN" >> $GITHUB_ENV
fi
