<?php
class LogDatabaseTest extends ShimmiePHPUnitTestCase {
	public function testLog() {
		$this->log_in_as_admin();
		$this->get_page("log/view");
		$this->get_page("log/view?module=core-image");
		$this->get_page("log/view?time=2012-03-01");
		$this->get_page("log/view?user=demo");
		$this->get_page("log/view?priority=10");
	}
}
