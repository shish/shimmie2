<?php

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/reporter.php');

class ShimmieSimpleTestCase extends WebTestCase {
	var $options;
	var $database;
	
	function ShimmieTestCase() {
		$this->options = getopt("d:");
		$this->database = $this->options["d"];
	}
	
	function testOptions() {
		print_r($this->options);
		$this->assertNotNull($this->database);
	}
	
	function installShimmie()
	{
		$this->get('http://127.0.0.1/');
		$this->assertResponse(200);
	}
	
}
