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

// set up and purify the environment
define("DEBUG", true);
define("COVERAGE", true);
define("CACHE_MEMCACHE", false);
define("CACHE_DIR", false);
define("VERSION", 'trunk');
define("SCORE_VERSION", 's2hack/'.VERSION);
define("COOKIE_PREFIX", 'shm');

if(empty($database_dsn) && !file_exists("config.php")) {
	header("Location: install.php");
	exit;
}

require_once "config.php";
require_once "core/util.inc.php";
if(COVERAGE) {
	_start_coverage();
	register_shutdown_function("_end_coverage");
}
_version_check();
_sanitise_environment();
_start_cache();

try {
	// load base files
	$files = array_merge(glob("core/*.php"), glob("ext/*/main.php"));
	foreach($files as $filename) {
		require_once $filename;
	}


	// connect to the database
	$database = new Database();
	//$database->db->fnExecute = '_count_execs'; // FIXME: PDO equivalent
	$database->db->beginTransaction();
	$config = new DatabaseConfig($database);


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


	// initialise the extensions
	foreach(get_declared_classes() as $class) {
		if(is_subclass_of($class, "SimpleExtension")) {
			$c = new $class();
			$c->i_am($c);
			add_event_listener($c, $c->get_priority());
		}
	}


	// start the page generation waterfall
	$page = class_exists("CustomPage") ? new CustomPage() : new Page();
	$user = _get_user($config, $database);
	send_event(new InitExtEvent());
	send_event(_get_page_request());
	$page->display();

	$database->db->commit();
	_end_cache();
}
catch(Exception $e) {
	$version = VERSION;
	$message = $e->getMessage();
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
	$database->db->rollback();
}
?>
