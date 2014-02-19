<?php
/**
 * SimpleTest integration with Travis CI for Shimmie
 * 
 * @package    Shimmie
 * @author     jgen <jeffgenovy@gmail.com>
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @copyright  Copyright (c) 2014, jgen
 */

require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

// Enable all errors.
error_reporting(E_ALL);

// The code below is based on the code in index.php
//--------------------------------------------------

require_once "core/sys_config.inc.php";
require_once "core/util.inc.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

// load base files
$files = array_merge(zglob("core/*.php"), zglob("ext/{".ENABLED_EXTS."}/main.php"));
foreach($files as $filename) {
	require_once $filename;
}

// We also need to pull in the SimpleTest extension.
require_once('ext/simpletest/main.php');

// connect to the database
$database = new Database();
$config = new DatabaseConfig($database);

// load the theme parts
foreach(_get_themelet_files(get_theme()) as $themelet) {
	require_once $themelet;
}

_load_extensions();

// Create the necessary users for the tests.

$userPage = new UserPage();
$userPage->onUserCreation(new UserCreationEvent("demo", "demo", ""));
$database->commit(); // Need to commit the new user to the database.

$database->beginTransaction();

$userPage->onUserCreation(new UserCreationEvent("test", "test", ""));
$database->commit(); // Need to commit the new user to the database.

$database->beginTransaction();

// Continue

$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$user = _get_user();
send_event(new InitExtEvent());

// Now we can run all the tests.
$all = new TestFinder("");
$results = $all->run(new TextReporter());

// At this point this isn't really necessary as the test machines are stateless.
$database->commit();

exit ($results ? 0 : 1);
