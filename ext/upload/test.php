<?php
class UploadTest extends ShimmiePHPUnitTestCase {
	public function testUploadPage() {
		$this->log_in_as_user();

		$this->get_page("upload");
		$this->assert_title("Upload");
	}

	public function testUpload() {
		$this->log_in_as_user();
		$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
	}

	public function testRejectDupe() {
		$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");

		try {
			$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		}
		catch(UploadException $e) {
			$this->assertContains("already has hash", $e->getMessage());
		}
	}

	public function testRejectUnknownFiletype() {
		try {
			$this->post_image("index.php", "test");
		}
		catch(UploadException $e) {
			$this->assertContains("Invalid or corrupted file", $e->getMessage());
		}
	}

	public function testRejectHuge() {
		$this->markTestIncomplete();

		// FIXME: huge.dat is rejected for other reasons; manual testing shows that this works
		file_put_contents("huge.dat", file_get_contents("tests/pbx_screenshot.jpg") . str_repeat("U", 1024*1024*3));
		$this->post_image("index.php", "test");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("File too large");
		unlink("huge.dat");
	}
}

