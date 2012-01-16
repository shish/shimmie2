<?php
class TwitterSocTest extends SCoreWebTestCase {
	function testFeed() {
		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->set_field("_config_twitter_soc_username", "shish2k");
		$this->click("Save Settings");

		/*
		$this->get_page("post/list");
		$this->assert_text("Note");
		$this->assert_text("kittens");
		*/

		$this->get_page("setup");
		$this->set_field("_config_twitter_soc_username", "");
		$this->click("Save Settings");

		/*
		$this->get_page("post/list");
		$this->assert_no_text("Note");
		$this->assert_no_text("kittens");
		*/

		$this->log_out();
	}
}
?>
