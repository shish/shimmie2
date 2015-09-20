<?php
class RSSImagesTest extends ShimmiePHPUnitTestCase {
    function testImageFeed() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->log_out();

		$this->get_page('rss/images');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");

		$this->get_page('rss/images/1');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");

		# FIXME: test that the image is actually found
		$this->get_page('rss/images/computer/1');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");

		# valid tag, invalid page
		$this->get_page('rss/images/computer/2');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");

		# not found
		$this->get_page('rss/images/waffle/2');
		//$this->assert_mime("application/rss+xml");
		$this->assert_no_content("Exception");
    }
}

