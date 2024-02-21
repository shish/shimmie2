#!/bin/sh

set -e

# if user shimmie doesn't already exist, create it
if ! id -u shimmie >/dev/null 2>&1; then
    groupadd -g $GID shimmie || true
    useradd -ms /bin/bash -u $UID -g $GID shimmie || true
fi
mkdir -p /app/data
chown shimmie:shimmie /app/data
php /app/.docker/configgen.php > /var/lib/unit/conf.json

exec "$@"
