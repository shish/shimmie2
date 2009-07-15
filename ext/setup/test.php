<?php
class SetupTest extends ShimmieWebTestCase {
	function testAuth() {
        $this->get_page('setup');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");

		$this->log_in_as_user();
        $this->get_page('setup');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");
		$this->log_out();

		$this->log_in_as_admin();
        $this->get_page('setup');
		$this->assertTitle("Shimmie Setup");
		$this->assertText("General");
		$this->log_out();
	}
}
?>
