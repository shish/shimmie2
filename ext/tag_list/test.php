<?php
class TagListTest extends WebTestCase {
	function testTagList() {
		$this->get(TEST_BASE.'/tags/map');
		$this->assertTitle('Tag List');

		$this->get(TEST_BASE.'/tags/alphabetic');
		$this->assertTitle('Tag List');

		$this->get(TEST_BASE.'/tags/popularity');
		$this->assertTitle('Tag List');

		$this->get(TEST_BASE.'/tags/categories');
		$this->assertTitle('Tag List');
	}
}
?>
