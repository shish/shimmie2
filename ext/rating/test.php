<?php
class RatingTest extends ShimmiePHPUnitTestCase {
	public function testRating() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");

		# test for bug #735: user forced to set rating, can't
		# set tags and leave unrated
		$this->get_page("post/view/$image_id");
		$this->assert_title("Image $image_id: pbx");

		$this->markTestIncomplete();

		$this->set_field("tag_edit__tags", "new");
		$this->click("Set");
		$this->assert_title("Image $image_id: new");

		# set safe
		$this->set_field("rating", "s");
		$this->click("Set");
		$this->assert_title("Image $image_id: new");

		# search for it in various ways
		$this->get_page("post/list/rating=Safe/1");
		$this->assert_title("Image $image_id: new");

		$this->get_page("post/list/rating=s/1");
		$this->assert_title("Image $image_id: new");

		$this->get_page("post/list/rating=sqe/1");
		$this->assert_title("Image $image_id: new");

		# test that search by tag still works
		$this->get_page("post/list/new/1");
		$this->assert_title("Image $image_id: new");

		# searching for a different rating should return nothing
		$this->get_page("post/list/rating=q/1");
		$this->assert_text("No Images Found");

		# now set explicit, for the next test
		$this->get_page("post/view/$image_id");
		$this->set_field("rating", "e");
		$this->click("Set");
		$this->assert_title("Image $image_id: new");

		$this->log_out();

		# the explicit image shouldn't show up in anon's searches
		$this->get_page("post/list/new/1");
		$this->assert_text("No Images Found");
	}
}

