<?php
class TipsTest extends SCoreWebTestCase {
	function testTips() {
		$this->log_in_as_admin();
		$this->get_page("tips/list");
		$this->assert_title("Tips List");
		$this->click("Save Settings");
		$this->log_out();
	}
}
?>
