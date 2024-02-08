<?php

declare(strict_types=1);

namespace Shimmie2;

define("UNITTEST", true);
$_all_exts = glob('ext/*');
assert($_all_exts !== false);
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', $_all_exts)));

define("DATABASE_DSN", null);
define("DATABASE_TIMEOUT", 10000);
define("CACHE_DSN", null);
define("DEBUG", false);
define("COOKIE_PREFIX", 'shm');
define("SPEED_HAX", false);
define("WH_SPLITS", 1);
define("VERSION", 'unit-tests');
define("TRACE_FILE", null);
define("TRACE_THRESHOLD", 0.0);
define("TIMEZONE", 'UTC');
define("CLI_LOG_LEVEL", 50);
define("STATSD_HOST", null);
define("TRUSTED_PROXIES", []);
