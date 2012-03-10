<?php
class TagHistoryTest extends ShimmieWebTestCase {
	function testTagHistory() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assert_title("Image $image_id: pbx");
		$this->set_field("tag_edit__tags", "new");
		$this->click("Set");
		$this->assert_title("Image $image_id: new");
		$this->click("Tag History");
		$this->assert_text("new (Set by demo");
		$this->click("Revert To");
		$this->assert_title("Image $image_id: pbx");

		$this->get_page("tag_history");
		$this->click("Global Tag History");

		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
