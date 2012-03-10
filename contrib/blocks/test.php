<?php
class BlocksTest extends SCoreWebTestCase {
	function testNews() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->set_field("_config_blocks_text", "
Title: some text
Area: main
Priority: 100
Pages: *

waffles
");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_text("some text");
		$this->assert_text("waffles");

		$this->get_page("setup");
		$this->set_field("_config_blocks_text", "");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_no_text("some text");
		$this->assert_no_text("waffles");

		$this->log_out();
	}
}
?>
