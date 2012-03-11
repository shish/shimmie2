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
 * Dijkstra will kill me for personifying my architecture, but I can't think
 * of a better way without going into all the little details.
 * There are a bunch of Extension subclasses, they talk to eachother by sending
 * and recieving Event subclasses. The primary driver for each conversation is the
 * initial PageRequestEvent. If an Extension wants to display something to the
 * user, it adds a block to the Page data store. Once the conversation is over, the Page is passed to the
 * current theme's Layout class which will tidy up the data and present it to
 * the user. To see this in a more practical sense, see \ref hello.
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

if(!file_exists("config.php")) {
	header("Location: install.php");
	exit;
}
require_once "config.php";
require_once "core/default_config.inc.php";
require_once "core/util.inc.php";
require_once "lib/context.php";

// set up and purify the environment
if(CONTEXT) {
	ctx_set_log(CONTEXT);
}
ctx_log_start(@$_SERVER["REQUEST_URI"], true, true);
if(COVERAGE) {
	_start_coverage();
	register_shutdown_function("_end_coverage");
}
_version_check();
_sanitise_environment();
_start_cache();

try {
	// load base files
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

	// load the theme parts
	ctx_log_start("Loading themelets");
	$_theme = $config->get_string("theme", "default");
	if(!file_exists("themes/$_theme")) $_theme = "default";
	foreach(_get_themelet_files($_theme) as $themelet) {
		require_once $themelet;
	}
	ctx_log_endok();

	_load_extensions();

	// start the page generation waterfall
	$page = class_exists("CustomPage") ? new CustomPage() : new Page();
	$user = _get_user();
	send_event(new InitExtEvent());
	send_event(_get_page_request());
	$page->display();

	$database->db->commit();
	// saving cache data and profiling data to disk can happen later
	if(function_exists("fastcgi_finish_request")) fastcgi_finish_request();
	_end_cache();
	ctx_log_endok();
}
catch(Exception $e) {
	if($database && $database->db) $database->db->rollback();
	_fatal_error($e);
	ctx_log_ender();
}
?>
