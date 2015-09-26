<?php
class TagHistoryTest extends ShimmiePHPUnitTestCase {
	public function testTagHistory() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assert_title("Image $image_id: pbx");

		$this->markTestIncomplete();

		// FIXME
		$this->set_field("tag_edit__tags", "new");
		$this->click("Set");
		$this->assert_title("Image $image_id: new");
		$this->click("View Tag History");
		$this->assert_text("new (Set by demo");
		$this->click("Revert To");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("tag_history/all/1");
		$this->assert_title("Global Tag History");
	}
}

