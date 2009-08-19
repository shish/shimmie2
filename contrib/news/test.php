<?php
class NewsTest extends SCoreWebTestCase {
	function testNews() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->set_field("_config_news_text", "kittens");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_text("Note");
		$this->assert_text("kittens");

		$this->get_page("setup");
		$this->set_field("_config_news_text", "");
		$this->click("Save Settings");

		$this->get_page("post/list");
		$this->assert_no_text("Note");
		$this->assert_no_text("kittens");

		$this->log_out();
	}
}
?>
