<?php
class DowntimeTest extends SCoreWebTestCase {
	public function testDowntime() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_downtime", true);
		$this->set_field("_config_downtime_message", "brb, unit testing");
		$this->click("Save Settings");
		$this->assert_text("DOWNTIME MODE IS ON!");
		$this->log_out();

		$this->get_page("post/list");
		$this->assert_text("brb, unit testing");

		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_downtime", false);
		$this->click("Save Settings");
		$this->assert_no_text("DOWNTIME MODE IS ON!");
		$this->log_out();
	}
}

