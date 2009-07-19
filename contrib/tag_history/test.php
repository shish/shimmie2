<?php
class TagHistoryTest extends ShimmieWebTestCase {
	function testTagHistory() {
		$this->log_in_as_admin();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assertTitle("Image $image_id: pbx");
		$this->setField("tag_edit__tags", "new");
		$this->click("Set");
		$this->assertTitle("Image $image_id: new");
		$this->click("Tag History");
		$this->assertText("new (Set by demo");
		$this->click("Revert");
		$this->assertTitle("Image $image_id: pbx");
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
