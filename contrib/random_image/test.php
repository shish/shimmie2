<?php
class RandomTest extends ShimmieWebTestCase {
	function testRandom() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "test");
		$this->log_out();

        $this->get_page("random_image/view");
        $this->assertTitle("Image $image_id: test");

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test random_image/download
		# FIXME: test random_image/ratio=4:3/download
	}
}
?>
