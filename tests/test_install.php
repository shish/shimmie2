<?php

$options = getopt("d:");
$db = $options["d"];

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

class ShimmieSimpleTestCase extends WebTestCase {
	var $database;
	var $db_user, $db_pass;
	
	function ShimmieTestCase() {
		$this->database = $db;
		
		if ($db === "mysql") {
			$this->db_user = "travis";
			$this->db_pass = "";
		} elseif ($db === "pgsql") {
			$this->db_user = "postgres";
			$this->db_pass = "";
		}
	}
	
	function testInstallShimmie()
	{
		$this->get('http://127.0.0.1/');
		$this->assertResponse(200);
		$this->assertTitle("Shimmie Installation");
		$this->assertText("Database Install");
		
		$this->setFieldById("database_type", $this->database);
		$this->assertFieldByName("database_host", "localhost");
		$this->setFieldByName("database_user", $this->db_user);
		$this->setFieldByName("database_password", $this->db_pass);
		$this->assertFieldByName("database_name", "shimmie");
		$this->clickSubmit("Go!");
		$this->assertTitle("Welcome to Shimmie");
		$this->assertText("Welcome to Shimmie");
		$this->assertText("This message will go away once your first image");
	}
	
}
