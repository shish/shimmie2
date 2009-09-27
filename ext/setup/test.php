<?php
class SetupTest extends SCoreWebTestCase {
	function testAuth() {
        $this->get_page('setup');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_user();
        $this->get_page('setup');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
		$this->log_out();

		$this->log_in_as_admin();
        $this->get_page('setup');
		$this->assert_title("Shimmie Setup");
		$this->assert_text("General");

        $this->get_page('setup/advanced');
		$this->assert_title("Shimmie Setup");
		$this->assert_text("thumb_quality");
		$this->log_out();
	}
}
?>
