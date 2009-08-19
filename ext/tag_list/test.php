<?php
class TagListTest extends ShimmieWebTestCase {
	function testTagList() {
		$this->get_page('tags/map');
		$this->assert_title('Tag List');

		$this->get_page('tags/alphabetic');
		$this->assert_title('Tag List');

		$this->get_page('tags/popularity');
		$this->assert_title('Tag List');

		$this->get_page('tags/categories');
		$this->assert_title('Tag List');

		# FIXME: test that these show the right stuff
	}
}
?>
