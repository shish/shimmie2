<?php
class ReportImageTest extends ShimmiePHPUnitTestCase {
	public function testReportImage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("tests/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");

		$this->markTestIncomplete();

		$this->set_field('reason', "report details");
		$this->click("Report");
		$this->log_out();

		$this->log_in_as_admin();

		$this->get_page("setup");
		$this->set_field("_config_report_image_show_thumbs", true);
		$this->click("Save Settings");
		$this->get_page("image_report/list");
		$this->assert_title("Reported Images");
		$this->assert_text("report details");

		$this->get_page("setup");
		$this->set_field("_config_report_image_show_thumbs", false);
		$this->click("Save Settings");
		$this->get_page("image_report/list");
		$this->assert_title("Reported Images");
		$this->assert_text("report details");
		$this->assert_text("$image_id");

		$this->get_page("image_report/list");
		$this->click("Remove Report");
		$this->assert_title("Reported Images");
		$this->assert_no_text("report details");

		$this->delete_image($image_id);
		$this->log_out();

		# FIXME: test delete image from report screen
		# FIXME: test that >>123 works
	}
}

