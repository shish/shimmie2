<?php
class FeaturedTest extends ShimmieWebTestCase {
	function testFeatured() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->log_out();

        $this->get_page("post/view/$image_id");
		$this->click("Feature This");

        $this->get_page("post/list");
		$this->assertText("Featured Image");

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();

		# after deletion, there should be no feature
        $this->get_page("post/list");
		$this->assertNoText("Featured Image");

		# FIXME: test changing from one feature to another
	}
}
?>
