<?php
class OekakiTest extends SCoreWebTestCase {
	function testLog() {
		$this->log_in_as_user();
		$this->get_page("oekaki/create");
		$this->log_out();
	}
}
?>
