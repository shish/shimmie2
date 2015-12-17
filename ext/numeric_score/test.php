<?php
class NumericScoreTest extends ShimmiePHPUnitTestCase {
	public function testNumericScore() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");

		$this->markTestIncomplete();

		$this->assert_text("Current Score: 0");
		$this->click("Vote Down");
		$this->assert_text("Current Score: -1");
		$this->click("Vote Up");
		$this->assert_text("Current Score: 1");
		# FIXME: "remove vote" button?
		# FIXME: test that up and down are hidden if already voted up or down

		# test search by score
		$this->get_page("post/list/score=1/1");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("post/list/score>0/1");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("post/list/score>-5/1");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("post/list/-score>5/1");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("post/list/-score<-5/1");
		$this->assert_title("Image $image_id: pbx");

		# test search by vote
		$this->get_page("post/list/upvoted_by=test/1");
		$this->assert_title("Image $image_id: pbx");
		$this->assert_no_text("No Images Found");

		# and downvote
		$this->get_page("post/list/downvoted_by=test/1");
		$this->assert_text("No Images Found");

		# test errors
		$this->get_page("post/list/upvoted_by=asdfasdf/1");
		$this->assert_text("No Images Found");
		$this->get_page("post/list/downvoted_by=asdfasdf/1");
		$this->assert_text("No Images Found");
		$this->get_page("post/list/upvoted_by_id=0/1");
		$this->assert_text("No Images Found");
		$this->get_page("post/list/downvoted_by_id=0/1");
		$this->assert_text("No Images Found");

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}

