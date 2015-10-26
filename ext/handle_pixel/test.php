<?php
class PixelHandlerTest extends ShimmiePHPUnitTestCase {
	public function testPixelHander() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		//$this->assert_response(302);

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}

