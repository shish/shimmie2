<?php
class LogDatabaseTest extends SCoreWebTestCase {
	function testLog() {
		$this->log_in_as_admin();
		$this->get_page("log/view");
		$this->log_out();
	}
}
?>
