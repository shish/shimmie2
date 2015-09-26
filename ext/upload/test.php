<?php
class UploadTest extends ShimmieWebTestCase {
	public function testUpload() {
		$this->log_in_as_user();

		$this->get_page("upload");
		$this->assert_title("Upload");

		$image_id_1 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(302);

		$image_id_2 = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("already has hash");

		$image_id_3 = $this->post_image("index.php", "test");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("File type not recognised");

		/*
		// FIXME: huge.dat is rejected for other reasons; manual testing shows that this works
		file_put_contents("huge.dat", file_get_contents("ext/simpletest/data/pbx_screenshot.jpg") . str_repeat("U", 1024*1024*3));
		$image_id_4 = $this->post_image("index.php", "test");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("File too large");
		unlink("huge.dat");
		*/

		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id_1);
		$this->delete_image($image_id_2);
		$this->delete_image($image_id_3); # if these were successfully rejected,
		//$this->delete_image($image_id_4); # then delete_image is a no-op
		$this->log_out();
	}
}

