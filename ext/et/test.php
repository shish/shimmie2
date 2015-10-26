<?php
class ETTest extends ShimmiePHPUnitTestCase {
	public function testET() {
		$this->log_in_as_admin();
		$this->get_page("system_info");
		$this->assert_title("System Info");
	}
}
