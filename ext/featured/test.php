<?php
class FeaturedTest extends ShimmiePHPUnitTestCase {
	public function testFeatured() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

		# FIXME: test that regular users can't feature things

		$this->log_in_as_admin();
		$this->get_page("post/view/$image_id");
		$this->assert_title("Image $image_id: pbx");

		$this->markTestIncomplete();

		$this->click("Feature This");
		$this->get_page("post/list");
		$this->assert_text("Featured Image");

		# FIXME: test changing from one feature to another

		$this->get_page("featured_image/download");
		$this->assert_response(200);

		$this->get_page("featured_image/view");
		$this->assert_response(200);

		$this->delete_image($image_id);
		$this->log_out();

		# after deletion, there should be no feature
		$this->get_page("post/list");
		$this->assert_no_text("Featured Image");
	}
}

