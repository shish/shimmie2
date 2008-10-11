<?php
class TagListTest extends WebTestCase {
	function testTagList() {
		$this->get('http://shimmie.shishnet.org/v2/tags/map');
		$this->assertTitle('Tag List');

		$this->get('http://shimmie.shishnet.org/v2/tags/alphabetic');
		$this->assertTitle('Tag List');

		$this->get('http://shimmie.shishnet.org/v2/tags/popularity');
		$this->assertTitle('Tag List');

		$this->get('http://shimmie.shishnet.org/v2/tags/categories');
		$this->assertTitle('Tag List');
	}
}
?>
