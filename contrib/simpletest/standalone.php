<?php
#require_once('lib/simpletest/autorun.php');
require_once('simpletest/web_tester.php');
#require_once('simpletest/unit_tester.php'); # unit tests require shimmie to be running
require_once('simpletest/reporter.php');

chdir("../../");
require_once('config.php');

class SectionReporter extends TextReporter {
	function paintGroupStart($name, $size) {
		parent::paintGroupStart($name, $size);
		print "\n** $name\n";
	}
}

class AllTests extends TestSuite {
	function AllTests() {
		$this->TestSuite('All tests');
		foreach(glob("ext/*/test.php") as $file) {
			$this->addFile($file);
		}
	}
}

$all = new AllTests();
$all->run(new SectionReporter());
?>
