<?php
/**
 * SimpleTest integration with Travis CI for Shimmie
 * 
 * @package    Shimmie
 * @author     jgen <jeffgenovy@gmail.com>
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @copyright  Copyright (c) 2014, jgen
 */

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');
require_once('tests/test_install.php');
require_once("core/util.inc.php");

$options = getopt("d:");
$db = $options["d"];

if (empty($db)){ die("Error: need to specifiy a database for the test environment."); }

define("_TRAVIS_DATABASE", $db);

// Install Shimmie
$test_suite = new TestSuite('Shimmie tests');
$test_suite->add(new ShimmieInstallerTest());

// 
// From index.php
//

require_once("core/sys_config.inc.php");
include "data/config/shimmie.conf.php";

// set up and purify the environment
_version_check();
_sanitise_environment();

// load base files
$files = array_merge(zglob("core/*.php"), zglob("ext/{".ENABLED_EXTS."}/main.php"));
foreach($files as $filename) {
	require_once $filename;
}

// connect to the database
$database = new Database();
$config = new DatabaseConfig($database);

// load the theme parts
foreach(_get_themelet_files(get_theme()) as $themelet) {
	require_once $themelet;
}

_load_extensions();

$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$user = _get_user();
send_event(new InitExtEvent());

// Run all the tests
$all = new TestFinder();
$all->run(new TextReporter());

// Is this really needed?
$database->commit();
