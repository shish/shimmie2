<?php
class TipsTest extends SCoreWebTestCase {
	function testTips() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->setField("_config_tips_text", "alert:kittens");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assertText("kittens");

		$this->get_page("setup");
		$this->setField("_config_news_text", "");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assertNoText("kittens");

		$this->log_out();
	}
}
?>
