<?php
class BanWordsTest extends ShimmieWebTestCase {
	function testWordBan() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_banned_words", "viagra\nporn\n/http:.*\.cn\//");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");

		$this->get_page("post/view/$image_id");
		$this->setField('comment', "kittens and viagra");
		$this->click("Post Comment");
		$this->assertTitle("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->setField('comment', "kittens and ViagrA");
		$this->click("Post Comment");
		$this->assertTitle("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->setField('comment', "kittens and viagra!");
		$this->click("Post Comment");
		$this->assertTitle("Comment Blocked");

		$this->get_page("post/view/$image_id");
		$this->setField('comment', "some link to http://something.cn/");
		$this->click("Post Comment");
		$this->assertTitle("Comment Blocked");

		$this->get_page('comment/list');
		$this->assertTitle('Comments');
		$this->assertNoText('viagra');
		$this->assertNoText('ViagrA');
		$this->assertNoText('http://something.cn/');
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
