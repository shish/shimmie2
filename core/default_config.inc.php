<?php
/**
 * These are the default configuration options for Shimmie.
 *
 * All of these can be over-ridden by placing a 'define' in config.php
 *
 * Do NOT change them in this file. These are the defaults only!
 *
 * Example:
 *  define("SPEED_HAX", true);
 *
 */

function _d($name, $value) {if(!defined($name)) define($name, $value);}
_d("DATABASE_DSN", null);    // string   PDO database connection details
_d("CACHE_DSN", null);       // string   cache connection details
_d("DEBUG", false);          // boolean  print various debugging details
_d("DEBUG_SQL", false);      // boolean  dump SQL queries to data/sql.log
_d("COVERAGE", false);       // boolean  activate xdebug coverage monitor
_d("CONTEXT", null);         // string   file to log performance data into
_d("CACHE_MEMCACHE", false); // boolean  store complete rendered pages in memcache
_d("CACHE_DIR", false);      // boolean  store complete rendered pages on disk
_d("CACHE_HTTP", false);     // boolean  output explicit HTTP caching headers
_d("COOKIE_PREFIX", 'shm');  // string   if you run multiple galleries with non-shared logins, give them different prefixes
_d("SPEED_HAX", false);      // boolean  do some questionable things in the name of performance
_d("COMPILE_ELS", false);    // boolean  pre-build the list of event listeners
_d("NICE_URLS", false);      // boolean  force niceurl mode
_d("WH_SPLITS", 1);          // int      how many levels of subfolders to put in the warehouse
_d("VERSION", 'trunk');      // string   shimmie version
_d("SCORE_VERSION", 's2hack/'.VERSION); // string SCore version
_d("TIMEZONE", null);        // string   timezone
?>
