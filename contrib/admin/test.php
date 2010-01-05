<?php
class AdminPageTest extends ShimmieWebTestCase {
	function testAuth() {
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");

		$this->log_in_as_user();
		$this->get_page('admin');
		$this->assert_response(403);
		$this->assert_title("Permission Denied");
		$this->log_out();

		$this->log_in_as_admin();
		$this->get_page('admin');
		$this->assert_title("Admin Tools");
		$this->set_field("action", "purge unused tags");
		$this->click("Go");
		$this->log_out();

		# FIXME: make sure the admin tools actually work
	}
}
?>
