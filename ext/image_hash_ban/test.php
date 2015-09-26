<?php
class HashBanTest extends ShimmiePHPUnitTestCase {
	public function testBan() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->log_out();

		$this->log_in_as_admin();
		$this->get_page("post/view/$image_id");

		$this->markTestIncomplete();

		$this->click("Ban and Delete");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("post/view/$image_id");
		$this->assert_response(404);
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assert_response(404);

		$this->log_in_as_admin();
		$this->get_page("image_hash_ban/list/1");
		$this->click("Remove");

		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assert_response(200);
	}
}

