<?php
class ResLimitTest extends ShimmiePHPUnitTestCase {
	public function testResLimitOK() {
		global $config;
		$config->set_int("upload_min_height", 0);
		$config->set_int("upload_min_width", 0);
		$config->set_int("upload_max_height", 2000);
		$config->set_int("upload_max_width", 2000);
		$config->set_string("upload_ratios", "4:3 16:9");

		$this->log_in_as_user();
		$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		//$this->assert_response(302);
		$this->assert_no_text("Image too large");
		$this->assert_no_text("Image too small");
		$this->assert_no_text("ratio");
	}

	public function testResLimitSmall() {
		global $config;
		$config->set_int("upload_min_height", 900);
		$config->set_int("upload_min_width", 900);
		$config->set_int("upload_max_height", -1);
		$config->set_int("upload_max_width", -1);
		$config->set_string("upload_ratios", "4:3 16:9");

		$this->log_in_as_user();
		try {
			$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		}
		catch(UploadException $e) {
			$this->assertEquals("Image too small", $e->getMessage());
		}
	}

	public function testResLimitLarge() {
		global $config;
		$config->set_int("upload_min_height", 0);
		$config->set_int("upload_min_width", 0);
		$config->set_int("upload_max_height", 100);
		$config->set_int("upload_max_width", 100);
		$config->set_string("upload_ratios", "4:3 16:9");

		try {
			$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		}
		catch(UploadException $e) {
			$this->assertEquals("Image too large", $e->getMessage());
		}

	}

	public function testResLimitRatio() {
		global $config;
		$config->set_int("upload_min_height", -1);
		$config->set_int("upload_min_width", -1);
		$config->set_int("upload_max_height", -1);
		$config->set_int("upload_max_width", -1);
		$config->set_string("upload_ratios", "16:9");

		try {
			$this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		}
		catch(UploadException $e) {
			$this->assertEquals("Image needs to be in one of these ratios: 16:9", $e->getMessage());
		}

	}

	# reset to defaults, otherwise this can interfere with
	# other extensions' test suites
	public function tearDown() {
		parent::tearDown();

		global $config;
		$config->set_int("upload_min_height", -1);
		$config->set_int("upload_min_width", -1);
		$config->set_int("upload_max_height", -1);
		$config->set_int("upload_max_width", -1);
		$config->set_string("upload_ratios", "");
	}
}

