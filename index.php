<?php
/**
 * \mainpage Shimmie2 / SCore Documentation
 *
 * SCore is a framework designed for writing flexible, extendable applications.
 * Whereas most PHP apps are built monolithicly, score's event-based nature
 * allows parts to be mixed and matched. For instance, the most famous
 * collection of score extensions is the Shimmie image board, which includes
 * user management, a wiki, a private messaging system, etc. But one could
 * easily remove the image board bits and simply have a wiki with users and
 * PMs; or one could replace it with a blog module; or one could have a blog
 * which links to images on an image board, with no wiki or messaging, and so
 * on and so on...
 * 
 * To learn about the innards of SCore, start with the \ref overview.
 *
 *
 * \page overview High Level Overview
 *
 * Dijkstra will kill me for personifying my architecture, but I can't think
 * of a better way without going into all the little details.
 *
 * There are a bunch of Extension subclasses, they talk to eachother by sending
 * and recieving Event subclasses. The topic of conversation is decided by the
 * initial PageRequestEvent, and each extension puts its notes into the shared
 * Page data store. Once the conversation is over, the Page is passed to the
 * current theme's Layout class which will tidy up the data and present it to
 * the user.
 *
 * To learn more about the architecture:
 *
 * \li \ref eande
 * \li \ref themes
 *
 * To learn more about practical development:
 *
 * \li \ref scglobals
 * \li \ref unittests
 * \li \ref hello
 *
 * \page scglobals SCore Globals
 * 
 * There are four global variables which are pretty essential to most extensions:
 * 
 * \li $config -- some variety of Config subclass
 * \li $database -- a Database object used to get raw SQL access
 * \li $page -- a Page to holds all the loose bits of extension output
 * \li $user -- the currently logged in User
 *
 * Each of these can be imported at the start of a function with eg "global $page, $user;"
 */

if(empty($database_dsn) && !file_exists("config.php")) {
	header("Location: install.php");
	exit;
}
require_once "config.php";

// to change these system-level settings, do define("FOO", 123); in config.php
function _d($name, $value) {if(!defined($name)) define($name, $value);}
_d("DATABASE_DSN", null);    // string   PDO database connection details
_d("CACHE_DSN", null);       // string   cache connection details
_d("DEBUG", false);          // boolean  print various debugging details
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
_d("TIMEZONE", 'UTC');       // string   timezone

// set up and purify the environment
date_default_timezone_set(TIMEZONE);

require_once "core/util.inc.php";
require_once "lib/context.php";
if(CONTEXT) {
	ctx_set_log(CONTEXT);
}
ctx_log_start($_SERVER["REQUEST_URI"], true, true);
if(COVERAGE) {
	_start_coverage();
	register_shutdown_function("_end_coverage");
}
_version_check();
_sanitise_environment();
_start_cache();

try {
	// load base files
	ctx_log_start("Initialisation");
	ctx_log_start("Opening files");
	$files = array_merge(glob("core/*.php"), glob("ext/*/main.php"));
	foreach($files as $filename) {
		require_once $filename;
	}
	ctx_log_endok();


	ctx_log_start("Connecting to DB");
	// connect to the database
	$database = new Database();
	$database->db->beginTransaction();
	$config = new DatabaseConfig($database);
	ctx_log_endok();


	ctx_log_start("Loading themelets");
	// load the theme parts
	$_theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$_theme")) $_theme = "default";
	if(file_exists("themes/$_theme/custompage.class.php")) require_once "themes/$_theme/custompage.class.php";
	require_once "themes/$_theme/layout.class.php";
	require_once "themes/$_theme/themelet.class.php";

	$themelets = glob("ext/*/theme.php");
	foreach($themelets as $filename) {
		require_once $filename;
	}

	$custom_themelets = glob("themes/$_theme/*.theme.php");
	if($custom_themelets) {
		$m = array();
		foreach($custom_themelets as $filename) {
			if(preg_match("/themes\/$_theme\/(.*)\.theme\.php/",$filename,$m)
					&& in_array("ext/{$m[1]}/theme.php", $themelets)) {
				require_once $filename;
			}
		}
	}
	ctx_log_endok();


	ctx_log_start("Loading extensions");
	// initialise the extensions
	global $_event_listeners;
	if(COMPILE_ELS && file_exists("data/event_listeners.php")) {
		require_once("data/event_listeners.php");
	}
	else {
		$all_events = array();
		foreach(get_declared_classes() as $class) {
			if(is_subclass_of($class, "Event")) {
				$all_events[] = $class;
			}
		}
		foreach(get_declared_classes() as $class) {
			$rclass = new ReflectionClass($class);
			if($rclass->isAbstract()) {
				// don't do anything
			}
			elseif(is_subclass_of($class, "SimpleExtension")) {
				$c = new $class();
				$c->i_am($c);
				$my_events = array();
				foreach(get_class_methods($c) as $method) {
					if(substr($method, 0, 2) == "on") {
						$my_events[] = substr($method, 2) . "Event";
					}
				}
				add_event_listener($c, $c->get_priority(), $my_events);
			}
			elseif(is_subclass_of($class, "Extension")) {
				$c = new $class();
				add_event_listener($c, $c->get_priority(), $all_events);
			}
		}

		if(COMPILE_ELS) {
			$p = "<"."?php\n";

			foreach(get_declared_classes() as $class) {
				$rclass = new ReflectionClass($class);
				if($rclass->isAbstract()) {}
				elseif(is_subclass_of($class, "SimpleExtension")) {
					$p .= "\$$class = new $class(); ";
					$p .= "\${$class}->i_am(\$$class);\n";
				}
				elseif(is_subclass_of($class, "Extension")) {
					$p .= "\$$class = new $class();\n";
				}
			}

			$p .= "\$_event_listeners = array(\n";
			foreach($_event_listeners as $event => $listeners) {
				$p .= "\t'$event' => array(\n";
				foreach($listeners as $id => $listener) {
					$p .= "\t\t$id => \$".get_class($listener).",\n";
				}
				$p .= "\t),\n";
			}
			$p .= ");\n";

			$p .= "?".">";
			file_put_contents("data/event_listeners.php", $p);
		}
	}
	ctx_log_endok();
	ctx_log_endok("Initialisation");


	ctx_log_start("Page generation");
	// start the page generation waterfall
	$page = class_exists("CustomPage") ? new CustomPage() : new Page();
	$user = _get_user($config, $database);
	send_event(new InitExtEvent());
	send_event(_get_page_request());
	$page->display();
	ctx_log_endok("Page generation");

	$database->db->commit();
	_end_cache();
	ctx_log_endok();
}
catch(Exception $e) {
	$version = VERSION;
	$message = $e->getMessage();
	//$trace = var_dump($e->getTrace());
	header("HTTP/1.0 500 Internal Error");
	print <<<EOD
<html>
	<head>
		<title>Internal error - SCore-$version</title>
	</head>
	<body>
		<h1>Internal Error</h1>
		<p>$message
	</body>
</html>
EOD;
	if($database && $database->db) $database->db->rollback();
	ctx_log_ender();
}
?>
