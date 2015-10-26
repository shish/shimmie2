<?php
class RSSCommentsTest extends ShimmiePHPUnitTestCase {
    function testImageFeed() {
		global $user;
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		send_event(new CommentPostingEvent($image_id, $user, "ASDFASDF"));

		$this->get_page('rss/comments');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");
		$this->assert_content("ASDFASDF");
    }
}

