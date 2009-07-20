<?php
class FeaturedTest extends ShimmieWebTestCase {
	function testFeatured() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->log_out();

		# FIXME: test that regular users can't feature things

		$this->log_in_as_admin();
        $this->get_page("post/view/$image_id");
		$this->assertTitle("Image $image_id: pbx");
		$this->click("Feature This");
        $this->get_page("post/list");
		$this->assertText("Featured Image");
		$this->delete_image($image_id);
		$this->log_out();

		# after deletion, there should be no feature
        $this->get_page("post/list");
		$this->assertNoText("Featured Image");

		# FIXME: test changing from one feature to another
	}
}
?>
