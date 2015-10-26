<?php
class IcoHandlerTest extends ShimmiePHPUnitTestCase {
	public function testIcoHander() {
		$this->log_in_as_user();
		$image_id = $this->post_image("lib/static/favicon.ico", "shimmie favicon");
		$this->get_page("post/view/$image_id"); // test for no crash
		$this->get_page("get_ico/$image_id"); // test for no crash

		# FIXME: test that the thumb works
		# FIXME: test that it gets displayed properly
	}
}

