<?php
class BanWordsTest extends ShimmieWebTestCase {
	function testWordBan() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_banned_words", "viagra\nporn\n\n/http:.*\.cn\//");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");

		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "kittens and viagra");
		$this->click("Post Comment");
		$this->assert_title("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "kittens and ViagrA");
		$this->click("Post Comment");
		$this->assert_title("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "kittens and viagra!");
		$this->click("Post Comment");
		$this->assert_title("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "some link to http://something.cn/");
		$this->click("Post Comment");
		$this->assert_title("Comment Blocked");

		$this->get_page('comment/list');
		$this->assert_title('Comments');
		$this->assert_no_text('viagra');
		$this->assert_no_text('ViagrA');
		$this->assert_no_text('http://something.cn/');
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}

