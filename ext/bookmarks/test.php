<?php
class BookmarksTest extends ShimmieWebTestCase {
	public function testBookmarks() {
		$this->get_page("bookmark/add");
		$this->get_page("bookmark/remove");
	}
}

