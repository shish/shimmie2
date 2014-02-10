<?php

require_once('../lib/simpletest/autorun.php');

class SimpleTestCase extends UnitTestCase {
	
	var $options;
	
	function SimpleTestCase() {
		$options = getopt("database:url:"); 
	}
	
	function testOptions() {
		print_r($options);
		$this->assertTrue(true);
	}
	
}
