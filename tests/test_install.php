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

// Enable all errors.
error_reporting(E_ALL);

// Get the command line option telling us what database to use.
$options = getopt("d:");
$db = $options["d"];

if (empty($db)){ die("Error: need to specifiy a database for the test environment."); }

define("_TRAVIS_DATABASE", $db);

class ShimmieInstallerTest extends WebTestCase {
	function testInstallShimmie()
	{
		$db = constant("_TRAVIS_DATABASE");
		
		// Make sure that we know what database to use.
		$this->assertFalse(empty($db));
		
		$this->get('http://127.0.0.1/');
		$this->assertResponse(200);
		$this->assertTitle("Shimmie Installation");
		$this->assertText("Database Install");
		
		$this->setField("database_type", $db);
		$this->assertField("database_host", "localhost");
		
		if ($db === "mysql") {
			$this->setField("database_user", "root");
			$this->setField("database_password", "");
		} elseif ($db === "pgsql") {
			$this->setField("database_user", "postgres");
			$this->setField("database_password", "");
		}		
		
		$this->assertField("database_name", "shimmie");
		$this->clickSubmit("Go!");
		
		if (!$this->assertText("Installation Succeeded!")) {
			$this->showSource();
		}
	}
}
