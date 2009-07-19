<?php
class ReportImageTest extends ShimmieWebTestCase {
	function testReportImage() {
		$this->log_in_as_user();
		$image_id = $this->post_image("ext/simpletest/data/pbx_screenshot.jpg", "pbx computer screenshot");
		$this->get_page("post/view/$image_id");
		$this->setField('reason', "report details");
		$this->click("Report");
		$this->log_out();

		$this->log_in_as_admin();
		$this->get_page("image_report/list");
		$this->assertTitle("Reported Images");
		$this->assertText("report details");
		$this->click("Remove Report");
		$this->assertTitle("Reported Images");
		$this->assertNoText("report details");

		$this->delete_image($image_id);
		$this->log_out();
	}
}
?>
