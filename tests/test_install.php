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

// Get the command line option telling us what database and host to use.
$options = getopt("d:h:");
$db = $options["d"];
$host = rtrim(trim(trim($options["h"], '"')), "/");

// Check if they are empty.
if (empty($db)){ die("Error: need to specify a database for the test environment."); }
if (empty($host)){ $host = "http://127.0.0.1"; }

define("_TRAVIS_DATABASE", $db);
define("_TRAVIS_WEBHOST", $host);

// Currently the tests only support MySQL and PostgreSQL.
if ($db === "mysql") {
	define("_TRAVIS_DATABASE_USERNAME", "root");
	define("_TRAVIS_DATABASE_PASSWORD", "");
} elseif ($db === "pgsql") {
	define("_TRAVIS_DATABASE_USERNAME", "postgres");
	define("_TRAVIS_DATABASE_PASSWORD", "");
} else {
	die("Unsupported Database Option");
}

class ShimmieInstallerTest extends WebTestCase {
	function testInstallShimmie()
	{
		// Get the settings from the global constants.
		$db = constant("_TRAVIS_DATABASE");
		$host = constant("_TRAVIS_WEBHOST");
		$username = constant("_TRAVIS_DATABASE_USERNAME");
		$password = constant("_TRAVIS_DATABASE_PASSWORD");

		// Make sure that we know where the host is.
		$this->assertFalse(empty($host));
		// Make sure that we know what database to use.
		$this->assertFalse(empty($db));

		$this->get($host);
		$this->assertResponse(200);
		$this->assertTitle("Shimmie Installation");
		$this->assertText("Database Install");

		$this->setField("database_type", $db);
		$this->assertField("database_type", $db);
		$this->assertField("database_host", "localhost");
		$this->setField("database_user", $username);
		$this->setField("database_password", $password);
		$this->assertField("database_name", "shimmie");
		$this->clickSubmit("Go!");

		if (!$this->assertText("Installation Succeeded!")) {
			print "ERROR --- '" + $db + "'";
			$this->showSource();
		}
	}
}

$test = new TestSuite('Install Shimmie');
$test->add(new ShimmieInstallerTest());
exit ($test->run(new TextReporter()) ? 0 : 1);
