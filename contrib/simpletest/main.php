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

class SimpleSCoreTest implements Extension {
	var $theme;

	public function receive_event(Event $event) {
		if(is_null($this->theme)) $this->theme = get_theme_object($this);

		if(($event instanceof PageRequestEvent) && $event->page_matches("test")) {
			$event->page->set_title("Test Results");
			$event->page->set_heading("Test Results");
			$event->page->add_block(new NavBlock());

			$all = new TestFinder($event->get_arg(0));
			$all->run(new SCoreReporter($event->page));
		}

		if($event instanceof UserBlockBuildingEvent) {
			if($event->user->is_admin()) {
				$event->add_link("Run Tests", make_link("test/all"));
			}
		}
	}
}
add_event_listener(new SimpleSCoreTest());
?>
