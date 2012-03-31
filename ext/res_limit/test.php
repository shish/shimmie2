<?php
class ResLimitTest extends ShimmieWebTestCase {
	function testResLimitOK() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_upload_min_height", "0");
		$this->set_field("_config_upload_min_width", "0");
		$this->set_field("_config_upload_max_height", "2000");
		$this->set_field("_config_upload_max_width", "2000");
		$this->set_field("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(302);
		$this->assert_no_text("Image too large");
		$this->assert_no_text("Image too small");
		$this->assert_no_text("ratio");
		$this->log_out();

		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitSmall() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_upload_min_height", "900");
		$this->set_field("_config_upload_min_width", "900");
		$this->set_field("_config_upload_max_height", "-1");
		$this->set_field("_config_upload_max_width", "-1");
		$this->set_field("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("Image too small");
		$this->log_out();

		# hopefully a noop, but just in case
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitLarge() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_upload_min_height", "0");
		$this->set_field("_config_upload_min_width", "0");
		$this->set_field("_config_upload_max_height", "100");
		$this->set_field("_config_upload_max_width", "100");
		$this->set_field("_config_upload_ratios", "4:3 16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("Image too large");
		$this->log_out();

		# hopefully a noop, but just in case
		$this->log_in_as_admin();
		$this->delete_image($image_id);
		$this->log_out();
	}

	function testResLimitRatio() {
		$this->log_in_as_admin();
		$this->get_page("setup");
		$this->set_field("_config_upload_min_height", "-1");
		$this->set_field("_config_upload_min_width", "-1");
		$this->set_field("_config_upload_max_height", "-1");
		$this->set_field("_config_upload_max_width", "-1");
		$this->set_field("_config_upload_ratios", "16:9");
		$this->click("Save Settings");
		$this->log_out();

		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->assert_response(200);
		$this->assert_title("Upload Status");
		$this->assert_text("Image needs to be in one of these ratios");
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
		$this->set_field("_config_upload_min_height", "-1");
		$this->set_field("_config_upload_min_width", "-1");
		$this->set_field("_config_upload_max_height", "-1");
		$this->set_field("_config_upload_max_width", "-1");
		$this->set_field("_config_upload_ratios", "");
		$this->click("Save Settings");
		$this->log_out();
	}
}
?>
