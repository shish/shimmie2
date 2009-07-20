<?php
class PixelHandlerTest extends ShimmieWebTestCase {
	function testPixelHander() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(302);
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}
?>
