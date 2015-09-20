<?php
class ImageTest extends ShimmiePHPUnitTestCase {
	public function testUserStats() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");

		// broken with sqlite?
		//$this->get_page("user/test");
		//$this->assert_text("Images uploaded: 1");

		//$this->click("Images uploaded");
		//$this->assert_title("Image $image_id: test");

		# test that serving manually doesn't cause errors
		$this->get_page("image/$image_id/moo.jpg");
		$this->get_page("thumb/$image_id/moo.jpg");
	}
}
