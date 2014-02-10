<?php

require_once('lib/simpletest/autorun.php');
require_once('lib/simpletest/unit_tester.php');
require_once('lib/simpletest/reporter.php');

class ShimmieTestCase extends UnitTestCase {
	
	var $options;
	
	function ShimmieTestCase() {
		$this->options = getopt("database:url:"); 
	}
	
	function testOptions() {
		print_r($this->options);
		$this->assertTrue(true);
	}
	
}
