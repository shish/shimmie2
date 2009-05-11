<?php
/**
 * Name: SimpleTest integration
 * Author: Shish <webmaster@shishnet.org>
 * License: GPLv2
 * Description: adds unit testing to SCore
 */

require_once('simpletest/web_tester.php');
require_once('simpletest/unit_tester.php');
require_once('simpletest/reporter.php');

class TestFinder extends TestSuite {
	function TestFinder($hint) {
		$dir = "*";
		if(file_exists("ext/$hint/test.php")) $dir = $hint; // FIXME: check for ..
		$this->TestSuite('All tests');
		foreach(glob("ext/$dir/test.php") as $file) {
			$this->addFile($file);
		}
	}
}

class SimpleSCoreTest extends SimpleExtension {
	public function onPageRequest($event) {
		global $page;
		if($event->page_matches("test")) {
			$page->set_title("Test Results");
			$page->set_heading("Test Results");
			$page->add_block(new NavBlock());

			$all = new TestFinder($event->get_arg(0));
			$all->run(new SCoreReporter($page));
		}
	}

	public function onUserBlockBuilding($event) {
		global $user;
		if($user->is_admin()) {
			$event->add_link("Run Tests", make_link("test/all"));
		}
	}
}
?>
