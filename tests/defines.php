<?php
define("UNITTEST", true);
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', glob('ext/*'))));

define("DATABASE_DSN", null);
define("DATABASE_TIMEOUT", 10000);
define("CACHE_DSN", null);
define("DEBUG", false);
define("COVERAGE", false);
define("CACHE_HTTP", false);
define("COOKIE_PREFIX", 'shm');
define("SPEED_HAX", false);
define("NICE_URLS", false);
define("WH_SPLITS", 1);
define("VERSION", '2.8-dev');
define("BASE_URL", null);
define("MIN_PHP_VERSION", '7.3');
define("TRACE_FILE", null);
define("TRACE_THRESHOLD", 0.0);
define("ENABLED_MODS", "imageboard");
define("SCORE_VERSION", 'develop/'.VERSION);
define("TIMEZONE", 'UTC');
define("BASE_HREF", "/");
define("CLI_LOG_LEVEL", 50);

