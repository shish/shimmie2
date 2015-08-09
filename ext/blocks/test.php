<?php
class BlocksTest extends ShimmiePHPUnitTestCase {
	function testBlocks() {
		$this->log_in_as_admin();
		$this->get_page("blocks/list");
		$this->assert_response(200);
		$this->assert_title("Blocks");
	}
}

