<?php

$options = getopt("d:");
$db = $options["d"];

if (empty($db)){
	die("Error: need to specifiy a database for the test environment.");
}

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

class ShimmieSimpleTestCase extends WebTestCase {
	var $database;
	
	function ShimmieTestCase() {
		$this->database = $db;
	}
	
	function testInstallShimmie()
	{
		// Make sure that we know what database to use.
		$this->assertFalse(empty($this->database));
		
		$this->get('http://127.0.0.1/');
		$this->assertResponse(200);
		$this->assertTitle("Shimmie Installation");
		$this->assertText("Database Install");
		
		$this->setField("database_type", $this->database);
		$this->assertField("database_host", "localhost");
		
		if ($this->database === "mysql") {
			$this->setField("database_user", "root");
			$this->setField("database_password", "");
		} elseif ($this->database === "pgsql") {
			$this->setField("database_user", "postgres");
			$this->setField("database_password", "");
		}		
		
		$this->assertField("database_name", "shimmie");
		$this->clickSubmit("Go!");
		
		if (!$this->assertTitle("Welcome to Shimmie")) {
			$this->showSource();
		}
		
		$this->assertText("Welcome to Shimmie");
		$this->assertText("This message will go away once your first image");
	}
	
}
