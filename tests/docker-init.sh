#!/bin/sh
echo "<?php define(\"DATABASE_DSN\", \"${DB_DSN}\");" > data/config/auto_install.conf.php
/usr/bin/php -d upload_max_filesize=50M -d post_max_size=50M -S 0.0.0.0:8000 tests/router.php
