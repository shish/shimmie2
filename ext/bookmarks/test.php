<?php
class BookmarksTest extends ShimmiePHPUnitTestCase {
	function testBookmarks() {
		$this->get_page("bookmark/add");
		$this->get_page("bookmark/remove");
	}
}

