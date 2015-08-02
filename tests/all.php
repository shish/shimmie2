<?php
/**
 * SimpleTest integration with Travis CI for Shimmie
 *
 * @package    Shimmie
 * @author     jgen <jeffgenovy@gmail.com>
 * @license    http://opensource.org/licenses/gpl-2.0.php GNU General Public License v2
 * @copyright  Copyright (c) 2014, jgen
 */

if(PHP_SAPI !== 'cli') die('cli only');

require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

// Enable all errors.
error_reporting(E_ALL);
define("CLI_LOG_LEVEL", -100); // output everything.

// Get the command line option telling us where the webserver is.
$options = getopt("h:");
$host = rtrim(trim(trim($options["h"], '"')), "/");

if (empty($host)){ $host = "http://127.0.0.1"; }

define("_TRAVIS_WEBHOST", $host);

// The code below is based on the code in index.php
//--------------------------------------------------

require_once('core/_bootstrap.inc.php');
require_once('ext/simpletest/main.php');

// Fire off the InitExtEvent()
$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$user = _get_user();
send_event(new InitExtEvent());

// Create the necessary users for the tests.
$userPage = new UserPage();
$userPage->onUserCreation(new UserCreationEvent("demo", "demo", ""));
$userPage->onUserCreation(new UserCreationEvent("test", "test", ""));

// Commit the users.
$database->commit();

// Fire off the InitExtEvent() again after we have made the users.
$page = class_exists("CustomPage") ? new CustomPage() : new Page();
$user = _get_user();
send_event(new InitExtEvent());

// Now we can actually run all the tests.
$all = new TestFinder("");
$results = $all->run(new TextReporter());

// Travis-CI needs to know the results of the tests.
exit ($results ? 0 : 1);
