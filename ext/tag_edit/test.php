<?php
class TagEditTest extends ShimmieWebTestCase {
	function testTagEdit() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->get_page("post/view/$image_id");
		$this->assertTitle("Image $image_id: pbx");
		$this->setField("tag_edit__tags", "new");
		$this->click("Set");
		$this->assertTitle("Image $image_id: new");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
