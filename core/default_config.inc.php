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

// to change these system-level settings, do define("FOO", 123); in config.php
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
_d("EXTRA_USER_CLASSES", serialize(array())); // array extra classes that a user can be*

/**
 * Defining extra user classes:
 *   see core/userclass.class.php for flags
 *
 * This is a kind of ugly way of doing things...
 *

define("EXTRA_USER_CLASSES", serialize(array(
	// a regular user, with some extra powers
	array(
		"moderator", # name for the new class
		"user",      # class to base it on
		array(       # parts of the base class to override
			"edit_image_lock" => True,
			"view_ip" => True,
			"ban_ip" => True,
			"delete_image" => True,
			"delete_comment" => True,
			"manage_alias_list" => True,
			"mass_tag_edit" => True,
			"edit_image_tag" => True,
			"edit_image_source" => True,
			"edit_image_owner" => True,
			"view_image_report" => True,
		)
	),
	// an admin, minus the ability to create / remove other admins
	array(
		"manager", # name for the new class
		"admin",   # class to base it on
		array(     # parts of the base class to override
			"override_config" => False,
			"edit_user_password" => False,
			"edit_user_info" => False,
			"delete_user" => False,
			"manage_extension_list" => False,
		)
	),
)));

 */
?>
