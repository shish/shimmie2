<?php
class TagListTest extends ShimmieWebTestCase {
	function testTagList() {
		$this->get_page('tags/map');
		$this->assertTitle('Tag List');

		$this->get_page('tags/alphabetic');
		$this->assertTitle('Tag List');

		$this->get_page('tags/popularity');
		$this->assertTitle('Tag List');

		$this->get_page('tags/categories');
		$this->assertTitle('Tag List');

		# FIXME: test that these show the right stuff
	}
}
?>
