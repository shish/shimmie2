<?php
class ResLimitTest extends ShimmieWebTestCase {
	function testResLimitOK() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_upload_min_height", "0");
		$this->setField("_config_upload_min_width", "0");
		$this->setField("_config_upload_max_height", "2000");
		$this->setField("_config_upload_max_width", "2000");
		$this->setField("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(302);
		$this->assertNoText("Image too large");
		$this->assertNoText("Image too small");
		$this->assertNoText("ratio");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitSmall() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_upload_min_height", "900");
		$this->setField("_config_upload_min_width", "900");
		$this->setField("_config_upload_max_height", "-1");
		$this->setField("_config_upload_max_width", "-1");
		$this->setField("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(200);
		$this->assertTitle("Upload Status");
		$this->assertText("Image too small");
		$this->log_out();

		# hopefully a noop, but just in case
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitLarge() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_upload_min_height", "0");
		$this->setField("_config_upload_min_width", "0");
		$this->setField("_config_upload_max_height", "100");
		$this->setField("_config_upload_max_width", "100");
		$this->setField("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(200);
		$this->assertTitle("Upload Status");
		$this->assertText("Image too large");
		$this->log_out();

		# hopefully a noop, but just in case
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitRatio() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_upload_min_height", "-1");
		$this->setField("_config_upload_min_width", "-1");
		$this->setField("_config_upload_max_height", "-1");
		$this->setField("_config_upload_max_width", "-1");
		$this->setField("_config_upload_ratios", "16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assertResponse(200);
		$this->assertTitle("Upload Status");
		$this->assertText("Image needs to be in one of these ratios");
		$this->log_out();

		# hopefully a noop, but just in case
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	# reset to defaults, otherwise this can interfere with
	# other extensions' test suites
	public function tearDown() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->setField("_config_upload_min_height", "-1");
		$this->setField("_config_upload_min_width", "-1");
		$this->setField("_config_upload_max_height", "-1");
		$this->setField("_config_upload_max_width", "-1");
		$this->setField("_config_upload_ratios", "");
		$this->click("Save Settings");
		$this->log_out();
	}
}
?>
