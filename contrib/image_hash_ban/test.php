<?php
class HashBanTest extends ShimmieWebTestCase {
	function testBan() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx");
		$this->log_out();

		$this->log_in_as_admin();
		$this->get_page("post/view/$image_id");
		$this->click("Ban and Delete");
		$this->log_out();

	}
}
?>
