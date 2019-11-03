<?php
/*
 * First, load the user-specified settings
 */
@include_once "data/config/shimmie.conf.php";
@include_once "data/config/extensions.conf.php";


/**
 * For any values that aren't defined in the above files, Shimmie
 * will set the values to their defaults
 *
 * All of these can be over-ridden by placing a 'define' in data/config/shimmie.conf.php
 *
 * Do NOT change them in this file. These are the defaults only!
 *
 * Example:
 *  define("SPEED_HAX", true);
 *
 */

function _d(string $name, $value): void
{
    if (!defined($name)) {
        define($name, $value);
    }
}
_d("DATABASE_DSN", null);    // string   PDO database connection details
_d("DATABASE_KA", true);     // string   Keep database connection alive
_d("DATABASE_TIMEOUT", 10000);// int     Time to wait for each statement to complete
_d("CACHE_DSN", null);       // string   cache connection details
_d("DEBUG", false);          // boolean  print various debugging details
_d("COVERAGE", false);       // boolean  activate xdebug coverage monitor
_d("CACHE_HTTP", false);     // boolean  output explicit HTTP caching headers
_d("COOKIE_PREFIX", 'shm');  // string   if you run multiple galleries with non-shared logins, give them different prefixes
_d("SPEED_HAX", false);      // boolean  do some questionable things in the name of performance
_d("COMPILE_ELS", false);    // boolean  pre-build the list of event listeners
_d("NICE_URLS", false);      // boolean  force niceurl mode
_d("SEARCH_ACCEL", false);   // boolean  use search accelerator
_d("WH_SPLITS", 1);          // int      how many levels of subfolders to put in the warehouse
_d("VERSION", '2.8-dev');    // string   shimmie version
_d("TIMEZONE", null);        // string   timezone
_d("EXTRA_EXTS", "");        // string   optional extra extensions
_d("BASE_URL", null);        // string   force a specific base URL (default is auto-detect)
_d("MIN_PHP_VERSION", '7.3');// string   minimum supported PHP version
_d("TRACE_FILE", null);      // string   file to log performance data into
_d("TRACE_THRESHOLD", 0.0);  // float    log pages which take more time than this many seconds
_d("ENABLED_MODS", "imageboard");
_d("AUTO_DB_UPGRADE", true); // bool     whether or not to automatically run DB schema updates

/*
 * Calculated settings - you should never need to change these
 * directly, only the things they're built from
 */
_d("SCORE_VERSION", 'develop/'.VERSION); // string SCore version
