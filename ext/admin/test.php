<?php
class AdminPageTest extends ShimmieWebTestCase {
	function testAuth() {
		$this->get_page('admin');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");

		$this->log_in_as_user();
		$this->get_page('admin');
		$this->assertResponse(403);
		$this->assertTitle("Permission Denied");
		$this->log_out();

		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assertTitle("Admin Tools");
		$this->log_out();

		# FIXME: make sure the admin tools actually work
	}
}
?>
