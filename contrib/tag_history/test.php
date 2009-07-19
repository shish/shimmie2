<?php
class TagHistoryTest extends ShimmieWebTestCase {
	function testTagHistory() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assertTitle("Image $image_id: pbx");
		$this->setField("tags", "new");
		$this->click("Set");
		$this->assertTitle("Image $image_id: new");
		$this->click("Tag History");
		$this->assertText("new (Set by test");
		$this->click("Revert");
		$this->assertTitle("Image $image_id: pbx");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
