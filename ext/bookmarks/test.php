<?php
class BookmarksTest extends ShimmiePHPUnitTestCase {
	public function testBookmarks() {
		$this->get_page("bookmark/add");
		$this->get_page("bookmark/remove");
	}
}

