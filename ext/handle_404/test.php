<?php
class Handle404Test extends WebTestCase {
	function test404Handler() {
		$this->get(TEST_BASE.'/not/a/page');
		$this->assertResponse(404);
		$this->assertTitle('404');
		$this->assertText("No handler could be found for the page 'not/a/page'");
	}
}
?>
