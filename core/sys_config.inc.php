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

/** @private */
function _d($name, $value) {if(!defined($name)) define($name, $value);}
_d("DATABASE_DSN", null);    // string   PDO database connection details
_d("DATABASE_KA", true);     // string   Keep database connection alive
_d("CACHE_DSN", null);       // string   cache connection details
_d("DEBUG", false);          // boolean  print various debugging details
_d("DEBUG_SQL", false);      // boolean  dump SQL queries to data/sql.log
_d("DEBUG_CACHE", false);    // boolean  dump cache queries to data/cache.log
_d("COVERAGE", false);       // boolean  activate xdebug coverage monitor
_d("CONTEXT", null);         // string   file to log performance data into
_d("CACHE_HTTP", false);     // boolean  output explicit HTTP caching headers
_d("COOKIE_PREFIX", 'shm');  // string   if you run multiple galleries with non-shared logins, give them different prefixes
_d("SPEED_HAX", false);      // boolean  do some questionable things in the name of performance
_d("COMPILE_ELS", false);    // boolean  pre-build the list of event listeners
_d("NICE_URLS", false);      // boolean  force niceurl mode
_d("SEARCH_ACCEL", false);   // boolean  use search accelerator
_d("WH_SPLITS", 1);          // int      how many levels of subfolders to put in the warehouse
_d("VERSION", '2.5.5');      // string   shimmie version
_d("TIMEZONE", null);        // string   timezone
_d("CORE_EXTS", "bbcode,user,mail,upload,image,view,handle_pixel,ext_manager,setup,upgrade,handle_404,comment,tag_list,index,tag_edit,alias_editor"); // extensions to always enable
_d("EXTRA_EXTS", "");        // optional extra extensions


/*
 * Calculated settings - you should never need to change these
 * directly, only the things they're built from
 */
_d("SCORE_VERSION", 'develop/'.VERSION); // string SCore version
_d("ENABLED_EXTS", CORE_EXTS.",".EXTRA_EXTS);


