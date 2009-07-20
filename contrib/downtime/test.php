<?php
class DowntimeTest extends ShimmieWebTestCase {
	function testDowntime() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_downtime", true);
		$this->setField("_config_downtime_message", "brb, unit testing");
		$this->click("Save Settings");
		$this->assertText("DOWNTIME MODE IS ON!");
		$this->log_out();

		$this->assertText("brb, unit testing");

		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_downtime", false);
		$this->click("Save Settings");
		$this->assertNoText("DOWNTIME MODE IS ON!");
		$this->log_out();
	}
}
?>
