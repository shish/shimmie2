<?php

declare(strict_types=1);

namespace Shimmie2;

/**
 * For any values that aren't defined in data/config/*.php,
 * Shimmie will set the values to their defaults
 *
 * All of these can be over-ridden by placing a 'define' in
 * data/config/shimmie.conf.php.
 *
 * Do NOT change them in this file. These are the defaults only!
 *
 * Example:
 *  define("DEBUG", true);
 */

function _d(string $name, mixed $value): void
{
    if (!defined($name)) {
        define($name, $value);
    }
}

_d("DATABASE_DSN", null);       // string   PDO database connection details
_d("DATABASE_TIMEOUT", 10000);  // int      Time to wait for each statement to complete
_d("CACHE_DSN", null);          // string   cache connection details
_d("DEBUG", false);             // boolean  print various debugging details
_d("COOKIE_PREFIX", 'shm');     // string   if you run multiple galleries with non-shared logins, give them different prefixes
_d("WH_SPLITS", 1);             // int      how many levels of subfolders to put in the warehouse
_d("VERSION", "2.12.0-alpha");  // string   shimmie version
_d("TIMEZONE", null);           // string   timezone
_d("EXTRA_EXTS", "");           // string   optional extra extensions
_d("BASE_HREF", null);          // string   force a specific base URL (default is auto-detect)
_d("TRACE_FILE", null);         // string   file to log performance data into
_d("TRACE_THRESHOLD", 0.0);     // float    log pages which take more time than this many seconds
_d("TRUSTED_PROXIES", []);      // array    trust "X-Real-IP" / "X-Forwarded-For" / "X-Forwarded-Proto" headers from these IP ranges
_d("SECRET", DATABASE_DSN);     // string   A secret bit of data used to salt some hashes
