<?php
class ImageTest extends ShimmieWebTestCase {
	public function testUserStats() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test");

		$this->get_page("user/test");
		$this->assert_text("Images uploaded: 1");
		$this->click("Images uploaded");
		$this->assert_title("Image $image_id: test");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
