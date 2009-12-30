<?php
class LogDatabaseTest extends SCoreWebTestCase {
	function testLog() {
		$this->log_in_as_admin();
		$this->log_out();
	}
}
?>
