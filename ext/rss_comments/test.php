<?php
class RSSCommentsTest {
    function testImageFeed() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->set_field('comment', "Test Comment ASDFASDF");
		$this->click("Post Comment");
		$this->assert_text("ASDFASDF");
		$this->log_out();

		$this->get_page('rss/comments');
		$this->assert_mime("application/rss+xml");
		$this->assert_no_text("Exception");
		$this->assert_text("ASDFASDF");

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
    }
}

