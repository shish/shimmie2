<?php
class RandomTest extends ShimmiePHPUnitTestCase {
	public function testRandom() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
		$this->log_out();

		$this->get_page("random_image/view");
		$this->assert_title("Image $image_id: test");

		$this->get_page("random_image/view/test");
		$this->assert_title("Image $image_id: test");

		$this->get_page("random_image/download");
		# FIXME: assert($raw == file(blah.jpg))
	}

	public function testPostListBlock() {
		$this->log_in_as_admin();
		$this->get_page("setup");

		$this->markTestIncomplete();

		$this->set_field("_config_show_random_block", true);
		$this->click("Save Settings");
		$this->log_out();

		# enabled, no image = no text
		$this->get_page("post/list");
		$this->assert_no_text("Random Image");

		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "test");
		$this->log_out();

		# enabled, image = text
		$this->get_page("post/list");
		$this->assert_text("Random Image");

		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_show_random_block", true);
		$this->click("Save Settings");

		# disabled, image = no text
		$this->get_page("post/list");
		$this->assert_text("Random Image");

		$this->delete_image($image_id);
		$this->log_out();

		# disabled, no image = no image
		$this->get_page("post/list");
		$this->assert_no_text("Random Image");
	}
}

