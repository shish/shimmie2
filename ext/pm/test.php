<?php
class PrivMsgTest extends ShimmiePHPUnitTestCase {
	public function testPM() {
		$this->log_in_as_admin();
		$this->get_page("user/test");

		$this->markTestIncomplete();

		$this->set_field('subject', "message demo to test");
		$this->set_field('message', "message contents");
		$this->click("Send");
		$this->log_out();

		$this->log_in_as_user();
		$this->get_page("user");
		$this->assert_text("message demo to test");
		$this->click("message demo to test");
		$this->assert_text("message contents");
		$this->back();
		$this->click("Delete");
		$this->assert_no_text("message demo to test");

		$this->get_page("pm/read/0");
		$this->assert_text("No such PM");
		// GET doesn't work due to auth token check
		//$this->get_page("pm/delete/0");
		//$this->assert_text("No such PM");
		$this->get_page("pm/waffle/0");
		$this->assert_text("Invalid action");

		$this->log_out();
	}

	public function testAdminAccess() {
		$this->log_in_as_admin();
		$this->get_page("user/test");

		$this->markTestIncomplete();

		$this->set_field('subject', "message demo to test");
		$this->set_field('message', "message contents");
		$this->click("Send");

		$this->get_page("user/test");
		$this->assert_text("message demo to test");
		$this->click("message demo to test");
		$this->assert_text("message contents");
		$this->back();
		$this->click("Delete");

		# simpletest bug? - redirect(referrer) works in opera, not in
		# webtestcase, so we end up at the wrong page...
		$this->get_page("user/test");
		$this->assert_title("test's Page");
		$this->assert_no_text("message demo to test");
		$this->log_out();
	}
}

