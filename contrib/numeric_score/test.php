<?php
class NumericScoreTest extends ShimmieWebTestCase {
	function testNumericScore() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assertText("Current Score: 0");
		$this->click("Vote Down");
		$this->assertText("Current Score: -1");
		$this->click("Vote Up");
		$this->assertText("Current Score: 1");
		# FIXME: "remove vote" button?
		# FIXME: test that up and down are hidden if already voted up or down

		$this->get_page("post/list/score=1/1");
		$this->assertTitle("Image $image_id: pbx");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
