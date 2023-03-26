<?php

declare(strict_types=1);

namespace Shimmie2;

define("UNITTEST", true);
define("EXTRA_EXTS", str_replace("ext/", "", implode(',', glob('ext/*'))));

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
define("BASE_HREF", "/test");
define("CLI_LOG_LEVEL", 50);
define("STATSD_HOST", null);
define("REVERSE_PROXY_X_HEADERS", false);
