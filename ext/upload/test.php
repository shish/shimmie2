<?php
class UploadTest extends ShimmieWebTestCase {
	function testUpload() {
		$this->log_in_as_user();

		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(302);

		$image_id_2 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertTitle("Upload Status");
		$this->assertText("already has hash");

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->log_out();
	}
}
?>
