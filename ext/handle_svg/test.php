<?php
class SVGHandlerTest {  // extends ShimmiePHPUnitTestCase {
	function testSVGHander() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/test.svg", "something");
		$this->assert_response(302);

		$raw = $this->get_page("get_svg/$image_id");
		$this->assertTrue(strpos($raw, "www.w3.org") > 0);

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}
