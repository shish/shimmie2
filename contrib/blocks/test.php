<?php
class BlocksTest extends SCoreWebTestCase {
	function testNews() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->set_field("_config_blocks_text", "badgers");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_text("badgers");

		$this->get_page("setup");
		$this->set_field("_config_blocks_text", "");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_no_text("Note");
		$this->assert_no_text("badgers");

		$this->log_out();
	}
}
?>
