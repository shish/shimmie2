<?php
class BlocksTest extends SCoreWebTestCase {
	function testBlocks() {
		$this->log_in_as_admin();

		$this->get_page("blocks/list");

		$this->log_out();
	}
}
?>
