<?php
class NewsTest extends ShimmieWebTestCase {
	function testNews() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->setField("_config_news_text", "kittens");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assertText("Note");
		$this->assertText("kittens");

		$this->get_page("setup");
		$this->setField("_config_news_text", "");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assertNoText("Note");
		$this->assertNoText("kittens");

		$this->log_out();
	}
}
?>
