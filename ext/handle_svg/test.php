<?php
class SVGHandlerTest extends ShimmiePHPUnitTestCase {
	public function testSVGHander() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/test.svg", "something");
		$this->get_page("post/view/$image_id"); // test for no crash
		$this->get_page("get_svg/$image_id"); // test for no crash
		$this->assert_content("www.w3.org");

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}

